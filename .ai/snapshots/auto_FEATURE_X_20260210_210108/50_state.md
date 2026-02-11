# Feature State: FEATURE_X

> This file tracks the state of each role and provides completion signals.
> Update your section when completing checkpoints or changing status.

**Feature**: FEATURE_X
**Workflow**: default
**Created**: 2026-01-15
**Last Updated**: 2026-01-16

---

## Planner

| Field | Value |
|-------|-------|
| **Status** | `PENDING` |
| **Completion Signal** | `false` |
| **Started** | - |
| **Completed** | - |

### Progress
- [ ] Requirements analysis
- [ ] Architecture design
- [ ] API contracts
- [ ] Task breakdown

### Artifacts Created
<!-- List artifacts created by Planner -->

### Notes
Feature initialized, waiting for planning to start.

### Next Action
Start planning when feature requirements are provided.

---

## Backend

| Field | Value |
|-------|-------|
| **Status** | `PENDING` |
| **Completion Signal** | `false` |
| **Started** | - |
| **Completed** | - |
| **Depends On** | Planner |

### Checkpoints
- [ ] Domain Layer
- [ ] Application Layer
- [ ] Infrastructure Layer
- [ ] API Endpoints

### Auto-Correction Loop
| Checkpoint | Iterations | Result |
|------------|------------|--------|
| - | - | - |

### Artifacts Created
<!-- List files created by Backend -->

### Test Results
```
# Paste test output here
```

### Coverage
- **Lines**: -%
- **Branches**: -%

### Blockers
Planning not completed yet.

### Next Action
Wait for Planner.completion_signal == true

---

## Frontend

| Field | Value |
|-------|-------|
| **Status** | `PENDING` |
| **Completion Signal** | `false` |
| **Started** | - |
| **Completed** | - |
| **Depends On** | Planner |
| **API Ready** | `false` |

### Checkpoints
- [ ] Component Structure
- [ ] Form Logic
- [ ] API Integration
- [ ] Responsive & A11y

### Auto-Correction Loop
| Checkpoint | Iterations | Result |
|------------|------------|--------|
| - | - | - |

### Artifacts Created
<!-- List files created by Frontend -->

### Test Results
```
# Paste test output here
```

### Lighthouse Scores
| Metric | Score |
|--------|-------|
| Performance | - |
| Accessibility | - |
| Best Practices | - |
| SEO | - |

### Responsive Testing
- [ ] Mobile (375px)
- [ ] Tablet (768px)
- [ ] Desktop (1024px)

### Blockers
Planning not completed yet.

### Next Action
Wait for Planner.completion_signal == true

---

## QA

| Field | Value |
|-------|-------|
| **Status** | `PENDING` |
| **Completion Signal** | `false` |
| **Started** | - |
| **Completed** | - |
| **Depends On** | Backend, Frontend |
| **Decision** | - |

### Testing Phases
- [ ] Phase 1: API Testing
- [ ] Phase 2: UI Testing
- [ ] Phase 3: Automated Tests
- [ ] Phase 4: Code Quality
- [ ] Phase 5: Acceptance Criteria

### Findings Summary
| Severity | Count |
|----------|-------|
| Critical | 0 |
| Major | 0 |
| Minor | 0 |

### Acceptance Criteria
| Criterion | Status | Evidence |
|-----------|--------|----------|
| - | - | - |

### Decision
<!-- APPROVED or REJECTED with reasoning -->

### Next Action
Wait for Backend.completion_signal == true AND Frontend.completion_signal == true

---

## Status Reference

### Valid Statuses
- `PENDING` - Not started
- `IN_PROGRESS` - Currently working
- `BLOCKED` - Cannot proceed (document blocker)
- `WAITING_API` - Frontend waiting for backend API
- `COMPLETED` - Finished successfully
- `APPROVED` - QA approved
- `REJECTED` - QA rejected (list issues)

### Completion Signal
- `true` - Role has finished all work
- `false` - Role still has work to do

### Workflow Trigger Conditions
- **Backend/Frontend start**: Planner.completion_signal == true
- **QA start**: Backend.completion_signal == true AND Frontend.completion_signal == true
- **Feature done**: QA.status == APPROVED

---

## Comprehension Tracking

> Track comprehension debt to ensure sustainable development velocity

### Current Assessment

| Field | Value |
|-------|-------|
| **Debt Level** | 🟢 LOW / 🟡 MEDIUM / 🔴 HIGH |
| **Last Checkpoint** | - |
| **Knowledge Score** | -/5 |
| **Next Check Due** | After first checkpoint |

### Debt Indicators

| Indicator | Count | Notes |
|-----------|-------|-------|
| "Magic" code incidents | 0 | Code that works but isn't understood |
| Patterns copied without understanding | 0 | Patterns used without knowing why |
| Over-engineering flags | 0 | YAGNI violations |
| Unexplained abstractions | 0 | Abstractions without documented purpose |

### Self-Review Status

| Role | Self-Review Done | Score | Issues Found |
|------|------------------|-------|--------------|
| Backend | ⬜ Pending | - | - |
| Frontend | ⬜ Pending | - | - |

### Comprehension Checkpoints

| Checkpoint | Date | Score | Action Taken |
|------------|------|-------|--------------|
| - | - | - | - |

### Knowledge Gaps Identified

<!-- Document any areas where understanding is weak -->
None identified yet.

### Recommended Actions

<!-- Actions to reduce comprehension debt -->
Feature not started - no actions needed yet.

---

## Session Resume Info

> Fill this when ending a session to help resume later

### Last Session
| Field | Value |
|-------|-------|
| **Role** | - |
| **Date** | - |
| **Last Checkpoint** | - |
| **Context Status** | Fresh |

### Resume Instructions
This is a fresh feature. Start with Planner role.

### Important Context
No context yet - feature not started.

---

## Status History

| Date | Role | From | To | Reason |
|------|------|------|-----|--------|
| 2026-01-15 | All | - | PENDING | Feature initialized |
| 2026-01-16 | - | - | - | Updated to new completion signal format |

### Modified Files (Auto-tracked)
- /home/user/workflow/.ai/project/analysis/soul-md-analysis.md (2026-02-10T18:00:23+00:00)
- /home/user/workflow/README.md (2026-02-09T02:39:35+00:00)
- /home/user/workflow/INDEX.md (2026-02-09T02:38:39+00:00)
- /home/user/workflow/GLOSSARY.md (2026-02-09T02:38:17+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/commands/workflows/plan.md (2026-02-09T02:36:52+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/commands/workflows/shape.md (2026-02-09T02:36:15+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/breadboarder/SKILL.md (2026-02-09T02:35:25+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/shaper/SKILL.md (2026-02-09T02:34:43+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/core/roles/frontend.md (2026-02-09T00:25:55+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/core/roles/backend.md (2026-02-09T00:25:33+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/commands/workflows/work.md (2026-02-09T00:25:16+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/core/docs/SESSION_CONTINUITY.md (2026-02-08T23:36:34+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/commands/workflows/parallel.md (2026-02-08T23:35:00+00:00)
- /home/user/workflow/.ai/project/config.yaml (2026-02-08T23:24:35+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/core/rules/framework_rules.md (2026-02-08T23:24:21+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/core/docs/CAPABILITY_PROVIDERS.md (2026-02-08T23:23:29+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/core/providers.yaml (2026-02-08T23:22:23+00:00)
- /home/user/workflow/.ai/project/features/context-engineering-v2/30_tasks.md (2026-02-08T20:39:55+00:00)
- /home/user/workflow/.ai/project/features/context-engineering-v2/15_solutions.md (2026-02-08T20:39:46+00:00)
- /home/user/workflow/.ai/project/features/context-engineering-v2/12_specs.md (2026-02-08T20:39:13+00:00)
- /home/user/workflow/.ai/project/features/context-engineering-v2/00_problem_statement.md (2026-02-08T20:38:54+00:00)
- /home/user/workflow/docs/solid-design-patterns-guide.md (2026-02-06T02:57:19+00:00)
- /home/user/workflow/QUICKSTART.md (2026-02-05T21:20:33+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/commands/workflows/help.md (2026-02-05T21:20:14+00:00)
- /home/user/workflow/WELCOME.md (2026-02-05T21:20:08+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/commands/workflows/onboarding.md (2026-02-05T21:20:07+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/core/docs/CONTEXT_ENGINEERING.md (2026-02-05T21:12:42+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/README.md (2026-02-05T21:10:01+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/.claude-plugin/plugin.json (2026-02-05T21:04:27+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/CLAUDE.md (2026-02-05T21:03:01+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/commands/workflows/route.md (2026-02-05T21:01:50+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/commands/workflows/skill-dev.md (2026-02-05T21:01:12+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/worktree-manager/SKILL.md (2026-02-05T21:00:35+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/git-sync/SKILL.md (2026-02-05T21:00:33+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/commit-formatter/SKILL.md (2026-02-05T21:00:33+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/lint-fixer/SKILL.md (2026-02-05T21:00:30+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/checkpoint/SKILL.md (2026-02-05T21:00:29+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/test-runner/SKILL.md (2026-02-05T21:00:26+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/layer-validator/SKILL.md (2026-02-05T21:00:23+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/agents/review/pattern-recognition-specialist.md (2026-02-05T21:00:20+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/agents/review/code-simplicity-reviewer.md (2026-02-05T21:00:20+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/agents/review/agent-native-reviewer.md (2026-02-05T21:00:18+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/agents/review/code-review-ts.md (2026-02-05T21:00:17+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/agents/review/ddd-compliance.md (2026-02-05T21:00:16+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/agents/review/performance-review.md (2026-02-05T21:00:14+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/agents/review/security-review.md (2026-02-05T21:00:11+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/mcp-connector.md (2026-02-05T20:59:04+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/workflow-skill-solid-analyzer.md (2026-02-05T20:59:03+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/spec-merger/SKILL.md (2026-02-05T20:59:00+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/changelog-generator/SKILL.md (2026-02-05T20:58:59+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/coverage-checker/SKILL.md (2026-02-05T20:58:58+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/token-advisor.md (2026-02-05T20:58:56+00:00)
- /home/user/workflow/plugins/multi-agent-workflow/skills/consultant/SKILL.md (2026-02-05T20:58:03+00:00)
- /home/user/workflow/.ai/project/features/snaapi-scalable-architecture/30_tasks_backend.md (2026-02-02T22:58:31+00:00)
- /home/user/workflow/.ai/project/features/snaapi-scalable-architecture/10_architecture.md (2026-02-02T22:57:49+00:00)
- /home/user/workflow/.ai/project/features/snaapi-scalable-architecture/FEATURE_snaapi-scalable-architecture.md (2026-02-02T22:56:42+00:00)
- /home/user/workflow/.ai/extensions/rules/api_gateway_architecture.md (2026-02-02T22:56:05+00:00)
- /home/user/workflow/.gitignore (2026-02-02T20:44:58+00:00)
