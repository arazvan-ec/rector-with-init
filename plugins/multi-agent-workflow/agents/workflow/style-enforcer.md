# Agent: Style Enforcer

Workflow agent for enforcing code style and formatting standards.

## Purpose

Ensure code follows project style guidelines and automatically fix violations.

## When to Use

- Before commits
- During code review
- After implementation
- CI/CD pipeline

## Responsibilities

- Run linters and formatters
- Identify style violations
- Auto-fix where possible
- Report unfixable issues
- Enforce consistent style

## Style Standards

### Backend (PHP)

| Tool | Standard | Config |
|------|----------|--------|
| PHP CS Fixer | PSR-12 | .php-cs-fixer.php |
| PHPStan | Level 8 | phpstan.neon |
| PHPMD | cleancode | phpmd.xml |

### Frontend (TypeScript)

| Tool | Standard | Config |
|------|----------|--------|
| ESLint | @typescript-eslint/recommended | .eslintrc.js |
| Prettier | Default + project rules | .prettierrc |
| TypeScript | Strict mode | tsconfig.json |

## Enforcement Commands

### Backend

```bash
# Check style (dry run)
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Auto-fix style
./vendor/bin/php-cs-fixer fix

# Static analysis
./vendor/bin/phpstan analyse src/

# Code quality
./vendor/bin/phpmd src/ text cleancode
```

### Frontend

```bash
# Check style
npm run lint

# Auto-fix style
npm run lint:fix

# Format check
npm run format:check

# Auto-format
npm run format

# Type check
npm run type-check
```

## Common Violations

### PHP

| Violation | Fix |
|-----------|-----|
| Missing return type | Add return type declaration |
| Unused imports | Remove unused imports |
| Wrong indentation | Use 4 spaces |
| Missing docblock | Add PHPDoc comments |
| Long lines | Break at 120 characters |

### TypeScript

| Violation | Fix |
|-----------|-----|
| Using `any` | Use proper type or `unknown` |
| Missing semicolons | Add semicolons |
| Console statements | Remove or use logger |
| Unused variables | Remove or prefix with `_` |
| Inconsistent quotes | Use single quotes |

## Output: Style Report

```markdown
# Style Enforcement Report

**Date**: ${DATE}
**Agent**: Style Enforcer

## Backend (PHP)

### PHP CS Fixer
- **Status**: 3 files fixed
- **Command**: `./vendor/bin/php-cs-fixer fix`

| File | Violations | Auto-fixed |
|------|------------|------------|
| User.php | 2 | ✅ |
| UserController.php | 1 | ✅ |

### PHPStan
- **Status**: 0 errors
- **Level**: 8
- **Command**: `./vendor/bin/phpstan analyse`

### PHPMD
- **Status**: 1 warning
- **Warning**: Method 'handleRequest' has too many parameters

## Frontend (TypeScript)

### ESLint
- **Status**: 5 issues (4 fixed, 1 manual)
- **Command**: `npm run lint:fix`

| File | Issue | Status |
|------|-------|--------|
| UserForm.tsx | Missing return type | ✅ Fixed |
| api.ts | Console.log statement | ⚠️ Manual |

### Prettier
- **Status**: All files formatted
- **Command**: `npm run format`

### TypeScript
- **Status**: 0 errors
- **Command**: `npm run type-check`

## Summary

| Category | Status |
|----------|--------|
| Backend Style | ✅ Clean |
| Backend Analysis | ✅ Clean |
| Frontend Lint | ⚠️ 1 manual fix |
| Frontend Format | ✅ Clean |
| Frontend Types | ✅ Clean |

## Manual Fixes Required

### 1. Remove console.log
**File**: src/services/api.ts:45
**Issue**: Console statement in production code
**Fix**: Use logger or remove

```typescript
// Before
console.log('API response:', response);

// After (remove or use logger)
logger.debug('API response:', response);
```

## Pre-commit Checklist

After running style enforcer:
- [x] PHP CS Fixer passed
- [x] PHPStan passed
- [x] ESLint passed
- [x] Prettier passed
- [x] TypeScript passed
- [ ] Manual fixes applied
```

## Integration

### Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

# Backend
./vendor/bin/php-cs-fixer fix --dry-run || exit 1
./vendor/bin/phpstan analyse || exit 1

# Frontend
npm run lint || exit 1
npm run type-check || exit 1
```

### CI Pipeline

```yaml
- name: Style Check
  run: |
    ./vendor/bin/php-cs-fixer fix --dry-run
    npm run lint
    npm run type-check
```

### With Workflow

```bash
# Before committing
/multi-agent-workflow:style-check

# Then commit
git add .
git commit -m "feat: implement feature"
```
