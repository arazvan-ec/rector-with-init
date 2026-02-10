# Multi-Agent Workflow Plugin

A **compound engineering** framework for Claude Code that coordinates multiple AI agents working in parallel on software development.

> **"Each unit of engineering work should make subsequent units easier—not harder"**

## Features

### Core Capabilities
- **21 Specialized Agents** in 5 categories: roles, review, research, workflow, design
- **25 Workflow Commands**: Core workflows + session management + metrics + skill development
- **12 Skills**: Core, Quality, Workflow, Compound, Integration, and SOLID analysis
- **3 Parallelization Modes**: By roles, by layers (DDD), or by stacks
- **Quality Gates**: Blocking checkpoints with auto-correction loops
- **Compound Learning**: Capture insights from each feature

### v2.7.0 New Features (Agent-Native Execution)
- **Execution Mode Provider**: `/workflows:work` now supports `agent-executes` (agent generates code), `human-guided` (legacy), and `hybrid` (agent generates, human reviews) modes
- **Autonomous Code Generation**: In `agent-executes` mode, the agent reads existing patterns, generates TDD tests first, implements code, auto-corrects failures, validates SOLID, and checkpoints — all autonomously
- **Trust-Aware Detection**: `auto` mode routes sensitive areas (auth, security, payment) to `hybrid` automatically
- **Pattern-Following Generation**: Agent reads reference files from `30_tasks.md` to learn existing code style before generating new code
- **Same Workflow, New Capability**: `/workflows:plan` → `/workflows:work` → `/workflows:review` → `/workflows:compound` — the workflow doesn't change, but now the agent can execute the plan

### v2.6.0 New Features (Capability Providers)
- **Model-Agnostic Providers**: Abstraction layer that auto-detects model capabilities and selects the best implementation. Plugin works with Opus 4.5 and 4.6 without limiting either
- **Parallelization Provider**: `/workflows:parallel` auto-resolves to Agent Teams (Opus 4.6+ with TeammateTool) or worktrees+tmux (fallback). Same interface, different backends
- **Context Management Provider**: Thresholds adapt to model tier — relaxed for compaction-aware (Opus 4.6+), strict for manual-snapshots (standard)
- **Selective Fork Strategy**: With 200K-1M context windows (Opus 4.6+), only fork truly heavy operations. Inline lighter analysis for better integration
- **Provider Configuration**: `core/providers.yaml` — set to `auto` for detection or force specific providers
- **API Recommendations**: Documents optimal API settings (effort, speed, thinking) per workflow phase

### v2.5.0 New Features (Context Engineering v2)
- **Scoped Rules**: 3 rule files (`testing-rules.md`, `security-rules.md`, `git-rules.md`) that load only when matching file types are edited
- **Slim CLAUDE.md**: Reduced from ~514 to ~130 lines (~75% reduction). Detailed content moved to on-demand reference docs
- **Routing Reference**: Question templates and decision matrix in `core/docs/ROUTING_REFERENCE.md` (loaded on-demand)
- **Zero Duplication**: Single source of truth between CLAUDE.md and framework_rules.md
- **Urgency Calibration**: Clear guidance language — no uppercase MANDATORY/CRITICAL/MUST/NEVER
- **Combined always-loaded context**: ~300 lines (down from ~980, ~70% reduction)

### v2.4.0 Features (Context Engineering & Claude Code 2.1+)
- **Context Isolation**: Heavy skills and all review agents run with `context: fork` — isolated context windows that prevent pollution of the parent session
- **Portable Governance**: Scoped lifecycle hooks (PreToolUse, PostToolUse, Stop) embedded in skill YAML frontmatter — governance travels with the skill
- **Queen Agent Pattern**: `/workflows:route` can spawn forked sub-agents for parallel analysis before routing decisions
- **Skill Development Mode**: `/workflows:skill-dev` enables hot-reload iterative skill development with validation and testing
- **Calibrated Context Loading**: CLAUDE.md follows Fowler's activation model — critical rules always loaded, catalogs on-demand

### v2.2.0 Features
- **Lifecycle Hooks**: Automatic trust enforcement, audit trails, auto-checkpoints
- **MCP Integration**: Connect to postgres, github, slack, puppeteer
- **Session Continuity**: Snapshot/restore for long-running sessions
- **Workflow Metrics**: Track performance, identify bottlenecks
- **Skill Hot-Reload**: Update skills without losing context

## Installation

```bash
/plugin marketplace add https://github.com/arazvan-ec/workflow
/plugin install multi-agent-workflow
```

## Quick Start

```bash
# Plan a feature (80% of compound engineering)
/workflows:plan user-authentication

# Execute with specific mode
/workflows:work --mode=roles --role=backend user-authentication
/workflows:work --mode=layers --layer=domain user-authentication

# Review before merge
/workflows:review user-authentication

# Capture learnings (compound effect)
/workflows:compound user-authentication
```

## Core Workflows (4 Phases)

| Phase | Command | Purpose | Time |
|-------|---------|---------|------|
| **Plan** | `/workflows:plan` | Convert ideas into implementable strategies | 40% |
| **Work** | `/workflows:work` | Execute with parallelization modes | 40% |
| **Review** | `/workflows:review` | Multi-agent review before merge | 15% |
| **Compound** | `/workflows:compound` | Capture insights for future work | 5% |

## Parallelization Modes

### By Role (Standard)
```
Planner → Backend + Frontend (parallel) → QA
```

### By Layer (DDD)
```
Domain + Application + Infrastructure (parallel)
```

### By Stack
```
Backend complete + Frontend complete (parallel)
```

## Agent Categories

### Roles (4 agents)
| Agent | Purpose |
|-------|---------|
| `planner` | Define features, create contracts, coordinate |
| `backend` | Implement API with DDD, write tests |
| `frontend` | Implement UI, responsive design |
| `qa` | Review, test, approve/reject |

### Review (7 agents)
| Agent | Purpose |
|-------|---------|
| `security-review` | OWASP, vulnerabilities |
| `performance-review` | Speed, optimization |
| `ddd-compliance` | Layer separation, DDD rules |
| `code-review-ts` | TypeScript/React patterns |
| `agent-native-reviewer` | Action/context parity for agents |
| `code-simplicity-reviewer` | YAGNI, minimalism, simplification |
| `pattern-recognition-specialist` | Design patterns, anti-patterns |

### Research (5 agents)
| Agent | Purpose |
|-------|---------|
| `codebase-analyzer` | Understand structure, patterns |
| `git-historian` | Extract learnings from history |
| `dependency-auditor` | Security, updates |
| `learnings-researcher` | Search docs/solutions/ for past solutions |
| `best-practices-researcher` | Research external best practices |

### Workflow (3 agents)
| Agent | Purpose |
|-------|---------|
| `bug-reproducer` | Systematic bug reproduction |
| `spec-analyzer` | Validate vs specifications |
| `style-enforcer` | Code style automation |

### Design (2 agents)
| Agent | Purpose |
|-------|---------|
| `api-designer` | API contracts |
| `ui-verifier` | UI vs specs |

## Multi-Agent Commands

### Core Commands
| Command | Description |
|---------|-------------|
| `/workflows:route` | Primary entry point - routes to appropriate workflow |
| `/workflows:role <role> <feature>` | Work as a specific role |
| `/workflows:sync <feature>` | Synchronize state |
| `/workflows:status <feature>` | View all roles' status |

### Session & Context Commands (v2.2.0)
| Command | Description |
|---------|-------------|
| `/workflows:reload` | Hot-reload skills/agents without losing context |
| `/workflows:snapshot --name=<name>` | Save session state for later restoration |
| `/workflows:restore --name=<name>` | Restore session from snapshot |
| `/workflows:metrics` | Analyze workflow performance |

### Enhancement Commands (v2.3.0)
| Command | Description |
|---------|-------------|
| `/workflows:deepen-plan` | Enhance plans with parallel research agents |
| `/workflows:heal-skill` | Fix incorrect SKILL.md files |

### Skill Development Commands (v2.4.0)
| Command | Description |
|---------|-------------|
| `/workflows:skill-dev <name>` | Enter skill development mode with hot-reload |
| `/workflows:skill-dev <name> --create` | Scaffold a new skill from template |
| `/workflows:skill-dev <name> --validate` | Validate frontmatter, structure, and hooks |
| `/workflows:skill-dev <name> --test` | Test skill execution in forked context |

## Skills

### Core
- **consultant**: AI-powered project analysis
- **checkpoint**: Quality-gated progress saving
- **git-sync**: Repository synchronization

### Quality
- **test-runner**: Execute test suites
- **coverage-checker**: Validate coverage thresholds
- **lint-fixer**: Auto-fix code style

### Workflow
- **worktree-manager**: Parallel development with worktrees
- **commit-formatter**: Conventional commits

### Compound
- **changelog-generator**: Generate changelogs
- **layer-validator**: DDD layer validation

### Integration (v2.2.0)
- **mcp-connector**: Connect to external tools via MCP (postgres, github, slack, puppeteer)

### SOLID Analysis
- **workflow-skill-solid-analyzer**: Automated SOLID compliance analysis with severity scoring
- **workflow-skill-criteria-generator**: Generate acceptance criteria with `--solid-rigorous` mode

## Context Engineering (v2.4.0)

> *"Context engineering is the art of curating what information the model sees so that you get a better result."*
> — [Martin Fowler](https://martinfowler.com/articles/exploring-gen-ai/context-engineering-coding-agents.html)

This plugin applies context engineering principles to manage how agents receive and process information. This ensures agents see what they need, when they need it, without being overwhelmed.

### Context Activation Model

Not all content is loaded at all times. The plugin classifies content by how it gets activated:

| Content Type | Activation Method | When Loaded | Example |
|---|---|---|---|
| **Critical rules** | Always | Every session start | `CLAUDE.md` (routing rules, core principles) |
| **Role definitions** | LLM-determined | When agent adopts a role | `core/roles/backend.md` |
| **Skills** | Human-triggered | On `/skill:<name>` invocation | `skills/consultant/SKILL.md` |
| **Review agents** | Human-triggered | During `/workflows:review` | `agents/review/security-review.md` |
| **Lifecycle hooks** | Software-determined | Automatic on tool events | `.ai/hooks/lifecycle/pre_tool_use.sh` |

This follows Fowler's taxonomy of **instructions** (always), **interface contexts** (on-demand via skills/commands), and **software-determined** (automatic via hooks).

### Context Isolation with `context: fork`

Heavy-output skills and review agents run in **forked context windows** — they get their own isolated execution environment and return only summaries to the parent session.

```yaml
# Example: In a skill's YAML frontmatter
---
name: consultant
description: "Deep project analysis across 7 layers"
context: fork    # ← Runs in isolated context
model: opus
---
```

**Why this matters**: Without forking, a security review that reads 50 files would flood the parent context with thousands of lines. With `context: fork`, it runs independently and returns only its findings.

**Skills with `context: fork`**:

| Skill | Reason for Isolation |
|-------|---------------------|
| `consultant` | 7-layer deep analysis generates extensive output |
| `token-advisor` | Meta-analysis of session state |
| `coverage-checker` | Generates detailed coverage tables |
| `solid-analyzer` | Full codebase SOLID scanning with metrics |
| `spec-merger` | Reads and compares multiple spec files |
| `changelog-generator` | Processes full git history |
| `mcp-connector` | External service communication |

**Review agents with `context: fork`** (all 7):

| Agent | Reason for Isolation |
|-------|---------------------|
| `security-review` | OWASP checklist across entire codebase |
| `performance-review` | Profiling metrics and query analysis |
| `ddd-compliance` | Cross-layer dependency analysis |
| `code-review-ts` | TypeScript pattern scanning |
| `agent-native-reviewer` | UI/API capability mapping |
| `code-simplicity-reviewer` | Full codebase simplification analysis |
| `pattern-recognition-specialist` | Pattern detection across all files |

### Portable Governance via Scoped Hooks

Lifecycle hooks embedded in YAML frontmatter make governance **portable** — the rules travel with the skill, not in a central config file.

```yaml
# Example: layer-validator skill
---
name: layer-validator
hooks:
  PreToolUse:
    - matcher: Bash
      command: "echo '[layer-validator] Running DDD layer check...'"
  PostToolUse:
    - matcher: Bash
      command: "echo '[layer-validator] Layer check completed'"
  Stop:
    - command: "echo '[layer-validator] Validation report finalized'"
---
```

**Hook types and their purposes**:

| Hook | When It Fires | Common Uses |
|------|--------------|-------------|
| `PreToolUse` | Before a tool executes | Validation, pre-flight checks, trust enforcement |
| `PostToolUse` | After a tool executes | Audit logging, state sync, progress tracking |
| `Stop` | When the skill/agent finishes | Final reports, checkpoint saves, cleanup |

**Skills with scoped hooks**:
`layer-validator`, `test-runner`, `checkpoint`, `lint-fixer`, `commit-formatter`, `git-sync`, `worktree-manager`, `coverage-checker`, `solid-analyzer`, `spec-merger`, `mcp-connector`

**Agents with scoped hooks**:
`security-review`, `performance-review`, `ddd-compliance`, `code-review-ts`

### Queen Agent Pattern

The `/workflows:route` command can operate as a **Queen Agent** — spawning forked sub-agents for parallel analysis before making routing decisions.

```
┌──────────────────────────────────────────────────┐
│               QUEEN AGENT (route)                │
│                                                  │
│  ┌───────────┐ ┌───────────┐ ┌───────────────┐  │
│  │ consultant│ │spec-analyz│ │ git-historian  │  │
│  │  (fork)   │ │  (fork)   │ │   (fork)      │  │
│  └─────┬─────┘ └─────┬─────┘ └──────┬────────┘  │
│        │              │              │            │
│        ▼              ▼              ▼            │
│     ┌────────────────────────────────────┐       │
│     │      Aggregate → Route Decision    │       │
│     └────────────────────────────────────┘       │
└──────────────────────────────────────────────────┘
```

**When to use**: Ambiguous requests, complex multi-layer features, or sensitive areas (auth/payments). Not needed for clear simple requests.

**Result**: Evidence-based routing decisions instead of heuristic-based ones.

### Skill Development with Hot-Reload

The `/workflows:skill-dev` command enables rapid iterative skill development:

```
Edit SKILL.md → Save → Auto-reload → Test → Review → Iterate
```

**Four modes**:
- `--create`: Scaffold new skill with frontmatter template (name, description, context, hooks)
- `--edit`: Load existing skill, suggest Claude Code 2.1+ enhancements
- `--validate`: Check frontmatter structure, hooks validity, section completeness
- `--test`: Execute in forked context, verify hooks fire correctly

See `commands/workflows/skill-dev.md` for full documentation.

## Key Patterns

### Ralph Wiggum Pattern (Auto-Correction Loop)
```python
while tests_failing and iterations < 10:
    fix_code()
    run_tests()
    if passing: checkpoint_complete()

if iterations >= 10:
    mark_blocked()
    document_for_help()
```

### Compound Capture Pattern
After each feature:
1. Review commits and PRs
2. Identify patterns/anti-patterns
3. Update project rules
4. Document in compound_log.md

## Project Structure

```
plugins/multi-agent-workflow/
├── .claude-plugin/
│   └── plugin.json
├── agents/
│   ├── roles/           # 4 core roles
│   ├── review/          # 7 review agents
│   ├── research/        # 5 research agents
│   ├── workflow/        # 3 workflow agents
│   └── design/          # 2 design agents
├── commands/
│   └── workflows/
│       ├── plan.md
│       ├── work.md
│       ├── review.md
│       ├── compound.md
│       ├── role.md
│       ├── sync.md
│       ├── status.md
│       └── skill-dev.md        # v2.4.0: Hot-reload skill development
├── skills/
│   ├── consultant/
│   ├── checkpoint/
│   ├── git-sync/
│   ├── test-runner/
│   ├── coverage-checker/
│   ├── lint-fixer/
│   ├── worktree-manager/
│   ├── commit-formatter/
│   ├── changelog-generator/
│   └── layer-validator/
├── core/
│   ├── rules/
│   │   ├── framework_rules.md     # Core operational rules (~172 lines)
│   │   ├── testing-rules.md       # v2.5.0: TDD, coverage, Ralph Wiggum (scoped)
│   │   ├── security-rules.md      # v2.5.0: Trust model, supervision (scoped)
│   │   └── git-rules.md           # v2.5.0: Branching, commits, conflicts (scoped)
│   └── docs/
│       ├── ROUTING_REFERENCE.md   # v2.5.0: Question templates, decision matrix
│       ├── CONTEXT_ENGINEERING.md  # Context engineering reference
│       ├── KARPATHY_PRINCIPLES.md  # Coding principles
│       └── SESSION_CONTINUITY.md   # Snapshots, metrics
├── CLAUDE.md
└── README.md
```

## State Management

All roles communicate via `50_state.md`:

```markdown
## Backend Engineer
**Status**: IN_PROGRESS
**Checkpoint**: Domain layer complete
**Tests**: 15/15 passing, 92% coverage
```

Status values: `PENDING`, `IN_PROGRESS`, `BLOCKED`, `WAITING_API`, `COMPLETED`, `APPROVED`, `REJECTED`

## Best Practices

### Core
1. **Route First**: Every request passes through `/workflows:route`
2. **80% Planning, 20% Execution**: Invest in `/workflows:plan`
3. **One role per session**: Don't switch roles mid-conversation
4. **Sync before work**: Always pull latest changes first
5. **TDD always**: Write tests before implementation
6. **Compound always**: Run `/workflows:compound` after each feature

### Session Management (v2.2.0)
7. **Snapshot before breaks**: Save state before ending long sessions
8. **Reload after edits**: Use `/workflows:reload` after modifying skills
9. **Trust the hooks**: Lifecycle hooks enforce rules automatically

## The Compound Effect

```
Feature 1: 5 hours + 3 patterns captured
Feature 2: 3 hours (reused 2 patterns)
Feature 3: 2.5 hours (reused 4 patterns)
Feature 4: 2 hours (reused 5 patterns)

Time saved: 37.5%
```

## Integration

Works best with:
- **Git**: For synchronization between agents
- **Tilix/tmux**: For running multiple roles in parallel
- **Symfony/React**: Optimized for this stack (but adaptable)
- **DDD Architecture**: Domain-Driven Design patterns
- **Claude Code SDK**: Lifecycle hooks for automatic enforcement (v2.2.0)
- **MCP Servers**: External tool integration - postgres, github, slack (v2.2.0)

## License

MIT

## Author

arazvan-ec

## Institutional Knowledge (docs/solutions/)

Structure for compounding knowledge:
```
docs/solutions/
├── performance-issues/
├── database-issues/
├── runtime-errors/
├── security-issues/
├── integration-issues/
├── ui-bugs/
├── logic-errors/
├── best-practices/
└── patterns/
    └── critical-patterns.md
```

Use `/workflows:compound` to document learnings with YAML frontmatter for searchability.

## Intellectual Influences

This plugin synthesizes ideas from multiple sources. Each version builds on specific research and articles:

### Foundational: Compound Engineering
- **Source**: [EveryInc/compound-engineering-plugin](https://github.com/EveryInc/compound-engineering-plugin) and the [Compound Engineering philosophy](https://every.to/source-code/compound-engineering-how-every-codes-with-agents-af3a1bae-cf9b-458e-8048-c6b4ba860e62)
- **Influence**: The core idea that each unit of work should make subsequent work easier. This shapes the entire plugin architecture: compound capture, learnings documentation, pattern reuse, and the 4-phase workflow (Plan → Work → Review → Compound).
- **Where you see it**: `/workflows:compound`, `docs/solutions/`, `compound_log.md`, pattern templates

### v2.2.0: Karpathy Principles + Claude Agent SDK
- **Source**: Andrej Karpathy's principles for AI-assisted coding, Claude Code SDK documentation
- **Influence**: Four principles (Think Before Coding, Simplicity First, Surgical Changes, Goal-Driven Execution) that prevent common AI failures. The SDK hooks system enabled automatic enforcement.
- **Where you see it**: `/workflows:route` (assumptions + success criteria steps), `core/docs/KARPATHY_PRINCIPLES.md`, lifecycle hooks in `.claude/settings.json`

### v2.4.0: Context Engineering (Fowler) + Agent Skills Architecture (Hightower)

Two articles directly shaped the v2.4.0 release:

#### Martin Fowler — "Context Engineering for Coding Agents"
- **Source**: [martinfowler.com](https://martinfowler.com/articles/exploring-gen-ai/context-engineering-coding-agents.html)
- **Key insight**: *"Context engineering is the art of curating what the model sees so that you get a better result."* More context is not better — indiscriminate loading reduces effectiveness. Strategic calibration matters.
- **Taxonomy adopted**:
  - **Content types**: Instructions (CLAUDE.md), guides/rules (core/rules/), interface contexts (skills, commands, MCP)
  - **Activation methods**: Always loaded (critical rules), LLM-determined (role selection), human-triggered (slash commands), software-determined (hooks)
- **Influence on the plugin**:
  - Restructured CLAUDE.md from ~700 to ~500 lines, moving detailed catalogs to reference files
  - Added Context Activation Model section documenting when each content type loads
  - Classified all plugin content by activation method
  - Applied the principle that heavy analysis should not pollute the parent context (→ `context: fork`)
- **Fowler's warning applied**: *"As long as LLMs are involved, we can never be certain of anything"* — the plugin optimizes probability of good outcomes through context calibration, not guarantees

#### Rick Hightower — "Build Agent Skills Faster with Claude Code 2.1 Release"
- **Source**: [Medium/Spillwave](https://medium.com/@richardhightower/build-agent-skills-faster-with-claude-code-2-1-release-6d821d5b8179)
- **Key insight**: Claude Code 2.1's three features (hot-reload, lifecycle hooks in frontmatter, `context: fork`) transform it from a terminal assistant into an **agent operating system**. Skills become "processes with their own lifecycle."
- **Concepts adopted**:
  - **Skill hot-reload**: Edit → save → instant reload, no session restart
  - **Hooks in frontmatter**: Governance portable with the skill, not in a central config
  - **`context: fork`**: Sub-agents as isolated processes, not syntactic sugar
  - **Queen Agent pattern**: A coordinator that spawns forked workers for parallel analysis
  - **Hooks as event bus**: Sub-agents emit hooks that the parent can observe
- **Influence on the plugin**:
  - Added `context: fork` to 7 skills and all 7 review agents
  - Moved hook definitions into YAML frontmatter of 13 skills/agents
  - Created `/workflows:skill-dev` command with hot-reload development loop
  - Enhanced `/workflows:route` with Queen Agent pattern and forked parallel analysis
  - Added skill template with frontmatter best practices

### How the Articles Complement Each Other

| Dimension | Fowler (Theory) | Hightower (Practice) | Plugin Implementation |
|---|---|---|---|
| **Perspective** | Top-down: what context to curate | Bottom-up: how features enable it | Both: theory guides, features implement |
| **On context** | Calibrate what the model sees | Fork heavy work to isolate context | `context: fork` on heavy skills + activation model |
| **On hooks** | "Software-determined" activation | Portable governance in frontmatter | Hooks in YAML frontmatter of each skill |
| **On skills** | "Interface contexts" that agents invoke | Live, hot-reloadable units with lifecycle | `/workflows:skill-dev` + hot-reload workflow |
| **On agents** | Framework for organizing multi-agent | Queen agent + process model | Queen pattern in `/workflows:route` |
| **Caution level** | High ("never certain") | Enthusiastic ("agent OS") | Pragmatic: optimize probability, test iteratively |

See `core/docs/CONTEXT_ENGINEERING.md` for the full reference document.

---

**Version**: 2.7.0
**Aligned with**: Compound Engineering + Karpathy Principles + Claude Agent SDK + Context Engineering (Fowler) + Agent Skills Architecture (Hightower) + Capability Providers + Agent-Native Execution
**Changelog**: See CLAUDE.md and CONTEXT_ENGINEERING.md for version history
