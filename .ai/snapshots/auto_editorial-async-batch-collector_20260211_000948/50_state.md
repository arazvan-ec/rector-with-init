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

## QA / Reviewer
**Status**: CONDITIONALLY APPROVED
**Review Date**: 2026-02-11
**SOLID Design Score**: 21/25 (minimum 18/25)
**Critical Issues**: 0
**Medium Issues**: 4 (Security), 4 (DDD MAJOR)
**Minor Issues**: 4 (DDD), 4 (Security LOW)

### Review Summary
- **SOLID Score**: 21/25 — S:4 O:4 L:5 I:4 D:4
- **DDD**: 4 PASS, 4 MAJOR, 4 MINOR, 0 CRITICAL
- **Security**: 0 CRITICAL, 0 HIGH, 4 MEDIUM, 4 LOW
- **Tests**: UNVERIFIED (no vendor/ in review environment — must verify in CI)
- **Review Agents**: SOLID, DDD, Security (full multi-agent review)

### Decision
CONDITIONALLY APPROVED — Feature approved pending:
1. P0: Add try-catch in `resolveSubEditorialSignatures` (crash prevention)
2. All CI quality gates pass (PHPUnit, PHPStan L9, PSR-12, MSI >= 79%)

### P1 Recommendations (follow-up PR)
- Extract inserted/recommended editorial loops into shared method
- Extract `'principal'` to class constant
- Runtime type assertions on collector `get()` results

### Full Report
See `60_qa_report.md` for detailed findings from all review agents.

### Task Status
| Task | Description | Status |
|------|-------------|--------|
| QA-001 | AsyncBatchCollector Unit Tests | UNVERIFIED |
| QA-002 | Regression Tests | UNVERIFIED |
| QA-003 | Batch Behavior Tests | UNVERIFIED |
| QA-004 | Full Quality Suite | COMPLETED (multi-agent code review) |

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

**State File Version**: 3.0
**Last Modified**: 2026-02-11
**Modified By**: Session claude/refactor-editorial-async-UrZ1X

### Modified Files (Auto-tracked)
- /home/user/rector-with-init/.ai/project/features/editorial-async-batch-collector/60_qa_report.md (2026-02-11T00:09:01+00:00)
- /home/user/rector-with-init/tests/Orchestrator/Chain/EditorialOrchestratorTest.php (2026-02-10T22:39:10+00:00)
- /home/user/rector-with-init/src/Orchestrator/Chain/EditorialOrchestrator.php (2026-02-10T22:32:56+00:00)
- /home/user/rector-with-init/src/Infrastructure/Async/AsyncBatchCollector.php (2026-02-10T22:12:14+00:00)
- /home/user/rector-with-init/src/Infrastructure/Async/AsyncBatchCollectorInterface.php (2026-02-10T22:12:03+00:00)
- /home/user/rector-with-init/tests/Infrastructure/Async/AsyncBatchCollectorTest.php (2026-02-10T22:10:12+00:00)
- /home/user/rector-with-init/.ai/project/features/editorial-async-batch-collector/50_state.md (2026-02-10T21:46:00+00:00)
- FEATURE_editorial-async-batch-collector.md (2026-02-10)
- 00_requirements_analysis.md (2026-02-10)
- 10_architecture.md (2026-02-10)
- 30_tasks_backend.md (2026-02-10)
- 32_tasks_qa.md (2026-02-10)
- 50_state.md (2026-02-10)

### Test Runs (Auto-tracked)
- 2026-02-10T22:10:21+00:00: ./bin/phpunit tests/Infrastructure/Async/AsyncBatchCollectorTest.php 2>&1 | tail -20
