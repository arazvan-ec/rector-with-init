# API Gateway Architecture Rules

## Overview

These rules define the architectural patterns for building scalable API Gateways that aggregate data from multiple microservices. This applies to projects like SNAAPI that serve as BFF (Backend For Frontend) or API Aggregators.

## Core Architecture: Hexagonal + CQRS (Query Side)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            INFRASTRUCTURE LAYER                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │  Controller  │  │  HTTP Client │  │    Cache     │  │   Messenger  │    │
│  │   (Adapter)  │  │   (Adapter)  │  │   (Adapter)  │  │   (Adapter)  │    │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘    │
└─────────┼─────────────────┼─────────────────┼─────────────────┼────────────┘
          │                 │                 │                 │
          ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                              PORTS (Interfaces)                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ QueryHandler │  │   Gateway    │  │ CachePort    │  │  EventPort   │    │
│  │   Interface  │  │  Interface   │  │  Interface   │  │  Interface   │    │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────────────────────┘
          │                 │                 │                 │
          ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                            APPLICATION LAYER                                 │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                         Query Handlers (Use Cases)                    │   │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐         │   │
│  │  │GetEditorialById│  │GetEditorialList│  │SearchEditorials│         │   │
│  │  └────────────────┘  └────────────────┘  └────────────────┘         │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                         Aggregators / Composers                       │   │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐         │   │
│  │  │EditorialAggr.  │  │MultimediaAggr. │  │ SectionAggr.   │         │   │
│  │  └────────────────┘  └────────────────┘  └────────────────┘         │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                         Response Transformers                         │   │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐         │   │
│  │  │BodyTransformer │  │MediaTransformer│  │ TagTransformer │         │   │
│  │  └────────────────┘  └────────────────┘  └────────────────┘         │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                              DOMAIN LAYER                                    │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐             │
│  │  Value Objects  │  │   Exceptions    │  │     Enums       │             │
│  │  (ReadModels)   │  │   (Domain)      │  │   (Domain)      │             │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Directory Structure

```
src/
├── Application/
│   ├── Query/                          # CQRS Query Handlers
│   │   ├── GetEditorialById/
│   │   │   ├── GetEditorialByIdQuery.php
│   │   │   ├── GetEditorialByIdHandler.php
│   │   │   └── GetEditorialByIdResponse.php
│   │   └── GetEditorialList/
│   │       └── ...
│   │
│   ├── Aggregator/                     # Service Aggregation
│   │   ├── EditorialAggregator.php
│   │   ├── MultimediaAggregator.php
│   │   └── AggregatorInterface.php
│   │
│   ├── Transformer/                    # Response Transformers
│   │   ├── Editorial/
│   │   │   ├── EditorialTransformer.php
│   │   │   └── BodyElementTransformerChain.php
│   │   ├── Multimedia/
│   │   │   └── MultimediaTransformerChain.php
│   │   └── TransformerInterface.php
│   │
│   └── DTO/                           # Data Transfer Objects
│       ├── Request/
│       │   └── GetEditorialRequest.php
│       └── Response/
│           ├── EditorialResponse.php
│           └── MultimediaResponse.php
│
├── Domain/
│   ├── ReadModel/                     # Value Objects for reads
│   │   ├── Editorial.php
│   │   ├── Multimedia.php
│   │   ├── Section.php
│   │   └── Tag.php
│   │
│   ├── Exception/                     # Domain Exceptions
│   │   ├── EditorialNotFoundException.php
│   │   └── ServiceUnavailableException.php
│   │
│   ├── Enum/                          # Domain Enums
│   │   ├── EditorialType.php
│   │   ├── MultimediaType.php
│   │   └── Site.php
│   │
│   └── Port/                          # Interfaces (Ports)
│       ├── Gateway/
│       │   ├── EditorialGatewayInterface.php
│       │   ├── MultimediaGatewayInterface.php
│       │   ├── SectionGatewayInterface.php
│       │   ├── TagGatewayInterface.php
│       │   └── JournalistGatewayInterface.php
│       ├── Cache/
│       │   └── CacheInterface.php
│       └── Event/
│           └── EventDispatcherInterface.php
│
└── Infrastructure/
    ├── Controller/                    # HTTP Adapters (entry points)
    │   └── V1/
    │       ├── EditorialController.php
    │       └── HealthController.php
    │
    ├── Gateway/                       # HTTP Client Adapters
    │   ├── Http/
    │   │   ├── EditorialHttpGateway.php
    │   │   ├── MultimediaHttpGateway.php
    │   │   ├── SectionHttpGateway.php
    │   │   ├── TagHttpGateway.php
    │   │   └── JournalistHttpGateway.php
    │   └── Cache/
    │       └── CachedEditorialGateway.php  # Decorator
    │
    ├── Cache/                         # Cache Adapters
    │   └── RedisCacheAdapter.php
    │
    ├── Service/                       # Infrastructure Services
    │   ├── Thumbor/
    │   │   └── ThumborImageService.php
    │   └── CircuitBreaker/
    │       └── CircuitBreakerService.php
    │
    └── EventSubscriber/               # Framework Subscribers
        ├── ExceptionSubscriber.php
        └── CacheControlSubscriber.php
```

## Design Patterns

### 1. Gateway Pattern (Port & Adapter)

Every external microservice MUST be abstracted behind a Gateway interface:

```php
// Domain/Port/Gateway/EditorialGatewayInterface.php
interface EditorialGatewayInterface
{
    public function findById(EditorialId $id): ?Editorial;
    public function findByIdAsync(EditorialId $id): PromiseInterface;
}

// Infrastructure/Gateway/Http/EditorialHttpGateway.php
final class EditorialHttpGateway implements EditorialGatewayInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $baseUrl,
    ) {}

    public function findById(EditorialId $id): ?Editorial
    {
        // HTTP implementation
    }
}
```

### 2. Aggregator Pattern

Aggregators combine data from multiple gateways:

```php
// Application/Aggregator/EditorialAggregator.php
final class EditorialAggregator
{
    public function __construct(
        private readonly EditorialGatewayInterface $editorialGateway,
        private readonly MultimediaGatewayInterface $multimediaGateway,
        private readonly SectionGatewayInterface $sectionGateway,
        private readonly TagGatewayInterface $tagGateway,
        private readonly JournalistGatewayInterface $journalistGateway,
    ) {}

    public function aggregate(EditorialId $id): AggregatedEditorial
    {
        // 1. Fetch base editorial
        $editorial = $this->editorialGateway->findById($id);

        // 2. Fetch related data in parallel
        $promises = [
            'multimedia' => $this->multimediaGateway->findByIdAsync($editorial->multimediaId),
            'section' => $this->sectionGateway->findByIdAsync($editorial->sectionId),
            'tags' => $this->tagGateway->findByIdsAsync($editorial->tagIds),
            'journalists' => $this->journalistGateway->findByIdsAsync($editorial->journalistIds),
        ];

        // 3. Resolve all promises
        $results = Utils::settle($promises)->wait();

        // 4. Compose aggregated response
        return new AggregatedEditorial(
            editorial: $editorial,
            multimedia: $results['multimedia'],
            section: $results['section'],
            tags: $results['tags'],
            journalists: $results['journalists'],
        );
    }
}
```

### 3. Chain of Responsibility (Transformer Chain)

For transforming different types of content:

```php
// Application/Transformer/TransformerChainInterface.php
interface TransformerChainInterface
{
    public function supports(mixed $element): bool;
    public function transform(mixed $element): array;
}

// Registration via Compiler Pass with service tag
```

### 4. Circuit Breaker Pattern

Resilience for microservice failures:

```php
// Infrastructure/Service/CircuitBreaker/CircuitBreakerService.php
final class CircuitBreakerService
{
    private const FAILURE_THRESHOLD = 5;
    private const RECOVERY_TIMEOUT = 30;

    public function call(callable $operation, string $service): mixed
    {
        if ($this->isOpen($service)) {
            return $this->fallback($service);
        }

        try {
            $result = $operation();
            $this->recordSuccess($service);
            return $result;
        } catch (Throwable $e) {
            $this->recordFailure($service);
            throw $e;
        }
    }
}
```

### 5. Decorator Pattern (Caching)

```php
// Infrastructure/Gateway/Cache/CachedEditorialGateway.php
final class CachedEditorialGateway implements EditorialGatewayInterface
{
    public function __construct(
        private readonly EditorialGatewayInterface $innerGateway,
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600,
    ) {}

    public function findById(EditorialId $id): ?Editorial
    {
        $key = "editorial:{$id->value()}";

        return $this->cache->get($key, fn() => $this->innerGateway->findById($id), $this->ttl);
    }
}
```

## SOLID Compliance

| Principle | Implementation |
|-----------|----------------|
| **S** - Single Responsibility | Each Gateway handles ONE microservice. Each Transformer handles ONE element type. |
| **O** - Open/Closed | Add new transformers via service tags without modifying existing code. |
| **L** - Liskov Substitution | All gateways implement the same interface, interchangeable. |
| **I** - Interface Segregation | Small interfaces: `EditorialGatewayInterface` only has editorial methods. |
| **D** - Dependency Inversion | Application layer depends on Gateway interfaces, not HTTP implementations. |

## Async Processing Rules

1. **Parallel fetching**: Always use async methods when fetching from multiple services
2. **Promise resolution**: Use `GuzzleHttp\Promise\Utils::settle()` to wait for all promises
3. **Graceful degradation**: Handle rejected promises, don't fail entire request
4. **Timeout configuration**: Configure per-service timeouts in gateway adapters

## Caching Strategy

| Level | Where | TTL | Purpose |
|-------|-------|-----|---------|
| HTTP Cache | Response headers | 60-3600s | CDN/Browser caching |
| Application Cache | Redis | 300-1800s | Reduce microservice calls |
| Circuit Breaker | Memory | 30s | Prevent cascade failures |

## Testing Requirements

1. **Unit Tests**: Test transformers, aggregators in isolation with mocked gateways
2. **Integration Tests**: Test gateway implementations with real HTTP calls (mocked server)
3. **Contract Tests**: Validate gateway responses match expected schema
4. **Coverage**: 79% MSI minimum for mutation testing

## Anti-Patterns to Avoid

- **God Orchestrator**: Don't put all aggregation logic in one class
- **Leaky Abstractions**: Don't expose HTTP details in Gateway interface
- **Primitive Obsession**: Use Value Objects (EditorialId, MultimediaId)
- **Anemic Domain**: Put validation in Value Objects
- **Direct Service Calls**: Always go through Gateway interface
