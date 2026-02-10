---
name: workflows:tdd
description: "Check TDD compliance and generate test templates"
argument_hint: <check | display <file> | generate <file>>
---

# Multi-Agent Workflow: TDD

> **Tier 3 (Automatic)**: TDD is enforced automatically inside `/workflows:work` (Step 5: TDD cycle). Only invoke this command manually to check TDD compliance of staged files or generate test templates outside the work flow.

Enforce Test-Driven Development practices and generate test templates.

## Usage

```bash
# Check staged files for TDD compliance
/workflows:tdd check

# Display TDD status for a file
/workflows:tdd display src/auth/user.ts

# Generate test template
/workflows:tdd generate src/module.ts

# Verify commit order (test-first)
/workflows:tdd verify HEAD~5..HEAD
```

## TDD Workflow

The TDD enforcer promotes the Red-Green-Refactor cycle:

1. **RED**: Write a failing test first
2. **GREEN**: Write minimum code to pass
3. **REFACTOR**: Clean up while keeping tests green

## Check Command

```bash
/workflows:tdd check
```

Checks all staged files:
- Detects missing test files
- Blocks test deletions
- Reports violations

Output:
```
Checking TDD compliance for staged files...

[2/2] Checking test coverage...
OK: src/auth/user.ts has test
WARNING: Missing test for: src/utils/helpers.ts

=== TDD Check Summary ===
Strictness: medium
Errors: 0
Warnings: 1

TDD check passed with warnings
```

## Display Command

```bash
/workflows:tdd display src/module.ts
```

Shows TDD status for a specific file:

```
╔════════════════════════════════════════════════════════════╗
║               TDD STATUS                                   ║
╚════════════════════════════════════════════════════════════╝

File: src/module.ts
Type: Source file

Expected test locations:
  [EXISTS] src/module.test.ts
  [MISSING] src/module.spec.ts
  [MISSING] tests/module.test.ts

Status: TDD COMPLIANT
```

## Generate Command

```bash
/workflows:tdd generate src/module.ts
```

Generates a test file template:

```typescript
import { describe, it, expect } from 'vitest';
// import { module } from './module';

describe('module', () => {
  it('should exist', () => {
    // TODO: Add tests
    expect(true).toBe(true);
  });

  // RED: Write failing test first
  // GREEN: Implement minimum code to pass
  // REFACTOR: Clean up while keeping tests green
});
```

### Supported Languages

| Language | Test Pattern | Template Type |
|----------|--------------|---------------|
| TypeScript | `*.test.ts`, `*.spec.ts` | Vitest |
| JavaScript | `*.test.js`, `*.spec.js` | Jest |
| Python | `test_*.py`, `*_test.py` | pytest |
| PHP | `*Test.php` | PHPUnit |
| Go | `*_test.go` | testing |
| Bash | `test_*.sh` | Custom |

## Strictness Levels

| Level | Missing Tests | Test Deletions |
|-------|---------------|----------------|
| **strict** | Block commit | Block commit |
| **medium** | Warn only | Block commit |
| **relaxed** | Skip check | Block commit |

Set with environment variable:
```bash
TDD_STRICTNESS=strict /workflows:tdd check
```

## Pre-commit Hook

Install the TDD pre-commit hook:

```bash
# Copy hook
cp .ai/workflow/hooks/pre_commit_tdd.sh .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

# Or symlink
ln -sf ../../.ai/workflow/hooks/pre_commit_tdd.sh .git/hooks/pre-commit
```

The hook runs automatically on every commit:
- Checks for test deletions (always blocked)
- Checks test coverage (based on strictness)

### Skip Hook

To skip TDD checks temporarily:

```bash
# Skip via environment
TDD_SKIP=true git commit -m "message"

# Skip via commit message
git commit -m "message [skip-tdd]"
```

## Implementation

This command executes:

```bash
source .ai/workflow/enforcement/tdd_enforcer.sh

case "$ARGUMENTS" in
    check)
        tdd_check_staged
        ;;
    display)
        tdd_display "$FILE_PATH"
        ;;
    generate)
        tdd_generate_test "$FILE_PATH"
        ;;
    verify)
        tdd_verify_order "$COMMIT_RANGE"
        ;;
esac
```

## Test File Discovery

The enforcer looks for test files in these locations:

### TypeScript/JavaScript
```
src/module.ts -> src/module.test.ts
                 src/module.spec.ts
                 src/__tests__/module.test.ts
                 tests/module.test.ts
```

### Python
```
src/module.py -> src/test_module.py
                 src/module_test.py
                 tests/test_module.py
```

### PHP
```
src/Module.php -> src/ModuleTest.php
                  tests/ModuleTest.php
                  tests/Unit/ModuleTest.php
```

## Best Practices

1. **Write tests first**: Before implementation code
2. **One test per feature**: Focus tests on specific behavior
3. **Keep tests independent**: No test should depend on another
4. **Name tests clearly**: `it('should calculate total with tax')`
5. **Don't delete tests**: Add new tests instead

## Integration with CI

```yaml
# .github/workflows/ci.yml
jobs:
  tdd-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: TDD Compliance Check
        run: |
          source .ai/workflow/enforcement/tdd_enforcer.sh
          TDD_STRICTNESS=strict tdd_check_staged
```

## Related Commands

- `/workflows:validate` - Validate spec files
- `/workflows:progress` - Track session progress
- `/workflows:compound` - Track learnings

## Source

Based on:
- Test-Driven Development by Kent Beck
- Anthropic's engineering practices
- "Working Effectively with Legacy Code" by Michael Feathers
