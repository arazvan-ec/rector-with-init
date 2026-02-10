# Architecture: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 10_architecture.md
> **Created**: 2026-02-10
> **Updated**: 2026-02-10

---

## 1. Premisa

Todos los clients `ec/*` soportan el patron `$async` boolean flag:

```php
$promise = $client->findXById($id, self::ASYNC);  // devuelve Promise
$result  = $client->findXById($id);                // devuelve objeto (sync, default)
```

Se introduce un `AsyncBatchCollector` como abstraccion de agrupacion que envuelve este patron en una API configurable con grupos nombrados y encadenamiento.

---

## 2. AsyncBatchCollector

### 2.1 Ubicacion

```
src/Infrastructure/Async/AsyncBatchCollector.php
tests/Infrastructure/Async/AsyncBatchCollectorTest.php
```

### 2.2 API

```php
declare(strict_types=1);

namespace App\Infrastructure\Async;

class AsyncBatchCollector
{
    /** @var array<string, array<string, callable>> Callables por grupo */
    private array $callables = [];

    /** @var array<string, array<string, mixed>> Resultados resueltos por grupo */
    private array $results = [];

    /**
     * Registra un callable que devuelve una Promise en un grupo nombrado.
     * Si la key ya existe en el grupo, no se sobreescribe (dedup natural).
     */
    public function add(string $group, string $key, callable $callable): void;

    /**
     * Ejecuta todos los callables del grupo, obtiene promises,
     * resuelve con Utils::settle(), y almacena resultados fulfilled.
     */
    public function settle(string $group): void;

    /**
     * Obtiene el resultado resuelto de una key en un grupo.
     * Lanza excepcion si el grupo no ha sido settled o la key no existe/fallo.
     */
    public function get(string $group, string $key): mixed;

    /**
     * Obtiene todos los resultados fulfilled de un grupo.
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array;

    /**
     * Verifica si una key en un grupo fue resuelta exitosamente (fulfilled).
     */
    public function has(string $group, string $key): bool;
}
```

### 2.3 Implementacion interna (settle)

```php
public function settle(string $group): void
{
    $promises = [];
    foreach ($this->callables[$group] ?? [] as $key => $callable) {
        $promises[$key] = $callable(); // Cada callable devuelve Promise
    }

    $settled = Utils::settle($promises)->wait(true);

    $this->results[$group] = [];
    foreach ($settled as $key => $result) {
        if (Promise::FULFILLED === $result['state']) {
            $this->results[$group][$key] = $result['value'];
        }
        // Rejected promises se ignoran silenciosamente (tolerancia a fallos)
    }

    // Limpiar callables del grupo (ya resueltos)
    unset($this->callables[$group]);
}
```

### 2.4 Deduplicacion

```php
public function add(string $group, string $key, callable $callable): void
{
    // Si la key ya existe, no sobreescribir (dedup natural por ID)
    $this->callables[$group][$key] ??= $callable;
}
```

Esto garantiza: `$collector->add('tags', 'tag_5', ...)` llamado 3 veces = 1 sola promise.

### 2.5 Ventajas

| Aspecto | Sin collector | Con AsyncBatchCollector |
|---------|---------------|------------------------|
| Acumulacion | Arrays manuales dispersos | API unificada con grupos |
| Dedup | `$arr[$key] ??= ...` manual | Builtin en `add()` |
| Settle | `Utils::settle()` directo | Encapsulado en `settle()` |
| Resultados | Arrays con `fulfilled`/`rejected` | Solo fulfilled, API limpia |
| Encadenamiento | Manual con variables intermedias | `settle('A') → add('B') → settle('B')` |
| Testing | Mock Utils::settle + promises | Mock collector directamente |

---

## 3. Flujo Refactorizado del execute()

### Diagrama por PR

```
=== PR1: Editorial Principal ===

FASE 1 - Editorial Principal (sync obligatorio)
├── findEditorialById($id)                        [SYNC - necesita objeto para todo]
├── findSectionById($sectionId)                   [SYNC - necesita siteId para membership]
└── getMembershipUrl()                             [ASYNC - ya existente, Promise directa]

FASE 2 - Dependencias principal (1 grupo async)
├── collector.add('principal', 'comments', ...)   [Comments async - NUEVO]
├── collector.add('principal', 'opening', ...)    [Opening multimedia async - NUEVO]
├── collector.add('principal', 'tag_$id', ...)    [Tags async - loop]
├── collector.add('principal', 'journalist_$aliasId', ...)  [Journalists async - dedup por aliasId]
├── collector.add('principal', 'photo_$id', ...)  [Photos body async - loop]
└── collector.settle('principal')                  [1 settle resuelve todo]

FASE 3 - Transformacion
├── comments = collector.get('principal', 'comments')
├── opening = collector.get('principal', 'opening')
├── tags = filtrar collector.getGroup('principal') por prefix 'tag_'
├── journalists = transformar con contexto (section, hasTwitter)
├── photos = filtrar por prefix 'photo_'
├── multimedia = existente (ya async)
└── membership = promise->wait() (ya existente)


=== PR2: Insertadas ===

FASE 4a - Ronda 1: Editorial fetches insertadas
├── foreach bodyTagInsertedNews:
│   └── collector.add('ins_editorials', "ins_$id", ...)
└── collector.settle('ins_editorials')

FASE 4b - Filtrar visibles + acumular dependencias
├── foreach collector.getGroup('ins_editorials'):
│   ├── if !editorial.isVisible() → skip
│   ├── collector.add('ins_deps', "section_$sectionId", ...)  [dedup por sectionId]
│   ├── foreach signatures:
│   │   └── collector.add('ins_deps', "journalist_$aliasId", ...)  [dedup por aliasId]
│   └── multimedia async (ya existente)
└── collector.settle('ins_deps')

FASE 4c - Transformar insertadas con dependencias resueltas
├── section = collector.get('ins_deps', "section_$sectionId")
├── journalists = transformar con contexto por editorial
└── Construir resolveData['insertedNews']


=== PR3: Recomendadas ===

FASE 5a-5c - Mismo patron que insertadas
├── Ronda 1: editorial fetches recomendadas
├── Filtrar visibles + acumular dependencias
└── Transformar con dependencias resueltas
```

### Journalist Dedup + Multi-Transform (detalle)

```php
// Acumulacion: dedup por aliasId
$journalistContexts = []; // Mapa de contextos

// Principal
foreach ($editorial->signatures() as $signature) {
    $aliasId = $signature->id()->id();
    $collector->add('principal', "journalist_{$aliasId}",
        fn() => $this->queryJournalistClient->findJournalistByAliasId(
            $this->journalistFactory->buildAliasId($aliasId), self::ASYNC
        )
    );
    $journalistContexts[$aliasId][] = [
        'section' => $section,
        'hasTwitter' => in_array($editorial->editorialType(), self::TWITTER_TYPES),
        'target' => 'principal',
    ];
}

// Insertadas (post-settle de editorials)
foreach ($visibleInsertadas as $insEditorial) {
    foreach ($insEditorial->signatures() as $signature) {
        $aliasId = $signature->id()->id();
        $collector->add('ins_deps', "journalist_{$aliasId}",
            fn() => $this->queryJournalistClient->findJournalistByAliasId(
                $this->journalistFactory->buildAliasId($aliasId), self::ASYNC
            )
        );
        $journalistContexts[$aliasId][] = [
            'section' => $insSection,
            'hasTwitter' => false,
            'target' => "insertada_{$insEditorial->id()}",
        ];
    }
}

// Post-settle: transformar N veces por aliasId
foreach ($journalistContexts as $aliasId => $contexts) {
    $journalist = $collector->get($group, "journalist_{$aliasId}");
    foreach ($contexts as $ctx) {
        $signature = $this->journalistsDataTransformer
            ->write($aliasId, $journalist, $ctx['section'], $ctx['hasTwitter'])
            ->read();
        // Asignar signature al target correcto
    }
}
```

---

## 4. Estructura de Cambios por PR

### PR1: AsyncBatchCollector + Editorial Principal

| Archivo | Cambio |
|---------|--------|
| `src/Infrastructure/Async/AsyncBatchCollector.php` | **CREAR** - Nueva clase |
| `tests/Infrastructure/Async/AsyncBatchCollectorTest.php` | **CREAR** - Tests unitarios |
| `src/Orchestrator/Chain/EditorialOrchestrator.php` | Refactor dependencias principal (tags, journalists, photos, comments, opening) |
| `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` | Adaptar mocks, nuevos tests |

### PR2: Insertadas Async

| Archivo | Cambio |
|---------|--------|
| `src/Orchestrator/Chain/EditorialOrchestrator.php` | Refactor loop insertadas (2 rondas) |
| `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` | Tests insertadas async |

### PR3: Recomendadas Async

| Archivo | Cambio |
|---------|--------|
| `src/Orchestrator/Chain/EditorialOrchestrator.php` | Refactor loop recomendadas (2 rondas) |
| `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` | Tests recomendadas async + full suite |

### Archivos SIN cambios (en ningun PR)
- Clients `ec/*` (soportan async, no se tocan)
- Config (httplug, services, etc.)
- Controllers, transformers, compiler passes
- API response structure

---

## 5. Metodos del EditorialOrchestrator

### Nuevos/Refactorizados

| Metodo | PR | Descripcion |
|--------|----|-------------|
| Constructor | PR1 | Inyectar `AsyncBatchCollector` |
| `execute()` principal deps | PR1 | Usar collector para tags, journalists, photos, comments, opening |
| `retrieveAliasFormat()` | PR1 | Eliminar o refactorizar — separar acumulacion de transformacion |
| `retrievePhotosFromBodyTags()` | PR1 | Refactorizar — acumular promises en collector |
| `execute()` insertadas | PR2 | 2 rondas async con collector |
| `execute()` recomendadas | PR3 | 2 rondas async con collector |

### Eliminados

| Metodo | Razon |
|--------|-------|
| `retrieveAliasFormat()` (en su forma actual) | Se descompone en acumulacion + transformacion post-resolve |

### Sin cambios

| Metodo | Razon |
|--------|-------|
| `fulfilledMultimedia()` | Ya funciona con el patron async existente |
| `getAsyncMultimedia()` | Ya async, compatible con collector |
| `createCallback()` | Patron existente, sigue usandose |

---

## 6. Tolerancia a Fallos

Misma que el patron actual con `fulfilledMultimedia()`:
- `Utils::settle()` NO lanza excepciones — devuelve todas las promises con su estado
- El collector filtra internamente por `Promise::FULFILLED`
- Promises rejected se ignoran silenciosamente
- Un tag/journalist/section/comment fallido NO rompe la respuesta completa
- El logging se mantiene donde ya existe (journalist catch en L293)

---

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Regresion en respuesta API | Media | Alto | Tests parametrizados, comparar JSON output |
| Rate limiting por rafagas de requests | Baja | Medio | Utils::settle maneja fallos. Monitorear |
| findEditorialById async no devuelve isVisible | Baja | Alto | Verificar que el objeto Editorial completo se devuelve |
| Orden de datos cambia en respuesta | Baja | Bajo | APIs JSON no garantizan orden, pero verificar tests |
| AsyncBatchCollector over-engineering | Baja | Bajo | API minima (5 metodos). Justificada por uso en 3+ PRs |

---

## 8. Lo que NO incluye

- **Multi-formato** (separar fetching de transformacion): Scope separado, futuro
- **Cache layer**: No se anade cache nuevo
- **Nuevos endpoints**: Refactor interno
- **Tags de insertadas/recomendadas**: Excluido por decision de producto
- **Decorators/Wrappers sobre clients**: Innecesarios con `$async` flag nativo

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
