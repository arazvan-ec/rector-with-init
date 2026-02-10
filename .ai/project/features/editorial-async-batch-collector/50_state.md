# Feature State: Editorial Async Batch Collector

## Overview
**Feature**: editorial-async-batch-collector
**Workflow**: task-breakdown
**Status**: PLANNING_COMPLETE
**Created**: 2026-02-10
**Updated**: 2026-02-10

---

## Planner
**Status**: COMPLETED

### Artifacts Created
- [x] FEATURE_editorial-async-batch-collector.md
- [x] 00_requirements_analysis.md
- [x] 10_architecture.md
- [x] 30_tasks_backend.md
- [x] 32_tasks_qa.md
- [x] 50_state.md

### Artifacts Skipped (justified)
- 15_data_model.md - No new data models, uses existing domain objects + 1 new class (AsyncBatchCollector)
- 20_api_contracts.md - No new endpoints, internal refactor only
- 31_tasks_frontend.md - No frontend in this project
- 35_dependencies.md - Dependencies are existing ec/* packages, documented in 00_requirements_analysis.md

### Key Findings
- All 7 clients support `$async` boolean flag
- AsyncBatchCollector provides configurable async grouping with chaining
- Journalist dedup: 1 HTTP call per aliasId, N transforms with different contexts
- isVisible(): 2-round async for insertadas/recomendadas (editorial resolve → filter → deps)
- Tags of insertadas/recomendadas intentionally excluded (product decision)
- Comments and opening multimedia included in async scope

### Next Action
`/workflows:work editorial-async-batch-collector --role=backend` to start BE-001

---

## Backend Engineer
**Status**: PENDING
**Tasks**: 10 (BE-001 to BE-010)

### Task Status by PR

#### PR1: AsyncBatchCollector + Principal
| Task | Description | Status |
|------|-------------|--------|
| BE-001 | AsyncBatchCollector class | PENDING |
| BE-002 | Tags principal async | PENDING |
| BE-003 | Journalists principal async + dedup | PENDING |
| BE-004 | Photos body tags async | PENDING |
| BE-005 | Comments async | PENDING |
| BE-006 | Opening multimedia async | PENDING |

#### PR2: Insertadas Async
| Task | Description | Status |
|------|-------------|--------|
| BE-007 | Insertadas editorial fetches (Ronda 1) | PENDING |
| BE-008 | Insertadas dependencias + journalist dedup (Ronda 2) | PENDING |

#### PR3: Recomendadas Async
| Task | Description | Status |
|------|-------------|--------|
| BE-009 | Recomendadas editorial fetches (Ronda 1) | PENDING |
| BE-010 | Recomendadas dependencias + full quality (Ronda 2) | PENDING |

---

## QA
**Status**: PENDING
**Tasks**: 4 (QA-001 to QA-004)

### Task Status
| Task | Description | Status |
|------|-------------|--------|
| QA-001 | AsyncBatchCollector Unit Tests | PENDING |
| QA-002 | Regression Tests | PENDING |
| QA-003 | Batch Behavior Tests | PENDING |
| QA-004 | Full Quality Suite | PENDING |

---

## Phase Progress

| PR | Status | Tasks Done | Tasks Total |
|----|--------|------------|-------------|
| PR1: AsyncBatchCollector + Principal | PENDING | 0 | 6 (BE-001 to BE-006) |
| PR2: Insertadas Async | PENDING | 0 | 2 (BE-007, BE-008) |
| PR3: Recomendadas Async | PENDING | 0 | 2 (BE-009, BE-010) |

---

## Blockers
None

## Quality Gates
- [ ] PHPStan level 9: 0 errors
- [ ] PHPUnit: all tests pass
- [ ] Mutation testing: MSI >= 79%
- [ ] PSR-12 + Symfony coding standards
- [ ] API v1 backward compatibility

## Decisions Log
| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-02-10 | No pipeline assumption | Previous snaapi-scalable-architecture was experimental, not adopted |
| 2026-02-10 | All clients support $async | All ec/* clients support $async flag. No decorators/wrappers needed |
| 2026-02-10 | TDD methodology | All code written test-first per 30_tasks_backend.md |
| 2026-02-10 | AsyncBatchCollector abstraction | Configurable async grouping with add/settle/get API. Replaces raw Utils::settle() calls. Justified by use in 3+ PRs |
| 2026-02-10 | 3 PRs incremental delivery | PR1: infra+principal, PR2: insertadas, PR3: recomendadas. Each PR testeable independently |
| 2026-02-10 | Journalist dedup HTTP + multi-transform | 1 HTTP call per aliasId, N transforms with different (section, hasTwitter) contexts |
| 2026-02-10 | isVisible 2-round async | Ronda 1: resolve editorials → filter visible → Ronda 2: dependencies only for visible. Avoids ~5 wasted calls per hidden editorial |
| 2026-02-10 | Tags insertadas/recomendadas excluded | Product decision — intentionally not fetched. Removed from scope |
| 2026-02-10 | Comments included in async | findCommentsByEditorialId moved to async via collector |
| 2026-02-10 | Opening multimedia included in async | findMultimediaOpeningById moved to async via collector |

---

**State File Version**: 2.0
**Last Modified**: 2026-02-10
**Modified By**: Session claude/refactor-editorial-async-UrZ1X

### Modified Files (Auto-tracked)
- /home/user/rector-with-init/.ai/project/features/editorial-async-batch-collector/50_state.md (2026-02-10T21:46:00+00:00)
- FEATURE_editorial-async-batch-collector.md (2026-02-10)
- 00_requirements_analysis.md (2026-02-10)
- 10_architecture.md (2026-02-10)
- 30_tasks_backend.md (2026-02-10)
- 32_tasks_qa.md (2026-02-10)
- 50_state.md (2026-02-10)
