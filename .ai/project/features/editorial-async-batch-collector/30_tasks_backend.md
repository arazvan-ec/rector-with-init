# Backend Tasks: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 30_tasks_backend.md
> **Role**: Backend Engineer
> **Methodology**: TDD
> **Created**: 2026-02-10
> **Updated**: 2026-02-10

---

## Task Overview

| PR | Tasks | Priority | Effort |
|----|-------|----------|--------|
| PR1: AsyncBatchCollector + Principal | BE-001, BE-002, BE-003, BE-004, BE-005, BE-006 | HIGH | High |
| PR2: Insertadas Async | BE-007, BE-008 | HIGH | High |
| PR3: Recomendadas Async | BE-009, BE-010 | HIGH | Medium |

---

## PR1: AsyncBatchCollector + Editorial Principal Async

### BE-001: AsyncBatchCollector class

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 7

**Requirements**:
- Crear `src/Infrastructure/Async/AsyncBatchCollector.php`
- API: `add(group, key, callable)`, `settle(group)`, `get(group, key)`, `getGroup(group)`, `has(group, key)`
- Dedup natural: `add()` con misma key no sobreescribe
- `settle()` ejecuta callables, resuelve con `Utils::settle()`, almacena solo fulfilled
- Encadenamiento: settle grupo A → add grupo B → settle grupo B

**Files to Create**:
```
src/Infrastructure/Async/AsyncBatchCollector.php
tests/Infrastructure/Async/AsyncBatchCollectorTest.php
```

**TDD Approach**:
1. **RED**: Test `add()` + `settle()` + `get()` con un callable que devuelve FulfilledPromise
2. **GREEN**: Implementar add, settle, get basicos
3. **RED**: Test dedup — mismo key no genera 2 calls
4. **GREEN**: Implementar `??=` en add
5. **RED**: Test rejected promise — `has()` devuelve false, `get()` lanza excepcion
6. **GREEN**: Filtrar por `Promise::FULFILLED` en settle
7. **RED**: Test `getGroup()` devuelve solo fulfilled
8. **GREEN**: Implementar getGroup
9. **RED**: Test encadenamiento — settle('A'), luego add('B') con datos de A, settle('B')
10. **GREEN**: Verificar que settle limpia callables y permite reusar

**Acceptance Criteria**:
- [ ] Clase creada con strict types, PHP 8.2+
- [ ] API completa (5 metodos publicos)
- [ ] Dedup por key funciona
- [ ] Tolerancia a fallos (rejected = ignorado)
- [ ] Encadenamiento de grupos
- [ ] Tests unitarios aislados (sin HTTP real)
- [ ] PHPStan level 9 compatible

**Done When**: Tests verdes, clase aislada y funcional.

---

### BE-002: Tags del principal en batch async

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 7

**Requirements**:
- El loop de tags (L222-230) actualmente hace `findTagById($id)` sync por cada tag
- Cambiar a: `collector->add('principal', "tag_{$id}", fn() => findTagById($id, self::ASYNC))`
- Dedup natural por tagId (mismo tag = misma key)
- Resolver con `collector->settle('principal')` (junto con otras dependencias)

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findTagById` se llama con `self::ASYNC` via collector
2. **GREEN**: Cambiar loop a usar collector
3. **RED**: Test que tags duplicados generan 1 sola add al collector
4. **GREEN**: Indexar por `tag_{$tagId}`
5. **REFACTOR**: Verificar que el resultado es compatible con `detailsAppsDataTransformer->write()`

**Acceptance Criteria**:
- [ ] Tags se acumulan en collector con prefix `tag_`
- [ ] Deduplicacion por tagId
- [ ] Tag fallido no rompe la respuesta
- [ ] Resultado compatible con transformer existente

**Done When**: Tests verdes, tags acumulados en collector.

---

### BE-003: Journalists del principal en batch async con dedup

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- `retrieveAliasFormat()` (L281-296) se llama sync por cada firma del principal (L243-252)
- Separar en: acumular promise, resolver batch, transformar post-resolve
- Dedup HTTP por aliasId: `collector->add('principal', "journalist_{$aliasId}", ...)`
- Mantener mapa de contextos: `$journalistContexts[$aliasId][] = {section, hasTwitter, target}`
- Post-settle: `journalistsDataTransformer->write()` con journalist resuelto + contexto

**Complejidad**: ALTA — mantener relacion aliasId → contexto para transformacion post-resolve.

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findJournalistByAliasId` se llama con `self::ASYNC` via collector
2. **GREEN**: Acumular promises del principal en collector
3. **RED**: Test que mismo aliasId = 1 sola add (dedup)
4. **GREEN**: Dedup por `journalist_{$aliasId}`
5. **RED**: Test que signatures del principal se transforman correctamente post-resolve
6. **GREEN**: Implementar mapa de contextos + transformacion post-settle
7. **RED**: Test que `hasTwitter` se calcula correctamente segun editorialType
8. **GREEN**: Pasar hasTwitter al mapa de contextos
9. **REFACTOR**: Evaluar si `retrieveAliasFormat()` se elimina o se renombra

**Acceptance Criteria**:
- [ ] Journalists del principal en collector con dedup
- [ ] Mapa de contextos `aliasId → [{section, hasTwitter}]`
- [ ] Transformacion post-resolve identica al resultado original
- [ ] `hasTwitter` correcto segun TWITTER_TYPES
- [ ] `retrieveAliasFormat()` refactorizado

**Done When**: Tests verdes, signatures del principal identicas al original.

---

### BE-004: Photos body tags en batch async

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 7

**Requirements**:
- `retrievePhotosFromBodyTags()` (L306-323) y `addPhotoToArray()` (L330-340) hacen `findPhotoById($id)` sync
- Cambiar a: `collector->add('principal', "photo_{$id}", fn() => findPhotoById($id, self::ASYNC))`
- Resolver con `collector->settle('principal')`

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findPhotoById` se llama con `self::ASYNC` via collector
2. **GREEN**: Acumular promises de photos
3. **RED**: Test que photo fallida no rompe respuesta
4. **GREEN**: Usar `collector->has()` antes de get
5. **REFACTOR**: Simplificar `retrievePhotosFromBodyTags()` y `addPhotoToArray()`

**Acceptance Criteria**:
- [ ] Photos en collector con prefix `photo_`
- [ ] `BodyTagPicture` y `BodyTagMembershipCard` incluidas
- [ ] Photo fallida no rompe respuesta
- [ ] Resultado compatible con `bodyDataTransformer->execute()`

**Done When**: Tests verdes, photos en batch.

---

### BE-005: Comments en batch async

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 5

**Requirements**:
- `findCommentsByEditorialId` (L239) actualmente sync
- Cambiar a: `collector->add('principal', 'comments', fn() => findCommentsByEditorialId($id, self::ASYNC))`
- Resolver con `collector->settle('principal')`

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findCommentsByEditorialId` se llama con `self::ASYNC` via collector
2. **GREEN**: Mover a collector
3. **RED**: Test que comments fallido no rompe respuesta
4. **GREEN**: Usar `collector->has()` con fallback a valor por defecto

**Acceptance Criteria**:
- [ ] Comments en collector bajo key `comments`
- [ ] Fallo no rompe respuesta
- [ ] Resultado compatible con transformer existente

**Done When**: Tests verdes, comments async.

---

### BE-006: Opening multimedia en batch async

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 5

**Requirements**:
- `findMultimediaOpeningById` (L449) actualmente sync
- Cambiar a: `collector->add('principal', 'opening', fn() => findMultimediaOpeningById($id, self::ASYNC))`
- Resolver con `collector->settle('principal')`

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findMultimediaOpeningById` se llama con `self::ASYNC` via collector
2. **GREEN**: Mover a collector
3. **RED**: Test que opening fallido no rompe respuesta
4. **GREEN**: Usar `collector->has()` con fallback

**Acceptance Criteria**:
- [ ] Opening multimedia en collector bajo key `opening`
- [ ] Fallo no rompe respuesta
- [ ] Resultado compatible con transformer existente

**Done When**: Tests verdes, opening async.

**PR1 Checkpoint**:
```bash
./bin/phpunit tests/Infrastructure/Async/AsyncBatchCollectorTest.php
./bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorTest.php
make test_stan
```

---

## PR2: Insertadas Async

### BE-007: Insertadas editorial fetches async (Ronda 1)

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 10
**Depends on**: PR1 merged

**Requirements**:
- El loop de insertadas (L127-161) actualmente hace `findEditorialById($id)` sync por cada insertada
- Ronda 1: acumular promises con `collector->add('ins_editorials', "ins_{$id}", ...)`
- Resolver con `collector->settle('ins_editorials')`
- Post-settle: iterar resultados, filtrar `isVisible()`, descartar no visibles
- Solo los visibles pasan a Ronda 2

**Cambio conceptual**:
```
ANTES:  foreach insertadas → findEditorial(sync) → if visible → acumular datos inline
DESPUES: foreach insertadas → add(async) → settle → foreach visible → add deps(async) → settle
```

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que `findEditorialById` se llama con `self::ASYNC` para insertadas via collector
2. **GREEN**: Acumular promises de insertadas
3. **RED**: Test que editorial no visible se excluye post-settle
4. **GREEN**: Filtrar `isVisible()` despues del settle
5. **RED**: Test que editorial fallido (rejected) se ignora
6. **GREEN**: Usar `collector->has()` para verificar fulfilled

**Acceptance Criteria**:
- [ ] Insertadas se resuelven via collector en Ronda 1
- [ ] `isVisible()` se verifica post-settle
- [ ] Editorials no visibles no generan dependencias
- [ ] Editorials rejected se ignoran

---

### BE-008: Insertadas dependencias async (Ronda 2) + journalist dedup

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 12
**Depends on**: BE-007

**Requirements**:
- Post-settle de BE-007: para cada insertada visible, acumular dependencias:
  - `collector->add('ins_deps', "section_{$sectionId}", ...)` — dedup por sectionId
  - `collector->add('ins_deps', "journalist_{$aliasId}", ...)` — dedup por aliasId
  - Multimedia async (mantener patron existente)
- Resolver con `collector->settle('ins_deps')`
- Post-settle: transformar cada insertada con sus dependencias resueltas
- Journalist multi-transform: mismo aliasId puede aparecer en principal (PR1) y en insertadas con distinto contexto (section, hasTwitter=false)

**Complejidad**: ALTA — coordinar journalists entre PR1 (principal) y PR2 (insertadas). Puede requerir un grupo compartido o un mapa de contextos cross-PR.

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que sections de insertadas visibles se acumulan en collector
2. **GREEN**: Acumular sections
3. **RED**: Test que journalists de insertadas se acumulan con dedup
4. **GREEN**: Acumular con `journalist_{$aliasId}` keys
5. **RED**: Test que journalist compartido entre principal e insertada = 1 HTTP call
6. **GREEN**: Dedup cross-group o grupo compartido
7. **RED**: Test que signatures de insertadas se transforman con section correcta
8. **GREEN**: Mapa de contextos + transformacion post-resolve
9. **RED**: Test que multimedia de insertadas sigue funcionando (ya async)
10. **GREEN**: Mantener compatibilidad con patron multimedia existente
11. **REFACTOR**: Clean up, verificar resolveData['insertedNews'] identico

**Acceptance Criteria**:
- [ ] Sections de insertadas visibles en collector (dedup por sectionId)
- [ ] Journalists de insertadas en collector (dedup por aliasId)
- [ ] Journalist dedup funciona cross-principal (mismo aliasId en principal e insertada = 1 call)
- [ ] Multi-transform: cada contexto genera su propia signature
- [ ] Multimedia insertadas sigue funcionando
- [ ] `resolveData['insertedNews']` identico al original

**Done When**: Tests verdes, insertadas completas en 2 rondas async.

**PR2 Checkpoint**:
```bash
./bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorTest.php
make test_unit
make test_stan
```

**Escape Hatch**: Si la complejidad de journalist dedup cross-principal es excesiva, permitir duplicacion de HTTP call entre principal e insertadas (misma aliasId = 2 calls, uno en cada grupo). Optimizar en PR3 si hay patron claro.

---

## PR3: Recomendadas Async

### BE-009: Recomendadas editorial fetches async (Ronda 1)

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 7
**Depends on**: PR2 merged

**Requirements**:
- Mismo patron que BE-007 pero para recomendadas (L167-207)
- Ronda 1: `collector->add('rec_editorials', "rec_{$id}", ...)`
- Post-settle: filtrar `isVisible()`

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que recomendadas se acumulan en collector
2. **GREEN**: Acumular promises
3. **RED**: Test que no visibles se excluyen
4. **GREEN**: Filtrar isVisible
5. **REFACTOR**: Evaluar si el patron insertadas/recomendadas se puede extraer a metodo comun

**Acceptance Criteria**:
- [ ] Recomendadas se resuelven en Ronda 1 via collector
- [ ] `isVisible()` post-settle
- [ ] Tests verdes

---

### BE-010: Recomendadas dependencias async (Ronda 2) + full quality

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 10
**Depends on**: BE-009

**Requirements**:
- Mismo patron que BE-008 pero para recomendadas
- Sections, journalists (dedup), multimedia
- Journalist dedup cross-principal + insertadas + recomendadas
- Full quality suite al final

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1-8: Mismo patron que BE-008 adaptado a recomendadas
9. **REFACTOR**: Extraer metodo comun para Ronda 2 (insertadas y recomendadas usan mismo patron)
10. **QUALITY**: Full suite

**Acceptance Criteria**:
- [ ] Recomendadas completas en 2 rondas async
- [ ] Journalist dedup funciona cross-3-contextos (principal + insertadas + recomendadas)
- [ ] `resolveData['recommendedEditorials']` identico al original
- [ ] `make tests` pasa completamente

**Done When**: Tests verdes, `make tests` verde, feature completa.

**PR3 Checkpoint (FINAL)**:
```bash
make tests
```

---

## Orden de Ejecucion Global

```
PR1: BE-001 → BE-002 + BE-004 + BE-005 + BE-006 (parallelizable) → BE-003 → PR1 checkpoint
PR2: BE-007 → BE-008 → PR2 checkpoint
PR3: BE-009 → BE-010 → PR3 checkpoint (FINAL)
```

Cada PR es testeable y deployable independientemente. Si PR2 se bloquea, PR1 ya da valor (editorial principal async).

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
