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

Esto elimina la necesidad de decorators, wrappers, o un BatchRequestCollector intermedio. El refactor se hace directamente en el `EditorialOrchestrator` usando los clients existentes.

---

## 2. Patron de Resolucion

### 2.1 Patron ya existente (multimedia)

```php
// Acumular promises
$resolveData['multimedia'][] = $this->queryMultimediaClient->findMultimediaById($id, self::ASYNC);

// Resolver batch
$resolveData['multimedia'] = Utils::settle($resolveData['multimedia'])
    ->then($this->createCallback([$this, 'fulfilledMultimedia']))
    ->wait(self::UNWRAPPED);
```

### 2.2 Patron a replicar (tags, journalists, sections, photos)

```php
// Acumular promises (con dedup por ID)
$tagPromises = [];
foreach ($allTagIds as $tagId) {
    if (!isset($tagPromises[$tagId])) {
        $tagPromises[$tagId] = $this->queryTagClient->findTagById($tagId, self::ASYNC);
    }
}

// Resolver batch
$resolvedTags = Utils::settle($tagPromises)
    ->then($this->createCallback([$this, 'fulfilledTags']))
    ->wait(self::UNWRAPPED);
```

**Clave**: Indexar promises por ID para deduplicar naturalmente (mismo ID = misma key = sin duplicado).

### 2.3 Callbacks de resolucion

Cada bounded context tendra su callback `fulfilled*()` siguiendo el patron de `fulfilledMultimedia()`:

```php
protected function fulfilledTags(array $promises): array
{
    $result = [];
    foreach ($promises as $id => $promise) {
        if (Promise::FULFILLED === $promise['state']) {
            $result[$id] = $promise['value'];
        }
    }
    return $result;
}
```

Los callbacks son identicos en estructura. Se puede evaluar extraer un metodo generico `fulfilledResults()` si la repeticion es excesiva, pero no es obligatorio.

---

## 3. Flujo Refactorizado del execute()

### 3 Fases Secuenciales

```
FASE 1 - Editorial Principal (inevitable sync)
├── findEditorialById($id)                       [SYNC - necesita el objeto para todo]
├── findSectionById($sectionId)                  [SYNC - necesita siteId para membership]
└── getMembershipUrl()                            [ASYNC - ya existente, devuelve Promise]

FASE 2 - Acumulacion de Promises (loops que lanzan async)
├── Loop insertadas:
│   ├── findEditorialById($idInserted, ASYNC)     [PROMISE - acumular]
│   (despues de resolve de editorials insertados:)
│   ├── addSection promise por cada insertada visible
│   ├── addJournalist promise por cada firma
│   ├── addTag promise por cada tag (NUEVO)
│   └── getAsyncMultimedia()                      [PROMISE - ya existente]
│
├── Loop recomendadas:
│   ├── findEditorialById($idRecommended, ASYNC)  [PROMISE - acumular]
│   (despues de resolve de editorials recomendados:)
│   ├── addSection promise por cada recomendada visible
│   ├── addJournalist promise por cada firma
│   ├── addTag promise por cada tag (NUEVO)
│   └── getAsyncMultimedia()                      [PROMISE - ya existente]
│
├── Tags editorial principal → promises
├── Journalists editorial principal → promises
├── Photos body tags → promises
└── Comments → promise (via QueryLegacyClient ASYNC)

FASE 3 - Batch Resolve
├── Utils::settle(editorialInsertedPromises) → filtrar isVisible → acumular más promises
├── Utils::settle(editorialRecommendedPromises) → filtrar isVisible → acumular más promises
├── Utils::settle(tagPromises)                  [BATCH]
├── Utils::settle(journalistPromises)           [BATCH]
├── Utils::settle(sectionPromises)              [BATCH]
├── Utils::settle(photoPromises)                [BATCH]
├── Utils::settle(multimediaPromises)           [BATCH - ya existente]
└── membership promise->wait()                  [RESOLVE - ya existente]
```

### Sub-fases dentro de Fase 2-3

**Problema**: Los editorials de insertadas/recomendadas se necesitan ANTES de poder acumular sus tags/journalists/sections (porque hay que verificar `isVisible()` y extraer IDs).

**Solucion**: 2 sub-fases de resolve:
1. **Resolve editorials** (insertadas + recomendadas) via `Utils::settle()`
2. **Acumular dependencias** de editorials visibles (tags, journalists, sections)
3. **Resolve dependencias** via `Utils::settle()` adicional

Esto da **4 rondas HTTP reales**:
1. Editorial principal + Section principal + Membership (sync + async mix)
2. Editorials insertadas + recomendadas (batch async)
3. Tags + Journalists + Sections + Photos + Multimedia (batch async, todo en paralelo)
4. Opening multimedia (ya existente, se puede mover a ronda 3)

---

## 4. Estructura de Cambios

### Archivos a Modificar
| Archivo | Cambio |
|---------|--------|
| `src/Orchestrator/Chain/EditorialOrchestrator.php` | Refactor execute() con async pattern |
| `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` | Adaptar mocks para `$async` flag, nuevos tests |

### Archivos a Crear
Ninguno. Todo el cambio es dentro del orchestrator existente.

### Archivos SIN cambios
- Clients `ec/*` (soportan async, no se tocan)
- Config (httplug, services, etc.)
- Controllers, transformers, compiler passes
- API response structure

---

## 5. Metodos Nuevos en EditorialOrchestrator

### fulfilled*() callbacks

```php
fulfilledTags(array $promises): array          // $id => Tag
fulfilledJournalists(array $promises): array   // $aliasId => Journalist
fulfilledSections(array $promises): array      // $id => Section
fulfilledPhotos(array $promises): array        // $id => MultimediaPhoto
fulfilledEditorials(array $promises): array    // $id => Editorial
```

### Posible refactor de retrieveAliasFormat()

Actualmente `retrieveAliasFormat()` hace HTTP sync + transform. Se separaria en:
1. **Acumular**: solo registrar aliasId como promise
2. **Resolver**: batch via `Utils::settle()`
3. **Transformar**: `journalistsDataTransformer->write()` con journalist ya resuelto

Esto elimina el metodo `retrieveAliasFormat()` como esta (sync call + transform), reemplazandolo por:
- Acumulacion de promises indexadas por aliasId
- Resolucion batch
- Transformacion post-resolve usando los datos de `resolvedJournalists[$aliasId]`

### Posible refactor de retrievePhotosFromBodyTags()

Similar: actualmente sync loop con `findPhotoById()`. Se cambia a:
- Acumular promises: `$photoPromises[$id] = $this->queryMultimediaClient->findPhotoById($id, self::ASYNC)`
- Resolver: `Utils::settle($photoPromises)`

---

## 6. Deduplicacion

La deduplicacion es natural al indexar promises por ID:

```php
$tagPromises[$tagId] = $this->queryTagClient->findTagById($tagId, self::ASYNC);
```

Si `$tagId` ya existe como key, se sobreescribe con la misma promise (o se puede hacer `??=` para no crear duplicada):

```php
$tagPromises[$tagId] ??= $this->queryTagClient->findTagById($tagId, self::ASYNC);
```

Esto garantiza 1 HTTP call por ID unico sin necesidad de un set aparte.

---

## 7. Tolerancia a Fallos

Misma que el patron actual con `fulfilledMultimedia()`:
- `Utils::settle()` NO lanza excepciones - devuelve todas las promises con su estado
- Cada callback `fulfilled*()` filtra por `Promise::FULFILLED`
- Promises rejected se ignoran silenciosamente (se puede agregar logging)
- Un tag/journalist/section fallido NO rompe la respuesta completa

---

## 8. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Regresion en respuesta API | Media | Alto | Tests parametrizados, comparar JSON output |
| Rate limiting por rafagas de requests | Baja | Medio | Utils::settle maneja fallos. Monitorear |
| findEditorialById async no devuelve isVisible | Baja | Alto | Verificar que el objeto Editorial completo se devuelve |
| Orden de datos cambia en respuesta | Baja | Bajo | APIs JSON no garantizan orden, pero verificar tests |

---

## 9. Lo que NO incluye

- **Multi-formato** (Fase C del FEATURE): Separado, cuando fetching este estable
- **Cache layer**: No se añade cache nuevo
- **Nuevos endpoints**: Refactor interno
- **BatchRequestCollector**: Eliminado de la arquitectura (innecesario con async nativo)
- **Decorators/Wrappers**: Innecesarios

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
