---
name: test-runner
description: "Executes test suites (PHPUnit, Jest, Cypress) and reports results for quality validation. Supports TDD Red-Green-Refactor workflow."
hooks:
  PostToolUse:
    - matcher: Bash
      command: "echo '[test-runner] Test execution step completed'"
  Stop:
    - command: "echo '[test-runner] Test run complete. Results reported.'"
---

# Test Runner Skill

Execute test suites and report results for quality validation.

## What This Skill Does

- Run backend tests (PHPUnit)
- Run frontend tests (Jest)
- Run E2E tests (Cypress/Playwright)
- Generate test reports
- Support TDD workflow

## When to Use

- After implementing code (TDD Green phase)
- Before checkpoints
- During QA review
- In CI/CD pipelines

## Commands

### Backend (PHP/PHPUnit)

```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Unit/Domain/Entity/UserTest.php

# Run specific test method
php bin/phpunit --filter test_user_can_be_created

# Run tests with coverage
php bin/phpunit --coverage-text

# Run only unit tests
php bin/phpunit tests/Unit/

# Run only integration tests
php bin/phpunit tests/Integration/
```

### Frontend (Jest)

```bash
# Run all tests
npm test

# Run specific test file
npm test -- UserForm.test.tsx

# Run tests matching pattern
npm test -- --testNamePattern="should validate"

# Run with coverage
npm test -- --coverage

# Run in watch mode (development)
npm test -- --watch
```

### E2E (Cypress)

```bash
# Run all E2E tests
npm run test:e2e

# Run specific spec
npm run test:e2e -- --spec "cypress/e2e/registration.cy.ts"

# Open Cypress UI
npm run cypress:open
```

## TDD Integration

### Red Phase (Write Failing Test)

```bash
# Write test first, run to confirm it fails
php bin/phpunit tests/Unit/Domain/Entity/UserTest.php
# Expected: FAIL (class doesn't exist)
```

### Green Phase (Make Test Pass)

```bash
# Implement minimal code, run to confirm pass
php bin/phpunit tests/Unit/Domain/Entity/UserTest.php
# Expected: PASS
```

### Refactor Phase

```bash
# Refactor, run to confirm still passing
php bin/phpunit tests/Unit/Domain/Entity/UserTest.php
# Expected: PASS
```

## Output Format

```markdown
## Test Results

**Suite**: Backend Unit Tests
**Date**: 2026-01-16
**Duration**: 12.5s

### Summary
| Metric | Value |
|--------|-------|
| Tests | 45 |
| Passed | 45 |
| Failed | 0 |
| Skipped | 0 |
| Coverage | 87% |

### Test Details
✓ UserTest::test_user_can_be_created_with_valid_email (0.015s)
✓ UserTest::test_user_rejects_invalid_email (0.012s)
✓ CreateUserUseCaseTest::test_it_creates_user (0.045s)
...

### Coverage Report
| File | Lines | Covered | % |
|------|-------|---------|---|
| User.php | 50 | 47 | 94% |
| Email.php | 30 | 30 | 100% |
| CreateUserUseCase.php | 40 | 32 | 80% |
```

## Integration with Checkpoint

Used automatically during checkpoint:

```bash
# Before checkpoint, verify tests pass
/workflows:checkpoint backend user-auth "Domain layer"

# Internally runs:
# 1. php bin/phpunit tests/Unit/Domain/
# 2. If pass → checkpoint created
# 3. If fail → auto-correction loop starts
```

## Failure Handling

If tests fail:

```markdown
## Test Failure Report

**Failed Test**: UserTest::test_user_rejects_invalid_email

**Error**:
```
InvalidEmailException expected but not thrown
```

**Stack Trace**:
```
at UserTest.php:45
at User::create()
```

**Suggestion**: Add validation in User::create() method

**Auto-correction**: Iteration 1 of 10
```
