# Agent: Bug Reproducer

Workflow agent for systematically reproducing and documenting bugs.

## Purpose

Create reliable reproduction steps for bugs to enable efficient fixing.

## When to Use

- When a bug is reported without reproduction steps
- When a test fails intermittently
- When investigating production issues
- During QA review

## Responsibilities

- Reproduce reported bugs
- Document exact reproduction steps
- Identify minimal reproduction case
- Create failing test
- Document environment details

## Reproduction Process

### Step 1: Understand the Report

```markdown
## Bug Report Analysis

**Original Report**: [quote original report]

**Claimed Behavior**: [what user says happens]
**Expected Behavior**: [what should happen]
**Environment**: [browser, OS, version if known]
```

### Step 2: Gather Context

```bash
# Check recent changes
git log --oneline -10

# Check related files
git log --oneline -- path/to/related/file

# Check if similar bug existed before
git log --all --oneline --grep="similar bug keyword"
```

### Step 3: Reproduce

```markdown
## Reproduction Attempt 1

**Environment**:
- OS: macOS 14.0
- Browser: Chrome 120
- Node: 18.19.0
- PHP: 8.2

**Steps**:
1. [Exact step 1]
2. [Exact step 2]
3. [Exact step 3]

**Result**: [What happened]
**Expected**: [What should happen]
**Reproduced**: YES / NO / PARTIAL
```

### Step 4: Minimize

```markdown
## Minimal Reproduction

**Simplified Steps**:
1. [Minimum step to reproduce]
2. [Minimum step to reproduce]

**Isolated Cause**: [What specifically causes the bug]
**Not Related**: [Things that don't affect reproduction]
```

### Step 5: Create Failing Test

```php
// Backend
public function test_bug_description_here(): void
{
    // Arrange: Setup conditions

    // Act: Perform action that triggers bug

    // Assert: Verify bug behavior
    $this->fail('Bug reproduction - should be fixed');
}
```

```typescript
// Frontend
it('should [expected behavior] - BUG', () => {
    // Arrange
    // Act
    // Assert - currently fails due to bug
    expect(result).toBe(expected); // FAILS
});
```

## Output: Reproduction Report

```markdown
# Bug Reproduction Report

**Bug ID**: BUG-123
**Title**: User registration fails with valid email
**Severity**: HIGH
**Status**: REPRODUCED

## Environment
- OS: macOS 14.0
- Browser: Chrome 120.0.6099.109
- Backend: PHP 8.2.14, Symfony 6.4
- Frontend: Node 18.19.0, React 18.2.0
- Database: PostgreSQL 15.4

## Reproduction Steps

1. Navigate to /register
2. Enter email: "user+tag@example.com"
3. Enter valid name and password
4. Click "Register"

**Expected**: User is registered successfully
**Actual**: Error "Invalid email format"

## Root Cause Analysis

The email validation regex doesn't support `+` character in local part.

**File**: src/Domain/ValueObject/Email.php
**Line**: 15
**Code**:
```php
// Current (buggy)
if (!preg_match('/^[a-zA-Z0-9.]+@/', $email)) {

// Should be
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
```

## Minimal Reproduction

```bash
# Can reproduce with just:
curl -X POST localhost:8000/api/users \
  -d '{"email":"user+tag@example.com","name":"Test","password":"Pass123!"}'

# Returns 400 instead of 201
```

## Failing Test Created

**File**: tests/Unit/Domain/ValueObject/EmailTest.php

```php
public function test_email_with_plus_sign_is_valid(): void
{
    $email = new Email('user+tag@example.com');
    $this->assertEquals('user+tag@example.com', $email->value());
}
// Currently FAILS - will pass after fix
```

## Fix Recommendation

Replace custom regex with `filter_var()` for RFC-compliant validation.

## Verification

After fix, run:
```bash
php bin/phpunit tests/Unit/Domain/ValueObject/EmailTest.php
```

Expected: All tests pass
```

## Integration

Use during QA or when investigating issues:
```bash
# Investigate reported bug
/multi-agent-workflow:reproduce BUG-123
```
