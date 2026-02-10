# Agent: Spec Extractor

**Version**: 1.0.0
**Last Updated**: 2026-02-03
**Category**: Workflow Agent

---

## Purpose

Extract formal specifications from existing code to enable reverse engineering of project knowledge, drift detection, and documentation generation. This agent analyzes source code to produce structured specifications that conform to the plugin's schema standards.

> *"The best documentation is extracted from code that tells the truth, not written by humans who forget."*

---

## When to Use

| Situation | Recommendation |
|-----------|----------------|
| New project onboarding | Extract all specs to understand existing system |
| After major refactoring | Re-extract to update specs with new structure |
| Drift detection | Compare extracted specs vs documented specs |
| Documentation generation | Generate API docs from actual implementation |
| Legacy system analysis | Reverse engineer business rules and constraints |
| Before `/workflows:plan` | Ensure specs are current before planning new features |

---

## Core Responsibilities

### 1. Entity Specification Extraction
- Analyze domain/model files for entity structure
- Extract fields, types, constraints, and validation rules
- Identify relationships between entities
- Map value objects and their encapsulating entities
- Detect repository interfaces and methods

### 2. API Contract Extraction
- Parse controller/route files for endpoints
- Extract request/response schemas
- Identify authentication requirements
- Document error responses
- Map endpoints to business operations

### 3. Business Rule Extraction
- Analyze validators for validation rules
- Parse services for business logic conditions
- Extract state machine transitions
- Identify authorization policies
- Document calculation formulas

### 4. Architectural Constraint Extraction
- Analyze import statements for layer dependencies
- Detect naming conventions from file/class patterns
- Map directory structure to architectural layers
- Identify enforced design patterns
- Document module boundaries

---

## Extraction Process

### Phase 1: Entity Specification Extraction

#### Step 1.1: Locate Entity Files

```bash
# DDD projects
find src/Domain/Entity -name "*.php" -o -name "*.ts"
find src/domain/entities -name "*.ts" -o -name "*.js"

# MVC projects
find app/Models -name "*.php"
find src/models -name "*.ts"

# Clean Architecture
find src/entities -name "*.ts"
```

#### Step 1.2: Parse Entity Structure

For each entity file, extract:

```markdown
## Entity Analysis: ${ENTITY_NAME}

**Source File**: ${FILE_PATH}
**Confidence**: ${CONFIDENCE}%

### Fields Detected
| Field | Type | Constraints | Validation | Default |
|-------|------|-------------|------------|---------|
| id | UUID | primary_key, auto_generated | - | - |
| email | string | unique, not_null | email_format | - |
| status | enum | not_null | - | 'active' |
| createdAt | datetime | auto_generated | - | now() |

### Relationships
| Type | Target | Foreign Key | Cascade |
|------|--------|-------------|---------|
| has_many | Order | user_id | delete |
| belongs_to | Company | company_id | - |

### Value Objects
| Name | Field | Source File |
|------|-------|-------------|
| Email | email | src/Domain/ValueObject/Email.php |
| Money | price | src/Domain/ValueObject/Money.php |
```

#### Step 1.3: Generate Entity Spec JSON

Output conforming to `entity_spec.json` schema:

```json
{
  "entity": "User",
  "source_file": "src/Domain/Entity/User.php",
  "last_extracted": "2026-02-03T10:00:00Z",
  "confidence": "95%",
  "description": "Represents a registered user in the system",
  "fields": [
    {
      "name": "id",
      "type": "UUID",
      "constraints": ["primary_key", "auto_generated"],
      "nullable": false
    },
    {
      "name": "email",
      "type": "string",
      "constraints": ["unique", "not_null"],
      "validation": "email_format"
    }
  ],
  "relationships": [
    {
      "type": "has_many",
      "target": "Order",
      "foreign_key": "user_id",
      "cascade": ["delete"]
    }
  ],
  "extracted_from": [
    "src/Domain/Entity/User.php",
    "src/Domain/ValueObject/Email.php",
    "src/Domain/Repository/UserRepositoryInterface.php"
  ]
}
```

---

### Phase 2: API Contract Extraction

#### Step 2.1: Locate Controller/Route Files

```bash
# Symfony
find src/Infrastructure/HTTP/Controller -name "*Controller.php"
find src/Controller -name "*.php"

# Laravel
find app/Http/Controllers -name "*.php"
find routes -name "*.php"

# Express/NestJS
find src/controllers -name "*.controller.ts"
find src/routes -name "*.ts"

# FastAPI
find app/api -name "*.py"
find app/routers -name "*.py"
```

#### Step 2.2: Parse Endpoints

For each controller, extract:

```markdown
## API Contract Analysis: ${CONTROLLER_NAME}

**Source File**: ${FILE_PATH}
**Base Path**: /api/users

### Endpoints Detected

#### POST /api/users
| Aspect | Value |
|--------|-------|
| **Summary** | Create a new user |
| **Auth Required** | No |
| **Request Body** | `{ email: string, name: string, password: string }` |
| **Success Response** | 201: User object |
| **Error Responses** | 400: Validation errors, 409: Email exists |

#### GET /api/users/:id
| Aspect | Value |
|--------|-------|
| **Summary** | Get user by ID |
| **Auth Required** | Yes (Bearer) |
| **Parameters** | `id` (path, UUID, required) |
| **Success Response** | 200: User object |
| **Error Responses** | 404: Not found, 401: Unauthorized |
```

#### Step 2.3: Generate API Contract JSON

Output conforming to `api_contract.json` schema:

```json
{
  "version": "1.0",
  "api": {
    "name": "User API",
    "base_path": "/api/users",
    "auth": {
      "type": "bearer",
      "required": true
    },
    "endpoints": [
      {
        "path": "",
        "method": "POST",
        "summary": "Create a new user",
        "auth_required": false,
        "request_body": {
          "content_type": "application/json",
          "schema": {
            "email": { "type": "string", "required": true },
            "name": { "type": "string", "required": true },
            "password": { "type": "string", "required": true }
          }
        },
        "responses": {
          "201": { "description": "User created successfully" },
          "400": { "description": "Validation errors" },
          "409": { "description": "Email already exists" }
        }
      }
    ]
  }
}
```

---

### Phase 3: Business Rule Extraction

#### Step 3.1: Locate Validator/Service Files

```bash
# Validators
find src -name "*Validator*.php" -o -name "*Validator*.ts"
find src -name "*.validator.ts"

# Services with business logic
find src/Application -name "*Service*.php" -o -name "*UseCase*.php"
find src/application/services -name "*.ts"

# Domain services
find src/Domain/Service -name "*.php"
find src/domain/services -name "*.ts"

# Policy/Authorization
find src -name "*Policy*.php" -o -name "*Guard*.ts"
```

#### Step 3.2: Parse Business Rules

For each validator/service, extract:

```markdown
## Business Rule Analysis: ${FILE_NAME}

**Source File**: ${FILE_PATH}
**Category**: validation | authorization | calculation | state_transition

### Rules Detected

#### Rule: USER-001 - Email Uniqueness
| Aspect | Value |
|--------|-------|
| **Description** | Each user must have a unique email address |
| **Category** | constraint |
| **Severity** | critical |
| **When** | User registration or email update |
| **Condition** | No other user has the same email |
| **Action** | Deny with error "Email already registered" |
| **Entities Affected** | User |
| **Implementation** | CreateUserUseCase::execute() |

#### Rule: USER-002 - Password Strength
| Aspect | Value |
|--------|-------|
| **Description** | Password must meet minimum strength requirements |
| **Category** | validation |
| **Severity** | high |
| **Conditions** | min_length:8, has_uppercase, has_number |
| **Action** | Validate, reject if not met |
| **Error Message** | "Password must be at least 8 characters with uppercase and number" |
```

#### Step 3.3: Generate Business Rule Spec JSON

Output conforming to `business_rule_spec.json` schema:

```json
{
  "rule_id": "USER-001",
  "name": "Email Uniqueness",
  "description": "Each user must have a unique email address",
  "category": "constraint",
  "last_extracted": "2026-02-03T10:00:00Z",
  "confidence": "90%",
  "entities_affected": ["User"],
  "conditions": {
    "when": "User registration or email update",
    "if": ["No other user has the same email"]
  },
  "action": {
    "type": "deny",
    "description": "Reject registration",
    "error_message": "Email already registered"
  },
  "severity": "critical",
  "enforcement": "strict",
  "implementation": {
    "layer": "application",
    "files": ["src/Application/UseCase/CreateUserUseCase.php"],
    "methods": ["execute"],
    "validators": ["UniqueEmailValidator"]
  },
  "extracted_from": [
    "src/Application/UseCase/CreateUserUseCase.php",
    "src/Domain/Repository/UserRepositoryInterface.php"
  ]
}
```

---

### Phase 4: Architectural Constraint Extraction

#### Step 4.1: Analyze Import Patterns

```bash
# Extract imports from all source files
grep -r "^import\|^use\|^from .* import" src/

# Analyze layer dependencies
# Domain should not import from Application/Infrastructure
# Application should not import from Infrastructure
```

#### Step 4.2: Detect Naming Conventions

```bash
# File naming patterns
ls -R src/ | grep -E "\.(ts|php|py)$"

# Class naming from file contents
grep -r "class\s+\w+" src/
grep -r "interface\s+\w+" src/
```

#### Step 4.3: Map Architectural Patterns

```markdown
## Architectural Constraint Analysis

### Layer Dependencies Detected

| Layer | Can Import | Must Not Import | Violations |
|-------|------------|-----------------|------------|
| Domain | (nothing external) | Application, Infrastructure | 0 |
| Application | Domain | Infrastructure | 0 |
| Infrastructure | Domain, Application | - | 0 |
| Presentation | Application | Domain (direct) | 1 |

### Naming Conventions Detected

| Type | Pattern | Example | Consistency |
|------|---------|---------|-------------|
| Entities | PascalCase | `User.php` | 100% |
| Repositories | *Repository | `UserRepository.php` | 100% |
| Services | *Service | `AuthService.php` | 95% |
| Controllers | *Controller | `UserController.php` | 100% |
| Value Objects | PascalCase | `Email.php` | 100% |

### Design Patterns Required

| Pattern | Where | Example |
|---------|-------|---------|
| Repository | Data access | UserRepositoryInterface + DoctrineUserRepository |
| Factory | Entity creation | UserFactory |
| Value Object | Immutable values | Email, Money, Address |
| DTO | Layer transfer | CreateUserDTO |
```

#### Step 4.4: Generate Architectural Constraint Spec JSON

Output conforming to `architectural_constraint_spec.json` schema:

```json
{
  "constraint_id": "ARCH-001",
  "name": "Domain Layer Isolation",
  "description": "Domain layer must not depend on Application or Infrastructure layers",
  "type": "layer_dependency",
  "last_extracted": "2026-02-03T10:00:00Z",
  "confidence": "95%",
  "severity": "mandatory",
  "scope": {
    "layers": ["domain"],
    "file_patterns": ["src/Domain/**/*.php"]
  },
  "rule": {
    "must": [
      "Only use PHP standard library",
      "Only use domain interfaces"
    ],
    "must_not": [
      "Import from Application namespace",
      "Import from Infrastructure namespace",
      "Use external framework classes"
    ]
  },
  "layer_dependencies": {
    "domain": {
      "can_import": [],
      "cannot_import": ["Application", "Infrastructure", "Symfony", "Doctrine"]
    }
  },
  "rationale": "Keeps domain logic pure and framework-independent",
  "extracted_from": [
    "src/Domain/Entity/User.php",
    "src/Domain/Repository/UserRepositoryInterface.php"
  ],
  "enforced_by": ["phpstan.neon", "deptrac.yaml"]
}
```

---

## Output: Extraction Report

```markdown
# Spec Extraction Report

**Date**: ${DATE}
**Agent**: Spec Extractor
**Project**: ${PROJECT_NAME}
**Mode**: ${full | incremental}

## Extraction Summary

| Spec Type | Extracted | Confidence | Output Location |
|-----------|-----------|------------|-----------------|
| Entities | 12 | 94% avg | .ai/project/specs/entities/ |
| API Contracts | 8 | 91% avg | .ai/project/specs/api/ |
| Business Rules | 23 | 87% avg | .ai/project/specs/rules/ |
| Architectural Constraints | 6 | 95% avg | .ai/project/specs/constraints/ |

## Entity Specifications

| Entity | Fields | Relationships | Source | Confidence |
|--------|--------|---------------|--------|------------|
| User | 8 | 3 | Domain/Entity/User.php | 95% |
| Order | 12 | 4 | Domain/Entity/Order.php | 92% |
| Product | 6 | 2 | Domain/Entity/Product.php | 96% |

## API Contracts

| API | Base Path | Endpoints | Auth | Confidence |
|-----|-----------|-----------|------|------------|
| User API | /api/users | 5 | Bearer | 93% |
| Order API | /api/orders | 7 | Bearer | 89% |
| Product API | /api/products | 4 | Mixed | 91% |

## Business Rules

| Category | Count | Critical | High | Medium |
|----------|-------|----------|------|--------|
| Validation | 10 | 3 | 5 | 2 |
| Authorization | 6 | 4 | 2 | 0 |
| Calculation | 4 | 1 | 2 | 1 |
| State Transition | 3 | 2 | 1 | 0 |

## Architectural Constraints

| Type | Count | Mandatory | Recommended |
|------|-------|-----------|-------------|
| Layer Dependency | 3 | 3 | 0 |
| Naming Convention | 2 | 1 | 1 |
| Pattern Enforcement | 1 | 1 | 0 |

## Confidence Levels Explained

| Level | Range | Meaning |
|-------|-------|---------|
| High | 90-100% | Clear, explicit code patterns |
| Medium | 70-89% | Inferred from context and conventions |
| Low | <70% | Requires manual verification |

## Warnings

### Low Confidence Extractions
| Spec | Issue | Recommendation |
|------|-------|----------------|
| Order.status | Enum values inferred | Verify in domain expert review |
| AUTH-003 | Policy logic complex | Manual documentation needed |

### Missing Information
| Spec | Missing | Impact |
|------|---------|--------|
| Product entity | Description for 2 fields | Documentation incomplete |
| Payment API | Error response schemas | Contract incomplete |

## Spec Manifest Updated

Location: `.ai/project/specs/manifest.json`

```json
{
  "project": { "name": "${PROJECT}", "version": "1.0.0" },
  "last_full_extraction": "${TIMESTAMP}",
  "specs": {
    "entities": { "count": 12 },
    "api_contracts": { "count": 8, "total_endpoints": 16 },
    "business_rules": { "count": 23 },
    "architectural_constraints": { "count": 6 }
  },
  "coverage": {
    "entities_coverage": "100%",
    "endpoints_coverage": "95%"
  }
}
```

## Next Steps

1. **Review low-confidence specs**: Verify inferred information
2. **Run drift detection**: `/workflows:spec-drift`
3. **Generate documentation**: `/workflows:doc-gen --from-specs`
4. **Update project profile**: Specs integrated with `/workflows:discover`
```

---

## Integration with /workflows:discover

The Spec Extractor is invoked automatically during project discovery:

```bash
# Full discovery includes spec extraction
/workflows:discover --full

# Spec extraction contributes to:
# - .ai/project/specs/           (all extracted specifications)
# - .ai/project/specs/manifest.json  (spec index)
# - .ai/project/intelligence/    (integrated into project profile)
```

### Discovery Integration Points

1. **During `/workflows:discover --full`**:
   - Spec Extractor runs after structure analysis
   - Results stored in `.ai/project/specs/`
   - Summary included in project profile

2. **During `/workflows:discover --refresh`**:
   - Only re-extracts from changed files
   - Updates manifest with deltas

3. **Spec data used by**:
   - `/workflows:plan` - To understand existing system
   - `/workflows:review` - To check spec compliance
   - `/workflows:compound` - To detect spec drift

---

## Commands

```bash
# Extract all specs (full analysis)
/workflows:extract-specs --full

# Extract specific spec type
/workflows:extract-specs --entities
/workflows:extract-specs --api
/workflows:extract-specs --rules
/workflows:extract-specs --constraints

# Extract from specific path
/workflows:extract-specs --path=src/Domain/Entity

# Output format
/workflows:extract-specs --format=json
/workflows:extract-specs --format=markdown

# Incremental (only changed files)
/workflows:extract-specs --incremental

# Compare extracted vs documented
/workflows:spec-drift
```

---

## Confidence Scoring

The agent assigns confidence scores based on:

### High Confidence (90-100%)
- Explicit type annotations present
- Clear naming conventions followed
- Validation rules explicitly defined
- Documentation/comments match code

### Medium Confidence (70-89%)
- Types inferred from usage
- Conventions mostly followed
- Some implicit business logic
- Partial documentation

### Low Confidence (<70%)
- Dynamic typing / `any` types
- Unconventional patterns
- Complex conditional logic
- No documentation

---

## Language-Specific Extraction

### PHP (Symfony/Laravel)

```php
// Entity detection
#[ORM\Entity]
class User {
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Email]
    private string $email;
}

// Extracts: id (UUID, primary_key), email (string, unique, email_format)
```

### TypeScript (NestJS/Express)

```typescript
// Entity detection
@Entity()
export class User {
    @PrimaryGeneratedColumn('uuid')
    id: string;

    @Column({ unique: true })
    @IsEmail()
    email: string;
}

// Extracts: id (UUID, primary_key), email (string, unique, email_format)
```

### Python (FastAPI/Django)

```python
# Entity detection (Pydantic/SQLAlchemy)
class User(Base):
    __tablename__ = "users"

    id: Mapped[UUID] = mapped_column(primary_key=True)
    email: Mapped[str] = mapped_column(unique=True)

# Extracts: id (UUID, primary_key), email (string, unique)
```

---

## Anti-Patterns to Detect

### Code Smells That Affect Extraction

| Anti-Pattern | Detection | Impact on Specs |
|--------------|-----------|-----------------|
| God Class | >15 fields in entity | Split into multiple entity specs |
| Hidden Rules | Business logic in controllers | Rules may be missed |
| Magic Numbers | Hardcoded values without constants | Constraints incomplete |
| Implicit Dependencies | No interface, direct instantiation | Architectural constraints unclear |

---

## Output Files

```
.ai/project/specs/
├── manifest.json                    # Master index (spec_manifest.json schema)
├── entities/
│   ├── user.json                    # entity_spec.json schema
│   ├── order.json
│   └── product.json
├── api/
│   ├── user-api.json                # api_contract.json schema
│   └── order-api.json
├── rules/
│   ├── USER-001-email-unique.json   # business_rule_spec.json schema
│   └── ORDER-001-total-calc.json
└── constraints/
    ├── ARCH-001-domain-isolation.json  # architectural_constraint_spec.json
    └── ARCH-002-naming-conventions.json
```

---

## Related Agents

| Agent | Relationship |
|-------|--------------|
| **Spec Analyzer** | Uses extracted specs to verify implementation compliance |
| **Style Enforcer** | Validates naming conventions extracted as constraints |
| **Comprehension Guardian** | Ensures extracted specs are understood by team |

---

## References

- Schema: `core/schemas/entity_spec.json`
- Schema: `core/schemas/api_contract.json`
- Schema: `core/schemas/business_rule_spec.json`
- Schema: `core/schemas/architectural_constraint_spec.json`
- Schema: `core/schemas/spec_manifest.json`
- Workflow: `/workflows:discover`

---

**Remember**: Extracted specs are only as accurate as the code they come from. Always verify low-confidence extractions with domain experts or existing documentation.
