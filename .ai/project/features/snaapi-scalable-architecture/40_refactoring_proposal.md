# Refactoring Proposal - SNAAPI

## Executive Summary

Este documento presenta la propuesta de refactorización del código actual de SNAAPI hacia una arquitectura Hexagonal + CQRS (Query side), manteniendo 100% de compatibilidad con la API actual.

---

## Before vs After Comparison

### 1. Controller Layer

**BEFORE** (Current):
```php
// src/Controller/V1/EditorialController.php
#[Route('/v1/editorials')]
class EditorialController
{
    public function __construct(
        private OrchestratorChainHandler $orchestratorChain,  // ❌ Acoplado a orquestador
    ) {}

    #[Route('/{id}', methods: ['GET'])]
    public function getEditorialById(string $id, Request $request): JsonResponse
    {
        return $this->orchestratorChain->handler('editorial', $request);  // ❌ String magic
    }
}
```

**AFTER** (Proposed):
```php
// src/Infrastructure/Controller/V1/EditorialController.php
#[Route('/v1/editorials')]
final class EditorialController
{
    public function __construct(
        private readonly GetEditorialByIdHandler $handler,  // ✅ Type-safe handler
    ) {}

    #[Route('/{id}', methods: ['GET'])]
    public function getById(string $id): JsonResponse
    {
        $query = new GetEditorialByIdQuery(new EditorialId($id));  // ✅ Value Object
        $response = ($this->handler)($query);  // ✅ Invocable handler

        return new JsonResponse($response);
    }
}
```

---

### 2. Orchestrator → Query Handler + Aggregator

**BEFORE** (Current):
```php
// src/Orchestrator/Chain/EditorialOrchestrator.php
class EditorialOrchestrator implements EditorialOrchestratorInterface
{
    public function __construct(
        private QueryEditorialClient $editorialClient,      // ❌ Concrete class
        private QueryMultimediaClient $multimediaClient,    // ❌ Concrete class
        private QuerySectionClient $sectionClient,          // ❌ Concrete class
        private DetailsAppsDataTransformer $transformer,
        // ... más dependencias
    ) {}

    public function execute(Request $request): JsonResponse
    {
        // Todo mezclado: fetch + transform + response
        $editorial = $this->editorialClient->findEditorialById($id);

        // Promises manuales
        $this->getAsyncMultimedia($editorial);
        $this->getPromiseMembershipLinks();
        Utils::settle($this->promises)->wait();

        // Transform
        $this->transformer->write(...)->read();

        return new JsonResponse(...);
    }
}
```

**AFTER** (Proposed):

```php
// src/Application/Query/GetEditorialById/GetEditorialByIdHandler.php
final readonly class GetEditorialByIdHandler
{
    public function __construct(
        private EditorialAggregator $aggregator,              // ✅ Separated responsibility
        private EditorialResponseTransformer $transformer,    // ✅ Clear purpose
    ) {}

    public function __invoke(GetEditorialByIdQuery $query): EditorialResponse
    {
        // 1. Aggregate all data
        $aggregated = $this->aggregator->aggregate($query->id);

        if ($aggregated === null) {
            throw new EditorialNotFoundException($query->id);
        }

        // 2. Transform to response
        return $this->transformer->transform($aggregated);
    }
}

// src/Application/Aggregator/EditorialAggregator.php
final readonly class EditorialAggregator
{
    public function __construct(
        private EditorialGatewayInterface $editorialGateway,      // ✅ Interface
        private MultimediaGatewayInterface $multimediaGateway,    // ✅ Interface
        private SectionGatewayInterface $sectionGateway,          // ✅ Interface
        private TagGatewayInterface $tagGateway,                  // ✅ Interface
        private JournalistGatewayInterface $journalistGateway,    // ✅ Interface
        private MembershipGatewayInterface $membershipGateway,    // ✅ Interface
    ) {}

    public function aggregate(EditorialId $id): ?AggregatedEditorial
    {
        // 1. Fetch base editorial (sync - need it first)
        $editorial = $this->editorialGateway->findById($id);

        if ($editorial === null || !$editorial->isVisible()) {
            return null;
        }

        // 2. Fetch related data in parallel
        $promises = [
            'multimedia' => $this->multimediaGateway->findByIdAsync($editorial->multimediaId()),
            'section' => $this->sectionGateway->findByIdAsync($editorial->sectionId()),
            'tags' => $this->tagGateway->findByIdsAsync($editorial->tagIds()),
            'journalists' => $this->journalistGateway->findByIdsAsync($editorial->journalistIds()),
            'membership' => $this->membershipGateway->findLinksAsync($editorial->id()),
        ];

        // 3. Wait for all promises
        $results = Utils::settle($promises)->wait();

        // 4. Build aggregated result
        return new AggregatedEditorial(
            editorial: $editorial,
            multimedia: $this->extractFulfilled($results, 'multimedia'),
            section: $this->extractFulfilled($results, 'section'),
            tags: $this->extractFulfilled($results, 'tags') ?? [],
            journalists: $this->extractFulfilled($results, 'journalists') ?? [],
            membershipLinks: $this->extractFulfilled($results, 'membership') ?? [],
        );
    }
}
```

---

### 3. External Clients → Gateway Pattern

**BEFORE** (Current):
```php
// Direct usage of external package client
class EditorialOrchestrator
{
    public function __construct(
        private QueryEditorialClient $editorialClient,  // ❌ From ec/editorial-domain package
    ) {}

    public function execute(Request $request): JsonResponse
    {
        // Directly coupled to external package
        $editorial = $this->editorialClient->findEditorialById($id);
    }
}
```

**AFTER** (Proposed):

```php
// src/Domain/Port/Gateway/EditorialGatewayInterface.php
interface EditorialGatewayInterface
{
    public function findById(EditorialId $id): ?Editorial;
    public function findByIdAsync(EditorialId $id): PromiseInterface;
    public function isVisible(EditorialId $id): bool;
}

// src/Infrastructure/Gateway/Http/EditorialHttpGateway.php
final readonly class EditorialHttpGateway implements EditorialGatewayInterface
{
    public function __construct(
        private QueryEditorialClient $client,  // Wrapped external client
    ) {}

    public function findById(EditorialId $id): ?Editorial
    {
        return $this->client->findEditorialById($id->value());
    }

    public function findByIdAsync(EditorialId $id): PromiseInterface
    {
        return $this->client->findEditorialById($id->value(), true);
    }

    public function isVisible(EditorialId $id): bool
    {
        $editorial = $this->findById($id);
        return $editorial !== null && $editorial->isVisible();
    }
}
```

---

### 4. Decorator Chain for Cross-Cutting Concerns

**BEFORE** (Current):
No caching or circuit breaker at gateway level.

**AFTER** (Proposed):

```php
// src/Infrastructure/Gateway/Decorator/CachedGatewayDecorator.php
final class CachedGatewayDecorator implements EditorialGatewayInterface
{
    public function __construct(
        private readonly EditorialGatewayInterface $inner,
        private readonly CacheInterface $cache,
        private readonly int $ttl = 300,
    ) {}

    public function findById(EditorialId $id): ?Editorial
    {
        $key = sprintf('editorial:%s', $id->value());

        return $this->cache->get(
            $key,
            fn() => $this->inner->findById($id),
            $this->ttl
        );
    }

    // ... other methods delegate to inner
}

// src/Infrastructure/Gateway/Decorator/CircuitBreakerDecorator.php
final class CircuitBreakerDecorator implements EditorialGatewayInterface
{
    public function __construct(
        private readonly EditorialGatewayInterface $inner,
        private readonly CircuitBreakerService $circuitBreaker,
        private readonly string $serviceName = 'editorial',
    ) {}

    public function findById(EditorialId $id): ?Editorial
    {
        return $this->circuitBreaker->execute(
            serviceName: $this->serviceName,
            operation: fn() => $this->inner->findById($id),
            fallback: fn() => null,  // Graceful degradation
        );
    }
}

// Service configuration (services.yaml)
services:
    # Decorator chain: Cache → CircuitBreaker → Http
    App\Domain\Port\Gateway\EditorialGatewayInterface:
        class: App\Infrastructure\Gateway\Decorator\CachedGatewayDecorator
        arguments:
            $inner: '@editorial.gateway.circuit_breaker'
            $cache: '@cache.app'
            $ttl: 300

    editorial.gateway.circuit_breaker:
        class: App\Infrastructure\Gateway\Decorator\CircuitBreakerDecorator
        arguments:
            $inner: '@App\Infrastructure\Gateway\Http\EditorialHttpGateway'
            $circuitBreaker: '@App\Infrastructure\Service\CircuitBreaker\CircuitBreakerService'
            $serviceName: 'editorial'
```

---

### 5. Body Transformers

**BEFORE** (Current):
```php
// src/Application/DataTransformer/BodyElementDataTransformerHandler.php
class BodyElementDataTransformerHandler
{
    private array $transformers = [];

    public function addTransformer(BodyElementDataTransformer $transformer): void
    {
        // Registration via Compiler Pass
    }

    public function execute(BodyElement $element, array $resolveData): array
    {
        $className = get_class($element);  // ❌ String-based matching
        if (!isset($this->transformers[$className])) {
            throw new TransformerNotFoundForBodyElementException($className);
        }
        return $this->transformers[$className]->write($element, $resolveData)->read();
    }
}
```

**AFTER** (Proposed):
```php
// src/Application/Transformer/Editorial/Element/ElementTransformerInterface.php
interface ElementTransformerInterface
{
    public function supports(BodyElement $element): bool;  // ✅ Type-safe check
    public function transform(BodyElement $element, TransformContext $context): ElementResponse;
}

// src/Application/Transformer/Editorial/BodyTransformerChain.php
final readonly class BodyTransformerChain
{
    /**
     * @param iterable<ElementTransformerInterface> $transformers
     */
    public function __construct(
        private iterable $transformers,  // ✅ Injected via service tag iterator
    ) {}

    public function transform(Body $body, TransformContext $context): BodyResponse
    {
        $elements = [];

        foreach ($body->elements() as $element) {
            $transformer = $this->findTransformer($element);
            if ($transformer !== null) {
                $elements[] = $transformer->transform($element, $context);
            }
        }

        return new BodyResponse($body->type(), $elements);
    }

    private function findTransformer(BodyElement $element): ?ElementTransformerInterface
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($element)) {
                return $transformer;
            }
        }
        return null;
    }
}

// src/Application/Transformer/Editorial/Element/ParagraphTransformer.php
final readonly class ParagraphTransformer implements ElementTransformerInterface
{
    public function supports(BodyElement $element): bool
    {
        return $element instanceof Paragraph;
    }

    public function transform(BodyElement $element, TransformContext $context): ElementResponse
    {
        /** @var Paragraph $element */
        return new ElementResponse(
            type: 'paragraph',
            content: $element->content(),
        );
    }
}
```

---

## Benefits Summary

| Aspect | Before | After | Benefit |
|--------|--------|-------|---------|
| **Testability** | Hard to mock clients | Easy to mock interfaces | Faster, isolated unit tests |
| **Extensibility** | Modify existing code | Add new implementations | Open/Closed principle |
| **Resilience** | No fallbacks | Circuit breaker + cache | Better availability |
| **Performance** | Cache at HTTP level only | Multi-level caching | Reduced latency |
| **Maintainability** | Mixed responsibilities | Clear separation | Easier to understand |
| **Type Safety** | String-based routing | Interface contracts | Compile-time checks |

---

## Migration Path

### Phase 1: Add New (No Breaking Changes)
- Create interfaces in `Domain/Port/`
- Create implementations in `Infrastructure/Gateway/`
- Both old and new code coexist

### Phase 2: Wire New
- Create Query Handlers
- Create Aggregators
- Wire to controllers

### Phase 3: Validate
- Comparison tests (old vs new output)
- Performance benchmarks
- QA validation

### Phase 4: Cleanup
- Deprecate old orchestrators
- Remove after validation
- Update documentation

---

## Risk Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Breaking API | Medium | High | Comparison tests before switching |
| Performance regression | Low | Medium | Benchmark before/after |
| External package incompatibility | Low | Medium | Adapter pattern isolates changes |
| Team unfamiliarity | Medium | Low | Documentation + examples |

---

## Conclusion

La refactorización propuesta:

1. **Mejora la arquitectura** sin cambiar la API pública
2. **Añade resiliencia** con Circuit Breaker y caching
3. **Facilita el testing** con interfaces
4. **Prepara para escalar** con patrones establecidos
5. **Mantiene compatibilidad** al 100% con clientes actuales

El código resultante será más mantenible, testeable y extensible, siguiendo los principios SOLID y las mejores prácticas de Clean Architecture.
