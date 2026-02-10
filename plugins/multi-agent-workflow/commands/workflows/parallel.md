---
name: workflows:parallel
description: "Launch multiple agents in parallel. Auto-detects provider: Agent Teams (Opus 4.6+) or worktrees+multiplexer (fallback). Terminal orchestrator auto-detects tmux/screen/zellij."
argument_hint: <feature-id> [--roles=backend,frontend,qa] [--provider=auto|agent-teams|worktrees] [--terminal=auto|tmux|screen|zellij|none] [--cleanup]
---

# Multi-Agent Workflow: Parallel

> **Tier 3 (Automatic)**: Parallelization is handled automatically by `/workflows:work --mode=roles`. Only invoke this command manually when you need explicit control over the parallel execution setup.

Launch multiple AI agents working in parallel on a feature. The command auto-resolves the best parallelization provider based on the running model and available tools.

## Usage

```bash
# Standard (auto-detects everything)
/workflows:parallel user-authentication --roles=backend,frontend,qa

# Force parallelization provider
/workflows:parallel user-authentication --provider=agent-teams
/workflows:parallel user-authentication --provider=worktrees

# Force terminal multiplexer (only with --provider=worktrees)
/workflows:parallel user-authentication --provider=worktrees --terminal=screen
/workflows:parallel user-authentication --provider=worktrees --terminal=zellij
/workflows:parallel user-authentication --provider=worktrees --terminal=none

# Cleanup after completion
/workflows:parallel user-authentication --cleanup
```

## Provider Resolution

Before launching agents, resolve the parallelization provider:

```
1. READ core/providers.yaml → providers.parallelization

2. IF "auto":
   ├── Is TeammateTool available in your tool list?
   │   YES → provider = agent-teams
   │   NO  → provider = worktrees
   │
   └── Log: "Resolved parallelization provider: {provider}"

3. IF explicit → use that provider directly

4. IF --provider flag passed → override config (session only)

5. IF provider = worktrees:
   ├── READ core/providers.yaml → providers.terminal_orchestrator
   ├── IF "auto" → detect first available: tmux → screen → zellij → none
   ├── IF --terminal flag passed → override config (session only)
   └── Log: "Resolved terminal orchestrator: {terminal}"
```

## Provider: Agent Teams

**Available when**: TeammateTool is in the tool list (Opus 4.6+ with `CLAUDE_CODE_EXPERIMENTAL_AGENT_TEAMS=1`)

### How It Works

```
┌──────────────────────────────────────────────────────────┐
│              ORCHESTRATOR (current session)                │
│                                                          │
│  1. Load feature context from 50_state.md                │
│  2. Parse task assignments per role from 30_tasks.md     │
│  3. Spawn teammates via TeammateTool                     │
│                                                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │  Teammate:   │  │  Teammate:   │  │  Teammate:   │     │
│  │  BACKEND     │  │  FRONTEND    │  │  QA           │     │
│  │             │  │             │  │             │     │
│  │  Reads:      │  │  Reads:      │  │  Reads:      │     │
│  │  - backend.md│  │  - frontend  │  │  - qa.md     │     │
│  │  - 50_state  │  │  - 50_state  │  │  - 50_state  │     │
│  │  - tasks_be  │  │  - tasks_fe  │  │  - tasks_qa  │     │
│  │             │  │             │  │             │     │
│  │  Writes:     │  │  Writes:     │  │  Writes:     │     │
│  │  - src/      │  │  - frontend/ │  │  - tests/    │     │
│  │  - tests/    │  │  - tests/    │  │  - 50_state  │     │
│  │  - 50_state  │  │  - 50_state  │  │             │     │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘     │
│         │                │                │             │
│         └────────┬───────┘────────────────┘             │
│                  ▼                                       │
│  4. Monitor progress via shared task list               │
│  5. Synchronize 50_state.md on completion               │
│  6. Report results                                       │
└──────────────────────────────────────────────────────────┘
```

### Orchestrator Prompt for Each Teammate

When spawning a teammate, provide this context:

```markdown
You are the {ROLE} agent for feature "{FEATURE_ID}".

## Your Role
Read: plugins/multi-agent-workflow/core/roles/{role}.md

## Feature Context
Read: .ai/project/features/{feature}/50_state.md
Read: .ai/project/features/{feature}/FEATURE_*.md

## Your Tasks
Read: .ai/project/features/{feature}/30_tasks_{role}.md

## Rules
Read: plugins/multi-agent-workflow/core/rules/framework_rules.md
Read: plugins/multi-agent-workflow/core/rules/testing-rules.md

## Coordination
- Update your section in 50_state.md after each checkpoint
- Follow TDD: Red → Green → Refactor
- SOLID score must meet thresholds at each checkpoint
- If blocked after 10 iterations, mark BLOCKED in 50_state.md
```

### Cleanup (Agent Teams)

```bash
/workflows:parallel user-auth --cleanup
# Agent Teams cleanup is automatic — no filesystem artifacts
# Just verify 50_state.md is synchronized
```

---

## Provider: Worktrees + Terminal Orchestrator

**Available when**: Always (fallback provider)

**Prerequisites**: git >= 2.30 + terminal multiplexer (optional)

### How It Works

Two layers: **worktrees** provide filesystem isolation, a **terminal orchestrator** provides session management.

```
┌──────────────────────────────────────────────────────────┐
│         TERMINAL ORCHESTRATOR (tmux/screen/zellij)        │
│                                                          │
│  ┌──────────────────────┬──────────────────────┐        │
│  │     BACKEND           │     FRONTEND          │        │
│  │     :3001             │     :3002             │        │
│  │  .worktrees/backend/  │  .worktrees/frontend/ │        │
│  ├──────────────────────┴──────────────────────┤        │
│  │                    QA  :3003                   │        │
│  │  .worktrees/qa/                               │        │
│  └──────────────────────────────────────────────┘        │
└──────────────────────────────────────────────────────────┘
```

### Step 1: Create Git Worktrees

For each role, create an isolated working directory:

```
.worktrees/
├── backend/   (branch: feature/{feature}-backend)
├── frontend/  (branch: feature/{feature}-frontend)
└── qa/        (branch: feature/{feature}-qa)
```

Each worktree:
- Has its own filesystem (no conflicts)
- Is on its own feature branch
- Can run its own dev server
- Shares git history with main repo

### Step 2: Allocate Ports

| Role | Port |
|------|------|
| backend | 3001 |
| frontend | 3002 |
| qa | 3003 |

### Step 3: Resolve Terminal Orchestrator

```
READ core/providers.yaml → providers.terminal_orchestrator

IF "auto":
  which tmux   && terminal = tmux
  which screen && terminal = screen
  which zellij && terminal = zellij
  else            terminal = none

IF --terminal flag → override
```

### Step 4: Launch Session

The orchestrator launches differently depending on the resolved terminal:

#### tmux (default, recommended)

```bash
tmux new-session -d -s "workflow-${FEATURE_ID}"
tmux split-window -h -t "workflow-${FEATURE_ID}"
tmux split-window -v -t "workflow-${FEATURE_ID}"
# Send cd + env setup to each pane
tmux send-keys -t "workflow-${FEATURE_ID}:0.0" "cd .worktrees/backend && export AGENT_ROLE=backend" Enter
tmux send-keys -t "workflow-${FEATURE_ID}:0.1" "cd .worktrees/frontend && export AGENT_ROLE=frontend" Enter
tmux send-keys -t "workflow-${FEATURE_ID}:0.2" "cd .worktrees/qa && export AGENT_ROLE=qa" Enter
```

| Shortcut | Action |
|----------|--------|
| `Ctrl+b d` | Detach (session keeps running) |
| `Ctrl+b arrow` | Navigate panes |
| `Ctrl+b z` | Zoom/unzoom pane |

#### GNU Screen

```bash
screen -dmS "workflow-${FEATURE_ID}"
screen -S "workflow-${FEATURE_ID}" -X screen -t backend
screen -S "workflow-${FEATURE_ID}" -X screen -t frontend
screen -S "workflow-${FEATURE_ID}" -X screen -t qa
# Send cd + env setup to each window
screen -S "workflow-${FEATURE_ID}" -p backend -X stuff "cd .worktrees/backend && export AGENT_ROLE=backend\n"
screen -S "workflow-${FEATURE_ID}" -p frontend -X stuff "cd .worktrees/frontend && export AGENT_ROLE=frontend\n"
screen -S "workflow-${FEATURE_ID}" -p qa -X stuff "cd .worktrees/qa && export AGENT_ROLE=qa\n"
```

| Shortcut | Action |
|----------|--------|
| `Ctrl+a d` | Detach (session keeps running) |
| `Ctrl+a n` / `Ctrl+a p` | Next/previous window |
| `Ctrl+a "` | List windows |

#### zellij

```bash
zellij --session "workflow-${FEATURE_ID}" --layout .ai/workflow/parallel/zellij_layout.kdl
# Or manually:
zellij --session "workflow-${FEATURE_ID}"
# zellij uses its own pane management (Alt+n for new pane, Alt+arrow to navigate)
```

| Shortcut | Action |
|----------|--------|
| `Ctrl+o d` | Detach |
| `Alt+arrow` | Navigate panes |
| `Alt+n` | New pane |

#### none (worktrees only)

```bash
# Only creates worktrees, no multiplexer session
git worktree add .worktrees/backend "feature/${FEATURE_ID}-backend"
git worktree add .worktrees/frontend "feature/${FEATURE_ID}-frontend"
git worktree add .worktrees/qa "feature/${FEATURE_ID}-qa"

echo "Worktrees created. Open separate terminals and cd into each:"
echo "  Terminal 1: cd .worktrees/backend"
echo "  Terminal 2: cd .worktrees/frontend"
echo "  Terminal 3: cd .worktrees/qa"
```

Use this when: SSH without multiplexer, IDE integrated terminals, CI/CD pipelines, or when you prefer your own terminal setup.

### Step 5: Set Up Environment

Each session/pane has:
- `AGENT_ROLE` — Current role (backend/frontend/qa)
- `AGENT_PORT` — Allocated port
- `FEATURE_ID` — Feature being worked on
- Working directory set to worktree

### Cleanup (Worktrees)

```bash
/workflows:parallel user-auth --cleanup

# This will:
# 1. Kill multiplexer session (if running)
# 2. Remove worktrees (if clean)
# 3. Release allocated ports
```

### Terminal Orchestrator Comparison

| Aspect | tmux | screen | zellij | none |
|--------|------|--------|--------|------|
| **Availability** | Most systems | Pre-installed | Install required | Always |
| **Maturity** | Since 2009 | Since 1987 | Since 2021 | N/A |
| **Pane layout** | Flexible splits | Windows (no splits) | Floating + tiles | Manual |
| **Scriptability** | Excellent | Good | Good | N/A |
| **Persistence** | Detach/attach | Detach/attach | Detach/attach | No |
| **Modern UX** | Good | Basic | Best | N/A |
| **Plugin system** | No | No | Yes (WASM) | N/A |
| **Config** | `.tmux.conf` | `.screenrc` | `config.kdl` | N/A |

---

## Output

Both providers produce the same output format:

```
╔════════════════════════════════════════════════════════════╗
║          PARALLEL SESSION CREATED                          ║
╚════════════════════════════════════════════════════════════╝

Provider: {agent-teams | worktrees}
Terminal: {tmux | screen | zellij | none | n/a}
Session: workflow-{feature}
Feature: {feature}

Agents launched:
  [0] backend    {provider-specific details}
  [1] frontend   {provider-specific details}
  [2] qa         {provider-specific details}

State file: .ai/project/features/{feature}/50_state.md

Next steps:
  - Each agent works on assigned tasks from 30_tasks.md
  - Progress tracked in 50_state.md
  - Quality gates enforced at each checkpoint
```

---

## Merging Work

After parallel development (same for both providers):

1. Each agent pushes their branch
2. Create PRs for each branch
3. Review and merge to main
4. Or merge branches locally:

```bash
git checkout main
git merge feature/user-auth-backend
git merge feature/user-auth-frontend
```

---

## Provider Comparison

| Aspect | Agent Teams | Worktrees + Multiplexer | Worktrees (none) |
|--------|------------|------------------------|-------------------|
| **Communication** | Direct (shared task list) | File-based (50_state.md + git) | File-based |
| **Isolation** | Context window per agent | Filesystem + terminal | Filesystem only |
| **Setup** | Automatic | Multiplexer + worktree | Worktree only |
| **Cleanup** | Automatic | `--cleanup` | `--cleanup` |
| **Visibility** | Orchestrator monitors | Panes/windows | Separate terminals |
| **Model requirement** | Opus 4.6+ | Any | Any |
| **External deps** | None | tmux/screen/zellij + git | git only |

Both providers:
- Use `50_state.md` for persistent state
- Enforce role definitions (one role per agent)
- Apply quality gates (TDD, SOLID, checkpoints)
- Support the same `/workflows:parallel` interface

---

## Tips

1. **Plan first**: Clear specs reduce conflicts between agents
2. **Define contracts**: API contracts let frontend mock while backend implements
3. **Regular syncs**: Merge main periodically to avoid drift
4. **One role per agent**: Don't mix responsibilities

## Related Commands

- `/workflows:plan` — Plan feature before parallel work
- `/workflows:work` — Execute tasks for a specific role
- `/workflows:status` — Check all agent status
- `/workflows:sync` — Sync state between agents

## Sources

- [Claude Code Agent Teams](https://www.anthropic.com/news/claude-opus-4-6) — Native parallel agents (Opus 4.6+)
- [workmux](https://github.com/raine/workmux) — Git worktrees + tmux
- [uzi](https://github.com/devflowinc/uzi) — Parallel coding agents
