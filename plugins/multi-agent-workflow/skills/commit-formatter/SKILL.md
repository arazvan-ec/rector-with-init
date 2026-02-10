---
name: commit-formatter
description: "Ensures commits follow conventional commit format with type(scope): subject pattern."
hooks:
  PreToolUse:
    - matcher: Bash
      command: "echo '[commit-formatter] Validating commit message format...'"
---

# Commit Formatter Skill

Ensure commits follow conventional commit format.

## What This Skill Does

- Format commit messages
- Enforce conventional commits
- Link commits to tasks
- Maintain clean git history

## Conventional Commit Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation |
| `style` | Code style (no logic change) |
| `refactor` | Code refactoring |
| `test` | Adding tests |
| `chore` | Maintenance tasks |

### Scopes

| Scope | Description |
|-------|-------------|
| `user` | User-related features |
| `auth` | Authentication |
| `api` | API changes |
| `ui` | User interface |
| `db` | Database |

### Examples

```bash
# Feature
feat(user): add user registration endpoint

# Bug fix
fix(auth): resolve token expiration issue

# Test
test(user): add CreateUserUseCase tests

# Refactoring
refactor(user): extract Email value object

# Documentation
docs(api): update API contract for user endpoint
```

## TDD Commit Pattern

```bash
# RED phase
test(user): add User entity creation test

# GREEN phase
feat(user): implement User entity

# REFACTOR phase
refactor(user): extract Email value object
```

## Multi-Agent Commit Pattern

```bash
# Include role in commit
[backend] feat(user): implement User entity
[frontend] feat(user): add RegistrationForm component
[qa] test(user): add E2E registration tests
```

## Commands

### Create Formatted Commit

```bash
# Interactive commit
git commit

# With message
git commit -m "feat(user): add user registration"

# With body
git commit -m "feat(user): add user registration

- Add User entity with email validation
- Add CreateUserUseCase
- Add POST /api/users endpoint

Closes #123"
```

### Amend Commit Message

```bash
git commit --amend -m "feat(user): add user registration endpoint"
```

### Validate Commit Message

```bash
# Check last commit
git log -1 --format="%s" | grep -E "^(feat|fix|docs|style|refactor|test|chore)\(.+\): .+"
```

## Commit Templates

### Feature Commit

```
feat(<scope>): <short description>

- <Change 1>
- <Change 2>
- <Change 3>

Closes #<issue-number>
```

### Bug Fix Commit

```
fix(<scope>): <short description>

Problem: <What was wrong>
Solution: <How it was fixed>
Root cause: <Why it happened>

Fixes #<issue-number>
```

### Test Commit (TDD)

```
test(<scope>): add <test description>

Tests added:
- <Test 1>
- <Test 2>

Coverage: <X>%
```

## Integration with Workflow

```bash
# After completing work, create formatted commit
/multi-agent-workflow:commit "feat(user): add user registration"

# Internally:
# 1. Validate message format
# 2. Stage relevant files
# 3. Create commit
# 4. Report success
```

## Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/commit-msg

commit_msg=$(cat "$1")
pattern="^(feat|fix|docs|style|refactor|test|chore)\(.+\): .+"

if ! echo "$commit_msg" | grep -qE "$pattern"; then
    echo "ERROR: Commit message doesn't follow conventional format"
    echo "Expected: <type>(<scope>): <subject>"
    echo "Example: feat(user): add user registration"
    exit 1
fi
```

## Git History Example

```
* abc1234 feat(user): add user registration endpoint
* def5678 feat(user): implement CreateUserUseCase
* ghi9012 test(user): add User entity tests
* jkl3456 feat(user): add User entity with Email VO
* mno7890 docs(user): add API contract for registration
```
