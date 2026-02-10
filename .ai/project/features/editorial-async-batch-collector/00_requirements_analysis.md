# Requirements Analysis: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 00_requirements_analysis.md
> **Created**: 2026-02-10

---

## 1. Analisis del Estado Actual

### 1.1 Flujo HTTP del EditorialOrchestrator

El `EditorialOrchestrator` (`src/Orchestrator/Chain/EditorialOrchestrator.php`) ejecuta llamadas HTTP a 7 bounded contexts para construir la respuesta de un editorial.

**Escenario tipico** (3 insertadas, 4 recomendadas, 2 firmas/editorial, 5 tags, 3 fotos body):

```
FASE 1 - Editorial principal (SYNC bloqueante)
├── findEditorialById (L104)                    1 call
├── findSectionById (L115)                      1 call
└── getMembershipUrl (L387)                     1 promise (ya async)

FASE 2 - Hijos (SYNC bloqueante en loops)
├── Insertadas (N=3)
│   ├── findEditorialById (L131)                3 calls
│   ├── findSectionById (L134)                  3 calls
│   ├── findJournalistByAliasId (L139)          6 calls (N x S)
│   └── findMultimediaById (L147)               3 promises (ya async)
├── Recomendadas (R=4)
│   ├── findEditorialById (L172)                4 calls
│   ├── findSectionById (L175)                  4 calls
│   ├── findJournalistByAliasId (L180)          8 calls (R x S)
│   └── findMultimediaById (L188)               4 promises (ya async)
└── Tags NO recuperados en insertadas/recomendadas

FASE 3 - Dependencias transversales (SYNC bloqueante en loops)
├── findMultimediaOpeningById (L449)            1 call
├── findPhotoById body tags (L333)              3 calls
├── findTagById (L226)                          5 calls
├── findCommentsByEditorialId (L239)            1 call
└── findJournalistByAliasId principal (L243)    2 calls

TOTAL: ~42 HTTP calls sync + ~10 promises async = ~4.2s I/O
```

### 1.2 Capacidad Async de los Clients

| Client | Package | Version | Async | Patron | Ubicacion |
|--------|---------|---------|-------|--------|-----------|
| QueryMultimediaClient | `ec/multimedia-client` | - | **SI** | `$async` boolean flag | vendor |
| QueryLegacyClient | local | - | **SI** | `$async` boolean flag | `src/Ec/Snaapi/Infrastructure/Client/Http/` |
| QueryMembershipClient | `ec/membership-client` | - | **SI** | Returns Promise | vendor |
| QueryEditorialClient | `ec/editorial-client` | - | **SI** | `$async` boolean flag | vendor |
| QuerySectionClient | `ec/section-client` | ^3.0 | **SI** | `$async` boolean flag | vendor |
| QueryTagClient | `ec/tag-client` | ^4.0 | **SI** | `$async` boolean flag | vendor |
| QueryJournalistClient | `ec/journalist-client` | ^5.2.2 | **SI** | `$async` boolean flag | vendor |

**Infraestructura comun**: Todos usan HTTPLug con Guzzle7 async client (`httplug.client.app_guzzle7.http_methods.inner`). Todos los clients soportan el patron `$async` boolean flag: cuando `$async = true`, devuelven `Promise` en vez de resolver sincrono.

### 1.3 Patron Async Existente

**QueryLegacyClient** (referencia local, codigo controlable):
```php
public function findEditorialById(
    string $editorialIdString,
    bool $async = false,
    bool $cached = false,
    int $ttlCache = 60,
): array {
    $promise = $this->execute($request, true, $cached, $ttlCache);
    $promise = $promise->then($this->createCallback([$this, 'buildEditorialFromArray'], $request));
    return $async ? $promise : $promise->wait(true);
}
```

**Resolucion con Utils::settle()** (EditorialOrchestrator L213-218):
```php
$resolveData['multimedia'] = Utils::settle($resolveData['multimedia'])
    ->then($this->createCallback([$this, 'fulfilledMultimedia']))
    ->wait(self::UNWRAPPED);
```

**Constantes**:
- `self::ASYNC = true`
- `self::UNWRAPPED = true`
- `Promise::FULFILLED` para verificar estado en callbacks

### 1.4 Datos Faltantes

Actualmente NO se recuperan:
1. **Tags de noticias insertadas** (L124-161) - El loop de insertadas no llama a `findTagById`
2. **Tags de editoriales recomendados** (L163-207) - El loop de recomendadas no llama a `findTagById`

---

## 2. Requisitos Funcionales

### RF-01: Acumulacion y Resolucion Async
Acumular promises de cada bounded context usando directamente los clients con `$async = true`, y resolver en batch con `Utils::settle()`.

### RF-02: Deduplicacion de IDs
Un mismo tag/journalist/section puede aparecer en editorial principal + insertadas + recomendadas. El collector deduplica antes de lanzar peticiones HTTP.

### RF-03: Batch Async Execution
Todas las llamadas HTTP independientes dentro de una fase se ejecutan en paralelo via `Utils::settle()`. Maximo 3 fases secuenciales.

### RF-04: Tags de Insertadas/Recomendadas
Recuperar tags de cada noticia insertada y editorial recomendado. Incluirlos en la respuesta transformada.

### RF-05: Tolerancia a Fallos
Un fallo en un elemento individual (tag, journalist, photo) no rompe la respuesta completa. Mantener patron `try/catch + continue` o equivalente con `Promise::FULFILLED` check.

### RF-06: Backward Compatibility
La respuesta API v1 mantiene la misma estructura JSON. Los datos nuevos (tags de insertadas/recomendadas) se anaden sin romper contratos existentes.

---

## 3. Requisitos No Funcionales

### RNF-01: Rendimiento
- Reducir de ~42 calls sync (~4.2s) a 3 fases con settle (~300-400ms)
- No introducir overhead significativo en la capa de acumulacion

### RNF-02: Quality Gates
- PHPStan level 9: 0 errores
- PHPUnit: todos los tests pasan
- Mutation testing: MSI >= 79%
- PSR-12 + Symfony coding standards

### RNF-03: Principios SOLID
- **S**: El orchestrator orquesta, los clients resuelven HTTP
- **O**: Extensible para nuevos bounded contexts
- **I**: Interfaces pequeñas y focalizadas
- **D**: Depender de abstracciones (interfaces de clients, no implementaciones)

---

## 4. Patron Async Uniforme

### Premisa
Todos los clients `ec/*` soportan (o soportaran) el patron `$async` boolean flag. Esto significa que podemos usar directamente:

```php
$promise = $this->queryTagClient->findTagById($id, self::ASYNC);       // Promise
$promise = $this->queryJournalistClient->findJournalistByAliasId($aliasId, self::ASYNC); // Promise
$promise = $this->querySectionClient->findSectionById($id, self::ASYNC); // Promise
```

Y resolver con el patron ya existente:
```php
$results = Utils::settle($promises)->wait(self::UNWRAPPED);
```

### Implicacion
No se necesitan decorators, wrappers, ni un BatchRequestCollector intermedio. El refactor se simplifica a:
1. Cambiar llamadas sync por `$client->method($id, self::ASYNC)` para obtener Promise
2. Acumular promises en arrays por contexto
3. Resolver con `Utils::settle()` + callback que filtra `Promise::FULFILLED`
4. Deduplicar IDs antes de lanzar promises (optimizacion)

---

## 5. Dependencias

### Packages Existentes (no modificar)
- `guzzlehttp/promises` - `Utils::settle()`, `Promise`, `FulfilledPromise`
- `php-http/httplug` - Cliente HTTP async subyacente
- `php-http/guzzle7-adapter` - Adapter Guzzle7 para HTTPLug
- `ec/editorial-client`, `ec/tag-client ^4.0`, `ec/section-client ^3.0`, `ec/journalist-client ^5.2.2`, `ec/multimedia-client`

### Archivos Clave
- `src/Orchestrator/Chain/EditorialOrchestrator.php` - Target principal del refactor
- `src/Ec/Snaapi/Infrastructure/Client/Http/QueryLegacyClient.php` - Referencia patron async
- `config/packages/httplug.yaml` - Config HTTP clients
- `config/packages/*/infrastructure.yaml` - Config inyeccion de clients
- `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` - Tests existentes

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
