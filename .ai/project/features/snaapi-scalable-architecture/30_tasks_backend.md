# Backend Tasks - SNAAPI Pipeline + DTO Factory Architecture

> **Architecture**: Pipeline (enrichment) + DTO Factory (transformation)
> **Decision Record**: See `ADR-001-architecture-choice.md`
> **Criteria**: See `12_architecture_criteria.md`

---

## Phase 1: Foundation (Interfaces & Context)

### Task 1.1: Create Gateway Interfaces
**Priority**: P0 | **Effort**: Low | **Files**: 6 new

```
src/Domain/Port/Gateway/
├── EditorialGatewayInterface.php
├── MultimediaGatewayInterface.php
├── SectionGatewayInterface.php
├── TagGatewayInterface.php
├── JournalistGatewayInterface.php
└── MembershipGatewayInterface.php
```

```php
interface EditorialGatewayInterface
{
    public function findById(string $id): ?Editorial;
    public function findByIdAsync(string $id): PromiseInterface;
}
```

**Acceptance Criteria**:
- [ ] Each interface has sync and async methods
- [ ] PHPStan Level 9 passes
- [ ] 100% test coverage

---

### Task 1.2: Create EditorialContext (Pipeline DTO)
**Priority**: P0 | **Effort**: Low | **Files**: 1 new

```php
// src/Application/Pipeline/EditorialContext.php
final class EditorialContext
{
    private array $data = [];

    public function __construct(
        private readonly string $editorialId,
    ) {}

    public function editorialId(): string { return $this->editorialId; }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    // Type-safe getters
    public function editorial(): ?Editorial { return $this->get('editorial'); }
    public function multimedia(): ?Multimedia { return $this->get('multimedia'); }
    public function section(): ?Section { return $this->get('section'); }
    public function tags(): array { return $this->get('tags', []); }
    public function journalists(): array { return $this->get('journalists', []); }
    public function commentsCount(): int { return $this->get('commentsCount', 0); }
}
```

**Acceptance Criteria**:
- [ ] 100% test coverage
- [ ] PHPStan Level 9 passes

---

### Task 1.3: Create EnricherInterface
**Priority**: P0 | **Effort**: Low | **Files**: 1 new

```php
// src/Application/Pipeline/EnricherInterface.php
interface EnricherInterface
{
    public function priority(): int;
    public function supports(EditorialContext $context): bool;
    public function enrich(EditorialContext $context): void;
}
```

---

### Task 1.4: Create EnrichmentPipeline
**Priority**: P0 | **Effort**: Medium | **Files**: 1 new

```php
// src/Application/Pipeline/EnrichmentPipeline.php
final class EnrichmentPipeline
{
    public function __construct(
        #[TaggedIterator('app.enricher')]
        private iterable $enrichers,
        private LoggerInterface $logger,
    ) {}

    public function process(EditorialContext $context): EditorialContext
    {
        $sorted = $this->sortByPriority($this->enrichers);

        foreach ($sorted as $enricher) {
            if (!$enricher->supports($context)) {
                continue;
            }

            try {
                $enricher->enrich($context);
                $this->logger->info('Enriched', ['enricher' => get_class($enricher)]);
            } catch (Throwable $e) {
                $this->logger->error('Enricher failed', [
                    'enricher' => get_class($enricher),
                    'error' => $e->getMessage(),
                ]);
                // Graceful degradation - continue pipeline
            }
        }

        return $context;
    }
}
```

**Acceptance Criteria**:
- [ ] Tagged iterator injection
- [ ] Sorted by priority
- [ ] Graceful error handling
- [ ] 100% test coverage

---

## Phase 2: Gateway Implementations

### Task 2.1: Implement EditorialHttpGateway
**Priority**: P0 | **Effort**: Low | **Files**: 1 new

```php
// src/Infrastructure/Gateway/Http/EditorialHttpGateway.php
final readonly class EditorialHttpGateway implements EditorialGatewayInterface
{
    public function __construct(
        private QueryEditorialClient $client,
    ) {}

    public function findById(string $id): ?Editorial
    {
        return $this->client->findEditorialById($id);
    }

    public function findByIdAsync(string $id): PromiseInterface
    {
        return $this->client->findEditorialById($id, true);
    }
}
```

---

### Task 2.2-2.6: Implement Other Gateways
**Priority**: P1 | **Effort**: Low each | **Files**: 5 new

- `MultimediaHttpGateway`
- `SectionHttpGateway`
- `TagHttpGateway`
- `JournalistHttpGateway`
- `MembershipHttpGateway`

---

## Phase 3: Enrichers (Pipeline Steps)

### Task 3.1: Create EditorialEnricher
**Priority**: P0 | **Effort**: Low | **Files**: 1 new

```php
// src/Application/Pipeline/Enricher/EditorialEnricher.php
final readonly class EditorialEnricher implements EnricherInterface
{
    public function __construct(
        private EditorialGatewayInterface $gateway,
    ) {}

    public function priority(): int { return 100; }

    public function supports(EditorialContext $context): bool
    {
        return true;
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $this->gateway->findById($context->editorialId());

        if ($editorial === null || !$editorial->isVisible()) {
            throw new EditorialNotFoundException($context->editorialId());
        }

        $context->set('editorial', $editorial);
    }
}
```

---

### Task 3.2-3.6: Create Other Enrichers
**Priority**: P0-P1 | **Effort**: Low each | **Files**: 5 new

- `MultimediaEnricher` (priority: 90)
- `SectionEnricher` (priority: 80)
- `TagsEnricher` (priority: 70)
- `JournalistsEnricher` (priority: 60)
- `MembershipEnricher` (priority: 50)

---

### Task 3.7: Create AsyncBatchEnricher (Optional Optimization)
**Priority**: P2 | **Effort**: Medium | **Files**: 1 new

Batch async calls for performance.

---

## Phase 4: Response DTOs

### Task 4.1: Create EditorialResponse DTO
**Priority**: P0 | **Effort**: Medium | **Files**: 1 new

```php
// src/Application/DTO/Response/EditorialResponse.php
final readonly class EditorialResponse implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $url,
        public TitlesResponse $titles,
        public string $lead,
        public string $publicationDate,
        public BodyResponse $body,
        public ?MultimediaResponse $multimedia,
        /** @var SignatureResponse[] */
        public array $signatures,
        public ?SectionResponse $section,
        /** @var TagResponse[] */
        public array $tags,
        public int $commentsCount,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'titles' => $this->titles,
            'lead' => $this->lead,
            'publicationDate' => $this->publicationDate,
            'body' => $this->body,
            'multimedia' => $this->multimedia,
            'signatures' => $this->signatures,
            'section' => $this->section,
            'tags' => $this->tags,
            'commentsCount' => $this->commentsCount,
        ];
    }
}
```

**Acceptance Criteria**:
- [ ] Full type safety
- [ ] PHPStan Level 9 passes
- [ ] 100% test coverage

---

### Task 4.2: Create BodyResponse DTO
**Priority**: P0 | **Effort**: Low | **Files**: 1 new

```php
// src/Application/DTO/Response/BodyResponse.php
final readonly class BodyResponse implements \JsonSerializable
{
    public function __construct(
        public string $type,
        /** @var BodyElementResponse[] */
        public array $elements,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'elements' => $this->elements,
        ];
    }
}
```

---

### Task 4.3: Create BodyElementResponse DTO
**Priority**: P0 | **Effort**: Medium | **Files**: 1 new

```php
// src/Application/DTO/Response/BodyElementResponse.php
final readonly class BodyElementResponse implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public ?string $content = null,
        public ?int $level = null,
        public ?string $imageUrl = null,
        public ?string $caption = null,
        public ?string $videoId = null,
        // ... other optional fields per element type
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type,
            'content' => $this->content,
            'level' => $this->level,
            'imageUrl' => $this->imageUrl,
            'caption' => $this->caption,
            'videoId' => $this->videoId,
        ], fn($v) => $v !== null);
    }
}
```

---

### Task 4.4-4.8: Create Other Response DTOs
**Priority**: P1 | **Effort**: Low each | **Files**: 5 new

- `TitlesResponse`
- `MultimediaResponse`
- `SectionResponse`
- `TagResponse`
- `SignatureResponse`

---

## Phase 5: Response Factories

### Task 5.1: Create EditorialResponseFactory
**Priority**: P0 | **Effort**: Medium | **Files**: 1 new

```php
// src/Application/Factory/Response/EditorialResponseFactory.php
final readonly class EditorialResponseFactory
{
    public function __construct(
        private BodyResponseFactory $bodyFactory,
        private MultimediaResponseFactory $multimediaFactory,
        private SectionResponseFactory $sectionFactory,
        private TagResponseFactory $tagFactory,
        private SignatureResponseFactory $signatureFactory,
    ) {}

    public function create(EditorialContext $context): EditorialResponse
    {
        $editorial = $context->editorial();

        return new EditorialResponse(
            id: $editorial->id(),
            url: $editorial->url(),
            titles: new TitlesResponse(
                preTitle: $editorial->titles()->preTitle(),
                title: $editorial->titles()->title(),
                urlTitle: $editorial->titles()->urlTitle(),
            ),
            lead: $editorial->lead(),
            publicationDate: $editorial->publicationDate()->format('c'),
            body: $this->bodyFactory->create($editorial->body()),
            multimedia: $context->multimedia()
                ? $this->multimediaFactory->create($context->multimedia())
                : null,
            signatures: array_map(
                fn($j) => $this->signatureFactory->create($j),
                $context->journalists()
            ),
            section: $context->section()
                ? $this->sectionFactory->create($context->section())
                : null,
            tags: array_map(
                fn($t) => $this->tagFactory->create($t),
                $context->tags()
            ),
            commentsCount: $context->commentsCount(),
        );
    }
}
```

**Acceptance Criteria**:
- [ ] Full type safety
- [ ] 100% test coverage
- [ ] Output matches current JSON structure

---

### Task 5.2: Create BodyResponseFactory
**Priority**: P0 | **Effort**: Medium | **Files**: 1 new

```php
// src/Application/Factory/Response/BodyResponseFactory.php
final readonly class BodyResponseFactory
{
    public function __construct(
        private BodyElementResponseFactory $elementFactory,
    ) {}

    public function create(Body $body): BodyResponse
    {
        return new BodyResponse(
            type: $body->type(),
            elements: array_map(
                fn($el) => $this->elementFactory->create($el),
                $body->elements()
            ),
        );
    }
}
```

---

### Task 5.3: Create BodyElementResponseFactory
**Priority**: P0 | **Effort**: High | **Files**: 1 new

```php
// src/Application/Factory/Response/BodyElementResponseFactory.php
final class BodyElementResponseFactory
{
    public function create(BodyElement $element): BodyElementResponse
    {
        return match (true) {
            $element instanceof Paragraph => new BodyElementResponse(
                type: 'paragraph',
                content: $element->content(),
            ),
            $element instanceof SubHead => new BodyElementResponse(
                type: 'subhead',
                content: $element->content(),
                level: $element->level(),
            ),
            $element instanceof BodyTagPicture => new BodyElementResponse(
                type: 'picture',
                imageUrl: $element->imageUrl(),
                caption: $element->caption(),
            ),
            $element instanceof BodyTagVideoYoutube => new BodyElementResponse(
                type: 'video_youtube',
                videoId: $element->videoId(),
            ),
            // ... all 18 element types
            default => throw new UnsupportedBodyElementException(get_class($element)),
        };
    }
}
```

**Acceptance Criteria**:
- [ ] All 18 body element types covered
- [ ] 100% test coverage (one test per type)
- [ ] PHPStan Level 9 passes

---

### Task 5.4-5.7: Create Other Factories
**Priority**: P1 | **Effort**: Low each | **Files**: 4 new

- `MultimediaResponseFactory`
- `SectionResponseFactory`
- `TagResponseFactory`
- `SignatureResponseFactory`

---

## Phase 6: Integration

### Task 6.1: Create GetEditorialHandler
**Priority**: P0 | **Effort**: Low | **Files**: 1 new

```php
// src/Application/Handler/GetEditorialHandler.php
final readonly class GetEditorialHandler
{
    public function __construct(
        private EnrichmentPipeline $pipeline,
        private EditorialResponseFactory $factory,
    ) {}

    public function __invoke(string $editorialId): EditorialResponse
    {
        $context = new EditorialContext($editorialId);
        $enrichedContext = $this->pipeline->process($context);

        return $this->factory->create($enrichedContext);
    }
}
```

---

### Task 6.2: Update EditorialController
**Priority**: P0 | **Effort**: Low | **Files**: 1 modified

```php
// src/Infrastructure/Controller/V1/EditorialController.php
#[Route('/v1/editorials')]
final class EditorialController
{
    public function __construct(
        private readonly GetEditorialHandler $handler,
    ) {}

    #[Route('/{id}', methods: ['GET'])]
    public function getById(string $id): JsonResponse
    {
        $response = ($this->handler)($id);
        return new JsonResponse($response);
    }
}
```

---

### Task 6.3: Configure Services
**Priority**: P0 | **Effort**: Low | **Files**: 1-2 modified

```yaml
# config/services.yaml
services:
    _instanceof:
        App\Application\Pipeline\EnricherInterface:
            tags: ['app.enricher']

    App\Application\Pipeline\EnrichmentPipeline:
        arguments:
            $enrichers: !tagged_iterator app.enricher

    App\Domain\Port\Gateway\EditorialGatewayInterface:
        alias: App\Infrastructure\Gateway\Http\EditorialHttpGateway
```

---

### Task 6.4: Create Backward Compatibility Tests
**Priority**: P0 | **Effort**: High | **Files**: 1 new

```php
// tests/Integration/BackwardCompatibilityTest.php
final class BackwardCompatibilityTest extends KernelTestCase
{
    #[DataProvider('editorialIdsProvider')]
    public function testNewOutputMatchesLegacy(string $id): void
    {
        $legacyResponse = $this->legacyOrchestrator->execute($id);
        $newResponse = ($this->handler)($id);

        $this->assertJsonStringEqualsJsonString(
            json_encode($legacyResponse),
            json_encode($newResponse),
        );
    }
}
```

---

## Phase 7: Decorators (Optional)

### Task 7.1: Create CachedGatewayDecorator
**Priority**: P2 | **Effort**: Medium | **Files**: 1 new

### Task 7.2: Create CircuitBreakerDecorator
**Priority**: P2 | **Effort**: Medium | **Files**: 1 new

---

## Phase 8: Cleanup

### Task 8.1: Deprecate Old Orchestrators
**Priority**: P3 | **Effort**: Low

### Task 8.2: Remove Deprecated Code
**Priority**: P3 | **Effort**: Medium

### Task 8.3: Update CLAUDE.md
**Priority**: P2 | **Effort**: Low

---

## Summary

| Phase | Tasks | New Files | Key Deliverable |
|-------|-------|-----------|-----------------|
| 1. Foundation | 4 | 4 | Pipeline infrastructure |
| 2. Gateways | 6 | 6 | HTTP adapters |
| 3. Enrichers | 7 | 7 | Pipeline steps |
| 4. DTOs | 8 | 8 | Response types |
| 5. Factories | 7 | 7 | Response builders |
| 6. Integration | 4 | 2 | Wire everything |
| 7. Decorators | 2 | 2 | Cache + Circuit Breaker |
| 8. Cleanup | 3 | 0 | Remove old code |

**Total**: 41 tasks, ~36 new files

### Validation Test

**Add `commentsCount` to JSON response:**

```
1. CommentsEnricher.php (NEW)           → fetch data
2. EditorialResponse.php (MODIFY)       → add property
3. EditorialResponseFactory.php (MODIFY)→ pass value
4. EditorialContext.php (MODIFY)        → add getter

Files: 1 new + 3 modified = 4 files
```

### Quality Gates (Must Pass)

- [ ] PHPUnit: 100% coverage
- [ ] PHPStan: Level 9, 0 errors
- [ ] Infection: 100% MSI
- [ ] PHP-CS-Fixer: 0 errors
- [ ] Backward compatibility tests: PASS
