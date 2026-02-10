# Feature: Editorial Async Batch Collector

> **Feature ID**: editorial-async-batch-collector
> **Priority**: HIGH
> **Status**: PLANNING
> **Created**: 2026-02-10
> **Updated**: 2026-02-10
> **Workflow**: task-breakdown

---

## Objective

Refactorizar el sistema de orquestacion editorial para agrupar todas las peticiones HTTP del dominio en batches async paralelos, eliminando las ~42 llamadas secuenciales bloqueantes actuales. Introducir un `AsyncBatchCollector` como abstraccion de agrupacion configurable que permita encadenar fases de resolucion async.

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

### Datos que NO se recuperan (decision intencional de producto)

- Tags de noticias insertadas (L124-161) — **EXCLUIDO del scope, decision de producto**
- Tags de editoriales recomendados (L163-207) — **EXCLUIDO del scope, decision de producto**

### Escenario tipico

Editorial con 3 insertadas, 4 recomendadas, 2 firmas cada una, 5 tags, 3 fotos body:
- **SYNC secuenciales**: ~42 HTTP calls = ~4.2s
- **ASYNC (solo multimedia)**: ~9 promises

---

## Acceptance Criteria

### Infraestructura: AsyncBatchCollector
- [ ] Clase `AsyncBatchCollector` con API: `add(group, key, callable)`, `settle(group)`, `get(group, key)`
- [ ] Soporte para grupos nombrados independientes
- [ ] Encadenamiento: settle grupo A → poblar grupo B → settle grupo B
- [ ] Unit tests de la clase aislada

### Fase A: Editorial Principal Async
- [ ] Comments (`findCommentsByEditorialId`) via async en el collector
- [ ] Opening multimedia (`findMultimediaOpeningById`) via async en el collector
- [ ] Journalists del principal en batch async con dedup por aliasId
- [ ] Tags del principal en batch async
- [ ] Photos body tags en batch async
- [ ] Todas las dependencias del principal resueltas en 1 settle

### Fase B: Insertadas Async (2 rondas)
- [ ] Ronda 1: Editorial fetches de insertadas via collector (batch async)
- [ ] Post-settle: filtrar `isVisible()` — solo visibles generan dependencias
- [ ] Ronda 2: Sections, journalists, multimedia de insertadas visibles via collector
- [ ] Journalist dedup: mismo aliasId = 1 HTTP call, N transformaciones con distintos contextos
- [ ] Tolerancia a fallos por elemento

### Fase C: Recomendadas Async (2 rondas)
- [ ] Mismo patron que Fase B para recomendadas
- [ ] Ronda 1: Editorial fetches de recomendadas via collector
- [ ] Post-settle: filtrar `isVisible()`
- [ ] Ronda 2: Sections, journalists, multimedia de recomendadas visibles

### Quality Gates
- [ ] PHPStan level 9: 0 errores
- [ ] PHPUnit: tests pasan
- [ ] Mutation testing: MSI >= 79%
- [ ] PSR-12 + Symfony coding standards
- [ ] Compatibilidad API v1 (no breaking changes)

---

## Delivery Model: 3 PRs Incrementales

### PR1: AsyncBatchCollector + Editorial Principal Async
- Clase `AsyncBatchCollector` (Infrastructure)
- Refactor editorial principal: comments, opening, journalists, tags, photos body — todo async en 1 grupo
- Tests unitarios del collector + tests del orchestrator adaptados

### PR2: Insertadas Async
- Refactor loop insertadas: 2 rondas (editorial resolve → filter visible → dependencies)
- Journalist dedup con multi-transform
- Tests

### PR3: Recomendadas Async
- Mismo patron que PR2 para recomendadas
- Tests
- Full quality suite (`make tests`)

---

## Specs Funcionales

### SP-01: AsyncBatchCollector
Clase en `src/Infrastructure/Async/AsyncBatchCollector.php` que abstrae la agrupacion de promises async. API:
- `add(string $group, string $key, callable $callable): void` — registra un callable en un grupo
- `settle(string $group): void` — ejecuta todos los callables del grupo, resuelve con `Utils::settle()`
- `get(string $group, string $key): mixed` — obtiene resultado resuelto
- `getGroup(string $group): array` — obtiene todos los resultados del grupo
- `has(string $group, string $key): bool` — verifica si existe un resultado fulfilled
Internamente usa `Utils::settle()` de GuzzleHttp con el patron `$async` de los clients existentes.

### SP-02: Async directo con `$async` flag
Todos los clients soportan `$client->findXById($id, self::ASYNC)` devolviendo Promise. El collector encapsula este patron.

### SP-03: Deduplicacion de IDs
Un mismo tag/section puede aparecer en editorial principal + insertadas + recomendadas. Indexar por ID (`$promises[$id] ??= ...`) deduplica naturalmente.

### SP-04: Journalist Dedup HTTP + Multi-Transform
`findJournalistByAliasId` se deduplica por aliasId (1 HTTP call por periodista unico). Post-resolve, se transforma N veces con distintas combinaciones de `(section, hasTwitter)`. Requiere un mapa de contextos `aliasId → [{section, hasTwitter, targetEditorial}]`.

### SP-05: Journalists en batch async
`retrieveAliasFormat()` (L281-296) se descompone en: acumular promise, resolver batch, transformar post-resolve. El metodo original se refactoriza o elimina.

### SP-06: Sections en batch async
`findSectionById` se llama sync para insertadas (L134) y recomendadas (L175). Mover a batch async via collector. Section del principal sigue sync (necesaria para membership).

### SP-07: Photos de body tags en batch async
`retrievePhotosFromBodyTags()` (L306-323) hace HTTP sync por cada foto. Mover a batch async via collector.

### SP-08: Comments en batch async
`findCommentsByEditorialId` (L239) actualmente sync. Mover a async via collector.

### SP-09: Opening multimedia en batch async
`findMultimediaOpeningById` (L449) actualmente sync. Mover a async via collector.

### SP-10: Soporte async en clients externos
Todos los clients `ec/*` soportan (o soportaran) el flag `$async`. No se necesitan decorators ni wrappers.

### SP-11: isVisible() con 2 rondas async
Para insertadas y recomendadas: Ronda 1 resuelve editorials → filtra isVisible() → Ronda 2 solo lanza dependencias de visibles. Evita HTTP calls innecesarios para editorials ocultos (~5 calls por editorial no visible).

### SP-12: Tolerancia a fallos
Mantener patron actual de `try/catch` + `continue`. `Utils::settle()` maneja promises rejected individualmente. Un fallo en un elemento no rompe la respuesta completa.

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
DESPUES: Fases con settle async agrupado = ~300-400ms
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
