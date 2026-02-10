# Feature State: Editorial Async Batch Collector

## Overview
**Feature**: editorial-async-batch-collector
**Workflow**: task-breakdown
**Status**: PLANNING_COMPLETE
**Created**: 2026-02-10

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
- 15_data_model.md - No new data models, uses existing domain objects
- 20_api_contracts.md - No new endpoints, internal refactor only
- 31_tasks_frontend.md - No frontend in this project
- 35_dependencies.md - Dependencies are existing ec/* packages, documented in 00_requirements_analysis.md

### Key Findings
- All 7 clients support (or will support) `$async` boolean flag
- Pattern: `$async = true` -> return Promise, default -> `->wait(true)`
- No BatchRequestCollector needed - direct async calls on existing clients
- No new files to create - refactor entirely within EditorialOrchestrator

### Next Action
`/workflows:work editorial-async-batch-collector --role=backend` to start BE-001

---

## Backend Engineer
**Status**: PENDING
**Tasks**: 6 (BE-001 to BE-006)

### Task Status
| Task | Description | Status |
|------|-------------|--------|
| BE-001 | Async batch editorials insertadas + recomendadas | PENDING |
| BE-002 | Async batch tags | PENDING |
| BE-003 | Async batch journalists | PENDING |
| BE-004 | Async batch sections | PENDING |
| BE-005 | Async batch photos body tags | PENDING |
| BE-006 | Tags for insertadas/recomendadas (new data) | PENDING |

---

## QA
**Status**: PENDING
**Tasks**: 4 (QA-001 to QA-004)

### Task Status
| Task | Description | Status |
|------|-------------|--------|
| QA-001 | Regression Tests | PENDING |
| QA-002 | Batch Behavior Tests | PENDING |
| QA-003 | Tags Insertadas/Recomendadas Tests | PENDING |
| QA-004 | Full Quality Suite | PENDING |

---

## Phase Progress

| Phase | Status | Tasks Done | Tasks Total |
|-------|--------|------------|-------------|
| A. Batch Editorials | PENDING | 0 | 1 (BE-001) |
| B. Batch Dependencias | PENDING | 0 | 4 (BE-002, BE-003, BE-004, BE-005) |
| C. Tags Insertadas/Recomendadas | PENDING | 0 | 1 (BE-006) |

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
| 2026-02-10 | 3-phase execution model | Dependencies between editorial -> children -> dependencies require sequential phases |
| 2026-02-10 | Skip frontend/API docs | Backend-only refactor, no new endpoints or data models |
| 2026-02-10 | All clients support $async | Assumption: all ec/* clients support $async flag. No decorators/wrappers needed |
| 2026-02-10 | No BatchRequestCollector | Direct async calls on existing clients. No new classes to create |
| 2026-02-10 | TDD methodology | All code written test-first per 30_tasks_backend.md |

---

**State File Version**: 1.1
**Last Modified**: 2026-02-10
**Modified By**: Planner

### Modified Files (Auto-tracked)
- /home/user/rector-with-init/.ai/project/features/editorial-async-batch-collector/FEATURE_editorial-async-batch-collector.md (2026-02-10T21:12:20+00:00)
- /home/user/rector-with-init/.ai/project/features/editorial-async-batch-collector/50_state.md (2026-02-10T21:12:06+00:00)
