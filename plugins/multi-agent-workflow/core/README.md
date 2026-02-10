# Plugin Core

This directory contains the **core framework components** of the Multi-Agent Workflow plugin.

## Structure

```
core/
├── rules/              # Framework rules (immutable)
│   └── framework_rules.md
├── roles/              # Base role definitions
│   ├── planner.md
│   ├── backend.md
│   ├── frontend.md
│   └── qa.md
├── schemas/            # JSON schemas for validation
│   ├── feature_spec.json
│   ├── task_spec.json
│   └── api_contract.json
├── templates/          # YAML templates
│   ├── feature_spec.yaml
│   └── api_contract.yaml
├── docs/               # Methodology documentation
│   ├── COMPREHENSION_DEBT.md
│   ├── PAIRING_PATTERNS.md
│   └── GIT_WORKFLOW.md
└── README.md           # This file
```

## Important: DO NOT MODIFY

These files are part of the plugin core and should NOT be modified per-project.

For project-specific customizations, use:
- `.ai/extensions/rules/` for project rules
- `.ai/extensions/workflows/` for custom workflows
- `.ai/extensions/trust/` for trust configuration

## Components

### Rules (`rules/`)

Contains the fundamental framework rules that apply to ALL projects:
- Explicit context principle
- Immutable roles
- Workflow governance
- State synchronization
- Context window management
- Comprehension debt management
- Trust model methodology

### Roles (`roles/`)

Base definitions for the four core roles:
- **Planner**: Architect and coordinator
- **Backend**: Backend engineer
- **Frontend**: Frontend engineer
- **QA**: Quality assurance

These define responsibilities, permissions, and workflow for each role.

### Schemas (`schemas/`)

JSON schemas for validating:
- Feature specifications
- Task definitions
- API contracts

### Templates (`templates/`)

YAML templates for creating:
- Feature specifications
- API contracts

### Docs (`docs/`)

Methodology documentation:
- Comprehension Debt framework (Addy Osmani's 80% Problem)
- Pairing Patterns for AI collaboration
- Git workflow guidelines

## Version

This core is part of plugin version **2.0.0**.

See `plugins/multi-agent-workflow/.claude-plugin/plugin.json` for version info.
