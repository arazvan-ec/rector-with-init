# QA Tasks: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 32_tasks_qa.md
> **Role**: QA Engineer
> **Created**: 2026-02-10

---

## Task Overview

| Phase | Tasks | Priority | Effort |
|-------|-------|----------|--------|
| A. BatchRequestCollector Tests | QA-001 to QA-002 | HIGH | Medium |
| B. EditorialOrchestrator Regression | QA-003 to QA-005 | HIGH | High |
| C. Quality Gates | QA-006 | HIGH | Low |

---

## Phase A: BatchRequestCollector Tests

### QA-001: Unit Tests BatchRequestCollector

**Priority**: HIGH
**Reference**: `30_tasks_backend.md` BE-002
**Max Iterations**: 7

**Test Scenarios (DataProvider)**:

| Scenario | Input | Expected |
|----------|-------|----------|
| No IDs added | `resolveTags()` sin `addTag()` | `[]` |
| Single ID | `addTag('1')` | `['1' => Tag]` |
| Multiple IDs | `addTag('1'), addTag('2')` | `['1' => Tag, '2' => Tag]` |
| Duplicate IDs | `addTag('1'), addTag('1')` | 1 sola llamada HTTP, `['1' => Tag]` |
| Failed ID | Mock throws exception | `[]`, logger called |
| Mixed success/fail | `addTag('1'), addTag('bad')` | `['1' => Tag]`, logger called for 'bad' |

**Repetir para**: `resolveJournalists()`, `resolveSections()`, `resolvePhotos()`

**Verification**:
```bash
./bin/phpunit tests/Infrastructure/Http/BatchRequestCollectorTest.php
```

**Done When**: 100% cobertura del collector, todos los escenarios cubiertos.

---

### QA-002: Integration Smoke Test

**Priority**: MEDIUM
**Max Iterations**: 5

**Test**:
- Verificar que `BatchRequestCollector` se resuelve correctamente del container Symfony
- Verificar que las dependencias (clients, logger) se inyectan

**Verification**:
```bash
make test_container
```

**Done When**: Container compila con el nuevo servicio.

---

## Phase B: EditorialOrchestrator Regression

### QA-003: Tests Existentes Sin Regresion

**Priority**: HIGH
**Max Iterations**: 5

**Objetivo**: Todos los tests existentes en `EditorialOrchestratorTest.php` pasan despues del refactor.

**Verification**:
```bash
./bin/phpunit tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**Done When**: Cero tests fallando que pasaban antes del refactor.

---

### QA-004: Tests Nuevos - Batch Behavior

**Priority**: HIGH
**Max Iterations**: 10

**Test Scenarios**:

| Scenario | Verificacion |
|----------|-------------|
| Tags via collector | `collector->addTag()` llamado N veces, `resolveTags()` 1 vez |
| Journalists via collector | `collector->addJournalist()` llamado para insertadas + recomendadas + principal |
| Sections via collector | `collector->addSection()` llamado para insertadas + recomendadas |
| Photos via collector | `collector->addPhoto()` llamado para body tag pictures + membership cards |
| Dedup cross-editorial | Un tag compartido por principal + insertada solo genera 1 call |
| Fallo parcial tags | 1 tag falla, resto se resuelve normalmente |
| Fallo parcial journalists | 1 journalist falla, firma se omite |
| Editorial sin insertadas | No se llama addTag/addJournalist/addSection para insertadas |
| Editorial sin recomendadas | No se llama addTag/addJournalist/addSection para recomendadas |
| Editorial sin tags | No se llama addTag |

**Done When**: Todos los scenarios cubiertos con tests parametrizados.

---

### QA-005: Tests Nuevos - Tags Insertadas/Recomendadas

**Priority**: MEDIUM
**Max Iterations**: 7

**Test Scenarios**:

| Scenario | Input | Expected |
|----------|-------|----------|
| Insertada con 2 tags | Editorial insertada con tags | `resolveData['insertedNews'][$id]` contiene tags |
| Recomendada con 3 tags | Editorial recomendada con tags | `resolveData['recommendedEditorials'][$id]` contiene tags |
| Tags compartidos entre principal e insertada | Tag ID '5' en ambos | Solo 1 HTTP call (dedup) |
| Insertada sin tags | Editorial sin tags | No falla, tags = [] |

**Done When**: Tags de insertadas y recomendadas verificados en respuesta.

---

## Phase C: Quality Gates

### QA-006: Full Quality Suite

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

**Escape Hatch**: Si MSI baja por debajo de 79% debido a nuevos mutantes en el collector, añadir tests especificos para cubrir los mutantes escapados.

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
