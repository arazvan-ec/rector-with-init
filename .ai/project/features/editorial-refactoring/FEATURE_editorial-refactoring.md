# Feature: Editorial Refactoring

## Summary

Comprehensive refactoring of the editorial bounded context (~75 files) to improve async processing, eliminate code duplication, introduce proper DTOs, and decompose the monolithic EditorialOrchestrator into focused services.

## Motivation

The current editorial flow works correctly but suffers from:
1. **EditorialOrchestrator god class** (~590 lines, 10+ responsibilities)
2. **Code duplication** across 3 patterns: URL generation, multimedia shots, editorial fetching
3. **Weak type safety** with `array<string, mixed>` throughout transformers
4. **Hardcoded configuration** for image sizes and aspect ratios
5. **Suboptimal async handling** - not all operations parallelized

## Scope

### In Scope
- Decompose `EditorialOrchestrator` into focused services
- Extract shared services: `MultimediaShotService`, improved `UrlGeneratorTrait`
- Introduce DTOs for transformer responses
- Create `AspectRatio` enum and `ImageSize` value objects
- Hybrid async strategy: Promises for critical data + Messenger for secondary ops
- Extract `MembershipLinkResolver`, `InsertedNewsResolver`, `RecommendedEditorialsResolver`
- Create `SignatureResolver` for journalist/alias lookup

### Out of Scope
- API response structure changes (100% backward compatible)
- New API endpoints
- Changes to external client interfaces (bundles)
- Frontend/consumer changes

## Constraints
- **API backward compatibility**: JSON output must remain identical
- **PHPStan Level 9**: All new code must pass strict static analysis
- **79% MSI minimum**: Mutation testing threshold must be maintained
- **PSR-12 + Symfony CS**: Coding standards enforced
- **PHP 8.2+**: Use modern PHP features (enums, readonly, named args)

## Decision Log

| # | Decision | Choice | Rationale |
|---|----------|--------|-----------|
| 1 | Orchestrator decomposition | Services + max async | Enable parallel processing |
| 2 | Code deduplication | Maximum cleanup | DRY principle, fewer bugs |
| 3 | Type safety | Introduce DTOs | PHPStan L9 compliance, self-documenting |
| 4 | Image config | Enums + Value Objects | Domain concept, not environment config |
| 5 | Async strategy | Hybrid (Promises + Messenger) | Critical data via promises, secondary via Messenger |
| 6 | API compatibility | 100% retrocompatible | Mobile apps depend on exact JSON structure |
| 7 | Execution strategy | Specs first, then execute | Methodical, reviewable approach |
| 8 | InsertedNews/Recommended | Two services + shared trait | SRP with DRY via trait |
| 9 | Traits strategy | Hybrid: Multimedia->Service, URL->Trait | Complex logic as service, simple as trait |

## Specs

- [SPEC-01: Decompose EditorialOrchestrator](./12_specs.md#spec-01)
- [SPEC-02: EditorialUrlBuilder](./12_specs.md#spec-02)
- [SPEC-03: MultimediaShotService](./12_specs.md#spec-03)
- [SPEC-04: Response DTOs](./12_specs.md#spec-04)
- [SPEC-05: AspectRatio & ImageSize](./12_specs.md#spec-05)
- [SPEC-06: Hybrid Async Strategy](./12_specs.md#spec-06)
- [SPEC-07: MembershipLinkResolver](./12_specs.md#spec-07)

## Execution Phases

See [30_tasks_backend.md](./30_tasks_backend.md) for detailed phase ordering.
