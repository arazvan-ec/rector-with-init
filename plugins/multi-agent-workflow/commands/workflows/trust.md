---
name: workflows:trust
description: "Check trust level and supervision requirements for files or tasks"
argument_hint: <filepath | --task <type> | --batch <files...>>
---

# Multi-Agent Workflow: Trust

> **Tier 3 (Automatic)**: Trust evaluation runs automatically inside `/workflows:route` (routing logic) and `/workflows:work` (execution mode resolution). Only invoke this command manually to inspect trust levels of specific files.

Evaluate the trust level of files or tasks to determine appropriate supervision.

## Usage

```bash
# Check trust for a file
/workflows:trust src/User.php
/workflows:trust src/Security/AuthService.php

# Check trust for a task type
/workflows:trust --task payment_integration
/workflows:trust --task unit_tests

# Check multiple files (returns lowest trust)
/workflows:trust --batch src/User.php src/Auth.php tests/UserTest.php
```

## Trust Levels

| Level | Auto-Approve | Supervision | Use Cases |
|-------|--------------|-------------|-----------|
| **HIGH** | Yes | Minimal | Tests, docs, boilerplate |
| **MEDIUM** | No | Code review | Features, APIs, UI |
| **LOW** | No | Pair programming | Security, auth, payments |

## Output Example

```
=== Trust Evaluation ===
File: src/Security/AuthService.php

Trust Level: LOW
Auto-approve: NO
Supervision: pair_programming

Recommendations:
  - Work with human reviewer present
  - Document all decisions in DECISIONS.md
  - Run security scan before commit
  - Consider threat modeling
```

## Trust Model Configuration

Trust levels are configured in `.ai/workflow/trust_model.yaml`:

```yaml
trust_levels:
  high:
    description: "AI can work autonomously"
    auto_approve: true
    contexts:
      - pattern: "*.test.ts"
      - pattern: "docs/**/*"
      - task_type: "boilerplate"

  medium:
    description: "AI works, human reviews"
    auto_approve: false
    contexts:
      - pattern: "src/**/*"
      - task_type: "feature_implementation"

  low:
    description: "AI suggests, human implements"
    auto_approve: false
    escalation: true
    contexts:
      - pattern: "**/*security*/**"
      - pattern: "**/*payment*/**"
      - task_type: "authentication"
```

## Pattern Matching

Patterns support glob-style matching:

| Pattern | Matches |
|---------|---------|
| `*.test.ts` | `user.test.ts` |
| `**/*security*/**` | `src/security/auth.ts` |
| `docs/**/*` | `docs/api/readme.md` |
| `*Auth*.php` | `UserAuthService.php` |

## Task Types

### High Trust Tasks
- `boilerplate` - Repetitive code
- `documentation` - Writing docs
- `unit_tests` - Test files
- `formatting` - Code style
- `refactoring_simple` - Rename, extract

### Medium Trust Tasks
- `feature_implementation` - New features
- `api_endpoints` - API code
- `ui_components` - Frontend
- `bug_fix` - Fixing bugs
- `refactoring_complex` - Major changes

### Low Trust Tasks
- `security` - Security code
- `authentication` - Auth systems
- `authorization` - Permissions
- `payment` - Financial code
- `migration` - DB changes
- `infrastructure` - DevOps
- `cryptography` - Crypto code

## Implementation

This command executes:

```bash
source .ai/workflow/enforcement/trust_evaluator.sh

case "$ARGUMENTS" in
    --task)
        trust_evaluate_task "$TASK_TYPE"
        ;;
    --batch)
        trust_check_batch "${FILES[@]}"
        ;;
    *)
        trust_display "$FILEPATH"
        ;;
esac
```

## Programmatic Use

```bash
source .ai/workflow/enforcement/trust_evaluator.sh

# Get trust level
level=$(trust_get_level "src/Auth.php")  # Returns: low

# Check auto-approve
if trust_can_auto_approve "tests/UserTest.php"; then
    echo "Safe to auto-approve"
fi

# Get JSON assessment
trust_assess "src/Payment.php" | jq '.'
```

## Integration with Workflow

Trust levels affect workflow behavior:

### High Trust Files
- Can be auto-committed
- Minimal review required
- Standard CI checks

### Medium Trust Files
- Require code review
- Must pass all tests
- PR before merge

### Low Trust Files
- Block auto-commit
- Require human oversight
- Security scan required
- Document in DECISIONS.md

## Best Practices

1. **Check before coding**: Know the trust level before starting
2. **Respect the level**: Don't try to bypass low-trust restrictions
3. **Document decisions**: Especially for low-trust code
4. **Ask for help**: Low trust = get a human involved

## Why Trust Levels?

Based on Addy Osmani's research on the "70% Problem":

> AI can rapidly produce 70% of a solution, but that final 30% – edge cases, security, production integration – remains challenging.

Trust levels help calibrate AI supervision:
- Let AI work freely on safe tasks (high trust)
- Review AI work on normal tasks (medium trust)
- Closely supervise AI on risky tasks (low trust)

## Related Commands

- `/workflows:progress` - Track session progress
- `/workflows:tdd` - Check TDD compliance
- `/workflows:review` - Request code review

## Source

Inspired by [The 70% Problem](https://addyosmani.com/blog/ai-coding-workflow/) by Addy Osmani.
