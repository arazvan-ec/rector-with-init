---
name: layer-validator
description: "Validates DDD layer dependencies automatically. Detects forbidden imports between Domain, Application, and Infrastructure layers."
hooks:
  PreToolUse:
    - matcher: Bash
      command: "echo '[layer-validator] Running DDD layer dependency check...'"
  PostToolUse:
    - matcher: Bash
      command: "echo '[layer-validator] Layer check step completed'"
  Stop:
    - command: "echo '[layer-validator] Layer validation report finalized'"
---

# Layer Validator Skill

Validate DDD layer dependencies automatically.

## What This Skill Does

- Scan imports between layers
- Detect dependency violations
- Ensure clean architecture
- Generate violation reports

## DDD Layer Rules

```
┌─────────────────────────────────────┐
│           Infrastructure            │  ← Can depend on: Application, Domain
├─────────────────────────────────────┤
│            Application              │  ← Can depend on: Domain only
├─────────────────────────────────────┤
│              Domain                 │  ← Can depend on: Nothing external
└─────────────────────────────────────┘

Forbidden Dependencies:
- Domain → Application ❌
- Domain → Infrastructure ❌
- Application → Infrastructure ❌
```

## Validation Commands

### Check Domain Layer

```bash
# Domain should not import from Application or Infrastructure
grep -r "use App\\Application\|use App\\Infrastructure" src/Domain/
# Expected: No results

# Domain should not import Doctrine
grep -r "use Doctrine" src/Domain/
# Expected: No results

# Domain should not import Symfony
grep -r "use Symfony" src/Domain/
# Expected: No results (except maybe Uid)
```

### Check Application Layer

```bash
# Application should not import from Infrastructure
grep -r "use App\\Infrastructure" src/Application/
# Expected: No results

# Application should not import Doctrine directly
grep -r "use Doctrine\\ORM" src/Application/
# Expected: No results
```

### Check Infrastructure Layer

```bash
# Infrastructure can import from anywhere
# No restrictions
```

## Validation Script

```bash
#!/bin/bash
# validate-layers.sh

echo "Validating DDD layers..."

# Domain violations
DOMAIN_VIOLATIONS=$(grep -r "use App\\Application\|use App\\Infrastructure\|use Doctrine" src/Domain/ 2>/dev/null | wc -l)

# Application violations
APP_VIOLATIONS=$(grep -r "use App\\Infrastructure\|use Doctrine\\ORM" src/Application/ 2>/dev/null | wc -l)

echo "Domain violations: $DOMAIN_VIOLATIONS"
echo "Application violations: $APP_VIOLATIONS"

if [ $DOMAIN_VIOLATIONS -gt 0 ] || [ $APP_VIOLATIONS -gt 0 ]; then
    echo "❌ Layer violations found!"
    exit 1
else
    echo "✅ All layers clean"
    exit 0
fi
```

## Output Format

```markdown
# Layer Validation Report

**Date**: 2026-01-16
**Status**: VIOLATIONS FOUND

## Domain Layer

### Allowed Dependencies
- PHP built-in types
- Domain interfaces
- Value objects

### Violations Found

| File | Line | Violation | Severity |
|------|------|-----------|----------|
| User.php | 5 | use Doctrine\ORM\Mapping | CRITICAL |
| Order.php | 8 | use App\Application\DTO | HIGH |

### Details

#### Violation 1: Doctrine in Domain
**File**: src/Domain/Entity/User.php:5
**Code**: `use Doctrine\ORM\Mapping as ORM;`
**Problem**: Domain layer should not depend on Doctrine
**Fix**: Use XML/YAML mapping in Infrastructure layer

#### Violation 2: Application import in Domain
**File**: src/Domain/Entity/Order.php:8
**Code**: `use App\Application\DTO\OrderDTO;`
**Problem**: Domain cannot depend on Application
**Fix**: Create interface in Domain, implement in Application

## Application Layer

### Allowed Dependencies
- Domain layer
- PHP built-in types

### Violations Found
None ✅

## Infrastructure Layer

### Allowed Dependencies
- Application layer
- Domain layer
- External frameworks

### Violations Found
None ✅ (no restrictions)

## Summary

| Layer | Status | Violations |
|-------|--------|------------|
| Domain | ❌ | 2 |
| Application | ✅ | 0 |
| Infrastructure | ✅ | 0 |

## Recommendations

1. Remove Doctrine annotations from Domain entities
2. Use XML mapping in `config/doctrine/`
3. Move DTO dependency to interface pattern
```

## Integration with Workflow

### During Work

```bash
# After implementing domain layer
/workflows:work user-auth --mode=layers --layer=domain

# Layer validator runs automatically
# If violations: blocks progression
# If clean: continues to next layer
```

### During Review

```bash
# QA review includes layer validation
/workflows:review user-auth --agent=ddd

# Layer validator provides input to DDD compliance agent
```

## Common Violations

### Violation: Doctrine Annotations in Domain

```php
// ❌ WRONG
namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User { }

// ✅ CORRECT
namespace App\Domain\Entity;

class User { }

// Mapping in config/doctrine/User.orm.xml
```

### Violation: Infrastructure in Application

```php
// ❌ WRONG
namespace App\Application\UseCase;

use App\Infrastructure\Repository\DoctrineUserRepository;

class CreateUser {
    public function __construct(DoctrineUserRepository $repo) { }
}

// ✅ CORRECT
namespace App\Application\UseCase;

use App\Domain\Repository\UserRepository; // Interface

class CreateUser {
    public function __construct(UserRepository $repo) { }
}
```

## Automated Check

```yaml
# CI Pipeline
- name: Layer Validation
  run: ./scripts/validate-layers.sh
```
