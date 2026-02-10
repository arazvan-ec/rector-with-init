# SPEC: Batch Async Request Collector por Dominio Editorial

## Problema

El `EditorialOrchestrator` (`src/Orchestrator/Chain/EditorialOrchestrator.php`) realiza ~42 llamadas HTTP secuenciales bloqueantes para resolver un editorial completo (principal + insertadas + recomendadas). Esto genera ~4.2s de latencia solo en I/O.

### Llamadas HTTP actuales (secuenciales)

| Bounded Context | Llamada | Ubicacion | Repeticiones | Tipo |
|-----------------|---------|-----------|-------------|------|
| Editorial | `findEditorialById` (principal) | L104 | 1 | SYNC |
| Section | `findSectionById` (principal) | L115 | 1 | SYNC |
| Membership | `getMembershipUrl` | L387 | 1 | ASYNC (ya existe) |
| Editorial | `findEditorialById` (insertadas) | L131 | N | SYNC loop |
| Section | `findSectionById` (insertadas) | L134 | N | SYNC loop |
| Journalist | `findJournalistByAliasId` (insertadas) | L139->288 | N x S | SYNC nested loop |
| Multimedia | `findMultimediaById` (insertadas) | L147->431 | N | ASYNC (ya existe) |
| Editorial | `findEditorialById` (recomendadas) | L172 | R | SYNC loop |
| Section | `findSectionById` (recomendadas) | L175 | R | SYNC loop |
| Journalist | `findJournalistByAliasId` (recomendadas) | L180->288 | R x S | SYNC nested loop |
| Multimedia | `findMultimediaById` (recomendadas) | L188->431 | R | ASYNC (ya existe) |
| Multimedia | `findMultimediaOpeningById` | L449 | 1 | SYNC |
| Multimedia | `findMultimediaById` (principal) | L212->431 | 1 | ASYNC (ya existe) |
| Multimedia | `findPhotoById` (body tags) | L333 | P | SYNC loop |
| Tag | `findTagById` | L226 | T | SYNC loop |
| Legacy | `findCommentsByEditorialId` | L239 | 1 | SYNC |
| Journalist | `findJournalistByAliasId` (principal) | L243->288 | S | SYNC loop |

**Variables**: N=insertadas, R=recomendadas, S=firmas por editorial, T=tags, P=fotos en body

### Datos que NO se recuperan actualmente

- Tags de noticias insertadas (L124-161): no se incluyen en `$resolveData['insertedNews']`
- Tags de editoriales recomendados (L163-207): no se incluyen en `$resolveData['recommendedEditorials']`

## Solucion Propuesta

### Patron: Request Collector / Batch Aggregator por Dominio

Crear una arquitectura que permita **agrupar todas las peticiones HTTP de todo el dominio editorial** y ejecutarlas en paralelo usando el patron de promises existente (`GuzzleHttp\Promise\Utils::settle()`).

### Fases de Ejecucion

Debido a dependencias entre datos, se requieren **3 fases secuenciales mínimas**:

#### Fase A: Fetch editorial principal (SYNC obligatorio)
```
Editorial principal → extraer IDs de insertadas/recomendadas + tags + signatures + multimedia
```

#### Fase B: Fetch editoriales hijas (ASYNC paralelo)
```
Lanzar en paralelo:
  - findEditorialById para cada insertada
  - findEditorialById para cada recomendada
Resolver → extraer SUS IDs de tags, signatures, multimedia, sections
```

#### Fase C: Fetch todas las dependencias (ASYNC paralelo, batch unico)
```
Recopilar y deduplicar TODOS los IDs por dominio:
  TagIds:        [tag1, tag2, tag3, tag5, tag8]        → deduplicados
  JournalistIds: [alias1, alias2, alias3]               → deduplicados
  SectionIds:    [sec1, sec2, sec3]                      → deduplicados
  MultimediaIds: [mm1, mm2, mm3, mm4]                   → deduplicados
  PhotoIds:      [photo1, photo2, photo3]                → deduplicados

Lanzar TODAS las promises en paralelo → Utils::settle() → distribuir resultados
```

### Mejora Esperada

```
ANTES:  ~42 HTTP calls secuenciales = ~4.2s
DESPUES: 3 fases (1 sync + 2 parallel settle) = ~300-400ms
Reduccion: ~90% en tiempo de I/O
```

## Specs Funcionales

### SP-01: Request Collector inyectable y reutilizable
- Crear un servicio `RequestCollector` que acumule IDs por bounded context
- Debe ser reutilizable por otros orquestadores (no solo editorial)
- Metodos: `addTagId()`, `addJournalistId()`, `addSectionId()`, `addMultimediaId()`, `addPhotoId()`
- Metodo `collectAll(): ResolvedData` que lanza todas las promises y devuelve resultados indexados por ID

### SP-02: Deduplicacion de IDs
- Un mismo tag/journalist/section puede aparecer en editorial principal + insertadas + recomendadas
- El collector debe deduplicar IDs antes de lanzar peticiones
- Evita HTTP calls redundantes

### SP-03: Tags para noticias insertadas
- Recuperar tags de cada noticia insertada
- Incluirlos en `$resolveData['insertedNews'][$id]['tags']`
- Transformar via el `DetailsAppsDataTransformer` existente

### SP-04: Tags para editoriales recomendados
- Recuperar tags de cada editorial recomendado
- Incluirlos en `$resolveData['recommendedEditorials'][$id]['tags']`

### SP-05: Journalists en batch async
- Actualmente `retrieveAliasFormat()` (L281-296) hace HTTP sync por cada firma
- Se llama en 3 lugares: insertadas (L139), recomendadas (L180), principal (L243)
- Mover a batch async: recopilar todos los aliasIds, lanzar en paralelo

### SP-06: Sections en batch async
- `findSectionById` se llama sync para insertadas (L134) y recomendadas (L175)
- Mover a batch async en Fase C

### SP-07: Photos de body tags en batch async
- `retrievePhotosFromBodyTags()` (L306-323) hace HTTP sync por cada foto
- `addPhotoToArray()` (L330-340) llama `findPhotoById` secuencialmente
- Mover a batch async en Fase C

### SP-08: Clients deben soportar flag async
- Verificar que `QueryTagClient`, `QueryJournalistClient`, `QuerySectionClient` soporten `$async` flag
- Si no lo soportan, extender o decorar para devolver Promise

### SP-09: Arquitectura multi-formato (Apps/Web)
- El mismo orquestador debe poder alimentar diferentes formatos de respuesta
- Actualmente solo existe `src/Application/DataTransformer/Apps/`
- Disenar una capa de transformacion que soporte `Apps/` y `Web/` (y futuros)
- El `RequestCollector` y la fase de fetching es comun; solo cambia la transformacion

### SP-10: Tolerancia a fallos
- Mantener el patron actual de `try/catch` + `continue` por elemento
- `Utils::settle()` ya maneja promises rejected individualmente
- Un tag que falle no debe romper toda la editorial

## Restricciones Tecnicas

- PHP 8.2+ con strict types
- Symfony 6.4
- HTTPLug con Guzzle7 async client (ya configurado en `config/packages/httplug.yaml`)
- `GuzzleHttp\Promise\Utils::settle()` como mecanismo de resolucion
- Mantener compatibilidad con API v1 actual
- PHPStan level 9
- MSI >= 79% en mutation testing
- PSR-12 + Symfony coding standards

## Relacion con Arquitectura Existente

Esta spec complementa la feature `snaapi-scalable-architecture` (Pipeline + DTO Factory):
- Los **Gateways** definidos en esa arquitectura (`EditorialGatewayInterface`, `TagGatewayInterface`, etc.) son el punto de integracion
- El `RequestCollector` puede actuar como un **Enricher** dentro del `EnrichmentPipeline`
- Los **DTO Factories** consumen los datos resueltos del collector
