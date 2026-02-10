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
- 4 of 7 clients (Tag, Journalist, Section, Editorial) do NOT support async
- All use HTTPLug Guzzle7 (async-capable) internally
- Pattern: `$async` boolean flag -> return Promise or `->wait(true)`
- BatchRequestCollector will coordinate accumulation and batch resolution

### Next Action
`/workflows:work editorial-async-batch-collector --role=backend` to start BE-001

---

## Backend Engineer
**Status**: PENDING
**Tasks**: 8 (BE-001 to BE-008)

### Task Status
| Task | Description | Status |
|------|-------------|--------|
| BE-001 | BatchRequestCollectorInterface | PENDING |
| BE-002 | BatchRequestCollector Implementation | PENDING |
| BE-003 | Service Registration | PENDING |
| BE-004 | Inject Collector into EditorialOrchestrator | PENDING |
| BE-005 | Refactor tags accumulation | PENDING |
| BE-006 | Refactor journalists accumulation | PENDING |
| BE-007 | Refactor sections + photos accumulation | PENDING |
| BE-008 | Tags for insertadas/recomendadas | PENDING |

---

## QA
**Status**: PENDING
**Tasks**: 6 (QA-001 to QA-006)

### Task Status
| Task | Description | Status |
|------|-------------|--------|
| QA-001 | Unit Tests BatchRequestCollector | PENDING |
| QA-002 | Integration Smoke Test | PENDING |
| QA-003 | Regression Tests | PENDING |
| QA-004 | Batch Behavior Tests | PENDING |
| QA-005 | Tags Insertadas/Recomendadas Tests | PENDING |
| QA-006 | Full Quality Suite | PENDING |

---

## Phase Progress

| Phase | Status | Tasks Done | Tasks Total |
|-------|--------|------------|-------------|
| A. BatchRequestCollector | PENDING | 0 | 3 (BE-001, BE-002, BE-003) |
| B. Orchestrator Refactor | PENDING | 0 | 4 (BE-004, BE-005, BE-006, BE-007) |
| C. Tags Insertadas/Recomendadas | PENDING | 0 | 1 (BE-008) |

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
| 2026-02-10 | BatchRequestCollector pattern | Accumulate IDs, deduplicate, resolve in batch. Agnostic of client async support |
| 2026-02-10 | Clients ec/* not modified | Use as-is, concurrency via collector coordination |
| 2026-02-10 | TDD methodology | All code written test-first per 30_tasks_backend.md |

---

**State File Version**: 1.0
**Last Modified**: 2026-02-10
**Modified By**: Planner

### Modified Files (Auto-tracked)
- /home/user/rector-with-init/.ai/project/features/editorial-async-batch-collector/50_state.md (2026-02-10T21:06:15+00:00)
