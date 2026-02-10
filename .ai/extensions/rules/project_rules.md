# Project Rules - SNAAPI

**Project**: SNAAPI (API Gateway)
**Last Updated**: 2026-02-02
**Version**: 2.0

---

## Purpose

This file contains **project-specific rules** for SNAAPI, an API Gateway that aggregates content from multiple microservices for mobile applications.

**Base Framework Rules**: See `plugins/multi-agent-workflow/core/rules/framework_rules.md`
**Architecture Rules**: See `.ai/extensions/rules/api_gateway_architecture.md`

---

## Project Stack

### Backend
- **Language**: PHP 8.1+
- **Framework**: Symfony 6.4
- **Architecture**: Hexagonal + CQRS (Query side)
- **HTTP Client**: HTTPlug + Guzzle 7
- **Async**: Guzzle Promises

### External Microservices
| Service | Domain | Client Package |
|---------|--------|----------------|
| Editorial | Content articles | ec/editorial-domain |
| Multimedia | Photos, videos, widgets | ec/multimedia-domain |
| Section | Content hierarchy | ec/section-domain |
| Tag | Content tags | ec/tag-domain |
| Journalist | Authors | ec/journalist-domain |
| Membership | Subscriptions | ec/membership-domain |
| Widget | Embedded widgets | ec/widget-domain |

### Infrastructure
- **Container**: Docker + docker-compose
- **CI/CD**: GitLab CI
- **Messaging**: RabbitMQ (via Symfony Messenger)
- **Cache**: Redis (for response caching)

---

## Testing Requirements

### Mandatory Tests

| Type | Coverage | Framework |
|------|----------|-----------|
| Unit | 80% minimum | PHPUnit 10 |
| Mutation | 79% MSI minimum | Infection |
| Static Analysis | Level 9 | PHPStan |
| Code Style | PSR-12 + Symfony | PHP-CS-Fixer |

### Test Commands

```bash
# Full test suite (MUST pass before PR)
make tests

# Individual commands
make test_unit       # PHPUnit
make test_stan       # PHPStan Level 9
make test_cs         # PHP-CS-Fixer
make test_infection  # Mutation testing

# Run single test
./bin/phpunit tests/Path/To/TestFile.php --filter testMethodName
```

### Test Patterns

1. **Use DataProviders** for parameterized tests
2. **Use `#[CoversClass]`** attribute for coverage
3. **Mock all Gateway dependencies** in unit tests
4. **Compare JSON output** for backward compatibility tests

---

## Code Style

### PHP Standards

```php
<?php

declare(strict_types=1);

namespace App\Application\Query\GetEditorialById;

final readonly class GetEditorialByIdHandler
{
    public function __construct(
        private EditorialAggregator $aggregator,
        private EditorialResponseTransformer $transformer,
    ) {}

    public function __invoke(GetEditorialByIdQuery $query): GetEditorialByIdResponse
    {
        // Implementation
    }
}
```

**Rules**:
- `declare(strict_types=1)` in ALL files
- `readonly` classes when possible
- Constructor property promotion
- `final` for non-extended classes
- Named arguments for complex calls

### Run Before Commit

```bash
make test_cs    # Auto-fixes style
make test_stan  # Type checking
```

---

## Architecture Rules

### Layer Dependencies

```
✓ Controller → Query Handler (allowed)
✓ Query Handler → Aggregator (allowed)
✓ Aggregator → Gateway Interface (allowed)
✓ Gateway Impl → External Client (allowed)

✗ Controller → Gateway (forbidden - skip layers)
✗ Aggregator → HTTP Client (forbidden - must use interface)
✗ Domain → Infrastructure (forbidden - wrong direction)
```

### Gateway Pattern (Mandatory)

All external microservice calls MUST go through a Gateway interface:

```php
// ✓ CORRECT
public function __construct(
    private EditorialGatewayInterface $editorialGateway, // Interface
) {}

// ✗ WRONG
public function __construct(
    private QueryEditorialClient $client, // Concrete class
) {}
```

### Async Processing (Recommended)

When fetching from multiple services, use async:

```php
// ✓ CORRECT - Parallel fetching
$promises = [
    'multimedia' => $this->multimediaGateway->findByIdAsync($id),
    'section' => $this->sectionGateway->findByIdAsync($id),
];
$results = Utils::settle($promises)->wait();

// ✗ WRONG - Sequential fetching
$multimedia = $this->multimediaGateway->findById($id);
$section = $this->sectionGateway->findById($id);
```

### Transformer Chain

Use Chain of Responsibility for element transformation:

```php
// Register transformers via service tags
services:
    App\Application\Transformer\Editorial\Element\:
        resource: '../src/Application/Transformer/Editorial/Element/*'
        tags: ['app.body_element_transformer']
```

---

## Git Workflow

### Branching Strategy

- **main**: Production releases
- **develop**: Active development
- **feature/[name]**: New features
- **refactor/[name]**: Architecture changes
- **bugfix/[name]**: Bug fixes

### Commit Messages

```
<type>(<scope>): <subject>

<body>

<footer>
```

Types for SNAAPI:
- `feat(gateway)`: New gateway implementation
- `feat(transformer)`: New transformer
- `feat(aggregator)`: Aggregation logic
- `refactor(arch)`: Architecture changes
- `fix(editorial)`: Editorial-related fix
- `test(unit)`: Unit tests
- `chore(deps)`: Dependency updates

---

## Quality Metrics

| Metric | Threshold | Tool |
|--------|-----------|------|
| Test Coverage | > 80% | PHPUnit |
| Mutation Score | > 79% | Infection |
| Static Analysis | Level 9 | PHPStan |
| Code Style | 0 errors | PHP-CS-Fixer |

---

## Security Requirements

### API Gateway Specific

1. **No data persistence**: This is a read-only gateway
2. **No authentication logic**: Delegate to upstream services
3. **Rate limiting**: Implement at infrastructure level
4. **Timeout handling**: 2s default, configurable per service
5. **Circuit breaker**: Prevent cascade failures

### Forbidden

- Storing user data locally
- Implementing auth logic
- Direct database access
- Exposing internal service errors to clients

---

## Definition of Done

A task is **DONE** when:

- [ ] Code implements the task requirements
- [ ] PHPStan Level 9 passes
- [ ] PHPUnit tests pass (coverage > 80%)
- [ ] Mutation testing passes (MSI > 79%)
- [ ] PHP-CS-Fixer shows no errors
- [ ] JSON response matches existing API (if refactoring)
- [ ] `50_state.md` updated with status

---

## Microservice Integration

### Adding a New Microservice

1. Create interface in `Domain/Port/Gateway/`
2. Create HTTP implementation in `Infrastructure/Gateway/Http/`
3. Configure in `config/packages/[service]/infrastructure.yaml`
4. Register with Compiler Pass if needed
5. Add to Aggregator

### HTTP Client Configuration

```yaml
# config/packages/[service]/infrastructure.yaml
services:
    App\Infrastructure\Gateway\Http\[Service]HttpGateway:
        arguments:
            $client: '@httplug.client.app_guzzle7'
            $baseUrl: '%env(SERVICE_HOST)%'
        lazy: true
```

---

## Patterns Reference

| Pattern | Where Used | Example |
|---------|------------|---------|
| Gateway (Port & Adapter) | External services | `EditorialGatewayInterface` |
| Chain of Responsibility | Orchestration | `OrchestratorChainHandler` |
| Strategy | Transformation | `BodyElementTransformerHandler` |
| Decorator | Cross-cutting | `CachedGatewayDecorator` |
| Compiler Pass | Service registration | `BodyDataTransformerCompiler` |

---

**Last update**: 2026-02-02
**Updated by**: Planner
**Recent changes**: Configured for SNAAPI refactoring with Hexagonal + CQRS architecture
**Next review**: After Phase 1 completion
