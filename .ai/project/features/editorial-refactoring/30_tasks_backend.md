# Execution Phases: Editorial Refactoring

## Dependency Graph

```
SPEC-05 (Enums/VOs)
    │
    ▼
SPEC-03 (MultimediaShotService) ──────────┐
    │                                      │
    ▼                                      │
SPEC-02 (UrlGeneratorTrait)               │
    │                                      │
    ▼                                      ▼
SPEC-04 (DTOs) ◄───────────────── SPEC-07 (MembershipLinkResolver)
    │                                      │
    ▼                                      ▼
SPEC-01 (Orchestrator decomposition) ◄─── SPEC-06 (Async strategy)
```

## Phase 1: Foundation Layer (No Breaking Changes)

**Goal**: Create value objects, enums, and services that can coexist with existing code.

### Task 1.1: Create AspectRatio Enum (SPEC-05)
- **File**: `src/Infrastructure/Enum/AspectRatioEnum.php`
- **Test**: `tests/Infrastructure/Enum/AspectRatioEnumTest.php`
- **Risk**: None - new file, no existing code changes
- **Verify**: `make test_unit && make test_stan`

### Task 1.2: Create ImageSize & ImageSizeCollection Value Objects (SPEC-05)
- **Files**:
  - `src/Infrastructure/ValueObject/ImageSize.php`
  - `src/Infrastructure/ValueObject/ImageSizeCollection.php`
- **Tests**:
  - `tests/Infrastructure/ValueObject/ImageSizeTest.php`
  - `tests/Infrastructure/ValueObject/ImageSizeCollectionTest.php`
- **Risk**: None - new files
- **Verify**: `make test_unit && make test_stan`

### Task 1.3: Create MultimediaShotService (SPEC-03)
- **File**: `src/Infrastructure/Service/MultimediaShotService.php`
- **Test**: `tests/Infrastructure/Service/MultimediaShotServiceTest.php`
- **Dependencies**: Task 1.1, Task 1.2
- **Risk**: None - new file, coexists with `MultimediaTrait`
- **Verify**: `make test_unit && make test_stan`

### Phase 1 Checkpoint
- [ ] All new files created and tested
- [ ] No existing files modified yet
- [ ] `make tests` passes (full suite)
- [ ] New code at 100% mutation coverage

---

## Phase 2: Shared Infrastructure (Backward-Compatible Refactoring)

**Goal**: Migrate existing code to use new services while maintaining exact same behavior.

### Task 2.1: Improve UrlGeneratorTrait (SPEC-02)
- **File**: `src/Infrastructure/Trait/UrlGeneratorTrait.php` (modify)
- **Changes**: Add `editorialUrl()`, `sectionUrl()` to the trait
- **Risk**: LOW - extending a trait, not breaking existing usage
- **Verify**: `make test_unit`

### Task 2.2: Remove Duplicate editorialUrl() from Transformers (SPEC-02)
- **Files** (modify):
  - `src/Application/DataTransformer/Apps/DetailsAppsDataTransformer.php`
  - `src/Application/DataTransformer/Apps/Body/BodyTagInsertedNewsDataTransformer.php`
  - `src/Application/DataTransformer/Apps/RecommendedEditorialsDataTransformer.php`
- **Dependencies**: Task 2.1
- **Risk**: MEDIUM - modifying 3 files, must verify output identity
- **Verify**: `make test_unit` (existing tests must pass unchanged)

### Task 2.3: Migrate MultimediaTrait Users to MultimediaShotService (SPEC-03)
- **Files** (modify):
  - `src/Application/DataTransformer/Apps/RecommendedEditorialsDataTransformer.php`
  - `src/Application/DataTransformer/Apps/Body/BodyTagInsertedNewsDataTransformer.php`
  - `src/Application/DataTransformer/Apps/Body/BodyTagPictureDataTransformer.php`
  - `src/Application/DataTransformer/Apps/Media/DataTransformers/DetailsMultimediaPhotoDataTransformer.php`
  - `src/Application/DataTransformer/Apps/JournalistsDataTransformer.php`
- **Dependencies**: Task 1.3
- **Risk**: MEDIUM - modifying 5 files, must verify Thumbor URLs identical
- **Verify**: `make test_unit` (all existing tests)

### Task 2.4: Remove MultimediaTrait (SPEC-03)
- **File**: Delete `src/Infrastructure/Trait/MultimediaTrait.php`
- **Dependencies**: Task 2.3 (all consumers migrated)
- **Risk**: LOW if Task 2.3 fully complete
- **Verify**: `make test_unit && make test_stan`

### Phase 2 Checkpoint
- [ ] `UrlGeneratorTrait` improved with `editorialUrl()`
- [ ] 3 duplicate `editorialUrl()` methods removed
- [ ] `MultimediaTrait` removed, all consumers use `MultimediaShotService`
- [ ] `make tests` passes (full suite)
- [ ] Existing test assertions unchanged

---

## Phase 3: DTOs (Internal Type Safety)

**Goal**: Introduce DTOs. Transformers produce DTOs internally, `toArray()` at boundaries.

### Task 3.1: Create Core DTOs (SPEC-04)
- **Files**:
  - `src/Application/DTO/EditorialResponse.php`
  - `src/Application/DTO/EditorialTitlesDto.php`
  - `src/Application/DTO/EditorialTypeDto.php`
  - `src/Application/DTO/SectionDto.php`
  - `src/Application/DTO/TagDto.php`
  - `src/Application/DTO/SignatureDto.php`
  - `src/Application/DTO/DepartmentDto.php`
  - `src/Application/DTO/MultimediaResponseDto.php`
  - `src/Application/DTO/RecommendedEditorialDto.php`
- **Tests**: One test per DTO verifying `toArray()` output
- **Risk**: None - new files
- **Verify**: `make test_unit && make test_stan`

### Task 3.2: Create Resolver Data DTOs (SPEC-04 + SPEC-01)
- **Files**:
  - `src/Application/DTO/InsertedNewsData.php`
  - `src/Application/DTO/RecommendedEditorialData.php`
  - `src/Application/DTO/RelatedEditorialData.php`
  - `src/Orchestrator/Chain/Resolver/MembershipLinkPromise.php`
- **Risk**: None - new files
- **Verify**: `make test_unit && make test_stan`

### Task 3.3: Migrate Transformers to Return DTOs (SPEC-04)
- **Files** (modify): All transformer `read()` methods
- **Strategy**: Return DTO objects, but the **caller** calls `toArray()` where needed
- **Dependencies**: Task 3.1
- **Risk**: HIGH - touching many files, need careful testing
- **Verify**: `make test_unit` (must verify JSON output unchanged)

### Phase 3 Checkpoint
- [ ] All DTOs created with `toArray()`
- [ ] Transformers return DTOs
- [ ] `@phpstan-ignore` annotations reduced by ≥ 80%
- [ ] `make tests` passes (full suite)
- [ ] JSON output byte-identical to before

---

## Phase 4: Service Extraction (Orchestrator Decomposition)

**Goal**: Extract resolver services from the orchestrator.

### Task 4.1: Create SignatureResolver (SPEC-01)
- **File**: `src/Orchestrator/Chain/Resolver/SignatureResolver.php`
- **Test**: `tests/Orchestrator/Chain/Resolver/SignatureResolverTest.php`
- **Risk**: None - new file
- **Verify**: `make test_unit && make test_stan`

### Task 4.2: Create MembershipLinkResolver (SPEC-07)
- **File**: `src/Orchestrator/Chain/Resolver/MembershipLinkResolver.php`
- **Test**: `tests/Orchestrator/Chain/Resolver/MembershipLinkResolverTest.php`
- **Dependencies**: Task 3.2 (MembershipLinkPromise DTO)
- **Risk**: None - new file
- **Verify**: `make test_unit && make test_stan`

### Task 4.3: Create MultimediaResolver (SPEC-01)
- **File**: `src/Orchestrator/Chain/Resolver/MultimediaResolver.php`
- **Test**: `tests/Orchestrator/Chain/Resolver/MultimediaResolverTest.php`
- **Risk**: None - new file
- **Verify**: `make test_unit && make test_stan`

### Task 4.4: Create RelatedEditorialTrait (SPEC-01)
- **File**: `src/Orchestrator/Chain/Resolver/Trait/RelatedEditorialTrait.php`
- **Test**: Via InsertedNewsResolver and RecommendedEditorialsResolver tests
- **Risk**: None - new file
- **Verify**: `make test_unit && make test_stan`

### Task 4.5: Create InsertedNewsResolver (SPEC-01)
- **File**: `src/Orchestrator/Chain/Resolver/InsertedNewsResolver.php`
- **Test**: `tests/Orchestrator/Chain/Resolver/InsertedNewsResolverTest.php`
- **Dependencies**: Task 4.1, Task 4.3, Task 4.4
- **Risk**: None - new file
- **Verify**: `make test_unit && make test_stan`

### Task 4.6: Create RecommendedEditorialsResolver (SPEC-01)
- **File**: `src/Orchestrator/Chain/Resolver/RecommendedEditorialsResolver.php`
- **Test**: `tests/Orchestrator/Chain/Resolver/RecommendedEditorialsResolverTest.php`
- **Dependencies**: Task 4.1, Task 4.3, Task 4.4
- **Risk**: None - new file
- **Verify**: `make test_unit && make test_stan`

### Task 4.7: Refactor EditorialOrchestrator (SPEC-01 - THE BIG ONE)
- **File**: `src/Orchestrator/Chain/EditorialOrchestrator.php` (major rewrite)
- **Dependencies**: Tasks 4.1-4.6 (all resolvers ready)
- **Risk**: HIGH - core file rewrite, must maintain exact behavior
- **Strategy**:
  1. Inject new resolvers via constructor
  2. Replace inline code with resolver calls one method at a time
  3. Run tests after each replacement
  4. Once all replaced, remove dead code
- **Verify**: `make test_unit` after each step, `make tests` after completion

### Phase 4 Checkpoint
- [ ] 5 resolver services created + tested
- [ ] `RelatedEditorialTrait` shared between 2 resolvers
- [ ] `EditorialOrchestrator` ≤ 150 lines
- [ ] `make tests` passes (full suite)
- [ ] No `@phpstan-ignore` in new code

---

## Phase 5: Async Optimization (SPEC-06)

**Goal**: Maximize parallelization and add Messenger integration.

### Task 5.1: Add Promise Batching to InsertedNewsResolver
- Batch all editorial fetches into parallel promises
- Batch all section fetches for visible editorials
- **Risk**: MEDIUM - depends on external client async support (see SPEC-06 Q3)

### Task 5.2: Add Promise Batching to RecommendedEditorialsResolver
- Same pattern as Task 5.1
- **Risk**: MEDIUM

### Task 5.3: Add Promise Batching for Tags
- Fetch all tags in parallel instead of sequential loop
- **Risk**: LOW

### Task 5.4: Create Symfony Messenger Messages
- `WarmRelatedEditorialsCache` message + handler
- **Risk**: LOW - new files, non-blocking
- **Verify**: `make test_unit && make test_stan`

### Phase 5 Checkpoint
- [ ] All independent external calls parallelized
- [ ] Messenger integration for cache warming
- [ ] `make tests` passes
- [ ] Performance improvement measurable (fewer sequential HTTP calls)

---

## Phase 6: Cleanup & Verification

### Task 6.1: Remove Dead Code
- Any unused methods, imports, or constants
- Remove `use MultimediaTrait` from all files (already done in Phase 2)

### Task 6.2: Full Test Suite
- `make tests` (all quality gates)
- Verify 79% MSI threshold

### Task 6.3: Documentation Update
- Update `CLAUDE.md` architecture section if needed

---

## Risk Assessment

| Phase | Risk Level | Mitigation |
|-------|-----------|------------|
| Phase 1 | LOW | New files only, no existing code changes |
| Phase 2 | MEDIUM | Modify existing files, but one at a time with test verification |
| Phase 3 | MEDIUM-HIGH | Many DTO files + transformer migrations, need careful `toArray()` testing |
| Phase 4 | HIGH | Core orchestrator rewrite - incremental replacement with test per step |
| Phase 5 | MEDIUM | Async changes - depends on external client capabilities |
| Phase 6 | LOW | Cleanup only |

## Estimated New Files

| Category | Count | Examples |
|----------|-------|---------|
| Enums | 1 | AspectRatioEnum |
| Value Objects | 2 | ImageSize, ImageSizeCollection |
| Services | 2 | MultimediaShotService, MembershipLinkResolver |
| Resolvers | 4 | Signature, Multimedia, InsertedNews, RecommendedEditorials |
| Traits | 1 | RelatedEditorialTrait |
| DTOs | 11 | EditorialResponse, SignatureDto, etc. |
| Tests | ~15 | One per new class |
| Messenger | 2 | Message + Handler |
| **Total** | **~38** | New files |

## Files Modified

| File | Phase | Change Type |
|------|-------|-------------|
| `EditorialOrchestrator.php` | 4 | Major rewrite (590 → ~150 lines) |
| `UrlGeneratorTrait.php` | 2 | Add methods |
| `DetailsAppsDataTransformer.php` | 2, 3 | Remove duplicate URL, return DTO |
| `BodyTagInsertedNewsDataTransformer.php` | 2, 3 | Remove duplicate URL + MultimediaTrait, return DTO |
| `RecommendedEditorialsDataTransformer.php` | 2, 3 | Remove duplicate URL + MultimediaTrait, return DTO |
| `BodyTagPictureDataTransformer.php` | 2, 3 | Remove MultimediaTrait, return DTO |
| `DetailsMultimediaPhotoDataTransformer.php` | 2, 3 | Remove SIZES_RELATIONS + MultimediaTrait |
| `JournalistsDataTransformer.php` | 2, 3 | Remove thumbor direct usage, return DTO |
| `BodyTagMembershipCardDataTransformer.php` | 3 | Return DTO |
| `MultimediaTrait.php` | 2 | **DELETED** |

## Files Deleted

| File | Phase | Reason |
|------|-------|--------|
| `src/Infrastructure/Trait/MultimediaTrait.php` | 2 | Replaced by MultimediaShotService |
