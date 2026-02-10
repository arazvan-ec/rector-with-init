# Framework Rules - Multi-Agent Workflow

**Framework Version**: 2.9.0
**Last Updated**: 2026-02-10

---

## Purpose

These are the fundamental operational rules of the Multi-Agent Workflow framework. They apply to all projects using this plugin. Project-specific rules go in `.ai/extensions/rules/`.

For additional scoped rules see: `testing-rules.md`, `security-rules.md`, `git-rules.md` in this directory.

---

## Core Principles

### 1. Route Before Acting

Every interaction passes through the workflow router before work begins. See `CLAUDE.md` for the routing protocol and `core/docs/ROUTING_REFERENCE.md` for question templates.

### 2. Karpathy Principles — Apply to All Work

Four principles that prevent common AI failure modes. Apply them at every stage:

- **Think Before Coding**: State assumptions and clarify ambiguities before writing code
- **Simplicity First**: Implement only what is explicitly requested
- **Surgical Changes**: Touch only the code essential to the task
- **Goal-Driven Execution**: Transform vague requests into testable success criteria

See `core/docs/KARPATHY_PRINCIPLES.md` for detailed guidance with examples.

### 3. Explicit Context — No Implicit Memory

All shared knowledge lives in files. Never assume implicit context.

```
Avoid: "Remember we said earlier that..."
Prefer: "Read `.ai/project/features/FEATURE_X/50_state.md`"
```

### 4. Immutable Roles

One Claude instance = one fixed role during the entire session.

- Don't switch from Backend to Frontend mid-task
- Don't implement code if you're QA
- Don't make design decisions if you're Backend/Frontend

### 5. Execution Mode Awareness

The agent's execution behavior depends on the resolved execution_mode provider:

- **agent-executes**: Generate code following patterns, run tests, iterate autonomously
- **human-guided**: Create instructions, human implements, agent verifies
- **hybrid**: Generate code, pause for human review at checkpoints

Resolve execution_mode from `core/providers.yaml` before starting any task. In `auto` mode, low-trust paths (auth/, security/, payment/) default to `hybrid`.

### 6. Workflow Sequence and Flow Guards

Follow the defined workflow without skipping stages. Each core command enforces prerequisites:

- **`plan`** requires: request routed via `/workflows:route`
- **`work`** requires: planner status = `COMPLETED` in `50_state.md`
- **`review`** requires: implementation status = `COMPLETED` in `50_state.md`
- **`compound`** requires: QA status = `APPROVED` in `50_state.md`

If a prerequisite is not met, STOP and complete the missing step first.

- For complex or unclear features, Shape before Planning (`/workflows:shape` → `/workflows:plan`)
- Don't implement before Planning is `COMPLETED`
- Don't QA before Implementation is `COMPLETED`
- If you need to change workflow, document why in `DECISIONS.md`

**Full sequence**: Route → Shape (optional) → Plan → Work → Review → Compound

### 7. Command Tiers

Commands are organized in tiers. Only Tier 1 and Tier 2 commands should be invoked directly by users:

- **Tier 1 (Core Flow)**: `route`, `shape`, `plan`, `work`, `review`, `compound`
- **Tier 2 (Support)**: `quickstart`, `status`, `help`, `specs`, `discover`
- **Tier 3 (Automatic)**: Operations handled automatically by Tier 1 commands (sync, checkpoint, snapshot, tdd, trust, validate, comprehension, criteria, parallel, progress, monitor, solid-refactor, role, metrics, restore)
- **Tier 4 (Developer-Only)**: Plugin development tools (`skill-dev`, `heal-skill`, `reload`)

Tier 3 commands exist for edge cases but should NOT be part of the normal flow.

### 8. Synchronized State

Use `50_state.md` to communicate state between roles.

- Update `50_state.md` frequently
- Read other roles' state before starting
- Use standard states: `PENDING`, `IN_PROGRESS`, `BLOCKED`, `WAITING_API`, `COMPLETED`, `APPROVED`, `REJECTED`

---

## Context Window Management

Treat context as a resource. Thresholds depend on the active provider (see `core/providers.yaml`).

### Signs to Restart Session

Resolve the context_management provider first, then apply thresholds:

| Signal | Manual Snapshots (standard) | Compaction-Aware (advanced) |
|--------|----------------------------|----------------------------|
| Files read | > 20 | > 50 |
| Session duration | > 2 hours | > 4 hours |
| Messages | > 50 | > 150 |
| Context capacity | > 70% | > 85% |

If provider is `auto`, detect tier per `core/docs/CAPABILITY_PROVIDERS.md` Detection Protocol.

### Session Restart Protocol
1. Save current state in `50_state.md`
2. Commit all work with checkpoint message
3. Document resume point (last checkpoint, next task, relevant files)
4. Start new session reading: role.md, 50_state.md, relevant files

See `core/docs/SESSION_CONTINUITY.md` for detailed strategies.

---

## Comprehension Debt (The 80% Problem)

Code generation speed should not exceed comprehension speed.

### Indicators

| Level | Indicator | Action |
|-------|-----------|--------|
| Critical | "Works but I don't know why" | Stop and understand before continuing |
| High | Need to re-read file for every change | Document key concepts |
| Medium | Copy patterns without understanding why | Study the pattern first |
| Healthy | Can explain code from memory | Continue |

### Before Marking COMPLETED

Verify you can:
- Explain what the code does without looking at it
- Rewrite it if necessary
- Explain all abstractions used
- Explain decisions to a new team member

---

## Workflow Evolution (Governance)

The workflow evolves deliberately, not by fashion. Every addition demonstrates value before implementation.

### Evaluation Criteria

| Criterion | Weight |
|----------|--------|
| Solves a real problem we have | 30% |
| Proven in production by others | 20% |
| Integrates with our system | 20% |
| Benefit justifies complexity | 15% |
| Maintainable long-term | 15% |

Minimum threshold: weighted score ≥ 3.5/5 to proceed.

Avoid: implementing trends because they're fashionable, adding tools without concrete use cases, major refactors without impact analysis.

---

## Permissions

### Reading — Each role can read:
- Their own role definition
- All rules files
- Workflow definitions
- Feature states (`50_state.md`)
- Code relevant to their role

### Writing — Each role can only write to:
- Their assigned code area
- Their section of `50_state.md`
- Files assigned to their role

Only the Planner role can modify project rules (with justification in `DECISIONS.md`).

---

## Global Prohibitions

1. Committing code without tests
2. Pushing with failing tests
3. Changing code without documenting why
4. Skipping the workflow
5. Implementing features not defined by Planner
6. Changing API contracts without consensus
7. Force pushing to `main` or `develop`
8. Committing generated files (`.env`, `node_modules/`, `vendor/`, etc.)

---

## Scoped Rules Reference

| Rule File | Applies To | Content |
|-----------|-----------|---------|
| `testing-rules.md` | Test files (`*Test.php`, `*.test.ts`, etc.) | TDD workflow, coverage, Ralph Wiggum loop |
| `security-rules.md` | Auth, security, payment paths | Trust model, supervision calibration, security prohibitions |
| `git-rules.md` | Git operations | Branching, commits, conflict management, multi-agent sync |

---

**Note**: These rules are part of the Multi-Agent Workflow Plugin and should not be modified per-project. For project-specific rules, create files in `.ai/extensions/rules/`.
