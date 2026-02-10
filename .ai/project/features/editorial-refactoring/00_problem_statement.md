# Problem Statement: Editorial Refactoring

## What We're Building

A comprehensive refactoring of the editorial bounded context in SNAAPI to decompose the monolithic `EditorialOrchestrator` (590 lines, 10+ responsibilities) into focused, async-capable services while eliminating code duplication and introducing proper type safety through DTOs and value objects.

## Why It's Needed

### Current Pain Points

1. **EditorialOrchestrator god class**: 590 lines with 10+ distinct responsibilities violates SRP. Adding new editorial features (e.g., new body element types, new multimedia formats) requires modifying this single class.

2. **Code duplication**: The same `editorialUrl()` method is copy-pasted in 3 files (`DetailsAppsDataTransformer`, `BodyTagInsertedNewsDataTransformer`, `RecommendedEditorialsDataTransformer`). Multimedia shot generation is duplicated across `MultimediaTrait`, `DetailsMultimediaPhotoDataTransformer`, and multiple consumers.

3. **Sequential processing**: Inserted news and recommended editorials are fetched sequentially in loops, each making multiple synchronous HTTP calls (editorial → section → signatures → multimedia). This creates a waterfall of HTTP requests that slows API response time.

4. **Weak type safety**: All transformers return `array<string, mixed>`, requiring `@phpstan-ignore` annotations and manual type casting. PHPStan Level 9 compliance is maintained through suppression rather than proper typing.

5. **Hardcoded configuration**: Image sizes (50+ entries across 5 aspect ratios) and dimensions are defined as PHP constants in `DetailsMultimediaPhotoDataTransformer::SIZES_RELATIONS` and `MultimediaTrait::$sizes`, making them impossible to override without code changes.

## Who Benefits

- **Mobile app teams**: Faster API responses through parallelized external service calls
- **Backend developers**: Cleaner, more maintainable codebase with focused services
- **QA**: Better test isolation - each resolver can be tested independently
- **DevOps**: Potential to add caching layers to individual resolvers

## Constraints

### Technical
- **API backward compatibility**: JSON response must be byte-identical to current output (mobile apps depend on exact structure)
- **Symfony 6.4**: Must work within current framework version
- **PHP 8.2+**: Use modern PHP features (enums, readonly, named arguments)
- **PHPStan Level 9**: All new code must pass strict static analysis
- **PSR-12 + Symfony CS**: Coding standards enforced via PHP-CS-Fixer
- **79% MSI**: Mutation testing threshold via Infection

### Business
- **No downtime**: Refactoring must not affect production behavior
- **Incremental delivery**: 6 phases, each independently deployable

### Dependencies
- All external clients (`QueryEditorialClient`, `QuerySectionClient`, `QueryTagClient`, etc.) support async mode with `async: true`
- Thumbor service remains unchanged (only the calling pattern changes)
- External bundle interfaces are NOT modified

## Success Criteria

1. `EditorialOrchestrator` reduced from 590 lines to ≤ 150 lines
2. Zero duplicate `editorialUrl()` implementations (from 3 to 1)
3. `MultimediaTrait` completely removed, replaced by `MultimediaShotService`
4. All transformer `read()` methods return DTOs with `toArray()` for backward compat
5. `@phpstan-ignore` annotations reduced by ≥ 80% in editorial code
6. All independent external service calls parallelized via promises
7. `make tests` passes (full suite: CS, YAML, container, unit, static analysis, mutation)
8. JSON API response identical to current output (verified by existing tests)
