---
name: workflows:skill-dev
description: "Interactive skill development mode with hot-reload. Edit skills in real-time without restarting sessions."
argument_hint: <skill-name> [--create|--edit|--test|--validate]
---

# /workflows:skill-dev - Skill Development Mode

> **Tier 4 (Developer-Only)**: This command is for plugin development, not for feature work. Use it to create, edit, and test plugin skills.

**Version**: 1.0.0
**Category**: Development
**Requires**: Claude Code 2.1+ (hot-reload support)

---

## Purpose

Enable rapid iterative development of skills with automatic hot-reload. When you save a skill file, Claude Code 2.1+ automatically picks up changes without session restart or reinstall.

**Inspired by**: Hightower's "Build Agent Skills Faster" - treating skills as live, editable units with instant feedback loops.

## Invocation

```bash
# Enter skill development mode for existing skill
/workflows:skill-dev test-runner

# Create a new skill from scratch
/workflows:skill-dev my-new-skill --create

# Validate skill frontmatter and structure
/workflows:skill-dev test-runner --validate

# Test skill invocation in isolated context
/workflows:skill-dev test-runner --test
```

## Workflow

### Mode: --create (New Skill)

```
Step 1: Scaffold
├── Ask: skill name, description, purpose
├── Generate SKILL.md with YAML frontmatter template
├── Include: name, description, model, context, hooks
└── Place in: plugins/multi-agent-workflow/skills/<name>/SKILL.md

Step 2: Configure Frontmatter
├── Set context: fork (if skill generates heavy output)
├── Define hooks (PreToolUse, PostToolUse, Stop)
├── Set model preference (inherit, opus, sonnet)
└── Validate YAML syntax

Step 3: Write Instructions
├── Define "What This Skill Does"
├── Define "When to Use"
├── Add commands and examples
├── Add output format template
└── Add integration points with workflow

Step 4: Hot-Reload Test
├── Save file → automatic reload
├── Invoke skill via /skill:<name>
├── Verify output matches expectations
└── Iterate: edit → save → test → repeat
```

### Mode: --edit (Existing Skill)

```
Step 1: Load skill file
├── Read current SKILL.md
├── Show current frontmatter configuration
└── Identify missing Claude Code 2.1 features

Step 2: Suggest Enhancements
├── Missing context: fork? (for heavy skills)
├── Missing hooks? (for governance)
├── Missing model specification?
├── Outdated patterns?
└── Present suggestions as checklist

Step 3: Apply Changes
├── Edit frontmatter with user approval
├── Hot-reload automatically on save
└── Verify changes took effect

Step 4: Test
├── Invoke skill in current session
├── Verify behavior matches expectations
└── Document any issues
```

### Mode: --validate

Validates skill file structure:

```markdown
## Skill Validation Report: <skill-name>

### Frontmatter Check
| Field | Status | Value |
|-------|--------|-------|
| name | ok/missing | <value> |
| description | ok/missing | <value> |
| model | ok/missing/default | <value> |
| context | ok/missing | fork/inherit |
| hooks | ok/none | <count> hooks |

### Structure Check
| Section | Status |
|---------|--------|
| Title (# heading) | ok/missing |
| What This Skill Does | ok/missing |
| When to Use | ok/missing |
| Commands/Process | ok/missing |
| Output Format | ok/missing |
| Integration | ok/missing |

### Hook Validation
| Hook | Matcher | Command | Valid |
|------|---------|---------|-------|
| PreToolUse | <matcher> | <cmd> | ok/error |
| PostToolUse | <matcher> | <cmd> | ok/error |
| Stop | - | <cmd> | ok/error |

### Verdict: VALID / NEEDS FIXES
```

### Mode: --test

Tests skill in forked context:

```markdown
## Skill Test: <skill-name>

### Test Environment
- Context: forked (isolated)
- Model: <skill model or inherited>
- Hooks: <count> active

### Invocation Test
- Command: /skill:<name>
- Result: SUCCESS / FAILURE
- Output preview: [first 50 lines]

### Hook Test
- PreToolUse fired: yes/no
- PostToolUse fired: yes/no
- Stop fired: yes/no

### Performance
- Execution time: ~Xs
- Context impact: minimal (forked) / high (shared)
```

## Skill Template

When creating new skills, use this template:

```yaml
---
name: <skill-name>
description: "<one-line description>. <example>Context: <when>.\\nuser: \"<trigger>\"\\nassistant: \"<response>\"</example>"
model: inherit
context: fork  # Use for heavy-output skills
hooks:
  PreToolUse:
    - matcher: <tool-pattern>
      command: "echo '[<skill-name>] Pre-check...'"
  PostToolUse:
    - matcher: <tool-pattern>
      command: "echo '[<skill-name>] Step completed'"
  Stop:
    - command: "echo '[<skill-name>] Complete.'"
---

# <Skill Name> Skill

<One-line description>.

## What This Skill Does

- <capability 1>
- <capability 2>
- <capability 3>

## When to Use

- <scenario 1>
- <scenario 2>

## Process

### Step 1: <Name>
<instructions>

### Step 2: <Name>
<instructions>

## Output Format

\`\`\`markdown
## <Skill Name> Report

<template>
\`\`\`

## Integration with Workflow

Used by: <commands that invoke this skill>
```

## Hot-Reload Development Loop

```
┌──────────────┐
│  Edit skill  │
│  SKILL.md    │
└──────┬───────┘
       │ save
       ▼
┌──────────────┐
│  Auto-reload │ ← Claude Code 2.1 hot-reload
│  (instant)   │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  Test with   │
│  /skill:<n>  │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│  Review      │
│  output      │──── satisfied? → Done
└──────┬───────┘
       │ iterate
       └──────────────► back to Edit
```

## Best Practices

1. **Start with frontmatter**: Define name, description, context, hooks FIRST
2. **Fork heavy skills**: Use `context: fork` for skills that read many files or generate long reports
3. **Scope your hooks**: Use specific matchers (e.g., `Bash`, `Write`) not catch-all
4. **Test in isolation**: Use `--test` mode to verify in forked context before production use
5. **Keep descriptions actionable**: Include example triggers in description for better LLM routing
6. **Iterate fast**: The hot-reload loop means you can refine skills in minutes, not hours

## Related

- `/workflows:reload` - Manual hot-reload trigger
- `/workflows:validate` - Validate all skills at once
- `consultant` skill - For initial project analysis
- `core/docs/SKILL_DEVELOPMENT.md` - Detailed skill authoring guide
