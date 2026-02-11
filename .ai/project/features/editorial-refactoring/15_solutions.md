# Solutions: Editorial Refactoring (SOLID-Compliant Design)

## SOLID Baseline

### Current State Analysis

| Principle | Current Score | Evidence |
|-----------|-------------|----------|
| **S** - SRP | 2/5 | `EditorialOrchestrator` has 10+ responsibilities; MultimediaTrait mixes data access + image processing |
| **O** - OCP | 4/5 | Compiler passes + service tags enable extension. Good pattern. |
| **L** - LSP | 4/5 | All orchestrators implement same interface correctly. |
| **I** - ISP | 3/5 | Some interfaces are focused (`MediaDataTransformer`), others too broad (`AppsDataTransformer` couples write/read). |
| **D** - DIP | 3/5 | Good: interfaces for orchestrators/transformers. Bad: traits create hidden coupling, `Thumbor` injected directly instead of through abstraction. |
| **Current Total** | **16/25** | Grade C - Needs improvement |

### Target State

| Principle | Target Score | How |
|-----------|-------------|-----|
| **S** - SRP | 5/5 | Each resolver has one responsibility; services focused |
| **O** - OCP | 5/5 | New resolvers extend without modifying orchestrator |
| **L** - LSP | 4/5 | Maintained via consistent interfaces |
| **I** - ISP | 4/5 | Small, focused interfaces per resolver |
| **D** - DIP | 5/5 | Traits replaced by injectable services; DTOs decouple layers |
| **Target Total** | **23/25** | Grade A - SOLID Compliant |

---

## Solution for SPEC-01: Decompose EditorialOrchestrator

**Approach**: Extract 5 focused resolver services using Extract Class refactoring. Orchestrator becomes a coordinator.

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Each resolver handles one concern (signatures, membership, multimedia, inserted news, recommended) | Extract Class |
| **O** - OCP | New resolvers can be added without modifying orchestrator | Strategy via DI |
| **L** - LSP | N/A - resolvers are not substitutable | - |
| **I** - ISP | Each resolver has its own focused interface/contract | Interface Segregation |
| **D** - DIP | Orchestrator depends on resolver abstractions, not concrete implementations | Dependency Injection |

**Files to Create**:

```
src/Orchestrator/Chain/Resolver/
├── SignatureResolver.php              (SRP: journalist alias resolution only)
├── MembershipLinkResolver.php         (SRP: membership URL promise handling only)
├── MultimediaResolver.php             (SRP: async multimedia fetch + body tag photos)
├── InsertedNewsResolver.php           (SRP: inserted news enrichment)
├── RecommendedEditorialsResolver.php  (SRP: recommended editorials enrichment)
└── Trait/
    └── RelatedEditorialTrait.php      (DRY: shared fetch+validate+enrich pattern)
```

**Expected SOLID Score**: 23/25

### Design Details

#### SignatureResolver

```php
final readonly class SignatureResolver
{
    // DIP: depends on interface (QueryJournalistClient), not implementation
    public function __construct(
        private QueryJournalistClient $queryJournalistClient,
        private JournalistFactory $journalistFactory,
        private JournalistsDataTransformer $journalistsDataTransformer,
        private LoggerInterface $logger,
    ) {}

    // SRP: only resolves signatures, nothing else
    /** @return SignatureDto[] */
    public function resolve(Signatures $signatures, Section $section, bool $hasTwitter = false): array;

    public function resolveAlias(string $aliasId, Section $section, bool $hasTwitter = false): SignatureDto;
}
```

#### InsertedNewsResolver (uses RelatedEditorialTrait)

```php
final readonly class InsertedNewsResolver
{
    use RelatedEditorialTrait;

    public function __construct(
        private QueryEditorialClient $queryEditorialClient,
        private QuerySectionClient $querySectionClient,
        private SignatureResolver $signatureResolver,     // DIP: depends on resolver
        private MultimediaResolver $multimediaResolver,   // DIP: depends on resolver
        private LoggerInterface $logger,
    ) {}

    // SRP: only resolves inserted news
    /** @return array<string, InsertedNewsData> */
    public function resolve(Body $body): array;
}
```

#### RelatedEditorialTrait (DRY shared logic)

```php
trait RelatedEditorialTrait
{
    // Shared between InsertedNewsResolver and RecommendedEditorialsResolver
    // Pattern: fetch editorial → check visibility → resolve signatures → resolve multimedia
    private function resolveRelatedEditorial(string $editorialId): ?RelatedEditorialData
    {
        $editorial = $this->queryEditorialClient->findEditorialById($editorialId);
        if (!$editorial->isVisible()) return null;

        $section = $this->querySectionClient->findSectionById($editorial->sectionId());
        $signatures = $this->signatureResolver->resolve($editorial->signatures(), $section);
        $multimediaId = $this->multimediaResolver->resolveEditorialMultimediaId($editorial);

        return new RelatedEditorialData($editorial, $section, $signatures, $multimediaId);
    }
}
```

---

## Solution for SPEC-02: Improve UrlGeneratorTrait

**Approach**: Add domain URL methods to existing trait (decision: simple logic stays as trait).

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Trait focuses only on URL generation | Template Method |
| **O** - OCP | New URL methods added without modifying existing | Extension |
| **D** - DIP | N/A - traits don't participate in DI | - |

**Changes**:

```php
// UrlGeneratorTrait - IMPROVED (add 2 new protected methods)
trait UrlGeneratorTrait
{
    // ... existing generateUrl() ...

    protected function editorialUrl(Editorial $editorial, Section $section): string
    {
        // Extracted from 3 duplicate implementations
    }

    protected function sectionUrl(Section $section): string
    {
        // Extracted from DetailsAppsDataTransformer::transformerSection()
    }
}
```

**Files Modified**: 3 (remove private `editorialUrl()` from each)

**Expected SOLID Score**: N/A (trait improvement, not a standalone class)

---

## Solution for SPEC-03: MultimediaShotService

**Approach**: Extract `MultimediaTrait` into an injectable service (decision: complex logic → service).

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Service handles only Thumbor crop generation | Extract Class |
| **O** - OCP | New shot generation methods can be added without modifying existing | Extension |
| **D** - DIP | All consumers inject the service, not use a trait | Dependency Injection |

**Files to Create**:

```
src/Infrastructure/Service/MultimediaShotService.php   (SRP: Thumbor shot generation)
```

**Key Methods**:

```php
final readonly class MultimediaShotService
{
    public function __construct(private Thumbor $thumbor) {}

    // Replaces MultimediaTrait::getShotsLandscape()
    public function generateLandscapeShots(string $file, Clippings $clippings): array;

    // Replaces MultimediaTrait::getShotsLandscapeFromMedia()
    public function generateLandscapeShotsFromMedia(array $multimediaOpening): array;

    // Replaces DetailsMultimediaPhotoDataTransformer inline loop
    public function generateResponsiveShots(string $file, Clippings $clippings): array;

    // Replaces JournalistsDataTransformer::photoUrl()
    public function generateJournalistPhoto(Journalist $journalist): string;
}
```

**Expected SOLID Score**: 24/25 (focused service, single responsibility, injected via DI)

---

## Solution for SPEC-04: Response DTOs

**Approach**: Immutable DTOs with `toArray()` for backward-compatible JSON serialization.

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Each DTO represents one response concept | Value Object |
| **O** - OCP | New DTOs can be added for new response types | Extension |
| **L** - LSP | N/A - DTOs are final readonly, no inheritance | - |
| **I** - ISP | Each DTO has only the fields it needs | Minimal Interface |
| **D** - DIP | Transformers return DTOs (abstractions), not arrays | Inversion |

**Files to Create**:

```
src/Application/DTO/
├── EditorialResponse.php           (aggregate response)
├── EditorialTitlesDto.php
├── EditorialTypeDto.php
├── SectionDto.php
├── TagDto.php
├── SignatureDto.php
├── DepartmentDto.php
├── MultimediaResponseDto.php
├── RecommendedEditorialDto.php
├── BodyElementDto.php              (single class, type discriminator)
├── InsertedNewsData.php            (internal resolver data)
├── RecommendedEditorialData.php    (internal resolver data)
└── RelatedEditorialData.php        (shared base for resolver data)
```

**Backward Compatibility Strategy**:

```php
final readonly class SignatureDto
{
    public function __construct(
        public string $journalistId,
        public string $aliasId,
        public string $name,
        public bool $private,
        public string $url,
        public string $photo,
        /** @var DepartmentDto[] */
        public array $departments,
        public ?string $twitter = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $result = [
            'journalistId' => $this->journalistId,
            'aliasId' => $this->aliasId,
            'name' => $this->name,
            'private' => $this->private,
            'url' => $this->url,
            'photo' => $this->photo,
            'departments' => array_map(fn(DepartmentDto $d) => $d->toArray(), $this->departments),
        ];

        if ($this->twitter !== null) {
            $result['twitter'] = $this->twitter;
        }

        return $result;
    }
}
```

**Expected SOLID Score**: 24/25

---

## Solution for SPEC-05: AspectRatio Enum & ImageSize

**Approach**: PHP 8.1+ enums for finite sets, value objects for composite data.

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Enum holds only ratio cases + clipping mapping | Value Object |
| **O** - OCP | New ratios added as enum cases | Enum Extension |

**Files to Create**:

```
src/Infrastructure/Enum/AspectRatioEnum.php
src/Infrastructure/ValueObject/ImageSize.php
src/Infrastructure/ValueObject/ImageSizeCollection.php
```

**Expected SOLID Score**: 25/25 (pure value objects, immutable)

---

## Solution for SPEC-06: Hybrid Async Strategy

**Approach**: Maximize promise parallelization for critical data; Symfony Messenger for secondary operations.

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Each resolver manages its own promises | Extract Class |
| **O** - OCP | New async operations added via new message handlers | Messenger |
| **D** - DIP | Resolvers accept async flag, don't know implementation | Strategy |

**Promise Batching in Resolvers**:

```php
// InsertedNewsResolver - batched async
public function resolve(Body $body): array
{
    $elements = $body->bodyElementsOf(BodyTagInsertedNews::class);

    // Batch 1: Fetch all editorials in parallel
    $promises = array_map(
        fn(BodyTagInsertedNews $el) => $this->queryEditorialClient
            ->findEditorialById($el->editorialId()->id(), async: true),
        $elements
    );
    $editorials = Utils::settle($promises)->wait();

    // Batch 2: For visible ones, fetch sections + multimedia in parallel
    // ... similar batching pattern
}
```

**Messenger Integration**:

```php
// New: WarmRelatedEditorialsCache message
final readonly class WarmRelatedEditorialsCache
{
    public function __construct(
        /** @var string[] */
        public array $editorialIds,
    ) {}
}

// Handler: dispatched after response is built
final readonly class WarmRelatedEditorialsCacheHandler
{
    // Pre-fetch and cache related editorials for next request
}
```

**Expected SOLID Score**: 22/25

---

## Solution for SPEC-07: MembershipLinkResolver

**Approach**: Extract membership link promise handling into a dedicated service with a value object.

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Only handles membership URL promise lifecycle | Extract Class |
| **D** - DIP | Injected into orchestrator, decoupled from body analysis | Dependency Injection |

**Files to Create**:

```
src/Orchestrator/Chain/Resolver/MembershipLinkResolver.php
src/Orchestrator/Chain/Resolver/MembershipLinkPromise.php   (Value Object)
```

**Expected SOLID Score**: 24/25

---

## Overall SOLID Score Summary

| Spec | Solution | Pattern | Expected Score |
|------|----------|---------|---------------|
| SPEC-01 | 5 resolvers + trait | Extract Class, Strategy, DI | 23/25 |
| SPEC-02 | Improved trait | Template Method | N/A |
| SPEC-03 | MultimediaShotService | Extract Class, DI | 24/25 |
| SPEC-04 | 13 DTOs | Value Object | 24/25 |
| SPEC-05 | Enum + Value Objects | Enum, Value Object | 25/25 |
| SPEC-06 | Batched promises + Messenger | Strategy, Command | 22/25 |
| SPEC-07 | MembershipLinkResolver | Extract Class, DI | 24/25 |
| **Overall** | | | **23/25 (Grade A)** |

### Pattern Selection Summary

| Need | Pattern Selected | SOLID Principles Addressed |
|------|-----------------|---------------------------|
| Decompose god class | **Extract Class** | SRP, OCP |
| Share fetch+validate+enrich | **Trait** (RelatedEditorialTrait) | DRY |
| Centralize Thumbor operations | **Service** (MultimediaShotService) | SRP, DIP |
| Type-safe responses | **Value Object / DTO** | SRP, DIP |
| Finite domain values | **Enum** (AspectRatio) | SRP |
| Async batching | **Promise** (Utils::settle) | SRP per resolver |
| Secondary operations | **Command** (Messenger) | OCP |
| URL generation | **Template Method** (trait) | DRY |
