# QA Tasks: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 32_tasks_qa.md
> **Role**: QA Engineer
> **Created**: 2026-02-10
> **Updated**: 2026-02-10

---

## Task Overview

| Phase | Tasks | Priority | Effort |
|-------|-------|----------|--------|
| A. AsyncBatchCollector Unit Tests | QA-001 | HIGH | Medium |
| B. Regression | QA-002 | HIGH | Medium |
| C. Async Behavior | QA-003 | HIGH | High |
| D. Quality Gates | QA-004 | HIGH | Low |

---

## Phase A: AsyncBatchCollector

### QA-001: Unit Tests de AsyncBatchCollector

**Priority**: HIGH
**Max Iterations**: 7

**Test Scenarios (DataProvider)**:

| Scenario | Verificacion |
|----------|-------------|
| Add + settle + get basico | Callable devuelve FulfilledPromise, get devuelve valor |
| Dedup por key | Misma key llamada 2 veces, callable ejecutado 1 sola vez |
| Rejected promise | `has()` devuelve false, `get()` lanza excepcion |
| Mixed fulfilled + rejected | `getGroup()` solo devuelve fulfilled |
| Encadenamiento | settle('A'), usar resultado en add('B'), settle('B') OK |
| Grupo vacio | settle de grupo sin adds no falla |
| Settle limpia callables | Despues de settle, add al mismo grupo acumula nuevos |
| Multiple groups | Dos grupos independientes se resuelven sin interferencia |

**Verification**:
```bash
./bin/phpunit tests/Infrastructure/Async/AsyncBatchCollectorTest.php
```

**Done When**: Todos los escenarios cubiertos, tests verdes.

---

## Phase B: Regression

### QA-002: Tests Existentes Sin Regresion

**Priority**: HIGH
**Max Iterations**: 5

**Objetivo**: Todos los tests existentes en `EditorialOrchestratorTest.php` pasan despues de cada PR.

**Verificacion continua**: Ejecutar despues de cada BE-* completada.

**Verification**:
```bash
./bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**Done When**: Cero tests fallando que pasaban antes del refactor. Ejecutar en cada PR.

---

## Phase C: Async Behavior

### QA-003: Tests de Batch Behavior

**Priority**: HIGH
**Max Iterations**: 12

**Test Scenarios por PR (DataProvider)**:

#### PR1: Editorial Principal

| Scenario | Verificacion |
|----------|-------------|
| Tags via collector | `findTagById` llamado con `self::ASYNC`, acumulados en collector |
| Tags dedup | Tag compartido = 1 sola add al collector |
| Tags fallo parcial | 1 tag falla, resto se resuelve normalmente |
| Journalists via collector | `findJournalistByAliasId` con `self::ASYNC` via collector |
| Journalists dedup | Mismo journalist en principal = 1 promise |
| Journalists fallo parcial | 1 journalist falla, firma se omite |
| Journalists hasTwitter | Blog editorial → hasTwitter=true, otros → false |
| Photos via collector | `findPhotoById` con `self::ASYNC` via collector |
| Photos fallo parcial | 1 photo falla, rest OK |
| Comments via collector | `findCommentsByEditorialId` con `self::ASYNC` via collector |
| Comments fallo | Comments falla, respuesta sigue funcionando |
| Opening via collector | `findMultimediaOpeningById` con `self::ASYNC` via collector |
| Opening fallo | Opening falla, respuesta sigue funcionando |
| Editorial sin tags | No se llama findTagById |
| Editorial sin firmas | No se llama findJournalistByAliasId |
| Settle resuelve todo | 1 solo settle para todas las dependencias del principal |

#### PR2: Insertadas

| Scenario | Verificacion |
|----------|-------------|
| Editorials insertadas via collector | `findEditorialById` con `self::ASYNC` para insertadas |
| Editorial no visible post-settle | Editorial insertada con `isVisible()=false` excluida |
| Editorial sin insertadas | No se acumulan promises |
| Sections insertadas via collector | `findSectionById` con `self::ASYNC` despues de Ronda 1 |
| Sections dedup | Misma section en 2 insertadas = 1 promise |
| Journalists insertadas dedup | Mismo journalist en insertada = 1 promise |
| Journalist cross-dedup | Mismo journalist en principal e insertada = 1 HTTP call |
| Journalist multi-transform | Mismo journalist con distinto section genera distintas signatures |
| Multimedia insertadas | Multimedia async sigue funcionando |
| 2 rondas | Ronda 1 (editorials) → Ronda 2 (deps) secuenciales |

#### PR3: Recomendadas

| Scenario | Verificacion |
|----------|-------------|
| Editorials recomendadas via collector | `findEditorialById` con `self::ASYNC` para recomendadas |
| Editorial no visible post-settle | Recomendada no visible excluida |
| Sections recomendadas | Sections via collector |
| Journalists recomendadas dedup | Dedup por aliasId |
| Journalist cross-3-contextos | Dedup entre principal + insertadas + recomendadas |
| Editorial sin recomendadas | No se acumulan promises |

**Done When**: Todos los escenarios cubiertos con tests parametrizados.

---

## Phase D: Quality Gates

### QA-004: Full Quality Suite

**Priority**: HIGH
**Max Iterations**: 3

**Gates** (ejecutar al final de cada PR, obligatorio en PR3):
- [ ] `make test_cs` - Coding standards
- [ ] `make test_stan` - PHPStan level 9
- [ ] `make test_unit` - All unit tests
- [ ] `make test_container` - DI container valid
- [ ] `make test_infection` - Mutation testing MSI >= 79%

**Verification**:
```bash
make tests
```

**Done When**: `make tests` pasa completamente en PR3.

**Escape Hatch**: Si MSI baja por debajo de 79%, anadir tests especificos para cubrir mutantes escapados en:
- `AsyncBatchCollector::settle()` — branch coverage para fulfilled/rejected
- `AsyncBatchCollector::add()` — dedup logic
- Journalist multi-transform — branch coverage por contexto

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
