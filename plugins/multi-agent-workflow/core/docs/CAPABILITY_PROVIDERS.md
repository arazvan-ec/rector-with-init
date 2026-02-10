# Capability Providers — Model-Agnostic Abstraction Layer

**Version**: 1.0.0
**Added in**: Plugin v2.6.0

---

## Overview

The plugin provides its own abstraction layer for capabilities that vary across Claude model versions. Instead of being locked to a specific model, the plugin defines **provider interfaces** — stable commands and behaviors that work identically from the user's perspective, while routing to different implementations based on the running model's capabilities.

**Principle**: The user always uses `/workflows:parallel`, `/workflows:snapshot`, etc. The plugin resolves the best implementation underneath.

```
User Command                  Provider Resolution              Implementation
─────────────                 ───────────────────              ──────────────
/workflows:parallel    ──►    parallelization: auto    ──►    Agent Teams (4.6+)
                                                        └──►  worktrees+tmux (4.5)

/workflows:snapshot    ──►    context_management: auto ──►    compaction-aware (4.6+)
                                                        └──►  manual-snapshots (4.5)

context: fork          ──►    fork_strategy: auto      ──►    selective (4.6+)
                                                        └──►  aggressive (4.5)
```

---

## How Detection Works

Detection is a two-step process combining **self-awareness** (Claude knows its model) with **tool inspection** (Claude checks available tools).

### Step 1: Model Self-Identification

Claude's system prompt includes its model identity. Use this for detection:

```
Detection Rule:
  IF model name contains "opus-4-6" or later  →  tier: advanced
  IF model name contains "opus-4-5" or "opus-4-1"  →  tier: standard
  IF model name contains "sonnet"  →  tier: standard
  IF model name contains "haiku"  →  tier: lightweight
  OTHERWISE  →  tier: standard (safe default)
```

### Step 2: Tool Availability Check

Before using a capability, verify the required tool exists:

```
For Agent Teams:
  Check: Is TeammateTool available in your tool list?
  YES → agent-teams provider available
  NO  → fall back to worktrees provider

For Context Fork:
  Check: Can you use the Task tool with subagent_type?
  YES → fork available (always true in Claude Code)
  NO  → shared context only
```

### Step 3: Configuration Override

Read `core/providers.yaml` for explicit overrides:

```
IF providers.parallelization == "auto"  →  use detection (steps 1-2)
IF providers.parallelization == "agent-teams"  →  force Agent Teams
IF providers.parallelization == "worktrees"  →  force worktrees
```

---

## Provider: Parallelization

### Interface (What Users See)

```bash
# Always the same command, regardless of provider
/workflows:parallel user-auth --roles=backend,frontend,qa
```

### Implementation: Agent Teams (tier: advanced)

**Prerequisites**: Claude Opus 4.6+, `CLAUDE_CODE_EXPERIMENTAL_AGENT_TEAMS=1`

**Behavior**:
1. Orchestrator (current session) creates shared task list from `50_state.md`
2. Spawns teammates via `TeammateTool` for each role
3. Each teammate gets: role definition, feature state, task assignments
4. Teammates work in independent context windows
5. Direct inter-agent communication for coordination
6. Orchestrator monitors progress and resolves conflicts
7. Results synchronized back to `50_state.md`

**Advantages over worktrees**:
- No tmux/filesystem setup required
- Direct communication (not just file-based)
- Orchestrator can reassign work dynamically
- Native progress tracking

**What stays the same**:
- `50_state.md` still updated (persistent state)
- Role definitions still apply (one role per agent)
- Quality gates still enforced (checkpoints, Ralph Wiggum loop)

### Implementation: Worktrees + tmux (tier: standard, lightweight)

**Prerequisites**: git >= 2.30, tmux >= 3.0

**Behavior**:
1. Creates git worktrees for each role
2. Launches tmux session with panes
3. Sets environment variables per pane
4. Coordination via `50_state.md` + git sync
5. Manual cleanup on completion

**This is the existing behavior** — no changes needed.

### Transition Guide

```
When moving from worktrees to Agent Teams:

1. /workflows:parallel still works identically from user perspective
2. worktree-manager skill becomes optional (not deleted)
3. 50_state.md remains the persistent state layer
4. tmux is no longer needed (but doesn't break anything if installed)
5. Port allocation is no longer needed (agents share filesystem)
```

---

## Provider: Context Management

### Interface (What Users See)

```bash
# Always the same commands
/workflows:snapshot --name="checkpoint-1"
/workflows:restore --name="checkpoint-1"
/skill:token-advisor --quick
```

### Implementation: Compaction-Aware (tier: advanced)

**Prerequisites**: Opus 4.6+ with Compaction API

**Key differences**:
- **Relaxed thresholds**: Context auto-summarized by server
  - Compact suggestion at 85% (not 70%)
  - Max files: 50 (not 20)
  - Max session: 4h (not 2h)
  - Max messages: 150 (not 50)
- **`pre_compact.sh` still fires**: Preserves state before server compaction
- **Snapshots for handoffs only**: Not needed for context exhaustion
- **token-advisor simplified**: Reports status but rarely triggers urgent actions

**Snapshot behavior**:
- Snapshots still work identically (for role handoffs, session boundaries)
- No longer the primary defense against context loss
- Recommended at milestones, not every 30 minutes

### Implementation: Manual Snapshots (tier: standard, lightweight)

**Prerequisites**: None (built-in)

**This is the existing behavior** — strict thresholds, proactive monitoring, frequent checkpoints. See `SESSION_CONTINUITY.md`.

### Threshold Comparison

| Metric | Manual Snapshots | Compaction-Aware |
|--------|-----------------|------------------|
| Compact suggestion | 70% | 85% |
| Max files read | 20 | 50 |
| Max session duration | 2h | 4h |
| Max messages | 50 | 150 |
| Snapshot frequency | Every 30-45 min | At milestones only |
| token-advisor urgency | High (critical resource) | Low (auto-managed) |

---

## Provider: Fork Strategy

### Interface (What Users See)

```yaml
# Skill authors declare fork as before
---
name: my-skill
context: fork
---
```

### Implementation: Selective Fork (tier: advanced)

With 200K-1M context windows, not everything needs forking.

**Fork when** (these rules override `context: fork` declarations):
- Skill reads > 30 files
- Skill generates > 500 lines of output
- Skill connects to external services (MCP, APIs)
- Skill does cross-codebase analysis (consultant, security-review)

**Inline when** (convert fork to shared):
- Skill reads < 15 files in a focused area
- Single-domain analysis (layer-validator on one module)
- Quick checks (token-advisor --quick)

**Skills that ALWAYS fork regardless of tier**:
- consultant (7-layer deep analysis, always heavy)
- security-review (sensitive, isolation is a feature)
- mcp-connector (external services)

**Skills that become inline in selective mode**:
- token-advisor (quick context check)
- coverage-checker (when checking single module)
- changelog-generator (reads git log, not heavy)

### Implementation: Aggressive Fork (tier: standard, lightweight)

**Fork everything** marked with `context: fork`. This is the existing behavior.

---

## Provider: Coordination

### Interface (What Users See)

```bash
# Always the same commands
/workflows:sync user-auth
/workflows:status user-auth
```

### Implementation: Native + State (tier: advanced)

When Agent Teams is active:
- **In-session**: Direct teammate communication
- **Cross-session**: `50_state.md` (persistent)
- **Conflict resolution**: Orchestrator mediates

### Implementation: State + Git (tier: standard)

The existing behavior:
- All state in `50_state.md`
- Git commits as sync points
- `/workflows:sync` for explicit sharing

---

## Detection Protocol

When a provider-dependent command is invoked, execute this protocol:

```
PROVIDER RESOLUTION PROTOCOL
─────────────────────────────

1. READ core/providers.yaml
   └── Get configured provider for this capability

2. IF provider == "auto":
   │
   ├── CHECK model identity (system prompt)
   │   └── Determine tier: advanced | standard | lightweight
   │
   ├── CHECK tool availability
   │   └── TeammateTool available? Fork available?
   │
   └── SELECT provider based on tier + tools
       └── advanced + TeammateTool → agent-teams
       └── advanced, no TeammateTool → worktrees (with relaxed thresholds)
       └── standard → worktrees
       └── lightweight → worktrees (minimal)

3. IF provider is explicit (not "auto"):
   └── USE specified provider directly

4. APPLY thresholds from providers.yaml
   └── IF threshold is null → use provider defaults
   └── IF threshold has value → override default

5. EXECUTE with resolved provider
   └── Log provider choice for debugging
```

---

## Configuration Guide

### For Opus 4.6 (Maximize New Capabilities)

```yaml
# providers.yaml
providers:
  parallelization: auto        # Will use Agent Teams when available
  context_management: auto     # Will use compaction-aware
  fork_strategy: auto          # Will use selective (less forking)
  coordination: auto           # Will use native-plus-state
```

### For Opus 4.5 (Full Compatibility)

```yaml
# providers.yaml
providers:
  parallelization: worktrees
  context_management: manual-snapshots
  fork_strategy: aggressive
  coordination: state-plus-git
```

### For Mixed Team (Some on 4.6, Some on 4.5)

```yaml
# providers.yaml — use auto for everything
providers:
  parallelization: auto
  context_management: auto
  fork_strategy: auto
  coordination: auto

# Coordination is ALWAYS compatible because 50_state.md is shared.
# An Opus 4.6 agent using Agent Teams and an Opus 4.5 agent using
# worktrees can collaborate via the same 50_state.md file.
```

---

## Provider: Execution Mode

### Interface (What Users See)

```bash
# Always the same command
/workflows:work user-auth --role=backend

# Force execution mode
/workflows:work user-auth --role=backend --exec=agent
/workflows:work user-auth --role=backend --exec=human
/workflows:work user-auth --role=backend --exec=hybrid
```

### Implementation: Agent Executes (tier: any model)

The agent IS the engineer. For each task in `30_tasks.md`:

```
EXECUTION LOOP (per task):
  1. READ task definition (acceptance criteria, SOLID requirements)
  2. READ pattern reference file (from task's "Reference" field)
  3. GENERATE test file FIRST (TDD Red phase)
  4. RUN tests → confirm they fail (expected)
  5. GENERATE implementation following pattern reference
  6. RUN tests (test-runner skill)
  7. IF tests fail → analyze error + fix (Ralph Wiggum loop, max 10)
  8. CHECK SOLID compliance (solid-analyzer skill)
  9. IF SOLID < threshold → refactor + re-run tests
  10. FIX lint issues (lint-fixer skill)
  11. CHECKPOINT (update 50_state.md)
  12. → Next task
```

**Key principle**: The agent uses Claude Code's native Write/Edit tools to generate code. No scaffolding engine needed — Claude already knows how to write code. The plugin provides the **structure** (what to write, in what order, following what patterns).

**Pattern Learning**: Before generating, the agent reads an existing file as reference (e.g., `src/Domain/Entity/Order.php` when creating `User.php`). This is specified in each task's "Reference" field in `30_tasks.md`.

### Implementation: Human Guided (legacy)

The agent creates detailed task specs and guidance. The human writes code. The agent verifies with test-runner and solid-analyzer. This is the pre-v2.7.0 behavior.

### Implementation: Hybrid

The agent generates code AND tests, but pauses before each checkpoint for human review. The human can:
- **Accept**: Agent continues to next task
- **Modify**: Agent incorporates changes and re-runs tests
- **Reject**: Agent re-generates following human's feedback

### Detection

```
1. READ core/providers.yaml → providers.execution_mode
2. IF "auto":
   │
   ├── Is the task in a LOW trust area (auth/, security/, payment/)?
   │   YES → hybrid (agent generates but human reviews)
   │
   ├── Does the task have a "Reference" file in 30_tasks.md?
   │   YES → agent-executes (pattern exists to follow)
   │
   └── OTHERWISE → agent-executes (default for well-planned tasks)

3. IF explicit → use that mode directly
4. IF --exec flag passed → override (session only)
```

---

## What the Plugin CANNOT Abstract

These are API-level settings controlled by whoever calls the Claude API, not by the plugin:

| Setting | Where It's Set | Plugin's Role |
|---------|---------------|---------------|
| `thinking: {type: "adaptive"}` | API request parameter | **Recommend** in `providers.yaml` under `api_recommendations` |
| `speed: "fast"` | API request parameter | **Recommend** per task type |
| `effort: "max"` | API request parameter | **Recommend** per workflow phase |
| Compaction API enablement | API/server configuration | **Adapt** thresholds when active |
| 1M context window | API beta flag | **Adapt** fork strategy and thresholds |
| `output_config.format` | API request parameter | **Document** migration path |

The plugin documents optimal API settings in `providers.yaml` under `api_recommendations` so the team can configure their API callers appropriately.

---

## Adding a New Provider

When a new capability becomes available (e.g., a future Claude 5.0 feature):

1. **Add provider option** to `providers.yaml` with documentation
2. **Add detection rule** to the Detection Protocol (Step 2)
3. **Update this document** with implementation details
4. **Test with `auto`** — ensure graceful fallback when capability unavailable

---

## Related Documentation

- `providers.yaml` — Provider configuration and thresholds
- `CONTEXT_ENGINEERING.md` — Fork model and context isolation
- `SESSION_CONTINUITY.md` — Snapshot/restore system
- `LIFECYCLE_HOOKS.md` — Hook system for state preservation
- Plugin `CLAUDE.md` — Entry point with provider reference
