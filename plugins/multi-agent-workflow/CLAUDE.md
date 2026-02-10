# Multi-Agent Workflow Plugin

A compound engineering framework for coordinating multiple AI agents in parallel on software development.

## The Flow (5 steps, in order)

```
  ROUTE ──> SHAPE ──> PLAN ──> WORK ──> REVIEW ──> COMPOUND
  (entry)  (optional)  (80%)   (15%)     (4%)       (1%)
```

**Every request starts with routing.** No exceptions.

### Core Commands

| # | Command | Purpose | When |
|---|---------|---------|------|
| 0 | `/workflows:route` | Classify request, ask questions, select workflow | **Always first** |
| 1 | `/workflows:shape` | Separate problem from solution, spike unknowns | Complex/unclear features only |
| 2 | `/workflows:plan` | Architecture-first planning with SOLID constraint | Before any implementation |
| 3 | `/workflows:work` | Execute implementation with TDD + Ralph Wiggum Loop | After plan is COMPLETED |
| 4 | `/workflows:review` | Multi-agent quality review | After work is COMPLETED |
| 5 | `/workflows:compound` | Capture learnings for future acceleration | After review is APPROVED |

### Flow Guards (enforced)

- `plan` requires: routing completed
- `work` requires: plan status = COMPLETED in `50_state.md`
- `review` requires: work status = COMPLETED in `50_state.md`
- `compound` requires: review status = APPROVED in `50_state.md`

---

## Support Commands

Use these when needed during the flow, not as primary workflow steps.

| Command | Purpose | Typical usage |
|---------|---------|---------------|
| `/workflows:quickstart` | Interactive onboarding for new projects | **First time setup** — auto-detects stack, configures plugin |
| `/workflows:status` | View all roles' progress | Check where things stand |
| `/workflows:help` | Quick reference and guidance | When lost or unsure |
| `/workflows:specs` | Manage living specifications | Before planning (discover existing specs) |
| `/workflows:discover` | Auto-analyze project architecture | First time with a new project |

---

## Automatic Operations (do NOT invoke manually)

The following are handled automatically by the core commands above. They exist as commands for edge cases but should **not** be part of the normal flow:

| Operation | Triggered automatically by | Manual command (edge case only) |
|-----------|---------------------------|--------------------------------|
| Git sync | `plan`, `work` (Step 2: Git Sync) | `/workflows:sync` |
| Checkpoint/save progress | `work` (Step 7: Checkpoint) | `/workflows:checkpoint` |
| Session snapshot | `work` (when context > 70%) | `/workflows:snapshot` |
| Restore session | Session start with existing `50_state.md` | `/workflows:restore` |
| TDD enforcement | `work` (Step 5: TDD cycle) | `/workflows:tdd` |
| Trust evaluation | `route` (routing logic) | `/workflows:trust` |
| Spec validation | `plan` (Phase 2: Specs) | `/workflows:validate` |
| Comprehension check | `review` (quality gates) | `/workflows:comprehension` |
| Criteria evaluation | `plan` (Phase 3: SOLID) | `/workflows:criteria` |
| Parallelization | `work --mode=roles` (auto-detects provider) | `/workflows:parallel` |
| Progress tracking | `work` (state updates in `50_state.md`) | `/workflows:progress` |
| Monitoring | `work --mode=roles` (parallel mode) | `/workflows:monitor` |
| SOLID refactoring | `review` (when score < 18/25) | `/workflows:solid-refactor` |
| Role assignment | `work --role=X` | `/workflows:role` |
| Metrics collection | `compound` (performance analysis) | `/workflows:metrics` |

---

## Developer-Only Commands

These are for plugin development, not for feature work:

| Command | Purpose |
|---------|---------|
| `/workflows:skill-dev` | Create/edit/test plugin skills |
| `/workflows:heal-skill` | Fix broken skill definitions |
| `/workflows:reload` | Hot-reload skills/agents mid-session |

---

## Routing Protocol

Every interaction passes through the workflow router before any work begins.

1. **Analyze** the user's request
2. **Classify** the work type (feature, bug, refactor, investigation, etc.)
3. **Assess** complexity and scope
4. **Ask clarifying questions** if confidence < 60%
5. **Route** to the appropriate workflow via `/workflows:route`

When the request is ambiguous, ask before acting. See `core/docs/ROUTING_REFERENCE.md` for question templates and the decision matrix.

Exception: continuing an already-routed task with valid `50_state.md` context.

## Agents

| Category | Agents | Invoked by |
|----------|--------|------------|
| Roles (4) | planner, backend, frontend, qa | `plan`, `work`, `review` |
| Review (7) | security, performance, ddd-compliance, code-ts, agent-native, simplicity, pattern-recognition | `review` |
| Research (5) | codebase-analyzer, git-historian, dependency-auditor, learnings-researcher, best-practices-researcher | `route`, `plan` |
| Workflow (5) | bug-reproducer, spec-analyzer, spec-extractor, style-enforcer, comprehension-guardian | `work`, `review` |
| Design (2) | api-designer, ui-verifier | `plan`, `review` |

Agents are invoked automatically by the core commands. You rarely need to invoke them directly.

## Skills

| Category | Skills |
|----------|--------|
| Core | consultant, checkpoint, git-sync |
| Quality | test-runner, coverage-checker, lint-fixer |
| Workflow | worktree-manager, commit-formatter |
| Compound | changelog-generator, layer-validator, spec-merger |
| Integration | mcp-connector |
| SOLID | solid-analyzer, criteria-generator |
| Shaping | shaper, breadboarder |

## Context Activation Model

| Content | Activation | When Loaded |
|---------|-----------|-------------|
| This file (CLAUDE.md) | Always | Every session |
| Operational rules (`core/rules/framework_rules.md`) | Always | Every session |
| Scoped rules (`core/rules/*-rules.md`) | Software-determined | When matching file types are edited |
| Role definitions (`core/roles/`) | LLM-determined | When role is active |
| Skills (`skills/`) | Human-triggered | On `/skill:X` invocation |
| Review agents (`agents/review/`) | Human-triggered | During `/workflows:review` |
| Project hooks (`.ai/hooks/`) | Software-determined | Automatic on tool events |

Heavy skills and all 7 review agents run with `context: fork` -- isolated context windows returning summaries only. See `core/docs/CONTEXT_ENGINEERING.md`.

## Capability Providers

The plugin is **model-agnostic** and **execution-agnostic**. It abstracts capabilities (parallelization, context management, fork strategy, execution mode) behind providers that auto-detect the running model and select the best implementation. Configuration in `core/providers.yaml`, detection logic in `core/docs/CAPABILITY_PROVIDERS.md`.

When `providers.yaml` is set to `auto` (default), resolve providers using the Detection Protocol before executing provider-dependent operations.

## Key Patterns

- **Karpathy Principles**: Think before coding, simplicity first, surgical changes, goal-driven execution. Details in `core/docs/KARPATHY_PRINCIPLES.md`.
- **Ralph Wiggum Loop**: Auto-correct up to 10 iterations, then mark BLOCKED. Details in `core/rules/framework_rules.md`.
- **Compound Capture**: After each feature, extract patterns and update rules via `/workflows:compound`.
- **Agent Compound Memory**: Review agents read `.ai/project/compound-memory.md` to calibrate intensity based on historical pain points. See `core/agent-memory.md`.
- **SOLID Constraint**: Phase 3 solutions target score >= 22/25. See `core/solid-pattern-matrix.md`.
- **Context as Resource**: Thresholds adapt to provider (compaction-aware or manual-snapshots). Details in `core/docs/SESSION_CONTINUITY.md`.

## State Management

All roles communicate via `50_state.md`. Status values: `PENDING`, `IN_PROGRESS`, `BLOCKED`, `WAITING_API`, `COMPLETED`, `APPROVED`, `REJECTED`.

## Best Practices

1. Route first -- every request through `/workflows:route`
2. Ask when unclear -- confidence < 60% means ask before acting
3. State assumptions before coding
4. Define testable success criteria
5. Implement only what's requested
6. Touch only essential code, preserve existing style
7. Plan 80%, execute 20%
8. One role per session
9. Write tests before implementation (TDD)
10. Snapshot before breaks or risky operations

## Reference Documentation

| Topic | Location |
|-------|----------|
| Capability providers & detection | `core/docs/CAPABILITY_PROVIDERS.md` |
| Provider configuration | `core/providers.yaml` |
| Routing details & question templates | `core/docs/ROUTING_REFERENCE.md` |
| Karpathy principles | `core/docs/KARPATHY_PRINCIPLES.md` |
| Context engineering & fork model | `core/docs/CONTEXT_ENGINEERING.md` |
| Session continuity & snapshots | `core/docs/SESSION_CONTINUITY.md` |
| Lifecycle hooks | `core/docs/LIFECYCLE_HOOKS.md` |
| MCP integration | `core/docs/MCP_INTEGRATION.md` |
| SOLID pattern matrix | `core/solid-pattern-matrix.md` |
| Agent compound memory system | `core/agent-memory.md` |
| Operational rules | `core/rules/framework_rules.md` |
| Testing conventions | `core/rules/testing-rules.md` |
| Security & trust model | `core/rules/security-rules.md` |
| Git workflow | `core/rules/git-rules.md` |

---

**Version**: 2.9.0 | **Aligned with**: Compound Engineering + Karpathy + Context Engineering (Fowler) + Capability Providers + Shape Up (Singer) + Agent Compound Memory
