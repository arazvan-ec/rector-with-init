---
name: coverage-checker
description: "Validates test coverage meets project requirements. Generates detailed coverage reports and blocks progression if thresholds are not met."
context: fork
hooks:
  PostToolUse:
    - matcher: Bash
      command: "echo '[coverage-checker] Coverage analysis step completed'"
  Stop:
    - command: "echo '[coverage-checker] Coverage report finalized'"
---

# Coverage Checker Skill

Validate test coverage meets project requirements.

## What This Skill Does

- Calculate test coverage
- Compare against thresholds
- Identify uncovered code
- Generate coverage reports
- Block progression if coverage insufficient

## Coverage Requirements

| Component | Minimum |
|-----------|---------|
| Backend | 80% |
| Frontend | 70% |
| Critical paths | 90% |

## Commands

### Backend (PHPUnit)

```bash
# Coverage text report
php bin/phpunit --coverage-text

# Coverage HTML report
php bin/phpunit --coverage-html coverage/

# Coverage for specific directory
php bin/phpunit --coverage-text tests/Unit/Domain/

# Coverage with filter
php bin/phpunit --coverage-text --coverage-filter src/Domain/
```

### Frontend (Jest)

```bash
# Coverage report
npm test -- --coverage

# Coverage with thresholds
npm test -- --coverage --coverageThreshold='{"global":{"lines":70}}'

# Coverage for specific files
npm test -- --coverage --collectCoverageFrom='src/components/**/*.tsx'
```

## Output Format

```markdown
## Coverage Report

**Date**: 2026-01-16
**Target**: Backend 80%, Frontend 70%

### Backend Coverage

| Directory | Lines | Covered | % | Status |
|-----------|-------|---------|---|--------|
| Domain/ | 200 | 185 | 92.5% | ✅ |
| Application/ | 150 | 128 | 85.3% | ✅ |
| Infrastructure/ | 300 | 234 | 78.0% | ❌ |
| **Total** | **650** | **547** | **84.2%** | ✅ |

### Frontend Coverage

| Directory | Lines | Covered | % | Status |
|-----------|-------|---------|---|--------|
| components/ | 400 | 320 | 80.0% | ✅ |
| hooks/ | 100 | 75 | 75.0% | ✅ |
| services/ | 150 | 90 | 60.0% | ❌ |
| **Total** | **650** | **485** | **74.6%** | ✅ |

### Uncovered Code

#### Critical (Must Cover)
1. `src/Infrastructure/Repository/DoctrineUserRepository.php:45-52`
   - Method: `findByEmail()`
   - Reason: No integration test

#### Non-Critical (Should Cover)
1. `src/components/ErrorBoundary.tsx:20-35`
   - Reason: Error handling paths

### Recommendations
1. Add integration test for `findByEmail()`
2. Add error boundary tests
```

## Threshold Enforcement

```bash
# If coverage below threshold:

Coverage Check: FAILED

Backend Coverage: 78% (required: 80%)
Missing: 2% (approximately 13 lines)

Uncovered files:
- DoctrineUserRepository.php: 65% (needs +15%)
- UserController.php: 72% (needs +8%)

Action Required:
1. Add tests for DoctrineUserRepository
2. Add tests for error paths in UserController

Checkpoint blocked until coverage >= 80%
```

## Integration with Checkpoint

```bash
# Coverage checked automatically at checkpoint
/workflows:checkpoint backend user-auth "Domain layer"

# If coverage insufficient:
# ❌ Checkpoint blocked
# Coverage: 78% (required: 80%)
# Add tests before proceeding
```

## CI/CD Integration

```yaml
# GitHub Actions
- name: Coverage Check
  run: |
    php bin/phpunit --coverage-clover coverage.xml
    # Upload to codecov or similar

- name: Coverage Threshold
  run: |
    COVERAGE=$(php bin/phpunit --coverage-text | grep "Lines:" | awk '{print $2}')
    if [ "$COVERAGE" -lt "80" ]; then
      echo "Coverage $COVERAGE% below 80%"
      exit 1
    fi
```
