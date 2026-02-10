---
name: lint-fixer
description: "Automatically fixes code style violations using PHP CS Fixer, ESLint, and Prettier."
hooks:
  PostToolUse:
    - matcher: Bash
      command: "echo '[lint-fixer] Lint fix step completed'"
  Stop:
    - command: "echo '[lint-fixer] All lint fixes applied.'"
---

# Lint Fixer Skill

Automatically fix code style violations.

## What This Skill Does

- Run linters (PHP CS Fixer, ESLint)
- Auto-fix style violations
- Report unfixable issues
- Ensure consistent code style

## Commands

### Backend (PHP)

```bash
# Check style (dry run)
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Auto-fix all files
./vendor/bin/php-cs-fixer fix

# Fix specific file
./vendor/bin/php-cs-fixer fix src/Domain/Entity/User.php

# Fix specific directory
./vendor/bin/php-cs-fixer fix src/Domain/
```

### Frontend (TypeScript)

```bash
# Check style
npm run lint

# Auto-fix
npm run lint:fix

# Check specific file
npx eslint src/components/UserForm.tsx

# Fix specific file
npx eslint --fix src/components/UserForm.tsx
```

### Formatting (Prettier)

```bash
# Check formatting
npm run format:check

# Auto-format
npm run format

# Format specific file
npx prettier --write src/components/UserForm.tsx
```

## Style Standards

### PHP (PSR-12)

```php
// ✅ Correct
namespace App\Domain\Entity;

class User
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}

// ❌ Incorrect
namespace App\Domain\Entity;
class User {
    private $email;
    public function __construct($email) {
        $this->email = $email;
    }
}
```

### TypeScript (ESLint + Prettier)

```typescript
// ✅ Correct
interface UserProps {
  name: string;
  email: string;
}

const UserCard: React.FC<UserProps> = ({ name, email }) => {
  return (
    <div className="user-card">
      <h2>{name}</h2>
      <p>{email}</p>
    </div>
  );
};

// ❌ Incorrect
const UserCard = (props: any) => {
  return <div className='user-card'>
    <h2>{props.name}</h2>
    <p>{props.email}</p>
  </div>
}
```

## Output Format

```markdown
## Lint Fix Report

**Date**: 2026-01-16

### Backend (PHP CS Fixer)

| File | Issues | Fixed |
|------|--------|-------|
| User.php | 3 | ✅ 3 |
| UserController.php | 5 | ✅ 5 |
| CreateUserUseCase.php | 2 | ✅ 2 |

**Total**: 10 issues fixed automatically

### Frontend (ESLint)

| File | Issues | Fixed | Manual |
|------|--------|-------|--------|
| UserForm.tsx | 4 | ✅ 3 | ⚠️ 1 |
| api.ts | 2 | ✅ 2 | 0 |

**Total**: 5 fixed, 1 requires manual fix

### Manual Fixes Required

1. **UserForm.tsx:45**
   - Issue: `console.log` in production code
   - Fix: Remove or use logger

### Commands Run
```bash
./vendor/bin/php-cs-fixer fix
npm run lint:fix
```
```

## Pre-commit Integration

```bash
#!/bin/bash
# .git/hooks/pre-commit

echo "Running lint fixes..."

# PHP
./vendor/bin/php-cs-fixer fix --quiet

# TypeScript
npm run lint:fix --silent

# Stage fixed files
git add -u

echo "Lint fixes applied"
```

## Integration with Workflow

```bash
# Before checkpoint, auto-fix style
/workflows:checkpoint backend user-auth "Domain layer"

# Internally runs:
# 1. ./vendor/bin/php-cs-fixer fix
# 2. git add fixed files
# 3. Continue with checkpoint
```
