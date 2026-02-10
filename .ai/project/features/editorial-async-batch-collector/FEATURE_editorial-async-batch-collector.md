# Feature: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Priority**: HIGH
> **Status**: PLANNING
> **Created**: 2026-02-10
> **Workflow**: task-breakdown

---

## Objective

Refactorizar el sistema de orquestacion editorial para agrupar todas las peticiones HTTP del dominio en batches async paralelos, eliminando las ~42 llamadas secuenciales bloqueantes actuales. Disenar una arquitectura multi-formato que soporte diferentes respuestas (Apps, Web) sobre la misma capa de fetching.

---

## Context

### Problema

El `EditorialOrchestrator` (`src/Orchestrator/Chain/EditorialOrchestrator.php`) agrega datos de 7 bounded contexts (Editorial, Section, Tag, Journalist, Multimedia, Membership, Legacy) para construir la respuesta de un editorial. La mayoria de llamadas HTTP son secuenciales y bloqueantes, generando ~4.2s de latencia en I/O.

### Llamadas HTTP actuales

| Bounded Context | Metodo | Linea | Repeticiones | Tipo |
|-----------------|--------|-------|-------------|------|
| Editorial | `findEditorialById` (principal) | 104 | 1 | SYNC |
| Section | `findSectionById` (principal) | 115 | 1 | SYNC |
| Membership | `getMembershipUrl` | 387 | 1 | ASYNC |
| Editorial | `findEditorialById` (insertadas) | 131 | N | SYNC loop |
| Section | `findSectionById` (insertadas) | 134 | N | SYNC loop |
| Journalist | `findJournalistByAliasId` (insertadas) | 139 | N x S | SYNC nested |
| Multimedia | `findMultimediaById` (insertadas) | 147 | N | ASYNC |
| Editorial | `findEditorialById` (recomendadas) | 172 | R | SYNC loop |
| Section | `findSectionById` (recomendadas) | 175 | R | SYNC loop |
| Journalist | `findJournalistByAliasId` (recomendadas) | 180 | R x S | SYNC nested |
| Multimedia | `findMultimediaById` (recomendadas) | 188 | R | ASYNC |
| Multimedia | `findMultimediaOpeningById` | 449 | 1 | SYNC |
| Multimedia | `findPhotoById` (body tags) | 333 | P | SYNC loop |
| Tag | `findTagById` | 226 | T | SYNC loop |
| Legacy | `findCommentsByEditorialId` | 239 | 1 | SYNC |
| Journalist | `findJournalistByAliasId` (principal) | 243 | S | SYNC loop |

**Variables**: N=insertadas, R=recomendadas, S=firmas/editorial, T=tags, P=fotos en body

### Datos que NO se recuperan

- Tags de noticias insertadas (L124-161)
- Tags de editoriales recomendados (L163-207)

### Escenario tipico

Editorial con 3 insertadas, 4 recomendadas, 2 firmas cada una, 5 tags, 3 fotos body:
- **SYNC secuenciales**: ~42 HTTP calls = ~4.2s
- **ASYNC (solo multimedia)**: ~9 promises

---

## Acceptance Criteria

### Fase A: Batch Editorials
- [ ] Editorials insertadas se resuelven via `Utils::settle()` batch async
- [ ] Editorials recomendadas se resuelven via `Utils::settle()` batch async
- [ ] `isVisible()` se verifica post-settle

### Fase B: Batch Dependencias
- [ ] Todas las llamadas HTTP independientes se ejecutan en paralelo via `Utils::settle()`
- [ ] Maximo 3 fases secuenciales (editorial principal -> hijos -> dependencias)
- [ ] Tags de insertadas y recomendadas se recuperan
- [ ] Journalists se recuperan en batch async
- [ ] Sections se recuperan en batch async
- [ ] Photos de body tags se recuperan en batch async
- [ ] Tolerancia a fallos por elemento (un tag fallido no rompe la editorial)

### Fase C: Multi-formato
- [ ] Un mismo orquestador alimenta diferentes formatos de respuesta
- [ ] Namespace `Apps/` existente sigue funcionando sin cambios
- [ ] Diseño extensible para futuros formatos (Web, AMP, etc.)

### Quality Gates
- [ ] PHPStan level 9: 0 errores
- [ ] PHPUnit: tests pasan
- [ ] Mutation testing: MSI >= 79%
- [ ] PSR-12 + Symfony coding standards
- [ ] Compatibilidad API v1 (no breaking changes)

---

## Specs Funcionales

### SP-01: Async directo con `$async` flag
Todos los clients soportan `$client->findXById($id, self::ASYNC)` devolviendo Promise. Se acumulan promises y se resuelven con `Utils::settle()`.

### SP-02: Deduplicacion de IDs
Un mismo tag/journalist/section puede aparecer en editorial principal + insertadas + recomendadas. Indexar promises por ID (`$promises[$id] ??= ...`) deduplica naturalmente.

### SP-03: Tags para noticias insertadas
Recuperar tags de cada noticia insertada e incluirlos en la respuesta transformada.

### SP-04: Tags para editoriales recomendados
Recuperar tags de cada editorial recomendado e incluirlos en la respuesta.

### SP-05: Journalists en batch async
`retrieveAliasFormat()` (L281-296) actualmente hace HTTP sync por cada firma en 3 lugares (insertadas L139, recomendadas L180, principal L243). Mover a batch async.

### SP-06: Sections en batch async
`findSectionById` se llama sync para insertadas (L134) y recomendadas (L175). Mover a batch async.

### SP-07: Photos de body tags en batch async
`retrievePhotosFromBodyTags()` (L306-323) hace HTTP sync por cada foto. Mover a batch async.

### SP-08: Soporte async en clients externos
Todos los clients `ec/*` soportan (o soportaran) el flag `$async`. No se necesitan decorators ni wrappers.

### SP-09: Arquitectura multi-formato
Separar la capa de fetching (comun) de la capa de transformacion (formato-especifica). Actualmente solo existe `src/Application/DataTransformer/Apps/`. Diseñar extension para soportar `Web/` y futuros formatos.

### SP-10: Tolerancia a fallos
Mantener patron actual de `try/catch` + `continue`. `Utils::settle()` maneja promises rejected individualmente.

---

## Restricciones Tecnicas

- PHP 8.2+ con strict types
- Symfony 6.4
- HTTPLug con Guzzle7 async client (`config/packages/httplug.yaml`)
- `GuzzleHttp\Promise\Utils::settle()` como mecanismo de resolucion
- Clients externos: paquetes `ec/editorial-client`, `ec/tag-client ^4.0`, `ec/section-client`, `ec/multimedia-client`, `ec/journalist-client`
- Compatibilidad API v1 actual

---

## Nota sobre Arquitectura

La feature `snaapi-scalable-architecture` (Pipeline + DTO Factory) en `.ai/project/features/` fue un experimento previo. **NO debe asumirse como solucion adoptada**. La arquitectura final debe diseñarse desde cero, evaluando criticamente cualquier patron antes de aplicarlo. El punto de partida real es el codigo actual del `EditorialOrchestrator` y los patrones existentes en produccion (Chain of Responsibility, Strategy, Compiler Passes).

Las features documentadas en `.ai/project/features/` pueden servir como referencia pero deben cuestionarse antes de adoptarse.

---

## Mejora Esperada

```
ANTES:  ~42 HTTP calls secuenciales = ~4.2s I/O
DESPUES: 3 fases (1 sync + 2 parallel settle) = ~300-400ms
Reduccion: ~90% en tiempo de I/O
```

---

## References

### Codigo Fuente
- `src/Orchestrator/Chain/EditorialOrchestrator.php` - Orquestador actual
- `src/Orchestrator/OrchestratorChainHandler.php` - Chain handler
- `src/Application/DataTransformer/Apps/` - Transformers actuales
- `config/packages/httplug.yaml` - Configuracion HTTP clients
- `config/packages/orchestrators.yaml` - Registro de orquestadores

### Patrones Existentes en Produccion
- `Utils::settle()` para multimedia (EditorialOrchestrator L216-218)
- Promise async para membership (EditorialOrchestrator L387-392)
- Compiler Passes para registro dinamico (`src/DependencyInjection/Compiler/`)

---

**Document Status**: PLANNING_COMPLETE
**Last Updated**: 2026-02-10
**Author**: Session claude/refactor-editorial-async-UrZ1X
