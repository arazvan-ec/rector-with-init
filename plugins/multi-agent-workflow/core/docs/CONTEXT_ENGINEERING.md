# Context Engineering for Multi-Agent Workflows

**Version**: 3.0.0
**Added in**: Plugin v2.4.0, updated in v2.5.0, provider-aware fork strategy in v2.6.0
**Based on**: [Fowler: Context Engineering for Coding Agents](https://martinfowler.com/articles/exploring-gen-ai/context-engineering-coding-agents.html), [Hightower: Build Agent Skills Faster with Claude Code 2.1](https://medium.com/@richardhightower/build-agent-skills-faster-with-claude-code-2-1-release-6d821d5b8179)

---

## What is Context Engineering?

> *"Context engineering is the art of curating what information the model sees so that you get a better result."*
> — Martin Fowler

In the context of AI-assisted development, everything the model "sees" shapes its behavior: the system prompt, loaded files, tool definitions, conversation history, MCP servers, skills, and hooks. **Context engineering** is the discipline of strategically managing this information flow.

The key insight is that **more context is not better**. Loading information indiscriminately reduces the model's effectiveness. What matters is the right information at the right time.

## The Three Dimensions

### 1. Content Types

Following Fowler's taxonomy, all plugin content falls into three categories:

| Type | Definition | Plugin Examples |
|------|-----------|-----------------|
| **Instructions** | Direct behavioral rules the agent must follow | `CLAUDE.md` (routing rules, self-check protocol, Karpathy principles) |
| **Guides/Rules** | Reference material that shapes decisions | `core/rules/framework_rules.md`, `core/docs/KARPATHY_PRINCIPLES.md`, `core/solid-pattern-matrix.md` |
| **Interface Contexts** | Tools and capabilities available to the agent | Skills (`/skill:consultant`), Commands (`/workflows:plan`), MCP servers, Review agents |

### 2. Activation Methods

Content enters the agent's context through different mechanisms:

| Method | Who Decides | When | Examples |
|--------|------------|------|----------|
| **Always loaded** | System | Every session start | `CLAUDE.md` (~130 lines), `core/rules/framework_rules.md` (~173 lines) |
| **Scoped rules** | System | When matching file types are edited | `core/rules/testing-rules.md`, `security-rules.md`, `git-rules.md` |
| **LLM-determined** | The model | When it judges content is relevant | Role definitions (`core/roles/backend.md`) loaded when adopting a role |
| **Human-triggered** | The user | On explicit invocation | Slash commands (`/workflows:plan`), skills (`/skill:consultant`) |
| **Software-determined** | The system | Automatic on events | Lifecycle hooks (PreToolUse, PostToolUse, Stop) |

### 3. Isolation Level

How content interacts with the parent context:

| Level | Mechanism | Impact on Parent Context | Use Case |
|-------|-----------|-------------------------|----------|
| **Shared** | Default | Full bi-directional — everything seen and produced stays in context | Simple commands, role work |
| **Forked** | `context: fork` | Isolated — executes in separate window, returns summary only | Heavy analysis, reviews, external integrations |

## How the Plugin Applies Context Engineering

### Always-Loaded Content (Minimal, Critical)

The `CLAUDE.md` file contains only what every session needs:
- Routing protocol (compact 5-step version)
- Command/agent/skill catalogs (names and categories only)
- Context Activation Model table
- Key patterns (one-liner references with doc pointers)
- Best practices (numbered list, no explanations)

**Design decision**: CLAUDE.md was reduced from ~700 lines (v2.3) to ~500 (v2.4.0) to ~130 lines (v2.5.0). Content moved to on-demand reference docs:
- Routing question templates and decision matrix → `core/docs/ROUTING_REFERENCE.md`
- SOLID scoring tables and pattern details → `core/solid-pattern-matrix.md`
- MCP server details → `core/docs/MCP_INTEGRATION.md`
- Snapshot/metrics workflow → `core/docs/SESSION_CONTINUITY.md`
- Lifecycle hooks details → `core/docs/LIFECYCLE_HOOKS.md`
- Trust model and security → `core/rules/security-rules.md`
- TDD and Ralph Wiggum loop → `core/rules/testing-rules.md`
- Git workflow and conflicts → `core/rules/git-rules.md`

Additionally, `framework_rules.md` was reduced from ~464 to ~173 lines by extracting domain-specific content into scoped rule files and eliminating duplication with CLAUDE.md. Combined always-loaded context dropped from ~980 to ~300 lines (~70% reduction).

### On-Demand Content (Skills & Commands)

Skills are activated only when invoked. Each skill carries its own:
- **Description**: Tells the LLM when to suggest the skill
- **Model preference**: Can request `opus` for complex analysis
- **Context isolation**: `context: fork` for heavy skills
- **Hooks**: Scoped lifecycle hooks in frontmatter

```yaml
---
name: consultant
description: "Deep project analysis across 7 layers..."
model: opus
context: fork
hooks:
  Stop:
    - command: "echo '[consultant] Analysis complete.'"
---
```

The description field serves double duty: it helps the LLM route to the right skill AND provides the human a quick reference of what the skill does.

### Forked Context (Heavy Analysis)

Skills and agents marked with `context: fork` execute in isolated context windows:

```
Parent Context                    Forked Context
┌─────────────┐                  ┌──────────────────┐
│ User session │──invokes────▶   │ consultant skill  │
│              │                  │ (isolated)        │
│ Clean context│                  │ Reads 30 files    │
│ preserved    │◀──summary────   │ Analyzes patterns │
│              │                  │ Returns: summary  │
└─────────────┘                  └──────────────────┘
```

### Fork Strategy Providers

The fork decision depends on the active provider (see `core/providers.yaml` → `fork_strategy`):

#### Aggressive Fork (standard tier — Opus 4.5, Sonnet, Haiku)

Fork everything marked with `context: fork`. This protects the limited context window.

**Forked skills** (7): consultant, token-advisor, coverage-checker, solid-analyzer, spec-merger, changelog-generator, mcp-connector

**Forked agents** (7): security-review, performance-review, ddd-compliance, code-review-ts, agent-native-reviewer, code-simplicity-reviewer, pattern-recognition-specialist

**When to fork**: Always, if declared with `context: fork`.

#### Selective Fork (advanced tier — Opus 4.6+ with 200K-1M context)

With larger context windows, fork only when isolation is truly needed:

**Always fork** (regardless of tier):
- consultant (7-layer deep analysis, always heavy)
- security-review (sensitive — isolation is a security feature, not just a space optimization)
- mcp-connector (external services — isolation prevents side effects)

**Fork only when heavy** (convert to inline when focused):
- coverage-checker: Fork when full project scan, inline when single module
- changelog-generator: Typically lightweight (reads git log), inline by default
- token-advisor: Quick context check, inline by default
- spec-merger: Fork when merging multiple specs, inline for single spec

**Fork decision thresholds** (selective mode):
- Reads > 30 files → fork
- Generates > 500 lines of output → fork
- Cross-codebase analysis → fork
- Connects to external services → fork
- Single-domain, focused analysis → inline

#### Detection

```
1. READ core/providers.yaml → providers.fork_strategy
2. IF "auto" → detect tier from model identity
3. advanced tier → selective fork
4. standard/lightweight tier → aggressive fork
```

### When to fork a skill (general guidelines)

- It reads more than 10 files (aggressive) or 30 files (selective)
- It generates output longer than 200 lines (aggressive) or 500 lines (selective)
- It performs cross-codebase analysis
- It connects to external services
- Its output is consumed as a summary, not line-by-line

### Portable Governance (Hooks in Frontmatter)

Traditional approach: all hooks in a central `.claude/settings.json`.
Context engineering approach: hooks travel with the skill in YAML frontmatter.

**Benefits**:
1. **Portability**: Install the plugin, hooks come included
2. **Scoping**: Each skill has only the hooks relevant to it
3. **Discoverability**: Reading a skill file shows its complete behavior
4. **Maintenance**: Change a skill's governance by editing one file

**Hook types and their uses in this plugin**:

| Hook | Matcher | Purpose | Example Skills |
|------|---------|---------|---------------|
| `PreToolUse` | `Bash` | Validate before execution | layer-validator, ddd-compliance, commit-formatter |
| `PreToolUse` | `Write` | Validate before file changes | checkpoint |
| `PreToolUse` | `mcp__*` | Pre-flight for MCP calls | mcp-connector |
| `PostToolUse` | `Bash` | Audit logging after execution | test-runner, lint-fixer, coverage-checker, git-sync, worktree-manager |
| `PostToolUse` | `mcp__*` | Audit MCP interactions | mcp-connector |
| `Stop` | (all) | Final report, cleanup | Most skills and review agents |

### Queen Agent Pattern (Dynamic Routing)

The `/workflows:route` command can operate as a Queen Agent — instead of static decision trees, it spawns forked sub-agents for parallel analysis:

```
User Request: "Necesito agregar pagos con Stripe"
        │
        ▼
   Queen Agent spawns 3 forked workers:
        │
   ┌────┼────────────────┐
   │    │                 │
   ▼    ▼                 ▼
consultant  spec-analyzer  git-historian
(0.8s)      (0.5s)         (0.6s)
   │         │              │
   └────┬────┘──────────────┘
        │
        ▼
   Aggregated Evidence:
   - Stack: Symfony 6.4 + DDD (consultant)
   - No payment specs exist (spec-analyzer)
   - Greenfield area, no recent changes (git-historian)
        │
        ▼
   Informed Decision:
   → task-breakdown workflow (HIGH complexity)
   → LOW trust level (payments)
   → security + performance reviews required
```

**When to activate**: Ambiguous requests, complex features, sensitive areas.
**When to skip**: Clear simple requests, continuing previous work.

## Fowler's Warnings Applied

Fowler explicitly warns against several temptations:

### 1. "More context = better results"
**Reality**: Overloading context reduces quality. The plugin addresses this by:
- Keeping CLAUDE.md focused on critical rules only
- Using `context: fork` so analysis output doesn't accumulate
- Classifying content by activation method (not everything loads at once)

### 2. "We can ensure the agent does X"
**Reality**: With LLMs, certainty is impossible. The plugin addresses this by:
- Quality gates (SOLID ≥18/25) as probabilistic filters, not guarantees
- Ralph Wiggum pattern (retry up to 10 times, then escalate)
- Multiple review agents for cross-validation
- Human checkpoints at critical moments

### 3. "Copy someone else's configuration"
**Reality**: What works for one team may not work for another. The plugin addresses this by:
- Separating immutable framework (`core/`) from customizable project (`.ai/`)
- Providing templates that teams adapt, not prescriptions they follow
- `/workflows:compound` captures team-specific patterns over time

## Hightower's Process Model Applied

Hightower's key insight is that Claude Code 2.1 features enable a **process model** for agents:

| OS Concept | Claude Code Equivalent | Plugin Implementation |
|---|---|---|
| Process | Skill with `context: fork` | 7 forked skills + 7 forked agents |
| Process lifecycle | Hooks (PreToolUse, PostToolUse, Stop) | Scoped hooks in 13 skills/agents |
| IPC (Inter-Process Communication) | Hook emissions + result summaries | Queen Agent observes sub-agent hooks |
| Hot-reload | Save → instant skill reload | `/workflows:skill-dev` development loop |
| Process isolation | Forked context window | Review agents don't pollute work context |

This makes the plugin function as an **agent operating system** where:
- Skills are processes with lifecycles
- Hooks are the event bus
- Fork provides memory isolation
- The Queen Agent is the process scheduler

## Quick Reference

### Which skills are forked?
```bash
grep -rl "context: fork" plugins/multi-agent-workflow/skills/
grep -rl "context: fork" plugins/multi-agent-workflow/agents/
```

### Which skills have hooks?
```bash
grep -rl "hooks:" plugins/multi-agent-workflow/skills/
grep -rl "hooks:" plugins/multi-agent-workflow/agents/
```

### How to add context: fork to a skill
Add to the YAML frontmatter:
```yaml
---
name: my-skill
context: fork
---
```

### How to add a scoped hook to a skill
Add to the YAML frontmatter:
```yaml
---
name: my-skill
hooks:
  PostToolUse:
    - matcher: Bash
      command: "echo '[my-skill] Step completed'"
  Stop:
    - command: "echo '[my-skill] Done.'"
---
```

### How to develop a new skill with hot-reload
```bash
/workflows:skill-dev my-new-skill --create
# Edit the generated SKILL.md
# Save → auto-reload → test → iterate
/workflows:skill-dev my-new-skill --validate
/workflows:skill-dev my-new-skill --test
```

## Related Documentation

- `CLAUDE.md` — Plugin instructions with Context Activation Model
- `README.md` — Plugin overview with Intellectual Influences section
- `core/docs/LIFECYCLE_HOOKS.md` — Detailed hook system documentation
- `core/docs/SESSION_CONTINUITY.md` — Context management strategies
- `commands/workflows/skill-dev.md` — Skill development workflow
- `commands/workflows/route.md` — Queen Agent pattern details

## Sources

- Fowler, M. (2025). "Context Engineering for Coding Agents." martinfowler.com.
- Hightower, R. (2025). "Build Agent Skills Faster with Claude Code 2.1 Release." Medium/Spillwave.
- Anthropic (2025). Claude Code SDK documentation — Hooks, Skills, Agents.
