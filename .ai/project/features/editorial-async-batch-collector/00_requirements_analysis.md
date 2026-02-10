# Requirements Analysis: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 00_requirements_analysis.md
> **Created**: 2026-02-10
> **Updated**: 2026-02-10

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
└── Tags NO recuperados en insertadas/recomendadas (decision de producto)

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

### 1.4 Analisis isVisible() — Impacto en Agrupacion Async

Actualmente `isVisible()` se verifica ANTES de acumular dependencias de cada editorial hijo:

**Insertadas (L132)**:
```php
if ($insertedEditorials->isVisible()) {
    $sectionInserted = $this->querySectionClient->findSectionById(...);  // SKIP if not visible
    foreach ($signatures as $signature) {
        $result = $this->retrieveAliasFormat(...);  // SKIP if not visible
    }
    $multimedia = $this->getAsyncMultimedia(...);    // SKIP if not visible
}
```

**Impacto por editorial NO visible**: ~5 HTTP calls evitados (1 section + ~3 journalists + 1 multimedia).
**Decision**: Usar 2 rondas async para insertadas/recomendadas:
- Ronda 1: resolve editorials en batch
- Post-settle: filtrar isVisible()
- Ronda 2: lanzar dependencias SOLO de visibles

### 1.5 Analisis retrieveAliasFormat() — Journalist Dedup

**Hallazgo critico**: Un mismo periodista (aliasId) puede aparecer con DIFERENTES combinaciones de `(section, hasTwitter)`:
- Principal: `retrieveAliasFormat('J1', $sectionPrincipal, hasTwitter=true)` (si editorial es Blog)
- Insertada: `retrieveAliasFormat('J1', $sectionInsertada, hasTwitter=false)` (siempre false)
- Recomendada: `retrieveAliasFormat('J1', $sectionRecomendada, hasTwitter=false)` (siempre false)

**Sin embargo**: El HTTP call (`findJournalistByAliasId`) devuelve SIEMPRE los mismos datos para el mismo aliasId. La diferencia esta en la transformacion (`journalistsDataTransformer->write()`), que varia segun section y hasTwitter.

**Decision**: Dedup el HTTP call por aliasId. Post-resolve, transformar N veces con los distintos contextos.

---

## 2. Requisitos Funcionales

### RF-01: AsyncBatchCollector
Clase `AsyncBatchCollector` que abstrae la agrupacion de promises async en grupos nombrados. Soporta: add, settle, get, encadenamiento de grupos.

### RF-02: Acumulacion y Resolucion Async
Acumular promises de cada bounded context usando los clients con `$async = true`, encapsulados en el collector. Resolver por grupo con `Utils::settle()`.

### RF-03: Deduplicacion de IDs
Un mismo tag/section puede aparecer en editorial principal + insertadas + recomendadas. El collector deduplica antes de lanzar peticiones HTTP.

### RF-04: Journalist Dedup HTTP + Multi-Transform
Deduplicar HTTP call por aliasId. Mantener mapa de contextos `aliasId → [{section, hasTwitter, targetEditorial}]`. Post-resolve, transformar N veces.

### RF-05: Comments Async
`findCommentsByEditorialId` (L239) mover de sync a async via collector.

### RF-06: Opening Multimedia Async
`findMultimediaOpeningById` (L449) mover de sync a async via collector.

### RF-07: isVisible() con 2 Rondas
Insertadas y recomendadas usan 2 rondas: resolve editorials → filter visible → launch dependencies. Evita ~5 HTTP calls por editorial oculto.

### RF-08: Tolerancia a Fallos
Un fallo en un elemento individual (tag, journalist, photo) no rompe la respuesta completa. Mantener patron `try/catch + continue` o equivalente con `Promise::FULFILLED` check.

### RF-09: Backward Compatibility
La respuesta API v1 mantiene la misma estructura JSON. Sin breaking changes.

---

## 3. Requisitos No Funcionales

### RNF-01: Rendimiento
- Reducir de ~42 calls sync (~4.2s) a fases con settle async (~300-400ms)
- No introducir overhead significativo en la capa de acumulacion

### RNF-02: Quality Gates
- PHPStan level 9: 0 errores
- PHPUnit: todos los tests pasan
- Mutation testing: MSI >= 79%
- PSR-12 + Symfony coding standards

### RNF-03: Principios SOLID
- **S**: AsyncBatchCollector solo agrupa/resuelve, orchestrator solo orquesta, clients solo HTTP
- **O**: Extensible para nuevos bounded contexts via collector
- **I**: Interfaces pequeñas y focalizadas
- **D**: Depender de abstracciones (interfaces de clients, no implementaciones)

---

## 4. Patron Async: AsyncBatchCollector

### Premisa
Los clients `ec/*` soportan `$async` boolean flag. El AsyncBatchCollector envuelve este patron en una API de agrupacion configurable.

### API
```php
// Registrar callables en grupos
$collector->add('principal_deps', 'comments', fn() => $this->queryLegacyClient->findCommentsByEditorialId($id, self::ASYNC));
$collector->add('principal_deps', 'opening', fn() => $this->queryMultimediaClient->findMultimediaOpeningById($id, self::ASYNC));
$collector->add('principal_deps', 'tag_5', fn() => $this->queryTagClient->findTagById(5, self::ASYNC));

// Resolver grupo
$collector->settle('principal_deps');

// Obtener resultados
$comments = $collector->get('principal_deps', 'comments');
$opening = $collector->get('principal_deps', 'opening');
```

### Encadenamiento para insertadas/recomendadas
```php
// Ronda 1: editorials
$collector->add('editorials', 'ins_42', fn() => $this->queryEditorialClient->findEditorialById(42, self::ASYNC));
$collector->settle('editorials');

// Filtrar visibles
$editorial = $collector->get('editorials', 'ins_42');
if ($editorial->isVisible()) {
    // Ronda 2: dependencias solo de visibles
    $collector->add('deps', 'section_42', fn() => $this->querySectionClient->findSectionById($editorial->sectionId(), self::ASYNC));
}
$collector->settle('deps');
```

### Implicacion
El collector NO reemplaza los clients — los envuelve. Los clients siguen inalterados. El collector es una nueva clase en Infrastructure que proporciona la abstraccion de agrupacion.

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

### Archivos a Crear
- `src/Infrastructure/Async/AsyncBatchCollector.php` - Nueva clase
- `tests/Infrastructure/Async/AsyncBatchCollectorTest.php` - Tests unitarios

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
