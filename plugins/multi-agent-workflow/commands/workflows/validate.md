---
name: workflows:validate
description: "Validate YAML specs against JSON schemas"
argument_hint: <spec-path> [--all] [--json]
---

# Multi-Agent Workflow: Validate

> **Tier 3 (Automatic)**: Spec validation runs automatically inside `/workflows:plan` (Phase 2: Specs). Only invoke this command manually to validate specs independently.

Validate specification files (YAML) against their JSON schemas.

## Usage

```bash
# Validate a single spec
/workflows:validate .ai/project/features/auth/spec.yaml

# Validate all specs in features directory
/workflows:validate --all

# Get JSON output
/workflows:validate spec.yaml --json
```

## Output Example

```
╔════════════════════════════════════════════════════════════╗
║               SPEC VALIDATION                              ║
╚════════════════════════════════════════════════════════════╝

File: .ai/project/features/auth/spec.yaml
Type: feature

YAML syntax: valid
Validating against: feature schema
Schema validation: PASSED

Status: VALID
```

## Spec Types

| Type | Schema | Used For |
|------|--------|----------|
| **feature** | `feature_spec.json` | Feature specifications |
| **api** | `api_contract.json` | API contracts |
| **task** | `task_spec.json` | Individual tasks |

The validator auto-detects spec type based on content.

## Validation Checks

### YAML Syntax
- Proper YAML formatting
- No syntax errors
- Valid structure

### Schema Validation
- Required fields present
- Field types correct
- Enum values valid
- Pattern matching (IDs, etc.)

## Required Fields by Type

### Feature Spec
```yaml
version: "1.0"           # Required
feature:
  id: "feature-id"       # Required (kebab-case)
  name: "Feature Name"   # Required
  priority: medium       # Required (critical/high/medium/low)
  status: planning       # Required
objective:
  summary: "..."         # Required
requirements:            # Required
  functional: [...]
```

### API Contract
```yaml
version: "1.0"           # Required
api:
  name: "API Name"       # Required
  base_path: "/api/v1"   # Required
  endpoints: [...]       # Required (at least one)
```

### Task Spec
```yaml
version: "1.0"           # Required
task:
  id: "BE-001"           # Required (pattern: XX-NNN)
  title: "Task"          # Required
  role: backend          # Required
  acceptance_criteria:   # Required
    - "..."
  done_when: "..."       # Required
```

## JSON Output

```bash
/workflows:validate spec.yaml --json
```

Returns:
```json
{
  "path": "spec.yaml",
  "type": "feature",
  "syntax_valid": true,
  "schema_valid": true,
  "errors": []
}
```

## Validate All

```bash
/workflows:validate --all
```

Scans `.ai/project/features/` for all YAML files and validates each.

Output:
```
=== Spec Validation Report ===
Directory: .ai/project/features

Validating: .ai/project/features/auth/spec.yaml
YAML syntax: valid
Schema validation: PASSED

Validating: .ai/project/features/payments/spec.yaml
YAML syntax: valid
Schema validation: PASSED

=== Summary ===
Total:  2
Passed: 2
Failed: 0
```

## Implementation

This command executes:

```bash
source .ai/workflow/specs/validator.sh

case "$ARGUMENTS" in
    --all)
        spec_validate_all ".ai/project/features"
        ;;
    --json)
        spec_validate_json "$SPEC_PATH"
        ;;
    *)
        spec_display "$SPEC_PATH"
        ;;
esac
```

## Compliance Checking

Beyond validation, check if implementation matches spec:

```bash
source .ai/workflow/specs/validator.sh

# Check compliance
spec_check_compliance "spec.yaml" "src/module.sh"
```

Returns compliance report showing:
- Whether tasks exist
- Requirement count
- Test coverage requirements

## Programmatic Use

```bash
source .ai/workflow/specs/validator.sh

# Validate and get result
if spec_validate "spec.yaml"; then
    echo "Spec is valid!"
fi

# Check syntax only
spec_validate_syntax "spec.yaml"

# Get JSON result for scripting
result=$(spec_validate_json "spec.yaml")
is_valid=$(echo "$result" | jq -r '.schema_valid')
```

## Requirements

For full validation:
- **yq** - YAML processor
- **jq** - JSON processor

Install:
```bash
# Debian/Ubuntu
apt install jq

# macOS
brew install yq jq
```

Basic syntax validation works without these tools using Python.

## Related Commands

- `/workflows:interview` - Generate specs through guided interview
- `/workflows:plan` - Plan feature implementation
- `/workflows:spec` - View spec contents

## Schema Location

Schemas are stored in:
```
.ai/workflow/specs/schema/
├── feature_spec.json
├── api_contract.json
└── task_spec.json
```

Templates are in:
```
.ai/workflow/specs/templates/
├── feature_spec.yaml
└── api_contract.yaml
```
