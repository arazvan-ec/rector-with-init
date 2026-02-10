# Backend Tasks: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 30_tasks_backend.md
> **Role**: Backend Engineer
> **Methodology**: TDD
> **Created**: 2026-02-10
> **Updated**: 2026-02-10

---

## Task Overview

| Phase | Tasks | Priority | Effort |
|-------|-------|----------|--------|
| A. Batch Editorials (insertadas + recomendadas) | BE-001 | HIGH | Medium |
| B. Batch Dependencias (tags, journalists, sections, photos) | BE-002 to BE-005 | HIGH | High |
| C. Tags insertadas/recomendadas (datos faltantes) | BE-006 | MEDIUM | Low |

**Nota**: No se crean archivos nuevos. Todo el refactor es en `EditorialOrchestrator.php` + tests.

---

## Phase A: Batch Editorials

### BE-001: Async batch de editorials insertadas y recomendadas

**Priority**: HIGH
**Reference**: `10_architecture.md` section 3 (sub-fases)
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Los loops de insertadas (L127-161) y recomendadas (L167-207) actualmente hacen `findEditorialById($id)` sync por cada editorial hijo
- Cambiar a: acumular promises con `findEditorialById($id, self::ASYNC)`, resolver con `Utils::settle()`, filtrar `isVisible()` despues
- Mantener acumulacion de multimedia async (ya existente)

**Cambio conceptual**:
```
ANTES:  foreach insertadas → findEditorial(sync) → if visible → acumular datos
DESPUES: foreach insertadas → findEditorial(ASYNC) → settle → foreach visible → acumular datos
```

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que verifica `findEditorialById` se llama con `self::ASYNC` para insertadas
2. **GREEN**: Cambiar loop insertadas a async + settle
3. **RED**: Test que editoriales no visibles se excluyen post-settle
4. **GREEN**: Filtrar `isVisible()` despues del settle
5. **RED**: Test para recomendadas con mismo patron
6. **GREEN**: Cambiar loop recomendadas a async + settle
7. **REFACTOR**: Extraer metodo comun si hay repeticion excesiva

**Acceptance Criteria**:
- [ ] Insertadas se resuelven via `Utils::settle()` batch
- [ ] Recomendadas se resuelven via `Utils::settle()` batch
- [ ] `isVisible()` se verifica despues del settle
- [ ] Multimedia async sigue funcionando (ya existente)
- [ ] Tests existentes adaptados y verdes

**Verification**:
```bash
./bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**Done When**: Tests verdes, editorial insertadas/recomendadas resueltas en batch.

**Escape Hatch**: Si el filtrado post-settle de `isVisible()` genera problemas con el flujo de datos, mantener sync para editorials pero hacer async todo lo demas.

---

## Phase B: Batch Dependencias

### BE-002: Async batch de tags

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 7

**Requirements**:
- El loop de tags (L222-230) actualmente hace `findTagById($id)` sync por cada tag
- Cambiar a: acumular promises con `findTagById($id, self::ASYNC)`, indexadas por ID (dedup)
- Resolver con `Utils::settle()` + callback `fulfilledTags()`
- Crear metodo `fulfilledTags()` siguiendo patron de `fulfilledMultimedia()`

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findTagById` se llama con `self::ASYNC` y promises se acumulan
2. **GREEN**: Cambiar loop a async
3. **RED**: Test que tags duplicados solo generan 1 promise (dedup por key)
4. **GREEN**: Indexar por `$tagId` con `??=`
5. **RED**: Test `fulfilledTags()` filtra correctamente
6. **GREEN**: Implementar callback

**Acceptance Criteria**:
- [ ] Tags se resuelven via batch async
- [ ] Deduplicacion por ID funciona
- [ ] `fulfilledTags()` callback implementado
- [ ] Tag fallido no rompe la respuesta
- [ ] `$tags` resultado compatible con `detailsAppsDataTransformer->write()`

**Done When**: Tests verdes, tags en batch.

---

### BE-003: Async batch de journalists

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- `retrieveAliasFormat()` (L281-296) se llama en 3 lugares: insertadas (L139), recomendadas (L180), principal (L243-252)
- Cada llamada hace `findJournalistByAliasId($aliasId)` sync + `journalistsDataTransformer->write()` transform
- Separar en: acumular promise `findJournalistByAliasId($aliasId, self::ASYNC)`, resolver batch, transformar post-resolve
- Necesita mapear cada journalist resuelto a su contexto (section, hasTwitter)

**Complejidad**: ALTA - hay que mantener la relacion `aliasId -> section -> hasTwitter` para la transformacion post-resolve.

**Estrategia**:
- Acumular promises indexadas por `$aliasId` (dedup natural)
- Mantener un mapa `$aliasId -> {section, hasTwitter}` por contexto
- Resolver batch journalists
- Transformar con `journalistsDataTransformer->write()` usando journalist resuelto + datos del mapa

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findJournalistByAliasId` se llama con `self::ASYNC`
2. **GREEN**: Acumular promises para insertadas
3. **RED**: Test que promises de recomendadas se acumulan
4. **GREEN**: Acumular para recomendadas y principal
5. **RED**: Test que `fulfilledJournalists()` filtra correctamente
6. **GREEN**: Implementar callback
7. **RED**: Test que signatures se transforman correctamente post-resolve
8. **GREEN**: Reconstruir signatures con journalists resueltos
9. **REFACTOR**: Simplificar `retrieveAliasFormat()` o eliminarlo

**Acceptance Criteria**:
- [ ] Todos los journalists se resuelven en un batch
- [ ] Deduplicacion por aliasId
- [ ] Signatures de insertadas correctas
- [ ] Signatures de recomendadas correctas
- [ ] Signatures de principal con `hasTwitter` flag
- [ ] `retrieveAliasFormat()` refactorizado o eliminado

**Done When**: Tests verdes, signatures identicas al original.

**Escape Hatch**: Si la complejidad de reconstruir signatures es excesiva, mantener `retrieveAliasFormat()` para el editorial principal (2 calls sync) y solo hacer async para insertadas/recomendadas.

---

### BE-004: Async batch de sections

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 7

**Requirements**:
- `findSectionById` se llama sync para insertadas (L134) y recomendadas (L175)
- Acumular promises con `findSectionById($sectionId, self::ASYNC)`, indexadas por sectionId (dedup)
- Resolver batch, mapear sections a sus editorials
- Section del editorial PRINCIPAL (L115) sigue sync (necesaria para membership en L117)

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findSectionById` se llama con `self::ASYNC` para insertadas
2. **GREEN**: Acumular promises
3. **RED**: Test que sections de recomendadas se acumulan
4. **GREEN**: Acumular para recomendadas
5. **RED**: Test que `fulfilledSections()` filtra correctamente
6. **GREEN**: Implementar callback
7. **REFACTOR**: Verificar que section principal sigue sync

**Acceptance Criteria**:
- [ ] Sections de insertadas/recomendadas via batch async
- [ ] Section principal sigue sync
- [ ] Deduplicacion por sectionId
- [ ] Sections resueltas se mapean a sus editorials

**Done When**: Tests verdes, sections en batch.

**Nota**: Las sections se necesitan DESPUES de resolver editorials (BE-001) porque el `sectionId` viene del objeto Editorial. Depende de BE-001.

---

### BE-005: Async batch de photos body tags

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 7

**Requirements**:
- `retrievePhotosFromBodyTags()` (L306-323) y `addPhotoToArray()` (L330-340) hacen `findPhotoById($id)` sync por cada foto
- Cambiar a: acumular promises con `findPhotoById($id, self::ASYNC)`, resolver batch
- Mantener estructura de retorno `$result[$id] = $photo`

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findPhotoById` se llama con `self::ASYNC`
2. **GREEN**: Acumular promises
3. **RED**: Test que `fulfilledPhotos()` filtra correctamente
4. **GREEN**: Implementar callback + settle
5. **REFACTOR**: Simplificar `retrievePhotosFromBodyTags()` y `addPhotoToArray()`

**Acceptance Criteria**:
- [ ] Photos se resuelven via batch async
- [ ] Fotos de `BodyTagPicture` y `BodyTagMembershipCard` incluidas
- [ ] Photo fallida no rompe la respuesta
- [ ] Resultado compatible con `bodyDataTransformer->execute()`

**Done When**: Tests verdes, photos en batch.

---

## Phase C: Tags Insertadas/Recomendadas

### BE-006: Recuperar tags de insertadas y recomendadas

**Priority**: MEDIUM
**Methodology**: TDD
**Max Iterations**: 7

**Requirements**:
- En los loops de insertadas y recomendadas (post-resolve de BE-001), extraer tags de cada editorial y acumular promises con `findTagById($tagId, self::ASYNC)`
- Los tags resueltos se incluyen en `resolveData['insertedNews'][$id]` y `resolveData['recommendedEditorials'][$id]`
- Evaluar si los transformers necesitan adaptacion para recibir tags

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**Evaluacion previa**: Verificar que `RecommendedEditorialsDataTransformer` y `BodyDataTransformer` aceptan tags de insertadas/recomendadas. Si no, adaptar.

**TDD Approach**:
1. **RED**: Test que tags de insertadas se acumulan como promises
2. **GREEN**: Extraer tags de cada editorial insertada visible
3. **RED**: Test que tags de recomendadas se acumulan
4. **GREEN**: Extraer tags de cada editorial recomendada visible
5. **RED**: Test que tags resueltos aparecen en resolveData
6. **GREEN**: Incluir tags en resolveData
7. **REFACTOR**: Clean up

**Acceptance Criteria**:
- [ ] Tags de insertadas se recuperan y acumulan
- [ ] Tags de recomendadas se recuperan y acumulan
- [ ] Tags incluidos en resolveData
- [ ] No rompe respuesta existente (backward compatible)

**Done When**: Tests verdes, tags presentes en respuesta.

---

## Orden de Ejecucion

```
BE-001 (batch editorials) → BE-004 (batch sections, depende de editorial IDs)
                           → BE-003 (batch journalists, depende de editorial signatures)
                           → BE-006 (tags insertadas/recomendadas, depende de editorial tags)

BE-002 (batch tags principal) - independiente
BE-005 (batch photos) - independiente

Paralelizables: BE-002 y BE-005 pueden hacerse en paralelo con BE-001
```

**Checkpoints**:
1. Despues de BE-001: `./bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorTest.php`
2. Despues de BE-002 + BE-005: `make test_unit`
3. Despues de BE-003 + BE-004: `make test_unit && make test_stan`
4. Despues de BE-006: `make tests` (full suite)

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
