# Architecture: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 10_architecture.md
> **Created**: 2026-02-10

---

## 1. Decision: Estrategia para Clients sin Async

### Problema

4 de 7 clients (`QueryTagClient`, `QueryJournalistClient`, `QuerySectionClient`, `QueryEditorialClient`) no soportan `$async` flag. Son paquetes `ec/*` externos que no podemos modificar.

### Opciones Evaluadas

| # | Opcion | Esfuerzo | Riesgo | Mantenimiento |
|---|--------|----------|--------|---------------|
| A | **Decorator por client** - Wrapper que añade `$async` | Alto (4 decorators) | Bajo | Alto (mantener interfaz duplicada) |
| B | **RequestCollector con sendAsync directo** | Medio | Medio | Medio (acoplado a URLs/responses) |
| C | **Ejecucion concurrente via resolve lazy** | Bajo | Bajo | Bajo |

### Decision: Opcion C - Resolve Lazy con Closures

**Razon**: Los clients `ec/*` ya usan internamente HTTPLug async (Guzzle7). Cuando llamamos `findTagById()`, el client hace `sendAsyncRequest()` y luego `->wait(true)` internamente. El overhead real esta en el `wait()`, no en la creacion del request.

**Estrategia**: En lugar de decorar los clients, creamos un `BatchRequestCollector` que:
1. **Acumula closures** (no IDs) - cada closure encapsula la llamada al client original
2. **Ejecuta en paralelo** usando `Utils::all()` con `FulfilledPromise` wrappers
3. **Devuelve resultados indexados** por ID para consumo posterior

**Ventaja critica**: No necesitamos modificar NI decorar ningún client externo. Usamos los clients tal cual pero ejecutamos las closures concurrentemente.

**Nota**: Guzzle7 con HTTPLug permite concurrencia real via `sendAsyncRequest`. Pero dado que los clients `ec/*` NO exponen este metodo y solo ofrecen sync wrappers, la concurrencia se logra via `Utils::all()` que ejecuta las promises en paralelo. Cada closure devolvera un `FulfilledPromise` que se resolvera concurrentemente via el event loop de Guzzle.

**Alternativa mas agresiva** (si la concurrencia con `FulfilledPromise` no da suficiente ganancia): Usar Symfony `HttpClient` con `AsyncDecoratorTrait` para hacer calls HTTP raw async, bypaseando los clients `ec/*`. Esto se evaluaria SOLO si las mediciones muestran que Option C no cumple el target de ~300-400ms.

---

## 2. Componentes

### 2.1 BatchRequestCollector

**Responsabilidad**: Acumular peticiones por bounded context, deduplicar IDs, y resolver todo en batch.

```
Namespace: App\Infrastructure\Http\BatchRequestCollector
```

**Interfaz**:
```php
interface BatchRequestCollectorInterface
{
    public function addTag(string $id): void;
    public function addJournalist(string $aliasId): void;
    public function addSection(string $id): void;
    public function addPhoto(string $id): void;

    /** @return array<string, Tag> indexed by ID */
    public function resolveTags(): array;

    /** @return array<string, Journalist> indexed by aliasId */
    public function resolveJournalists(): array;

    /** @return array<string, Section> indexed by ID */
    public function resolveSections(): array;

    /** @return array<string, MultimediaPhoto> indexed by ID */
    public function resolvePhotos(): array;
}
```

**Comportamiento**:
1. `addTag('123')` registra ID sin hacer HTTP call
2. `addTag('123')` segunda vez -> ignorado (dedup)
3. `resolveTags()` lanza N promises en paralelo via `Utils::settle()`, devuelve `array<string, Tag>`
4. Elementos fallidos se omiten (tolerancia a fallos), se loguean

**No incluye**: `addEditorial()` ni `addMultimedia()` porque:
- Editorial: se necesita el objeto Editorial para extraer IDs de hijos (dependencia secuencial)
- Multimedia: ya tiene soporte async nativo con `$async` flag

### 2.2 Refactored EditorialOrchestrator::execute()

**Flujo propuesto** (3 fases secuenciales):

```
FASE 1 - Editorial Principal (1 sync call, inevitable)
├── findEditorialById($id)                → Editorial object
├── findSectionById($editorial->sectionId()) → Section (podria ir en Fase 2)
└── getMembershipUrl() → Promise (ya async)

FASE 2 - Acumulacion + Hijos (sync loops que acumulan IDs)
├── Loop insertadas:
│   ├── findEditorialById($idInserted)     → sync (necesita isVisible check)
│   ├── collector->addSection($sectionId)
│   ├── collector->addJournalist($aliasId) x S firmas
│   └── getAsyncMultimedia()               → ya async
├── Loop recomendadas:
│   ├── findEditorialById($idRecommended)  → sync (necesita isVisible check)
│   ├── collector->addSection($sectionId)
│   ├── collector->addJournalist($aliasId) x S firmas
│   └── getAsyncMultimedia()               → ya async
├── Loop tags editorial principal:
│   └── collector->addTag($tagId)
├── Loop tags insertadas (NUEVO):
│   └── collector->addTag($tagId)
├── Loop tags recomendadas (NUEVO):
│   └── collector->addTag($tagId)
├── collector->addJournalist($aliasId) x S firmas principal
├── Loop body photos:
│   └── collector->addPhoto($photoId)
└── collector->addSection($editorial->sectionId()) (principal, si no se hizo en Fase 1)

FASE 3 - Resolucion Paralela (todo en batch)
├── collector->resolveTags()           → batch async
├── collector->resolveJournalists()    → batch async
├── collector->resolveSections()       → batch async
├── collector->resolvePhotos()         → batch async
├── Utils::settle(multimedia promises) → ya async
└── promise->wait() (membership)       → ya async
```

**Reduccion**:
- Fase 1: 1 sync call (editorial principal) - ~100ms
- Fase 2: N+R sync calls (editorials insertados/recomendados) - depende de N+R, ~100-200ms
  - NOTA: los findEditorialById de insertadas/recomendadas son SECUENCIALES porque necesitamos `isVisible()` antes de acumular mas IDs. Pero se podrian paralelizar con Utils::settle si aceptamos filtrar despues.
- Fase 3: 4 batch settles en paralelo + multimedia settle - ~100-200ms (limitado por el mas lento)

**Optimizacion Fase 2**: Se podria tambien hacer batch de los `findEditorialById` de insertadas y recomendadas, lanzando todos como promises y filtrando `isVisible()` despues del settle. Esto eliminaria el loop secuencial.

### 2.3 Estructura de Ficheros

```
src/
├── Infrastructure/
│   └── Http/
│       ├── BatchRequestCollector.php        # Implementacion
│       └── BatchRequestCollectorInterface.php # Interfaz
├── Orchestrator/
│   └── Chain/
│       └── EditorialOrchestrator.php        # Refactored (ya existe)
tests/
├── Infrastructure/
│   └── Http/
│       └── BatchRequestCollectorTest.php    # Unit tests
└── Orchestrator/
    └── Chain/
        └── EditorialOrchestratorTest.php    # Updated tests (ya existe)
```

---

## 3. Patron de Resolucion Batch

Cada `resolve*()` sigue el mismo patron:

```php
// Pseudocodigo - resolveTags()
$promises = [];
foreach ($this->tagIds as $id) {
    $promises[$id] = $this->createPromise(fn () => $this->queryTagClient->findTagById($id));
}

$results = Utils::settle($promises)->wait(true);

$resolved = [];
foreach ($results as $id => $result) {
    if ($result['state'] === Promise::FULFILLED) {
        $resolved[$id] = $result['value'];
    } else {
        $this->logger->warning("Failed to resolve tag {$id}");
    }
}
return $resolved;
```

**createPromise**: Wraps sync call en una `GuzzleHttp\Promise\Create::promiseFor()` o ejecuta via `coroutine()` para habilitar concurrencia real con el event loop.

### Concurrencia Real vs Wrapped

**IMPORTANTE**: `Utils::settle()` con `FulfilledPromise` wrappers NO da concurrencia real - simplemente ejecuta secuencialmente y wrappea el resultado. Para concurrencia REAL necesitamos que las promises se registren en el event loop de Guzzle ANTES del settle.

**Solucion**: Usar `$client->sendAsyncRequest()` directamente (el `HttpAsyncClient` inyectado en todos los clients) y deserializar la response nosotros. Esto SI da concurrencia real porque Guzzle lanza todos los requests HTTP antes de esperar respuestas.

Esto significa que el `BatchRequestCollector` necesita acceso al `HttpAsyncClient` de cada bounded context, NO a los clients `ec/*` directamente.

### Patron Actualizado

```php
class BatchRequestCollector implements BatchRequestCollectorInterface
{
    public function __construct(
        private readonly HttpAsyncClient $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly string $tagHostname,
        private readonly string $journalistHostname,
        private readonly string $sectionHostname,
        private readonly string $multimediaHostname,
        private readonly LoggerInterface $logger,
    ) {}

    public function resolveTags(): array
    {
        $promises = [];
        foreach (array_unique($this->tagIds) as $id) {
            $request = $this->requestFactory->createRequest('GET', "{$this->tagHostname}/tags/{$id}");
            $promises[$id] = $this->httpClient->sendAsyncRequest($request);
        }

        $results = Utils::settle($promises)->wait(true);
        // ... deserialize + filter fulfilled ...
    }
}
```

**Desventaja**: Acopla al collector con las URLs y deserializacion de cada client.
**Ventaja**: Concurrencia HTTP REAL.

### Decision Final

**Fase 1 de implementacion**: Empezar con el patron Decorator simple (Opcion A de la tabla original) para Tag, Journalist, Section. Razon:
- Los clients `ec/*` ya tienen el `HttpAsyncClient` inyectado internamente
- Un decorator que extienda la interfaz del client con `$async` es mas limpio que duplicar logica HTTP
- Si los clients no exponen `sendAsyncRequest` directamente, el decorator puede usar reflexion o constructor injection del mismo `HttpAsyncClient`

**Fase 2 (si necesario)**: Evaluar si los decorators dan suficiente concurrencia. Si no, migrar al patron `BatchRequestCollector` con `sendAsyncRequest` directo.

**Para esta feature**: Implementamos el `BatchRequestCollector` como coordinador que acumula IDs y delega la resolucion a los clients (decorados o no). El collector es agnostico de si el client subyacente es sync o async - solo le importa que devuelva el resultado.

---

## 4. Diagrama de Secuencia Simplificado

```
EditorialOrchestrator::execute()
    │
    ├─── findEditorialById($id)                          [SYNC - necesario]
    ├─── findSectionById($sectionId)                     [SYNC - necesario]
    ├─── getMembershipUrl()                               [ASYNC - ya existente]
    │
    ├─── foreach insertedNews:
    │    ├── findEditorialById($insertedId)               [SYNC - necesita isVisible]
    │    ├── collector.addSection($sectionId)              [ACUMULA]
    │    ├── collector.addJournalist($aliasId) x N        [ACUMULA]
    │    ├── collector.addTag($tagId) x T                 [ACUMULA - NUEVO]
    │    └── getAsyncMultimedia()                          [ASYNC - ya existente]
    │
    ├─── foreach recommendedEditorials:
    │    ├── findEditorialById($recommendedId)             [SYNC - necesita isVisible]
    │    ├── collector.addSection($sectionId)              [ACUMULA]
    │    ├── collector.addJournalist($aliasId) x N        [ACUMULA]
    │    ├── collector.addTag($tagId) x T                 [ACUMULA - NUEVO]
    │    └── getAsyncMultimedia()                          [ASYNC - ya existente]
    │
    ├─── foreach editorial.tags:
    │    └── collector.addTag($tagId)                      [ACUMULA]
    │
    ├─── foreach editorial.signatures:
    │    └── collector.addJournalist($aliasId)             [ACUMULA]
    │
    ├─── foreach body.photos:
    │    └── collector.addPhoto($photoId)                  [ACUMULA]
    │
    │ ═══════════ BATCH RESOLVE ═══════════
    │
    ├─── $tags = collector.resolveTags()                   [BATCH ASYNC]
    ├─── $journalists = collector.resolveJournalists()     [BATCH ASYNC]
    ├─── $sections = collector.resolveSections()           [BATCH ASYNC]
    ├─── $photos = collector.resolvePhotos()               [BATCH ASYNC]
    ├─── Utils::settle($multimediaPromises)                [BATCH ASYNC - ya existente]
    └─── $membership = promise->wait()                     [RESOLVE - ya existente]
    │
    │ ═══════════ TRANSFORM ═══════════
    │
    ├─── Build response using resolved data
    └─── Return $editorialResult
```

---

## 5. Impacto en el Codigo Existente

### Archivos a Modificar
| Archivo | Cambio | Riesgo |
|---------|--------|--------|
| `EditorialOrchestrator.php` | Refactored execute() con collector | ALTO - endpoint critico |
| `EditorialOrchestratorTest.php` | Adaptar mocks, nuevos tests | MEDIO |

### Archivos a Crear
| Archivo | Proposito |
|---------|-----------|
| `src/Infrastructure/Http/BatchRequestCollectorInterface.php` | Interfaz del collector |
| `src/Infrastructure/Http/BatchRequestCollector.php` | Implementacion |
| `tests/Infrastructure/Http/BatchRequestCollectorTest.php` | Tests unitarios |

### Archivos SIN cambios
- Todos los clients `ec/*` (no se tocan)
- `config/packages/httplug.yaml` (no cambia)
- Controllers, transformers, compiler passes (no cambian)
- API response structure (backward compatible)

---

## 6. Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Concurrencia limitada (FulfilledPromise no es async real) | Alta | Medio | Medir antes/despues. Si insuficiente, migrar a sendAsyncRequest directo |
| Regresion en respuesta API | Media | Alto | Tests parametrizados con escenarios reales. Comparar JSON output antes/despues |
| Rate limiting en clients externos por rafagas | Baja | Medio | Utils::settle maneja fallos individuales. Loguear y monitorear |
| Complejidad del collector crece con nuevos bounded contexts | Baja | Bajo | Interfaz segregada, metodos add/resolve por contexto |

---

## 7. Lo que NO incluye esta arquitectura

- **Multi-formato** (Fase C del FEATURE): Se diseñara separadamente cuando la capa de fetching este estable
- **Cache layer**: No se añade cache nuevo. Los clients ya tienen su propia cache via `$cached` param
- **Nuevos endpoints**: Es un refactor interno, no hay cambios en la API publica
- **Modificacion de paquetes `ec/*`**: Se usan tal cual

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
