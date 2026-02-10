# Backend Tasks: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Document**: 30_tasks_backend.md
> **Role**: Backend Engineer
> **Methodology**: TDD
> **Created**: 2026-02-10

---

## Task Overview

| Phase | Tasks | Priority | Effort |
|-------|-------|----------|--------|
| A. BatchRequestCollector | BE-001 to BE-003 | HIGH | Medium |
| B. EditorialOrchestrator Refactor | BE-004 to BE-007 | HIGH | High |
| C. Tags insertadas/recomendadas | BE-008 | MEDIUM | Low |

---

## Phase A: BatchRequestCollector

### BE-001: BatchRequestCollectorInterface

**Priority**: HIGH
**Reference**: `10_architecture.md` section 2.1
**Methodology**: TDD
**Max Iterations**: 5

**Requirements**:
- Interfaz con metodos `add*()` y `resolve*()` por bounded context
- Tipos de retorno estrictos con generics PHPStan
- No depende de implementacion concreta de clients

**Files to Create**:
```
src/Infrastructure/Http/BatchRequestCollectorInterface.php
```

**Acceptance Criteria**:
- [ ] Interfaz definida con metodos para Tag, Journalist, Section, Photo
- [ ] PHPStan level 9 pasa
- [ ] No tiene dependencias externas

**Done When**: Interfaz compilable, PHPStan clean.

---

### BE-002: BatchRequestCollector Implementation

**Priority**: HIGH
**Reference**: `10_architecture.md` section 2.1, 3
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Implementa `BatchRequestCollectorInterface`
- Recibe los 4 clients por constructor injection (Tag, Journalist, Section, Multimedia)
- Cada `add*()` registra ID en un `array<string, true>` (dedup natural por clave)
- Cada `resolve*()` itera IDs acumulados, ejecuta client call, maneja fallos
- `LoggerInterface` para loguear fallos individuales
- Tolerancia a fallos: un ID fallido no rompe el batch

**Files to Create**:
```
src/Infrastructure/Http/BatchRequestCollector.php
tests/Infrastructure/Http/BatchRequestCollectorTest.php
```

**TDD Approach**:
1. **RED**: Test `resolveTagsReturnsEmptyWhenNoIdsAdded`
2. **GREEN**: Implementar `resolveTags()` basico
3. **RED**: Test `resolveTagsDeduplicatesIds`
4. **GREEN**: Implementar dedup
5. **RED**: Test `resolveTagsSkipsFailedIds`
6. **GREEN**: Implementar try/catch por ID
7. **RED**: Test `resolveTagsReturnsIndexedById`
8. **GREEN**: Implementar indexacion
9. **REFACTOR**: Extraer patron comun entre resolve methods

**Acceptance Criteria**:
- [ ] Deduplicacion de IDs funciona
- [ ] Fallos individuales se omiten y loguean
- [ ] Resultados indexados por ID
- [ ] Cada resolve method es independiente
- [ ] PHPStan level 9 pasa
- [ ] Tests unitarios con 100% cobertura del collector

**Verification**:
```bash
./bin/phpunit tests/Infrastructure/Http/BatchRequestCollectorTest.php
```

**Done When**: Todos los tests verdes, PHPStan clean, collector funcional.

**Escape Hatch**: Si los clients sync dan problemas de tipo o interfaz, usar reflection para acceder al HttpAsyncClient interno.

---

### BE-003: Service Registration

**Priority**: HIGH
**Methodology**: Configuration
**Max Iterations**: 3

**Requirements**:
- Registrar `BatchRequestCollector` como servicio Symfony
- Autowire + autoconfigure
- Inyectar los 4 clients existentes y LoggerInterface

**Files to Create/Modify**:
```
config/services.yaml  (o config/packages/batch_collector.yaml)
```

**Acceptance Criteria**:
- [ ] `make test_container` pasa
- [ ] Servicio inyectable en `EditorialOrchestrator`

**Done When**: Container compila, servicio resolvible.

---

## Phase B: EditorialOrchestrator Refactor

### BE-004: Inyectar BatchRequestCollector en EditorialOrchestrator

**Priority**: HIGH
**Reference**: `10_architecture.md` section 2.2
**Methodology**: TDD
**Max Iterations**: 5

**Requirements**:
- Agregar `BatchRequestCollectorInterface` al constructor
- No cambiar comportamiento todavia - solo inyectar
- Tests existentes deben seguir pasando

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test existente falla por parametro nuevo en constructor
2. **GREEN**: Agregar mock del collector al setUp
3. **REFACTOR**: Verificar que todos los tests existentes pasan

**Acceptance Criteria**:
- [ ] Constructor actualizado con `BatchRequestCollectorInterface`
- [ ] Todos los tests existentes pasan sin cambios funcionales
- [ ] PHPStan level 9 pasa

**Done When**: Build verde, cero regresion.

---

### BE-005: Refactor fase de acumulacion (tags)

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Reemplazar el loop sync de tags (L222-230) por `collector->addTag()` + `collector->resolveTags()`
- Mantener el mismo resultado final: `$tags` array para `detailsAppsDataTransformer->write()`

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que verifica que collector.addTag se llama N veces
2. **GREEN**: Reemplazar loop sync por collector.addTag
3. **RED**: Test que verifica resolveTags se llama una vez y resultado se usa
4. **GREEN**: Llamar resolveTags y usar resultado
5. **REFACTOR**: Eliminar metodo original si queda sin uso

**Acceptance Criteria**:
- [ ] Tags se acumulan via collector
- [ ] Se resuelven en batch
- [ ] Resultado identico al original
- [ ] Tests existentes adaptados y verdes

**Done When**: Tests verdes, comportamiento identico.

---

### BE-006: Refactor fase de acumulacion (journalists)

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Reemplazar `retrieveAliasFormat()` sync (llamada en 3 lugares) por `collector->addJournalist()` + `collector->resolveJournalists()`
- El metodo `retrieveAliasFormat()` actualmente hace HTTP + transform. Separar en: acumular ID, resolver batch, transformar despues
- Mantener `JournalistsDataTransformer->write()` que necesita `Journalist` + `Section` + flags

**Complejidad**: ALTA - `retrieveAliasFormat()` se usa en insertadas (L139), recomendadas (L180), y principal (L243-252). Cada uso necesita el `Section` correspondiente y flag `$hasTwitter`.

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test que verifica addJournalist se llama con todos los aliasIds
2. **GREEN**: Acumular aliasIds en loops de insertadas, recomendadas, principal
3. **RED**: Test que resolveJournalists se llama y resultado se mapea correctamente
4. **GREEN**: Resolver batch y reconstruir signatures con datos resueltos
5. **REFACTOR**: Eliminar o simplificar `retrieveAliasFormat()`

**Acceptance Criteria**:
- [ ] Todas las firmas se acumulan antes de resolver
- [ ] Se resuelven en un unico batch
- [ ] Signatures de insertadas, recomendadas y principal correctas
- [ ] `hasTwitter` flag se mantiene para editorial principal

**Done When**: Tests verdes, signatures identicas al original.

**Escape Hatch**: Si la complejidad de reconstruir signatures es excesiva, mantener `retrieveAliasFormat()` pero envolver en promise pattern.

---

### BE-007: Refactor fase de acumulacion (sections + photos)

**Priority**: HIGH
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Sections: `findSectionById` se llama sync en insertadas (L134) y recomendadas (L175). Acumular + batch
- Photos: `retrievePhotosFromBodyTags` hace sync loop (L306-323). Acumular + batch
- La section del editorial PRINCIPAL (L115) sigue siendo sync porque se necesita para membership promise (L117)

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**TDD Approach**:
1. **RED**: Test addSection llamado por cada insertada/recomendada
2. **GREEN**: Acumular sectionIds
3. **RED**: Test resolveSections devuelve sections correctas
4. **GREEN**: Resolver batch y mapear a insertadas/recomendadas
5. **RED**: Test addPhoto llamado por cada body tag
6. **GREEN**: Acumular photoIds
7. **REFACTOR**: Simplificar `retrievePhotosFromBodyTags` para que solo acumule

**Acceptance Criteria**:
- [ ] Sections de insertadas/recomendadas via batch
- [ ] Photos de body tags via batch
- [ ] Section principal sigue sync (necesaria para membership)
- [ ] Tests verdes

**Done When**: Tests verdes, todos los resolves en fase batch.

---

## Phase C: Tags Insertadas/Recomendadas (Datos Faltantes)

### BE-008: Recuperar tags de insertadas y recomendadas

**Priority**: MEDIUM
**Methodology**: TDD
**Max Iterations**: 7

**Requirements**:
- En los loops de insertadas (L124-161) y recomendadas (L163-207), extraer tags de cada editorial y hacer `collector->addTag()`
- Los tags resueltos se incluyen en `resolveData['insertedNews'][$id]` y `resolveData['recommendedEditorials'][$id]`
- Los transformers correspondientes deben recibir y usar estos tags

**Files to Modify**:
```
src/Orchestrator/Chain/EditorialOrchestrator.php
tests/Orchestrator/Chain/EditorialOrchestratorTest.php
```

**Evaluacion previa**: Verificar que `RecommendedEditorialsDataTransformer` e `InsertedNewsDataTransformer` (via `BodyDataTransformer`) aceptan tags. Si no, ajustar transformers.

**TDD Approach**:
1. **RED**: Test que verifica addTag se llama con tags de insertadas
2. **GREEN**: Extraer tags de cada editorial insertada y acumular
3. **RED**: Test que tags de recomendadas se acumulan
4. **GREEN**: Extraer tags de cada editorial recomendada
5. **RED**: Test que tags resueltos aparecen en resolveData
6. **GREEN**: Incluir tags en resolveData por editorial
7. **REFACTOR**: Clean up

**Acceptance Criteria**:
- [ ] Tags de insertadas se recuperan
- [ ] Tags de recomendadas se recuperan
- [ ] Tags incluidos en resolveData
- [ ] Transformers reciben tags (o se adaptan)
- [ ] No rompe respuesta existente (backward compatible)

**Done When**: Tests verdes, tags presentes en respuesta.

---

## Orden de Ejecucion

```
BE-001 → BE-002 → BE-003 → BE-004 → BE-005 → BE-006 → BE-007 → BE-008
  │         │         │         │
  └─────────┴─────────┘         │
   (Collector completo)         │
                                └── (Refactor incremental del orchestrator)
```

**Checkpoints**:
1. Despues de BE-003: `make test_unit && make test_container && make test_stan`
2. Despues de BE-005: `make test_unit` (primera integracion collector-orchestrator)
3. Despues de BE-007: `make tests` (full suite, todo batch)
4. Despues de BE-008: `make tests` (full suite, datos nuevos)

---

**Document Status**: COMPLETE
**Last Updated**: 2026-02-10
