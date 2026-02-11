# State: Editorial Refactoring

## Current Phase: SPECS CREATED - AWAITING REVIEW

## Progress

| Phase | Status | Notes |
|-------|--------|-------|
| Phase 1: Foundation Layer | PENDING | Enums, VOs, MultimediaShotService |
| Phase 2: Shared Infrastructure | PENDING | URL trait, remove MultimediaTrait |
| Phase 3: DTOs | PENDING | Response DTOs for transformers |
| Phase 4: Service Extraction | PENDING | Orchestrator decomposition |
| Phase 5: Async Optimization | PENDING | Promise batching + Messenger |
| Phase 6: Cleanup | PENDING | Dead code removal, final verification |

## Specs Status

| Spec | Status | Open Questions |
|------|--------|---------------|
| SPEC-01: Orchestrator Decomposition | WRITTEN | Q1 RESOLVED: Resolvers return DTOs |
| SPEC-02: UrlGeneratorTrait | WRITTEN | - |
| SPEC-03: MultimediaShotService | WRITTEN | - |
| SPEC-04: Response DTOs | WRITTEN | Q2 RESOLVED: Single BodyElementDto |
| SPEC-05: AspectRatio & ImageSize | WRITTEN | - |
| SPEC-06: Hybrid Async | WRITTEN | Q3 RESOLVED: All clients support async |
| SPEC-07: MembershipLinkResolver | WRITTEN | - |

## Resolved Questions

### Q1 (SPEC-01): Resolver Return Types
**Answer**: DTOs. Resolvers return typed DTOs (InsertedNewsData, RecommendedEditorialData). Aligns with DDD layers.

### Q2 (SPEC-04): BodyElementDto Design
**Answer**: Single class with type discriminator. One BodyElementDto with optional fields, no 18 subclasses.

### Q3 (SPEC-06): External Client Async Support
**Answer**: All clients support async. QueryEditorialClient, QuerySectionClient, QueryTagClient all support `async: true`. Full parallelization possible in Phase 5.

## Decision Log

See [FEATURE_editorial-refactoring.md](./FEATURE_editorial-refactoring.md) for full decision table.

### Modified Files (Auto-tracked)
- /home/user/rector-with-init/.ai/project/features/editorial-refactoring/12_specs.md (2026-02-10T22:11:41+00:00)
- /home/user/rector-with-init/.ai/project/features/editorial-refactoring/50_state.md (2026-02-10T22:07:27+00:00)
