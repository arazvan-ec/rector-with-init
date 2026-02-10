# Project Extensions

This directory contains **project-specific extensions** to the Multi-Agent Workflow plugin.

## Directory Structure

```
.ai/extensions/
├── rules/              # Project-specific rules
│   ├── project_rules.md    # Main project rules (stack, conventions)
│   └── ddd_rules.md        # DDD rules (if applicable)
├── workflows/          # Custom workflow definitions
│   ├── default.yaml        # Default workflow
│   └── *.yaml              # Additional workflows
├── trust/              # Trust model configuration
│   └── trust_model.yaml    # File-based trust levels
├── scripts/            # Utility scripts
│   ├── git_sync.sh
│   └── *.sh
└── README.md           # This file
```

## What Goes Where

### Plugin Core (DO NOT MODIFY)
Location: `plugins/multi-agent-workflow/core/`

Contains:
- **Framework Rules**: Fundamental methodology rules
- **Base Roles**: Role definitions (planner, backend, frontend, qa)
- **Schemas**: JSON schemas for validation
- **Templates**: YAML templates for specs
- **Docs**: Methodology documentation

### Project Extensions (CUSTOMIZE HERE)
Location: `.ai/extensions/`

Contains:
- **Project Rules**: Stack-specific rules, conventions
- **Workflows**: Custom workflow definitions for this project
- **Trust Model**: File-based trust configuration
- **Scripts**: Project utility scripts

### Project Specs (FEATURE DATA)
Location: `.ai/project/`

Contains:
- **Features**: Feature specifications and state
- **Config**: Project configuration
- **Context**: Project context documentation
- **Sessions**: Checkpoint data

## How Extension Works

### 1. Rules Extension

The plugin loads rules in this order:
1. `plugins/.../core/rules/framework_rules.md` (base, immutable)
2. `.ai/extensions/rules/project_rules.md` (project-specific)
3. `.ai/extensions/rules/ddd_rules.md` (if using DDD)

Project rules can:
- Add new rules specific to the stack
- Define coverage thresholds
- Set code style standards
- Configure quality metrics

Project rules should NOT:
- Override fundamental framework rules
- Change the workflow methodology

### 2. Workflows Extension

Create custom workflows in `.ai/extensions/workflows/`:

```yaml
# .ai/extensions/workflows/my-workflow.yaml
name: "My Custom Workflow"
roles:
  - planner
  - backend
  - qa

stages:
  - id: planning
    role: planner
    # ...
```

### 3. Trust Model Extension

Configure trust levels in `.ai/extensions/trust/trust_model.yaml`:

```yaml
# File patterns and their trust levels
trust_levels:
  high_control:
    - "src/Auth/**"
    - "src/Payment/**"
  medium_control:
    - "src/Domain/**"
  low_control:
    - "src/Infrastructure/**"
```

## Migration from Old Structure

If migrating from the old `.ai/workflow/` structure:

1. Rules: Copy to `.ai/extensions/rules/`
2. Workflows: Copy to `.ai/extensions/workflows/`
3. Scripts: Copy to `.ai/extensions/scripts/`
4. Trust model: Copy to `.ai/extensions/trust/`
5. Delete `.ai/workflow/` (after verification)

## Best Practices

1. **Don't modify plugin core** - Changes should go in extensions
2. **Version your extensions** - Include version in rules files
3. **Document changes** - Update DECISIONS.md for rule changes
4. **Keep rules focused** - Separate concerns into different files
5. **Review periodically** - Rules should be reviewed monthly

## Related Documentation

- Framework Rules: `plugins/multi-agent-workflow/core/rules/framework_rules.md`
- Plugin README: `plugins/multi-agent-workflow/README.md`
- CLAUDE.md: `plugins/multi-agent-workflow/CLAUDE.md`
