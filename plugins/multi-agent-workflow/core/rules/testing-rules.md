# Testing Rules

Rules that apply when working with test files and test-related code.

**Applies to**: Test files (`*Test.php`, `*.test.ts`, `*.test.tsx`, `*.spec.ts`, `*.spec.tsx`), test configuration, and test utilities.

---

## TDD Workflow

Follow the Red-Green-Refactor cycle:

1. **Red**: Write a failing test that describes expected behavior
2. **Green**: Write the minimum code to make the test pass
3. **Refactor**: Improve code structure while keeping tests green

Always write tests before implementation. The test defines the contract; the implementation fulfills it.

## Test Requirements

- Every task includes tests as part of its definition of done
- Prefer unit tests for business logic, integration tests for infrastructure
- Test names should describe behavior, not implementation (`test_user_can_register_with_valid_email` over `test_create_user`)
- Each test should verify one behavior

## Coverage

- Target minimum 80% code coverage for new code
- Use `/skill:coverage-checker` to validate thresholds
- Coverage gaps in critical paths (auth, payments, domain logic) are not acceptable

## Prohibitions

- Do not commit code without corresponding tests
- Do not push with failing tests
- Do not mock domain entities — test them directly
- Do not write tests that depend on execution order

## Ralph Wiggum Auto-Correction Loop

When tests fail during implementation:

```
iteration = 0
while tests_failing and iteration < 10:
    analyze the failure
    fix the code (not the test, unless the test is wrong)
    run tests again
    iteration++

if iteration >= 10:
    mark task as BLOCKED
    document what was tried and what failed
```

This bounded retry prevents infinite loops while giving reasonable room for correction.
