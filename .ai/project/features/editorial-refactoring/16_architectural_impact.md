# Architectural Impact: Editorial Refactoring

## Summary

- Layers affected: Infrastructure, Application, Orchestrator
- Modules touched: 4 existing, 3 new directories
- Change scope: ~38 files to create, ~10 files to modify, 1 file to delete
- Complexity: HIGH (core orchestrator rewrite)

## Layer Analysis

| Layer | Impact Level | Changes Required |
|-------|--------------|------------------|
| **Infrastructure** | MEDIUM | 1 new service (MultimediaShotService), 1 new enum, 2 new VOs, 1 trait deleted |
| **Application** | HIGH | ~13 new DTOs, ~8 transformers modified to return DTOs |
| **Orchestrator** | HIGH | 5 new resolvers, 1 major rewrite (EditorialOrchestrator), 1 new config |
| **Controller** | NONE | No changes needed |
| **DependencyInjection** | LOW | No compiler pass changes (resolvers registered via YAML) |

## Affected Layers Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ CONTROLLER                                                       │
│   [NO CHANGE] EditorialController.php                            │
│   (still calls OrchestratorChain.handler(), return unchanged)    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ ORCHESTRATOR                                                     │
│   [MAJOR REWRITE] EditorialOrchestrator.php (590→~150 lines)    │
│   [NEW] Resolver/SignatureResolver.php                           │
│   [NEW] Resolver/MembershipLinkResolver.php                      │
│   [NEW] Resolver/MultimediaResolver.php                          │
│   [NEW] Resolver/InsertedNewsResolver.php                        │
│   [NEW] Resolver/RecommendedEditorialsResolver.php               │
│   [NEW] Resolver/Trait/RelatedEditorialTrait.php                 │
│   [NEW] Resolver/MembershipLinkPromise.php (Value Object)        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ APPLICATION - DataTransformers                                   │
│   [MODIFIED] DetailsAppsDataTransformer.php (remove dup URL)     │
│   [MODIFIED] JournalistsDataTransformer.php (inject service)     │
│   [MODIFIED] RecommendedEditorialsDataTransformer.php            │
│   [MODIFIED] StandfirstDataTransformer.php                       │
│   [MODIFIED] Body/BodyTagInsertedNewsDataTransformer.php         │
│   [MODIFIED] Body/BodyTagPictureDataTransformer.php              │
│   [MODIFIED] Body/BodyTagMembershipCardDataTransformer.php       │
│   [MODIFIED] Media/DetailsMultimediaPhotoDataTransformer.php     │
│                                                                   │
│ APPLICATION - DTOs (ALL NEW)                                     │
│   [NEW] DTO/EditorialResponse.php                                │
│   [NEW] DTO/EditorialTitlesDto.php                               │
│   [NEW] DTO/EditorialTypeDto.php                                 │
│   [NEW] DTO/SectionDto.php                                       │
│   [NEW] DTO/TagDto.php                                           │
│   [NEW] DTO/SignatureDto.php                                     │
│   [NEW] DTO/DepartmentDto.php                                    │
│   [NEW] DTO/MultimediaResponseDto.php                            │
│   [NEW] DTO/RecommendedEditorialDto.php                          │
│   [NEW] DTO/BodyElementDto.php                                   │
│   [NEW] DTO/InsertedNewsData.php                                 │
│   [NEW] DTO/RecommendedEditorialData.php                         │
│   [NEW] DTO/RelatedEditorialData.php                             │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ INFRASTRUCTURE                                                   │
│   [NEW] Service/MultimediaShotService.php                        │
│   [NEW] Enum/AspectRatioEnum.php                                 │
│   [NEW] ValueObject/ImageSize.php                                │
│   [NEW] ValueObject/ImageSizeCollection.php                      │
│   [DELETED] Trait/MultimediaTrait.php                             │
│   [MODIFIED] Trait/UrlGeneratorTrait.php (add editorialUrl)      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ CONFIG                                                           │
│   [MODIFIED] config/packages/orchestrators.yaml (add resolvers)  │
└─────────────────────────────────────────────────────────────────┘
```

## Existing Modules Touched

| Module | Files Touched | Risk Level | Notes |
|--------|---------------|------------|-------|
| `src/Orchestrator/Chain/` | 1 file (major rewrite) | **HIGH** | Core editorial flow |
| `src/Application/DataTransformer/Apps/` | 4 files modified | MEDIUM | Remove trait usage, return DTOs |
| `src/Application/DataTransformer/Apps/Body/` | 3 files modified | MEDIUM | Remove trait usage, return DTOs |
| `src/Application/DataTransformer/Apps/Media/` | 1 file modified | MEDIUM | Remove SIZES_RELATIONS constant |
| `src/Infrastructure/Trait/` | 1 modified, 1 deleted | LOW | UrlGeneratorTrait improved, MultimediaTrait deleted |
| `config/packages/` | 1 file modified | LOW | Add resolver service registration |

## New Directories Created

| Directory | Contents | Count |
|-----------|----------|-------|
| `src/Orchestrator/Chain/Resolver/` | Resolver services | 5 files |
| `src/Orchestrator/Chain/Resolver/Trait/` | Shared trait | 1 file |
| `src/Application/DTO/` | Response DTOs | 13 files |
| `src/Infrastructure/ValueObject/` | ImageSize, ImageSizeCollection | 2 files |

## Change Scope

```
╔══════════════════════════════════════════════════════════════════╗
║                    CHANGE SCOPE SUMMARY                          ║
╠══════════════════════════════════════════════════════════════════╣
║  Files to CREATE:    ~38                                         ║
║    - Resolvers:        6 (5 services + 1 trait)                  ║
║    - DTOs:            13                                         ║
║    - Services:         1 (MultimediaShotService)                 ║
║    - Enums/VOs:        3 (AspectRatioEnum, ImageSize, Collection)║
║    - Messenger:        2 (message + handler)                     ║
║    - Tests:          ~15                                         ║
║  ────────────────────────────────────────────────                ║
║  Files to MODIFY:    ~11                                         ║
║    - Orchestrator:     1 (EditorialOrchestrator - major)         ║
║    - Transformers:     8 (trait removal + DTO returns)           ║
║    - Traits:           1 (UrlGeneratorTrait - add methods)       ║
║    - Config:           1 (orchestrators.yaml)                    ║
║  ────────────────────────────────────────────────                ║
║  Files to DELETE:      1                                         ║
║    - MultimediaTrait.php                                         ║
║  ────────────────────────────────────────────────                ║
║  Total files affected: ~50                                       ║
║                                                                  ║
║  Estimated LOC added:   ~2,500                                   ║
║  Estimated LOC removed: ~600 (MultimediaTrait + orchestrator)    ║
║  Net LOC delta:         +1,900 (more files, but each smaller)    ║
║  ────────────────────────────────────────────────                ║
║  Complexity: HIGH                                                ║
║  Phases: 6 (incremental)                                         ║
╚══════════════════════════════════════════════════════════════════╝
```

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| EditorialOrchestrator rewrite breaks response | MEDIUM | **HIGH** | Phase 4 done incrementally; run tests after each method extraction |
| MultimediaTrait removal breaks Thumbor URLs | LOW | HIGH | Phase 2 validates identical URLs via existing tests |
| DTO `toArray()` doesn't match current JSON | LOW | **HIGH** | Snapshot testing: capture current response, compare after migration |
| Service registration missing in YAML | LOW | MEDIUM | `make test_container` catches unregistered services |
| External client async calls behave differently | LOW | MEDIUM | Promise error handling with fallback to sync in each resolver |
| Promise batching causes memory issues | LOW | LOW | Batch size limited per resolver (max 20 promises) |
| New tests don't cover edge cases | MEDIUM | MEDIUM | Mutation testing (79% MSI threshold) catches gaps |

## Test Impact

### New Tests Required

| Test File | Tests | Coverage |
|-----------|-------|----------|
| `tests/Orchestrator/Chain/Resolver/SignatureResolverTest.php` | 5-8 | Alias resolution, error handling, empty signatures |
| `tests/Orchestrator/Chain/Resolver/MembershipLinkResolverTest.php` | 4-6 | Promise creation, resolution, empty links |
| `tests/Orchestrator/Chain/Resolver/MultimediaResolverTest.php` | 6-10 | Async fetch, opening, body tags, meta image |
| `tests/Orchestrator/Chain/Resolver/InsertedNewsResolverTest.php` | 5-8 | Visible/invisible editorial, error handling |
| `tests/Orchestrator/Chain/Resolver/RecommendedEditorialsResolverTest.php` | 5-8 | Same pattern as inserted news |
| `tests/Infrastructure/Service/MultimediaShotServiceTest.php` | 6-10 | Landscape, responsive, journalist photo |
| `tests/Infrastructure/Enum/AspectRatioEnumTest.php` | 3-5 | Cases, clipping type mapping |
| `tests/Infrastructure/ValueObject/ImageSizeTest.php` | 3-5 | Construction, immutability |
| `tests/Infrastructure/ValueObject/ImageSizeCollectionTest.php` | 5-8 | Factory methods, per-ratio |
| `tests/Application/DTO/*Test.php` | ~20 | `toArray()` output for each DTO |
| **Total new tests** | **~60-90** | |

### Existing Tests Modified

| Test File | Change | Reason |
|-----------|--------|--------|
| `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` | Mock new resolvers | Constructor signature changes |
| `tests/Application/DataTransformer/Apps/RecommendedEditorialsDataTransformerTest.php` | Inject MultimediaShotService mock | Trait removed |

## Phase-by-Phase Risk Profile

```
Phase 1: ██░░░░░░░░ LOW    (new files only)
Phase 2: ████░░░░░░ MEDIUM (modify 5-6 files, remove trait)
Phase 3: ██████░░░░ MEDIUM-HIGH (many DTOs + transformer changes)
Phase 4: ████████░░ HIGH   (orchestrator rewrite)
Phase 5: ████░░░░░░ MEDIUM (async changes)
Phase 6: ██░░░░░░░░ LOW    (cleanup only)
```

Each phase is independently deployable. If Phase 4 introduces issues, Phases 1-3 can be deployed alone as improvements.
