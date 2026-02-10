# Functional Specs: Editorial Refactoring

## SPEC-01: Decompose EditorialOrchestrator into Focused Services

**Priority**: HIGH
**Affects**: `src/Orchestrator/Chain/EditorialOrchestrator.php` (590 lines → ~150 lines)

### Problem

`EditorialOrchestrator::execute()` violates Single Responsibility with 10+ distinct responsibilities:
1. Fetch editorial + validate visibility
2. Fetch section
3. Create membership link promises
4. Process inserted news (fetch editorial, section, signatures, multimedia)
5. Process recommended editorials (identical pattern to inserted news)
6. Get opening multimedia
7. Get async multimedia promises
8. Retrieve photos from body tags
9. Fetch tags
10. Fetch comments
11. Build editorial signatures
12. Resolve membership promises
13. Transform body, multimedia, standfirst, recommended

### Solution

Extract 5 new services. The orchestrator becomes a coordinator:

```
EditorialOrchestrator (coordinator, ~150 lines)
├── InsertedNewsResolver          (responsibility 4)
├── RecommendedEditorialsResolver (responsibility 5)
├── MembershipLinkResolver        (responsibilities 3, 12)
├── SignatureResolver             (responsibility 11, shared with 4, 5)
└── MultimediaResolver            (responsibilities 6, 7, 8)
```

### New Class: `InsertedNewsResolver`

**Location**: `src/Orchestrator/Chain/Resolver/InsertedNewsResolver.php`
**Responsibility**: Fetch, validate, and enrich all inserted news from the editorial body.

```php
final readonly class InsertedNewsResolver
{
    public function __construct(
        private QueryEditorialClient $queryEditorialClient,
        private QuerySectionClient $querySectionClient,
        private SignatureResolver $signatureResolver,
        private MultimediaResolver $multimediaResolver,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, InsertedNewsData>
     */
    public function resolve(Body $body): array;
}
```

**Uses**: `RelatedEditorialTrait` for shared fetch+validate+enrich pattern.

### New Class: `RecommendedEditorialsResolver`

**Location**: `src/Orchestrator/Chain/Resolver/RecommendedEditorialsResolver.php`
**Responsibility**: Fetch, validate, and enrich all recommended editorials.

```php
final readonly class RecommendedEditorialsResolver
{
    public function __construct(
        private QueryEditorialClient $queryEditorialClient,
        private QuerySectionClient $querySectionClient,
        private SignatureResolver $signatureResolver,
        private MultimediaResolver $multimediaResolver,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, RecommendedEditorialData>
     */
    public function resolve(RecommendedEditorials $recommendedEditorials): array;
}
```

**Uses**: `RelatedEditorialTrait` for shared pattern.

### New Trait: `RelatedEditorialTrait`

**Location**: `src/Orchestrator/Chain/Resolver/Trait/RelatedEditorialTrait.php`
**Responsibility**: Shared logic for fetching editorial → checking visibility → resolving signatures → resolving multimedia.

```php
trait RelatedEditorialTrait
{
    /**
     * Fetches an editorial, checks visibility, resolves signatures and multimedia.
     * Returns null if editorial is not visible.
     */
    private function resolveRelatedEditorial(
        string $editorialId,
        QueryEditorialClient $queryEditorialClient,
        QuerySectionClient $querySectionClient,
        SignatureResolver $signatureResolver,
        MultimediaResolver $multimediaResolver,
    ): ?RelatedEditorialData;
}
```

### New Class: `SignatureResolver`

**Location**: `src/Orchestrator/Chain/Resolver/SignatureResolver.php`
**Responsibility**: Resolve journalist signatures from alias IDs.

```php
final readonly class SignatureResolver
{
    public function __construct(
        private QueryJournalistClient $queryJournalistClient,
        private JournalistFactory $journalistFactory,
        private JournalistsDataTransformer $journalistsDataTransformer,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param Signatures $signatures
     * @return array<int, array<string, mixed>>
     */
    public function resolve(Signatures $signatures, Section $section, bool $hasTwitter = false): array;

    /**
     * @return array<string, mixed>
     */
    public function resolveAlias(string $aliasId, Section $section, bool $hasTwitter = false): array;
}
```

**Eliminates**: Duplication of `retrieveAliasFormat()` logic in orchestrator lines 281-296, and signature loops in lines 136-143, 178-184, 243-253.

### New Class: `MultimediaResolver`

**Location**: `src/Orchestrator/Chain/Resolver/MultimediaResolver.php`
**Responsibility**: Handle all async multimedia fetching, promise resolution, and body tag photo retrieval.

```php
final readonly class MultimediaResolver
{
    public function __construct(
        private QueryMultimediaClient $queryMultimediaClient,
        private QueryMultimediaOpeningClient $queryMultimediaOpeningClient,
        private MultimediaOrchestratorHandler $multimediaTypeOrchestratorHandler,
        private LoggerInterface $logger,
    ) {}

    /**
     * Fetch multimedia asynchronously, resolve promises, return resolved data.
     */
    public function resolveAsync(Multimedia $multimedia): array;

    public function resolveOpening(Editorial $editorial): array;

    public function resolveBodyTagPhotos(Body $body): array;

    public function resolveMetaImage(Editorial $editorial): array;
}
```

**Eliminates**: Methods `getAsyncMultimedia()`, `getOpening()`, `getMetaImage()`, `retrievePhotosFromBodyTags()`, `addPhotoToArray()`, `fulfilledMultimedia()`, `createCallback()` from the orchestrator.

### Refactored EditorialOrchestrator

After extraction, the orchestrator becomes a pure coordinator (~150 lines):

```php
public function execute(Request $request): array
{
    $editorial = $this->fetchAndValidateEditorial($id);
    $section = $this->querySectionClient->findSectionById($editorial->sectionId());

    // Parallel resolution of independent data
    $membershipPromise = $this->membershipLinkResolver->createPromise($editorial, $section->siteId());
    $insertedNews = $this->insertedNewsResolver->resolve($editorial->body());
    $recommended = $this->recommendedEditorialsResolver->resolve($editorial->recommendedEditorials());
    $multimediaData = $this->multimediaResolver->resolveAll($editorial);
    $tags = $this->tagResolver->resolve($editorial->tags());

    // Transform and build response
    $result = $this->buildResponse($editorial, $section, $tags, ...);

    return $result;
}
```

### Acceptance Criteria

- [ ] `EditorialOrchestrator` reduced to ≤ 150 lines
- [ ] 5 new resolver services created with proper interfaces
- [ ] `RelatedEditorialTrait` shared between `InsertedNewsResolver` and `RecommendedEditorialsResolver`
- [ ] Constructor injection only (no setters, no `@required`)
- [ ] All services tagged and autowired
- [ ] Existing tests pass without changes to assertions (API compat)
- [ ] New unit tests for each resolver service
- [ ] PHPStan Level 9 clean
- [ ] Zero `@phpstan-ignore` annotations in new code

### Open Questions

> **Q1 - RESOLVED**: Resolvers return DTOs (from SPEC-04) directly. The orchestrator coordinates resolvers and transformers as separate steps. This aligns with DDD layers. Confirmed by user.

---

## SPEC-02: Improve UrlGeneratorTrait and Extract EditorialUrlBuilder

**Priority**: MEDIUM
**Affects**: 3 files with identical `editorialUrl()` method

### Problem

Identical `editorialUrl()` implementation in:
1. `DetailsAppsDataTransformer.php:105-121`
2. `BodyTagInsertedNewsDataTransformer.php:88-104`
3. `RecommendedEditorialsDataTransformer.php:96-112`

All three have the exact same code:
```php
private function editorialUrl(Editorial $editorial, Section $section): string
{
    $editorialPath = sprintf('%s/%s/%s_%s',
        $section->getPath(),
        $editorial->publicationDate()->format('Y-m-d'),
        Encode::encodeUrl($editorial->editorialTitles()->urlTitle()),
        $editorial->id()->id()
    );
    return $this->generateUrl('https://%s.%s.%s/%s',
        $section->isSubdomainBlog() ? 'blog' : 'www',
        $section->siteId(),
        $editorialPath
    );
}
```

### Solution

**Keep `UrlGeneratorTrait`** (decision: simple logic stays as trait) but improve it:

1. Add `editorialUrl(Editorial, Section): string` method to the trait
2. Remove the 3 duplicate private implementations
3. Add `journalistUrl(Journalist, Section): string` from `JournalistsDataTransformer`
4. Add `tagUrl(Tag, Section): string` from `DetailsAppsDataTransformer`

### Updated UrlGeneratorTrait

```php
trait UrlGeneratorTrait
{
    private string $extension;

    private function setExtension(string $extension): void { ... }

    protected function generateUrl(string $format, string $subdomain, string $siteId, string $urlPath): string { ... }

    protected function editorialUrl(Editorial $editorial, Section $section): string
    {
        $editorialPath = sprintf('%s/%s/%s_%s',
            $section->getPath(),
            $editorial->publicationDate()->format('Y-m-d'),
            Encode::encodeUrl($editorial->editorialTitles()->urlTitle()),
            $editorial->id()->id()
        );
        return $this->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $editorialPath
        );
    }

    protected function sectionUrl(Section $section): string { ... }
}
```

### Acceptance Criteria

- [ ] `editorialUrl()` exists in ONE place only (the trait)
- [ ] 3 private `editorialUrl()` methods removed from transformers
- [ ] `sectionUrl()` extracted from `DetailsAppsDataTransformer::transformerSection()`
- [ ] All usages updated to use the trait method
- [ ] Tests pass unchanged
- [ ] PHPStan Level 9 clean

---

## SPEC-03: MultimediaShotService (Extract from Trait)

**Priority**: HIGH
**Affects**: `MultimediaTrait` + `DetailsMultimediaPhotoDataTransformer` + 3 consumers

### Problem

Shot generation logic duplicated across:
1. `MultimediaTrait::getShotsLandscape()` - for body tags (3 sizes: 202w, 144w, 128w)
2. `MultimediaTrait::getShotsLandscapeFromMedia()` - same logic, different input format
3. `DetailsMultimediaPhotoDataTransformer::read()` - for opening multimedia (50+ sizes across 5 aspect ratios)
4. `BodyTagPictureDataTransformer` - uses `MultimediaTrait`

All share the same Thumbor crop pattern but with different size configurations.

### Solution

Create `MultimediaShotService` as an injectable service (decision: complex logic → service):

**Location**: `src/Infrastructure/Service/MultimediaShotService.php`

```php
final readonly class MultimediaShotService
{
    public function __construct(
        private Thumbor $thumbor,
    ) {}

    /**
     * Generate landscape shots (3 sizes, 4:3 ratio) for listings/thumbnails.
     * Used by: recommended editorials, inserted news, body tag pictures.
     *
     * @return array<string, string> Map of size label => Thumbor URL
     */
    public function generateLandscapeShots(string $file, Clippings $clippings): array;

    /**
     * Generate landscape shots from media opening data.
     *
     * @param array{opening: MultimediaPhoto, resource: Photo} $multimediaOpening
     * @return array<string, string>
     */
    public function generateLandscapeShotsFromMedia(array $multimediaOpening): array;

    /**
     * Generate responsive shots for all aspect ratios (opening multimedia).
     * Used by: DetailsMultimediaPhotoDataTransformer.
     *
     * @return array<string, array<string, string>> Map of aspectRatio => (size => URL)
     */
    public function generateResponsiveShots(
        string $file,
        Clippings $clippings,
        AspectRatio $aspectRatio = null,
    ): array;

    /**
     * Generate journalist photo URL.
     */
    public function generateJournalistPhoto(Journalist $journalist): string;
}
```

### Migration

| Before | After |
|--------|-------|
| `$this->getShotsLandscape($multimedia)` | `$this->multimediaShotService->generateLandscapeShots($multimedia->file(), $multimedia->clippings())` |
| `$this->getShotsLandscapeFromMedia($data)` | `$this->multimediaShotService->generateLandscapeShotsFromMedia($data)` |
| `DetailsMultimediaPhotoDataTransformer::SIZES_RELATIONS` + inline loop | `$this->multimediaShotService->generateResponsiveShots(...)` |
| `$this->thumbor->createJournalistImage()` | `$this->multimediaShotService->generateJournalistPhoto($journalist)` |

### MultimediaTrait Removal

After extracting to `MultimediaShotService`:
- Remove `MultimediaTrait` entirely
- All classes that `use MultimediaTrait` now inject `MultimediaShotService`
- Affected classes: `EditorialOrchestrator`, `RecommendedEditorialsDataTransformer`, `BodyTagInsertedNewsDataTransformer`, `BodyTagPictureDataTransformer`, `DetailsMultimediaPhotoDataTransformer`

### Acceptance Criteria

- [ ] `MultimediaShotService` created as injectable service
- [ ] `MultimediaTrait` removed completely
- [ ] `DetailsMultimediaPhotoDataTransformer::SIZES_RELATIONS` moved to service (uses SPEC-05 value objects)
- [ ] All shot generation goes through the service
- [ ] `$sizes` array (202w, 144w, 128w) moved to `ImageSizeCollection` value object (SPEC-05)
- [ ] Thumbor interactions centralized in one service
- [ ] Unit tests for `MultimediaShotService` with all generation methods
- [ ] PHPStan Level 9 clean

---

## SPEC-04: Response DTOs for Transformers

**Priority**: MEDIUM
**Affects**: All transformers returning `array<string, mixed>`

### Problem

All transformers return untyped arrays (`array<string, mixed>`):
- No compile-time validation of response structure
- `@phpstan-ignore` annotations throughout
- Easy to forget fields or introduce typos
- No IDE autocomplete for response building

### Solution

Create DTOs that match the current JSON response structure (100% backward compatible):

**Location**: `src/Application/DTO/`

### DTOs

```php
// src/Application/DTO/EditorialResponse.php
final readonly class EditorialResponse
{
    public function __construct(
        public string $id,
        public string $url,
        public EditorialTitlesDto $titles,
        public string $lead,
        public string $publicationDate,
        public string $updatedOn,
        public string $endOn,
        public EditorialTypeDto $type,
        public bool $indexable,
        public bool $deleted,
        public bool $published,
        public string $closingModeId,
        public bool $commentable,
        public bool $isBrand,
        public bool $isAmazonOnsite,
        public string $contentType,
        public string $canonicalEditorialId,
        public string $urlDate,
        public int $countWords,
        public int $countComments,
        public SectionDto $section,
        /** @var TagDto[] */
        public array $tags,
        /** @var SectionDto[] */
        public array $adsOptions,
        /** @var SectionDto[] */
        public array $analiticsOptions,
        /** @var SignatureDto[] */
        public array $signatures,
        /** @var BodyElementDto[] */
        public array $body,
        public ?MultimediaResponseDto $multimedia,
        /** @var BodyElementDto[] */
        public array $standfirst,
        /** @var RecommendedEditorialDto[] */
        public array $recommendedEditorials,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

// src/Application/DTO/SignatureDto.php
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
}

// src/Application/DTO/MultimediaResponseDto.php
final readonly class MultimediaResponseDto
{
    public function __construct(
        public string $id,
        public string $type,
        public string $caption,
        public object $shots,
        public string $photo,
    ) {}
}

// src/Application/DTO/RecommendedEditorialDto.php
final readonly class RecommendedEditorialDto
{
    public function __construct(
        public string $type,
        public string $editorialId,
        /** @var SignatureDto[] */
        public array $signatures,
        public string $editorial, // URL
        public string $title,
        /** @var array<string, string> */
        public array $shots,
        public string $photo,
    ) {}
}
```

### Additional DTOs

- `EditorialTitlesDto` (title, preTitle, urlTitle, mobileTitle)
- `EditorialTypeDto` (id, name)
- `SectionDto` (id, name, url, encodeName)
- `TagDto` (id, name, url)
- `DepartmentDto` (id, name)
- `BodyElementDto` (type + dynamic fields per element type, union type)
- `InsertedNewsData` (editorial, section, signatures, multimediaId) - for resolver internal use

### Backward Compatibility Strategy

Each DTO implements `toArray(): array` that produces the **exact same structure** as the current arrays. The orchestrator calls `$response->toArray()` at the final step.

```php
// In EditorialOrchestrator::execute()
$response = new EditorialResponse(...);
return $response->toArray(); // Identical JSON output
```

### Acceptance Criteria

- [ ] At least 10 DTOs created covering the editorial response
- [ ] All DTOs are `final readonly` with constructor promotion
- [ ] Each DTO has `toArray()` producing backward-compatible output
- [ ] Transformers return DTOs instead of arrays
- [ ] `@phpstan-ignore` annotations reduced by ≥ 80%
- [ ] JSON response byte-identical to current output (verified by existing integration tests)
- [ ] PHPStan Level 9 clean with zero `mixed` types in DTOs

### Open Questions

> **Q2 - RESOLVED**: Single `BodyElementDto` with type discriminator. Creating 18 subclasses would add complexity without much benefit since the JSON structure is flat. The `toArray()` method already handles the differences in the transformer layer. Confirmed by user.

---

## SPEC-05: AspectRatio Enum & ImageSize Value Objects

**Priority**: LOW
**Affects**: `DetailsMultimediaPhotoDataTransformer`, `MultimediaTrait`, new `MultimediaShotService`

### Problem

Image sizes and aspect ratios are hardcoded as PHP arrays and string constants:
- `SIZES_RELATIONS` constant in `DetailsMultimediaPhotoDataTransformer` (50+ entries)
- `$sizes` array in `MultimediaTrait` (3 entries)
- String constants like `'16:9'`, `'4:3'` scattered in code

### Solution

#### AspectRatio Enum

**Location**: `src/Infrastructure/Enum/AspectRatioEnum.php`

```php
enum AspectRatioEnum: string
{
    case RATIO_16_9 = '16:9';
    case RATIO_4_3 = '4:3';
    case RATIO_3_2 = '3:2';
    case RATIO_2_3 = '2:3';
    case RATIO_3_4 = '3:4';

    /**
     * Returns the ClippingType associated with this aspect ratio for opening multimedia.
     */
    public function clippingType(): string;
}
```

#### ImageSize Value Object

**Location**: `src/Infrastructure/ValueObject/ImageSize.php`

```php
final readonly class ImageSize
{
    public function __construct(
        public string $label,    // e.g., '1440w', '414w', 'lo-res'
        public int $width,
        public int $height,
    ) {}
}
```

#### ImageSizeCollection Value Object

**Location**: `src/Infrastructure/ValueObject/ImageSizeCollection.php`

```php
final readonly class ImageSizeCollection
{
    /** @var ImageSize[] */
    private array $sizes;

    public static function landscape(): self;      // 202w, 144w, 128w
    public static function responsive(): self;     // All 50+ sizes from SIZES_RELATIONS
    public static function forAspectRatio(AspectRatioEnum $ratio): self;
}
```

### Acceptance Criteria

- [ ] `AspectRatioEnum` with 5 cases created
- [ ] `ImageSize` value object with label/width/height
- [ ] `ImageSizeCollection` with factory methods for each context
- [ ] `SIZES_RELATIONS` constant removed from `DetailsMultimediaPhotoDataTransformer`
- [ ] `$sizes` array removed from `MultimediaTrait` (which is also being removed per SPEC-03)
- [ ] String constants replaced with enum cases
- [ ] PHPStan Level 9 clean

---

## SPEC-06: Hybrid Async Strategy (Promises + Messenger)

**Priority**: HIGH
**Affects**: `EditorialOrchestrator`, new resolver services

### Problem

Current async handling:
1. Guzzle promises for multimedia fetching (good)
2. Sequential calls for inserted news, recommended editorials, tags (bad)
3. Membership links via promise but resolved synchronously (mixed)
4. No Symfony Messenger integration for secondary operations

### Solution

**Phase 1: Maximize Promise parallelization** (within current request):

```
CURRENT FLOW (sequential):                NEW FLOW (parallel where possible):

1. Fetch editorial  ─────────────────→    1. Fetch editorial
2. Fetch section    ─────────────────→    2. Fetch section
3. Loop: inserted news (sequential)  →    3. PARALLEL:
4. Loop: recommended (sequential)    →       ├── Inserted news promises (batch)
5. Get opening multimedia            →       ├── Recommended promises (batch)
6. Get async multimedia              →       ├── Opening multimedia
7. Fetch tags (sequential loop)      →       ├── Async multimedia
8. Fetch comments                    →       ├── Tags (batch)
9. Build signatures                  →       ├── Comments
10. Transform body                   →       └── Membership link promise
11. Transform multimedia                  4. Resolve all promises
12. Transform standfirst                  5. Build signatures (with resolved data)
13. Transform recommended                 6. Transform all (body, multimedia, etc.)
```

**Phase 2: Symfony Messenger for secondary operations** (async, non-blocking):

Operations that DON'T need to be in the response but enrich it:
- Cache warming for related editorials
- Pre-fetching recommended editorial multimedia
- Analytics/metrics collection

```php
// New message for async pre-warming
final readonly class WarmRelatedEditorialsCache
{
    public function __construct(
        /** @var string[] */
        public array $editorialIds,
    ) {}
}
```

### Detailed Promise Batching

For inserted news and recommended editorials, batch all external calls:

```php
// In InsertedNewsResolver
public function resolve(Body $body): array
{
    $insertedNewsElements = $body->bodyElementsOf(BodyTagInsertedNews::class);

    // Phase 1: Batch fetch all editorials in parallel
    $editorialPromises = [];
    foreach ($insertedNewsElements as $element) {
        $editorialPromises[$element->editorialId()->id()] =
            $this->queryEditorialClient->findEditorialById($element->editorialId()->id(), async: true);
    }
    $editorials = Utils::settle($editorialPromises)->wait();

    // Phase 2: For visible editorials, batch fetch sections and multimedia
    $sectionPromises = [];
    $multimediaPromises = [];
    foreach ($editorials as $id => $result) {
        if ($result['state'] === 'fulfilled' && $result['value']->isVisible()) {
            $sectionPromises[$id] = $this->querySectionClient->findSectionById(
                $result['value']->sectionId(), async: true
            );
            // ... multimedia promises
        }
    }

    // Phase 3: Resolve and build
    // ...
}
```

### Acceptance Criteria

- [ ] All independent external calls batched with promises
- [ ] Inserted news editorials fetched in parallel (not sequentially in loop)
- [ ] Recommended editorials fetched in parallel (not sequentially in loop)
- [ ] Tags fetched in parallel (not sequentially in loop)
- [ ] Symfony Messenger message class created for cache warming
- [ ] Handler created for `WarmRelatedEditorialsCache`
- [ ] Response time measurably improved (fewer sequential HTTP calls)
- [ ] Error handling: individual promise failures don't crash the entire response
- [ ] Circuit breaker pattern for external service failures (graceful degradation)

### Open Questions

> **Q3 - RESOLVED**: All external clients (QueryEditorialClient, QuerySectionClient, QueryTagClient) support async mode with `async: true`. Full parallelization is possible. Confirmed by user.

---

## SPEC-07: MembershipLinkResolver Service

**Priority**: MEDIUM
**Affects**: `EditorialOrchestrator` (lines 345-419), `BodyTagMembershipCardDataTransformer`

### Problem

Membership link handling is split across:
1. `getLinksFromBody()` → `getLinksOfBodyTagMembership()` in orchestrator
2. `getPromiseMembershipLinks()` in orchestrator (creates promise)
3. `resolvePromiseMembershipLinks()` in orchestrator (resolves promise)
4. `retrieveButtons()` in `BodyTagMembershipCardDataTransformer` (applies resolved links)

This creates tight coupling between orchestrator and transformer through `resolveData['membershipLinkCombine']`.

### Solution

**Location**: `src/Orchestrator/Chain/Resolver/MembershipLinkResolver.php`

```php
final readonly class MembershipLinkResolver
{
    public function __construct(
        private QueryMembershipClient $queryMembershipClient,
        private UriFactoryInterface $uriFactory,
    ) {}

    /**
     * Extract membership links from body and create async promise.
     *
     * @return MembershipLinkPromise Value object wrapping promise + original links
     */
    public function createPromise(Editorial $editorial, string $siteId): MembershipLinkPromise;

    /**
     * Resolve the promise and return link mapping.
     *
     * @return array<string, string> Map of original URL => resolved membership URL
     */
    public function resolve(MembershipLinkPromise $promise): array;
}
```

#### MembershipLinkPromise Value Object

```php
final readonly class MembershipLinkPromise
{
    public function __construct(
        public ?Promise $promise,
        /** @var array<int, string> */
        public array $originalLinks,
    ) {}

    public function hasLinks(): bool
    {
        return !empty($this->originalLinks);
    }
}
```

### Acceptance Criteria

- [ ] `MembershipLinkResolver` created as injectable service
- [ ] `MembershipLinkPromise` value object created
- [ ] 5 methods removed from `EditorialOrchestrator`: `getLinksFromBody()`, `getLinksOfBodyTagMembership()`, `getPromiseMembershipLinks()`, `resolvePromiseMembershipLinks()`
- [ ] `BodyTagMembershipCardDataTransformer` receives resolved links (not raw promise data)
- [ ] Unit tests for resolver
- [ ] PHPStan Level 9 clean
