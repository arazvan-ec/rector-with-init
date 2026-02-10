---
name: changelog-generator
description: "Generates changelogs from git history for compound learning, release notes, and sprint retrospectives."
context: fork
---

# Changelog Generator Skill

Generate changelogs from git history for compound learning.

## What This Skill Does

- Generate changelogs from commits
- Group changes by type
- Support compound capture
- Create release notes

## When to Use

- During `/workflows:compound`
- Before releases
- Sprint retrospectives
- Documentation updates

## Commands

### Generate Changelog

```bash
# From commits since last tag
git log --oneline $(git describe --tags --abbrev=0)..HEAD

# From specific range
git log --oneline v1.0.0..v1.1.0

# For feature branch
git log --oneline main..feature/user-auth
```

### Detailed Changelog

```bash
# With body and stats
git log --format="### %s%n%b%n" --stat main..feature/user-auth
```

## Output Format

```markdown
# Changelog

## [Unreleased]

### Features
- **user**: Add user registration endpoint ([abc1234])
- **user**: Implement CreateUserUseCase ([def5678])
- **auth**: Add JWT authentication ([ghi9012])

### Bug Fixes
- **auth**: Fix token expiration handling ([jkl3456])
- **api**: Resolve CORS issue ([mno7890])

### Tests
- **user**: Add User entity tests ([pqr1234])
- **user**: Add CreateUserUseCase tests ([stu5678])
- **e2e**: Add registration flow tests ([vwx9012])

### Refactoring
- **user**: Extract Email value object ([yza3456])
- **api**: Simplify error response format ([bcd7890])

### Documentation
- **api**: Update API contracts ([efg1234])
- **readme**: Add installation guide ([hij5678])

---

## [1.0.0] - 2026-01-15

### Features
- Initial release
- User management
- Authentication system
```

## Feature Changelog

For compound capture:

```markdown
# Feature Changelog: user-authentication

**Branch**: feature/user-auth
**Duration**: 2026-01-14 to 2026-01-16
**Commits**: 15

## Summary
Implemented user registration with email/password authentication.

## Changes by Role

### Planner
- docs: Add user-auth feature definition
- docs: Create API contracts

### Backend
- feat(user): Add User entity with Email VO
- feat(user): Implement CreateUserUseCase
- feat(api): Add POST /api/users endpoint
- test(user): Add domain and application tests

### Frontend
- feat(ui): Add RegistrationForm component
- feat(ui): Add form validation with yup
- feat(api): Integrate with registration endpoint
- test(ui): Add component tests

### QA
- test(e2e): Add registration flow tests
- docs: Create QA report

## Patterns Introduced
1. Email value object pattern
2. Registration form pattern
3. API error handling pattern

## Files Changed
- 12 files added
- 3 files modified
- 0 files deleted

## Test Coverage
- Backend: 87%
- Frontend: 78%
```

## Integration with Compound

```bash
# During compound capture
/workflows:compound user-auth

# Changelog generator creates:
# 1. Feature changelog
# 2. Patterns summary
# 3. Metrics for compound log
```

## Release Notes Template

```markdown
# Release Notes - v1.1.0

**Release Date**: 2026-01-16

## Highlights
- User registration is now available
- Improved authentication security

## New Features

### User Registration
Users can now create accounts with email and password.
- Email validation ensures valid format
- Passwords are securely hashed
- Duplicate emails are prevented

### Enhanced Authentication
- JWT tokens for stateless auth
- Refresh token support
- Improved session management

## Bug Fixes
- Fixed token expiration edge case
- Resolved CORS issues for frontend

## Breaking Changes
None in this release.

## Upgrade Guide
No special steps required. Deploy as usual.

## Contributors
- Backend: Claude Backend Agent
- Frontend: Claude Frontend Agent
- QA: Claude QA Agent
```

## Automation

```bash
# Generate changelog automatically
git log --format="- %s ([%h])" --no-merges main..HEAD | \
  sed 's/feat/### Features\n&/; s/fix/### Bug Fixes\n&/'
```
