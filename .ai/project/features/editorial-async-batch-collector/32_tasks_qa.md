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
| A. Regression | QA-001 | HIGH | Medium |
| B. Async Behavior | QA-002 to QA-003 | HIGH | High |
| C. Quality Gates | QA-004 | HIGH | Low |

---

## Phase A: Regression

### QA-001: Tests Existentes Sin Regresion

**Priority**: HIGH
**Max Iterations**: 5

**Objetivo**: Todos los tests existentes en `EditorialOrchestratorTest.php` pasan despues de cada tarea BE-*.

**Verificacion continua**: Ejecutar despues de cada BE-* completada.

**Verification**:
```bash
./bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**Done When**: Cero tests fallando que pasaban antes del refactor.

---

## Phase B: Async Behavior

### QA-002: Tests de Batch Behavior

**Priority**: HIGH
**Max Iterations**: 10

**Test Scenarios (DataProvider)**:

| Scenario | Verificacion |
|----------|-------------|
| Tags via async | `findTagById` llamado con `self::ASYNC`, promises acumuladas, settle resuelve |
| Tags dedup | Un tag compartido por principal + insertada = 1 sola promise |
| Tags fallo parcial | 1 tag falla, resto se resuelve normalmente |
| Journalists via async | `findJournalistByAliasId` con `self::ASYNC` para insertadas + recomendadas + principal |
| Journalists dedup | Mismo journalist en 2 editorials = 1 promise |
| Journalists fallo parcial | 1 journalist falla, firma se omite, rest OK |
| Sections via async | `findSectionById` con `self::ASYNC` para insertadas + recomendadas |
| Sections dedup | Misma section en 2 editorials = 1 promise |
| Photos via async | `findPhotoById` con `self::ASYNC` para body tags |
| Photos fallo parcial | 1 photo falla, rest OK |
| Editorials insertadas via async | `findEditorialById` con `self::ASYNC` para insertadas |
| Editorials recomendadas via async | `findEditorialById` con `self::ASYNC` para recomendadas |
| Editorial no visible post-settle | Editorial insertada con `isVisible()=false` se excluye |
| Editorial sin insertadas | No se llama findEditorialById async para insertadas |
| Editorial sin recomendadas | No se llama findEditorialById async para recomendadas |
| Editorial sin tags | No se llama findTagById |
| `fulfilledTags()` filtra | Solo Promise::FULFILLED incluidas en resultado |
| `fulfilledJournalists()` filtra | Solo Promise::FULFILLED incluidas |
| `fulfilledSections()` filtra | Solo Promise::FULFILLED incluidas |
| `fulfilledPhotos()` filtra | Solo Promise::FULFILLED incluidas |

**Done When**: Todos los escenarios cubiertos con tests parametrizados.

---

### QA-003: Tests Tags Insertadas/Recomendadas (Datos Nuevos)

**Priority**: MEDIUM
**Max Iterations**: 7

**Test Scenarios**:

| Scenario | Input | Expected |
|----------|-------|----------|
| Insertada con 2 tags | Editorial insertada con tags | Tags acumulados y resueltos |
| Recomendada con 3 tags | Editorial recomendada con tags | Tags acumulados y resueltos |
| Tags compartidos principal + insertada | Tag ID '5' en ambos | 1 sola promise (dedup) |
| Insertada sin tags | Editorial sin tags | No falla, tags = [] |
| Recomendada no visible | Editorial con isVisible=false | No se acumulan sus tags |

**Done When**: Tags de insertadas y recomendadas verificados.

---

## Phase C: Quality Gates

### QA-004: Full Quality Suite

**Priority**: HIGH
**Max Iterations**: 3

**Gates**:
- [ ] `make test_cs` - Coding standards
- [ ] `make test_stan` - PHPStan level 9
- [ ] `make test_unit` - All unit tests
- [ ] `make test_container` - DI container valid
- [ ] `make test_infection` - Mutation testing MSI >= 79%

**Verification**:
```bash
make tests
```

**Done When**: `make tests` pasa completamente.

**Escape Hatch**: Si MSI baja por debajo de 79%, añadir tests especificos para cubrir mutantes escapados en los nuevos callbacks `fulfilled*()`.

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
