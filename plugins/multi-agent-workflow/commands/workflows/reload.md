---
name: workflows:reload
description: "Hot-reload skills, agents, or workflow configurations without losing state or conversation context."
argument_hint: --skill=<name> | --agent=<name> | --all
---

# Multi-Agent Workflow: Reload

> **Tier 4 (Developer-Only)**: This command is for plugin development, not for feature work. Use it to hot-reload modified skill/agent definitions mid-session.

Hot-reload workflow components (skills, agents, workflow configurations) without losing current state or conversation context.

## Usage

```
/workflows:reload --skill=test-runner
/workflows:reload --agent=security-review
/workflows:reload --all
```

## Why Hot-Reload Matters

During active development sessions, you may:
- Modify skill definitions to fix behavior
- Update agent instructions mid-session
- Adjust workflow configurations
- Add new components without restarting

Hot-reload enables applying these changes **immediately** while:
- Preserving `50_state.md` and checkpoint progress
- Maintaining conversation context
- Keeping worktree state intact
- Avoiding session restart overhead

## Arguments

| Argument | Description | Example |
|----------|-------------|---------|
| `--skill=<name>` | Reload a specific skill | `--skill=test-runner` |
| `--agent=<name>` | Reload a specific agent | `--agent=security-review` |
| `--all` | Reload all skills, agents, and workflows | `--all` |

## Execution Steps

### Step 1: Preserve Current State

Before reloading, ensure state is preserved:

```bash
# Verify state file exists and note its location
STATE_FILE=".ai/project/features/$FEATURE/50_state.md"
if [ -f "$STATE_FILE" ]; then
    echo "State preserved: $STATE_FILE"
    echo "Last modified: $(stat -c %y "$STATE_FILE" 2>/dev/null || stat -f %Sm "$STATE_FILE")"
fi
```

### Step 2: Reload Based on Arguments

#### Option A: Reload Specific Skill (--skill=<name>)

```bash
SKILL_NAME="$1"  # e.g., test-runner

# Locate skill definition
SKILL_PATH="plugins/multi-agent-workflow/skills/$SKILL_NAME/SKILL.md"

if [ -f "$SKILL_PATH" ]; then
    echo "Reloading skill: $SKILL_NAME"
    echo "Source: $SKILL_PATH"
    # Re-read the skill definition into context
    cat "$SKILL_PATH"
    echo ""
    echo "Skill '$SKILL_NAME' reloaded successfully."
else
    echo "Error: Skill '$SKILL_NAME' not found at $SKILL_PATH"
    echo ""
    echo "Available skills:"
    ls -1 plugins/multi-agent-workflow/skills/
fi
```

#### Option B: Reload Specific Agent (--agent=<name>)

```bash
AGENT_NAME="$1"  # e.g., security-review

# Search for agent in all categories
AGENT_PATH=$(find plugins/multi-agent-workflow/agents -name "$AGENT_NAME.md" -type f 2>/dev/null | head -1)

if [ -n "$AGENT_PATH" ]; then
    echo "Reloading agent: $AGENT_NAME"
    echo "Source: $AGENT_PATH"
    # Re-read the agent definition into context
    cat "$AGENT_PATH"
    echo ""
    echo "Agent '$AGENT_NAME' reloaded successfully."
else
    echo "Error: Agent '$AGENT_NAME' not found"
    echo ""
    echo "Available agents:"
    find plugins/multi-agent-workflow/agents -name "*.md" -type f | sed 's|.*/||' | sed 's|\.md$||' | sort
fi
```

#### Option C: Reload All (--all)

```bash
echo "=== Reloading All Workflow Components ==="
echo ""

# 1. Reload all skills
echo "--- Skills ---"
for skill_dir in plugins/multi-agent-workflow/skills/*/; do
    skill_name=$(basename "$skill_dir")
    skill_file="$skill_dir/SKILL.md"
    if [ -f "$skill_file" ]; then
        echo "  [OK] $skill_name"
    fi
done
echo ""

# 2. Reload all agents
echo "--- Agents ---"
for agent_file in plugins/multi-agent-workflow/agents/**/*.md; do
    if [ -f "$agent_file" ]; then
        agent_name=$(basename "$agent_file" .md)
        category=$(basename $(dirname "$agent_file"))
        echo "  [OK] $category/$agent_name"
    fi
done
echo ""

# 3. Reload workflow configurations
echo "--- Workflow Configurations ---"
for workflow_file in .ai/extensions/workflows/*.yaml; do
    if [ -f "$workflow_file" ]; then
        workflow_name=$(basename "$workflow_file" .yaml)
        echo "  [OK] $workflow_name"
    fi
done
echo ""

echo "=== Reload Complete ==="
```

### Step 3: Verify State Integrity

```bash
# Confirm state was not affected
if [ -f "$STATE_FILE" ]; then
    echo "State verification:"
    echo "  File: $STATE_FILE"
    echo "  Status: Preserved"
    echo ""
    echo "Current state summary:"
    grep -E "^\*\*Status\*\*:|^## .* Engineer" "$STATE_FILE" | head -10
fi
```

### Step 4: Report Reload Status

```
Reload Summary
==============

Component Type: <skill|agent|all>
Component Name: <name or "all components">
Status: SUCCESS

Preserved Context:
  - State file: .ai/project/features/<feature>/50_state.md
  - Conversation: Maintained
  - Worktree: Unchanged

Reloaded Sources:
  - plugins/multi-agent-workflow/skills/*/SKILL.md
  - plugins/multi-agent-workflow/agents/**/*.md
  - .ai/extensions/workflows/*.yaml

Ready to continue with updated definitions.
```

## Source Locations

The reload command fetches updated definitions from:

| Component | Location Pattern |
|-----------|------------------|
| Skills | `plugins/multi-agent-workflow/skills/*/SKILL.md` |
| Agents | `plugins/multi-agent-workflow/agents/**/*.md` |
| Workflows | `.ai/extensions/workflows/*.yaml` |

## Available Skills

| Skill | Category | Description |
|-------|----------|-------------|
| `consultant` | Core | Expert consultation |
| `checkpoint` | Core | Progress checkpointing |
| `git-sync` | Core | Git synchronization |
| `test-runner` | Quality | Test execution |
| `coverage-checker` | Quality | Coverage analysis |
| `lint-fixer` | Quality | Lint auto-fixing |
| `worktree-manager` | Workflow | Worktree management |
| `commit-formatter` | Workflow | Commit message formatting |
| `changelog-generator` | Compound | Changelog generation |
| `layer-validator` | Compound | DDD layer validation |

## Available Agents

| Agent | Category | Description |
|-------|----------|-------------|
| `security-review` | Review | Security analysis |
| `performance-review` | Review | Performance analysis |
| `ddd-compliance` | Review | DDD compliance checking |
| `code-review-ts` | Review | TypeScript code review |
| `api-designer` | Design | API design assistance |
| `ui-verifier` | Design | UI verification |
| `codebase-analyzer` | Research | Codebase analysis |
| `git-historian` | Research | Git history analysis |
| `dependency-auditor` | Research | Dependency auditing |
| `bug-reproducer` | Workflow | Bug reproduction |
| `spec-analyzer` | Workflow | Spec analysis |
| `style-enforcer` | Workflow | Style enforcement |
| `comprehension-guardian` | Workflow | Comprehension debt tracking |

## Examples

### Reload After Modifying a Skill

```bash
# 1. Edit the skill definition
vim plugins/multi-agent-workflow/skills/test-runner/SKILL.md

# 2. Hot-reload the skill
/workflows:reload --skill=test-runner

# 3. Continue using the updated skill
/workflows:work --mode=tdd user-auth
```

### Reload After Adding New Agent

```bash
# 1. Create new agent
vim plugins/multi-agent-workflow/agents/review/accessibility-review.md

# 2. Reload all agents
/workflows:reload --agent=accessibility-review

# 3. Use the new agent
/workflows:review --agent=accessibility-review user-auth
```

### Full Reload After Git Pull

```bash
# 1. Pull latest changes
git pull origin main

# 2. Reload everything
/workflows:reload --all

# 3. Resume work with updated definitions
/workflows:status user-auth
```

## What Gets Preserved

| Component | Preserved | Notes |
|-----------|-----------|-------|
| `50_state.md` | Yes | All role states and checkpoints |
| Conversation context | Yes | Full conversation history |
| Worktree state | Yes | All git worktrees intact |
| Environment variables | Yes | Shell state maintained |
| Feature progress | Yes | Tasks and completion status |

## What Gets Reloaded

| Component | Reloaded | Notes |
|-----------|----------|-------|
| Skill definitions | Yes | From `skills/*/SKILL.md` |
| Agent instructions | Yes | From `agents/**/*.md` |
| Workflow configs | Yes | From `.ai/extensions/workflows/*.yaml` |
| Command definitions | No | Requires session restart |
| Plugin metadata | No | Requires plugin reinstall |

## Troubleshooting

### Skill Not Found

```
Error: Skill 'unknown-skill' not found

Solution: Check available skills with:
  ls plugins/multi-agent-workflow/skills/
```

### Agent Not Found

```
Error: Agent 'unknown-agent' not found

Solution: Check available agents with:
  find plugins/multi-agent-workflow/agents -name "*.md"
```

### State File Missing

```
Warning: No state file found for current feature

Solution: This is normal if no feature is active.
          State will be created when you start a feature.
```

## Best Practices

1. **Reload after editing**: Always reload after modifying skill/agent definitions
2. **Use specific reloads**: Prefer `--skill` or `--agent` over `--all` for faster reload
3. **Verify state**: Check `50_state.md` after reload to confirm preservation
4. **Test changes**: Run a simple command to verify the reload worked
5. **Document changes**: If modifying shared skills, document what changed
