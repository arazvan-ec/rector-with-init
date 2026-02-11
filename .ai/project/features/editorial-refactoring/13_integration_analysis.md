# Integration Analysis: Editorial Refactoring

## Summary

- Entities: 0 extended, 0 modified, 11 new (all DTOs/VOs)
- Endpoints: 0 extended, 0 modified, 0 new
- Business Rules: 0 conflicts, 0 new
- Services: 0 extended, 10 modified, 8 new
- Status: **CLEAR** - No conflicts detected

## Entities Impact (Domain Layer)

### Extended
*None - External bundle domain models are NOT modified.*

### Modified
*None - All domain models come from external bundles (`Ec\Editorial`, `Ec\Multimedia`, etc.) and are read-only for this project.*

### New

| Entity (DTO/VO) | Purpose | Layer |
|------------------|---------|-------|
| `AspectRatioEnum` | Enum for 5 aspect ratios (16:9, 4:3, 3:2, 2:3, 3:4) | Infrastructure |
| `ImageSize` | Value object for width/height/label | Infrastructure |
| `ImageSizeCollection` | Collection of ImageSize with factory methods | Infrastructure |
| `EditorialResponse` | Response DTO for editorial detail | Application |
| `SignatureDto` | DTO for journalist signatures | Application |
| `MultimediaResponseDto` | DTO for multimedia response | Application |
| `RecommendedEditorialDto` | DTO for recommended editorials | Application |
| `SectionDto` | DTO for section data | Application |
| `TagDto` | DTO for tag data | Application |
| `BodyElementDto` | Generic DTO for body elements (single class, type discriminator) | Application |
| `MembershipLinkPromise` | Value object wrapping async promise + original links | Orchestrator |

## API Contracts Impact

### Extended
*None.*

### Modified
*None - API response structure is 100% backward compatible. DTOs produce identical JSON via `toArray()`.*

### New
*None - No new endpoints created.*

## Business Rules Impact

### Conflicts
*None detected.*

### New
*None - This is a pure refactoring with no behavioral changes.*

## Service Registration Impact

### Current Service Configuration

The project uses Symfony DI with compiler passes for dynamic registration:

| Config File | Pattern | Impact |
|-------------|---------|--------|
| `config/packages/orchestrators.yaml` | Tags `app.orchestrators` for orchestrator chain | New resolvers need registration |
| `config/packages/data_transformer.yaml` | Tags `app.data_transformer` for body transformers | No changes needed |
| `config/services.yaml` | Autowire + autoconfigure with exclusions | New services autowired automatically |

### New Services That Need Registration

| Service | Registration Method | Notes |
|---------|-------------------|-------|
| `MultimediaShotService` | Auto (autowire) | In `src/Infrastructure/Service/`, not excluded |
| `SignatureResolver` | Manual (YAML) | In `src/Orchestrator/Chain/Resolver/`, currently excluded by `src/Orchestrator` |
| `MembershipLinkResolver` | Manual (YAML) | Same - needs explicit registration |
| `MultimediaResolver` | Manual (YAML) | Same |
| `InsertedNewsResolver` | Manual (YAML) | Same |
| `RecommendedEditorialsResolver` | Manual (YAML) | Same |

**Key finding**: `config/services.yaml` excludes `../src/Orchestrator` from auto-discovery. The new resolver services in `src/Orchestrator/Chain/Resolver/` will need explicit YAML registration OR we add a new resource path.

### Recommended Configuration Addition

```yaml
# config/packages/orchestrators.yaml (addition)
App\Orchestrator\Chain\Resolver\:
    resource: '../../src/Orchestrator/Chain/Resolver/'
    exclude:
        - '../../src/Orchestrator/Chain/Resolver/Trait/'
```

## Compiler Pass Impact

| Compiler Pass | Impact | Action Needed |
|---------------|--------|---------------|
| `EditorialOrchestratorCompiler` | No change | Resolvers are NOT orchestrators, no tag needed |
| `BodyDataTransformerCompiler` | No change | Body transformers unchanged |
| `MediaDataTransformerCompiler` | No change | Media transformers unchanged |
| `MultimediaOrchestratorCompiler` | No change | Multimedia orchestrators unchanged |

## Dependency Chain Impact

### Constructor Injection Changes

| Class | Current Dependencies | After Refactoring |
|-------|---------------------|-------------------|
| `EditorialOrchestrator` | 18 constructor params | ~8 params (resolvers + transformers) |
| `BodyTagInsertedNewsDataTransformer` | Thumbor, extension | MultimediaShotService, extension |
| `RecommendedEditorialsDataTransformer` | extension, Thumbor | extension, MultimediaShotService |
| `DetailsMultimediaPhotoDataTransformer` | Thumbor | MultimediaShotService |
| `JournalistsDataTransformer` | extension, Thumbor | extension, MultimediaShotService |
| `BodyTagPictureDataTransformer` | Thumbor | MultimediaShotService |

### Trait Removal Impact

| Trait | Current Users | Migration |
|-------|---------------|-----------|
| `MultimediaTrait` | EditorialOrchestrator, RecommendedEditorialsDataTransformer, BodyTagInsertedNewsDataTransformer, BodyTagPictureDataTransformer, DetailsMultimediaPhotoDataTransformer | All migrate to `MultimediaShotService` injection |
| `UrlGeneratorTrait` | 5+ classes | **Kept** but improved with `editorialUrl()` method |

## Compatibility Assessment

- **Backward Compatible**: YES - JSON output identical
- **Migration Required**: NO - Incremental refactoring, each phase is independently deployable
- **Breaking Changes**: NONE for API consumers
- **Internal Breaking Changes**: YES - Trait removal and service injection changes affect internal class APIs. Covered by existing tests.
