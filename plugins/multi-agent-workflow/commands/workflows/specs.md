---
name: workflows:specs
description: "Manage project specifications (Living Specs system). Track entities, APIs, business rules, and architectural constraints."
argument_hint: "[entities|api|rules|constraints|refresh|drift|validate] [--format=<table|json|yaml>]"
---

# Multi-Agent Workflow: Specs

Manage the Living Specs system - a dynamic specification repository that keeps documentation in sync with code.

> **"Specs that don't evolve with code become lies"** - Living Specs Philosophy

## Purpose

The Living Specs system provides:

1. **Single Source of Truth**: All specifications in one queryable location
2. **Drift Detection**: Automatic detection when code diverges from specs
3. **Code Extraction**: Generate specs from existing code patterns
4. **Validation**: Ensure implementations match specifications

## Usage

```bash
# Show summary of all project specs
/workflows:specs

# List entity specifications
/workflows:specs entities

# List API contract specifications
/workflows:specs api

# List business rules
/workflows:specs rules

# List architectural constraints
/workflows:specs constraints

# Re-extract specs from codebase
/workflows:specs refresh

# Detect drift between code and specs
/workflows:specs drift

# Validate code against specs
/workflows:specs validate
```

## Options

| Option | Default | Description |
|--------|---------|-------------|
| `--format` | table | Output format (table, json, yaml) |
| `--path` | `.ai/specs/` | Specs directory location |
| `--verbose` | false | Show detailed information |
| `--fix` | false | Auto-fix detected drift (with `drift` subcommand) |

---

## Subcommand: Summary (Default)

```bash
/workflows:specs
```

### Output Example

```
+======================================================================+
|                    PROJECT SPECIFICATIONS SUMMARY                     |
+======================================================================+

Project: my-project
Specs Location: .ai/specs/
Last Refresh: 2026-02-03 14:30:22
Drift Status: CLEAN

+------------------+-------+----------+-----------+
| Category         | Count | Verified | With Drift|
+------------------+-------+----------+-----------+
| Entities         |    12 |       11 |         1 |
| API Contracts    |     8 |        8 |         0 |
| Business Rules   |    24 |       22 |         2 |
| Constraints      |     6 |        6 |         0 |
+------------------+-------+----------+-----------+
| TOTAL            |    50 |       47 |         3 |
+------------------+-------+----------+-----------+

Drift Detected: 3 specs need attention
  - Entity: User (field mismatch)
  - Rule: BR-007 (implementation missing)
  - Rule: BR-012 (logic changed)

Quick Actions:
  /workflows:specs drift          # View drift details
  /workflows:specs refresh --fix  # Auto-fix where possible
  /workflows:specs validate       # Full validation report
```

### Execution Steps

1. **Load specs index** from `.ai/specs/index.yaml`
2. **Count specs** by category
3. **Check verification status** for each spec
4. **Detect drift** summary (detailed in drift subcommand)
5. **Display summary** with actionable recommendations

---

## Subcommand: Entities

```bash
/workflows:specs entities
/workflows:specs entities --format=json
/workflows:specs entities --verbose
```

Lists all entity specifications from the domain layer.

### Output Example

```
+======================================================================+
|                       ENTITY SPECIFICATIONS                           |
+======================================================================+

Found 12 entities in .ai/specs/entities/

+---------------+------------------+--------+------------+--------------+
| Entity        | Source File      | Status | Properties | Invariants   |
+---------------+------------------+--------+------------+--------------+
| User          | domain/User.ts   | DRIFT  |         8  |           3  |
| Order         | domain/Order.ts  | OK     |        12  |           5  |
| Product       | domain/Product.ts| OK     |         6  |           2  |
| Payment       | domain/Payment.ts| OK     |         9  |           4  |
| Address       | domain/Address.ts| OK     |         5  |           1  |
| Cart          | domain/Cart.ts   | OK     |         4  |           2  |
| CartItem      | domain/CartItem.ts| OK    |         3  |           1  |
| Invoice       | domain/Invoice.ts| OK     |         7  |           3  |
| Shipment      | domain/Shipment.ts| OK    |         6  |           2  |
| Discount      | domain/Discount.ts| OK    |         4  |           2  |
| Category      | domain/Category.ts| OK    |         3  |           1  |
| Review        | domain/Review.ts | OK     |         5  |           2  |
+---------------+------------------+--------+------------+--------------+

Legend: OK = Verified, DRIFT = Code differs from spec, NEW = Not yet documented

Entities with Drift:
  User: Property 'lastLoginAt' exists in code but not in spec
        Run: /workflows:specs refresh --entity=User
```

### Entity Spec Structure

Each entity spec in `.ai/specs/entities/[entity].yaml`:

```yaml
entity:
  name: User
  description: "Represents a registered user in the system"
  source: src/domain/entities/User.ts

properties:
  - name: id
    type: UserId (Value Object)
    required: true
    description: "Unique identifier"

  - name: email
    type: Email (Value Object)
    required: true
    unique: true
    description: "User's email address"

  - name: status
    type: UserStatus (Enum)
    required: true
    values: [ACTIVE, INACTIVE, SUSPENDED]

invariants:
  - id: INV-001
    description: "Email must be unique across all users"
    enforcement: database constraint + domain validation

  - id: INV-002
    description: "Status transitions must follow state machine"
    enforcement: domain method validation

relationships:
  - type: has_many
    target: Order
    description: "User can have multiple orders"

value_objects:
  - UserId
  - Email
  - UserStatus
```

---

## Subcommand: API

```bash
/workflows:specs api
/workflows:specs api --format=json
/workflows:specs api --verbose
```

Lists all API contract specifications.

### Output Example

```
+======================================================================+
|                      API CONTRACT SPECIFICATIONS                      |
+======================================================================+

Found 8 API contracts in .ai/specs/api/

+------------------+------------+--------+-----------+----------+
| API              | Base Path  | Status | Endpoints | Versions |
+------------------+------------+--------+-----------+----------+
| Users API        | /api/users | OK     |         6 | v1       |
| Orders API       | /api/orders| OK     |         8 | v1       |
| Products API     | /api/products| OK   |         5 | v1       |
| Auth API         | /api/auth  | OK     |         4 | v1       |
| Payments API     | /api/payments| OK   |         3 | v1       |
| Cart API         | /api/cart  | OK     |         4 | v1       |
| Search API       | /api/search| OK     |         2 | v1       |
| Webhooks API     | /api/webhooks| OK   |         3 | v1       |
+------------------+------------+--------+-----------+----------+

Total Endpoints: 35
Documented: 35 (100%)

Endpoint Coverage by HTTP Method:
  GET:    18 (51%)
  POST:   10 (29%)
  PUT:     4 (11%)
  DELETE:  3 (9%)
```

### API Spec Structure

Each API spec in `.ai/specs/api/[api-name].yaml`:

```yaml
api:
  name: Users API
  version: v1
  base_path: /api/users
  description: "User management endpoints"

endpoints:
  - method: GET
    path: /
    name: List Users
    description: "Get paginated list of users"
    auth: required
    roles: [admin]
    request:
      query:
        - name: page
          type: integer
          default: 1
        - name: limit
          type: integer
          default: 20
    response:
      200:
        schema: UserListResponse
        example: |
          {
            "users": [...],
            "pagination": {...}
          }
      401:
        description: "Unauthorized"

  - method: POST
    path: /
    name: Create User
    description: "Register a new user"
    auth: optional
    request:
      body:
        schema: CreateUserRequest
        required: [email, password, name]
    response:
      201:
        schema: UserResponse
      400:
        description: "Validation error"
      409:
        description: "Email already exists"

schemas:
  UserResponse:
    type: object
    properties:
      id: { type: string, format: uuid }
      email: { type: string, format: email }
      name: { type: string }
      status: { type: string, enum: [ACTIVE, INACTIVE, SUSPENDED] }
```

---

## Subcommand: Rules

```bash
/workflows:specs rules
/workflows:specs rules --format=json
/workflows:specs rules --verbose
/workflows:specs rules --category=pricing
```

Lists all business rules specifications.

### Output Example

```
+======================================================================+
|                      BUSINESS RULES SPECIFICATIONS                    |
+======================================================================+

Found 24 business rules in .ai/specs/rules/

+----------+----------------------------------+----------+--------+----------+
| Rule ID  | Description                      | Category | Status | Priority |
+----------+----------------------------------+----------+--------+----------+
| BR-001   | User email must be verified      | auth     | OK     | HIGH     |
| BR-002   | Password minimum 8 characters    | auth     | OK     | HIGH     |
| BR-003   | Order total minimum $10          | orders   | OK     | MEDIUM   |
| BR-004   | Free shipping over $50           | shipping | OK     | MEDIUM   |
| BR-005   | Max 10 items per cart            | cart     | OK     | LOW      |
| BR-006   | Discount max 50%                 | pricing  | OK     | HIGH     |
| BR-007   | Refund within 30 days            | payments | DRIFT  | HIGH     |
| BR-008   | Stock check before order         | inventory| OK     | CRITICAL |
| ...      | ...                              | ...      | ...    | ...      |
+----------+----------------------------------+----------+--------+----------+

Rules by Category:
  auth:      4 rules
  orders:    5 rules
  shipping:  3 rules
  cart:      2 rules
  pricing:   4 rules
  payments:  3 rules
  inventory: 3 rules

Rules with Issues:
  BR-007 (DRIFT): Implementation differs from spec
    Spec: "Refund within 30 days"
    Code: "Refund within 14 days" (found in PaymentService.ts:142)

  BR-012 (MISSING): No implementation found
    Spec: "Premium users get priority support"
    Expected in: UserService.ts
```

### Rule Spec Structure

Each rule in `.ai/specs/rules/[category].yaml`:

```yaml
category: payments
description: "Business rules related to payment processing"

rules:
  - id: BR-007
    name: Refund Window
    description: "Refunds must be requested within 30 days of purchase"
    priority: HIGH

    conditions:
      - "Order status is DELIVERED"
      - "Request date <= order date + 30 days"
      - "Items are not marked as non-refundable"

    actions:
      - "Create refund request"
      - "Notify customer service"
      - "Process refund within 5 business days"

    exceptions:
      - "Damaged items: 60 day window"
      - "Premium users: 45 day window"

    implementation:
      file: src/application/services/PaymentService.ts
      method: processRefund
      line: 142

    tests:
      - src/tests/payment/refund.test.ts

    last_verified: 2026-01-28
```

---

## Subcommand: Constraints

```bash
/workflows:specs constraints
/workflows:specs constraints --format=json
/workflows:specs constraints --verbose
```

Lists architectural constraints and invariants.

### Output Example

```
+======================================================================+
|                    ARCHITECTURAL CONSTRAINTS                          |
+======================================================================+

Found 6 constraints in .ai/specs/constraints/

+----------+----------------------------------------+------------+--------+
| ID       | Constraint                             | Type       | Status |
+----------+----------------------------------------+------------+--------+
| AC-001   | Domain layer has no external deps      | dependency | OK     |
| AC-002   | Controllers only call Application svc  | layer      | OK     |
| AC-003   | No direct DB access from domain        | layer      | OK     |
| AC-004   | All public APIs require authentication | security   | OK     |
| AC-005   | Sensitive data must be encrypted       | security   | OK     |
| AC-006   | Max 3 levels of inheritance            | design     | OK     |
+----------+----------------------------------------+------------+--------+

Constraint Types:
  dependency: 1 (Layer dependency rules)
  layer:      2 (Clean architecture layers)
  security:   2 (Security requirements)
  design:     1 (Design patterns/limits)

All Constraints Satisfied
```

### Constraint Spec Structure

Constraints in `.ai/specs/constraints/architecture.yaml`:

```yaml
constraints:
  - id: AC-001
    name: Domain Independence
    description: "Domain layer must have no dependencies on infrastructure or frameworks"
    type: dependency

    applies_to:
      - src/domain/**/*

    forbidden_imports:
      - "@prisma/*"
      - "express"
      - "axios"
      - "src/infrastructure/*"

    allowed_imports:
      - "src/domain/*"
      - "lodash" # Utility only

    enforcement:
      - eslint-rule: no-restricted-imports
      - ci-check: dependency-cruiser

    rationale: |
      Domain layer encapsulates business logic and must remain
      testable without infrastructure concerns.

  - id: AC-002
    name: Controller Layer Restriction
    description: "Controllers can only call Application services, not domain directly"
    type: layer

    applies_to:
      - src/presentation/controllers/**/*

    allowed_calls:
      - src/application/services/*
      - src/application/queries/*
      - src/application/commands/*

    forbidden_calls:
      - src/domain/services/*
      - src/domain/repositories/*

    enforcement:
      - eslint-rule: layer-boundaries
      - architecture-test: ArchUnit
```

---

## Subcommand: Refresh

```bash
/workflows:specs refresh
/workflows:specs refresh --entity=User
/workflows:specs refresh --category=api
/workflows:specs refresh --force
```

Re-extracts specifications from the codebase.

### Execution Steps

1. **Scan codebase** for entities, APIs, rules, and constraints
2. **Extract metadata** from code comments, decorators, and structure
3. **Compare with existing specs** to detect changes
4. **Update specs** or flag for manual review
5. **Generate report** of changes

### Output Example

```
+======================================================================+
|                      SPECS REFRESH IN PROGRESS                        |
+======================================================================+

Scanning codebase...

Phase 1: Entity Extraction
  Scanning src/domain/entities/...
  Found 12 entities

  Changes detected:
    [UPDATED] User - Added property 'lastLoginAt'
    [NEW] Subscription - New entity detected
    [UNCHANGED] Order, Product, Payment... (9 entities)

Phase 2: API Extraction
  Scanning src/presentation/controllers/...
  Found 35 endpoints across 8 APIs

  Changes detected:
    [UPDATED] Users API - New endpoint POST /api/users/bulk
    [UNCHANGED] Orders API, Products API... (7 APIs)

Phase 3: Business Rules Extraction
  Scanning src/application/services/...
  Scanning code comments for @rule annotations...
  Found 24 rules

  Changes detected:
    [UPDATED] BR-007 - Implementation changed (30 days -> 14 days)
    [UNCHANGED] 23 rules

Phase 4: Constraint Verification
  Running dependency analysis...
  Running layer boundary checks...

  All 6 constraints still satisfied

+======================================================================+
|                         REFRESH SUMMARY                               |
+======================================================================+

| Category    | Unchanged | Updated | New | Removed |
|-------------|-----------|---------|-----|---------|
| Entities    |         9 |       1 |   1 |       0 |
| APIs        |         7 |       1 |   0 |       0 |
| Rules       |        23 |       1 |   0 |       0 |
| Constraints |         6 |       0 |   0 |       0 |

Actions Required:
  1. Review updated specs for accuracy
  2. Add description for new entity 'Subscription'
  3. Confirm BR-007 change is intentional (30 -> 14 days)

Specs saved to: .ai/specs/
Run '/workflows:specs drift' to see detailed drift analysis
```

---

## Subcommand: Drift

```bash
/workflows:specs drift
/workflows:specs drift --category=entities
/workflows:specs drift --fix
/workflows:specs drift --verbose
```

Detects drift between code and specifications.

### Output Example

```
+======================================================================+
|                      DRIFT DETECTION REPORT                           |
+======================================================================+

Analyzing code against specifications...

+======================================================================+
|                         DRIFT DETECTED: 3                             |
+======================================================================+

DRIFT #1: Entity - User
+------------------------------------------------------------------+
| Type: Property Mismatch                                           |
+------------------------------------------------------------------+
| Spec says:                                                        |
|   Properties: id, email, name, status, createdAt, updatedAt      |
|                                                                   |
| Code has:                                                         |
|   Properties: id, email, name, status, createdAt, updatedAt,     |
|               lastLoginAt, preferences                            |
|                                                                   |
| Missing from spec: lastLoginAt, preferences                       |
| Location: src/domain/entities/User.ts:15-18                       |
+------------------------------------------------------------------+
| Recommendation: Update spec or remove properties from code        |
| Auto-fix available: YES                                           |
| Run: /workflows:specs refresh --entity=User                       |
+------------------------------------------------------------------+

DRIFT #2: Business Rule - BR-007
+------------------------------------------------------------------+
| Type: Implementation Mismatch                                     |
+------------------------------------------------------------------+
| Spec says:                                                        |
|   "Refunds must be requested within 30 days of purchase"         |
|                                                                   |
| Code implements:                                                  |
|   const REFUND_WINDOW_DAYS = 14;                                 |
|   if (daysSincePurchase > REFUND_WINDOW_DAYS) { ... }           |
|                                                                   |
| Discrepancy: 30 days (spec) vs 14 days (code)                    |
| Location: src/application/services/PaymentService.ts:142         |
+------------------------------------------------------------------+
| Recommendation: Align spec with code OR fix code                  |
| Auto-fix available: NO (requires business decision)               |
| Action: Consult with product owner                                |
+------------------------------------------------------------------+

DRIFT #3: Business Rule - BR-012
+------------------------------------------------------------------+
| Type: Missing Implementation                                      |
+------------------------------------------------------------------+
| Spec says:                                                        |
|   "Premium users get priority support ticket routing"            |
|   Expected in: UserService.ts or SupportService.ts               |
|                                                                   |
| Code status:                                                      |
|   No implementation found                                         |
|   Searched: UserService.ts, SupportService.ts, TicketService.ts  |
+------------------------------------------------------------------+
| Recommendation: Implement rule or remove from specs               |
| Auto-fix available: NO (requires implementation)                  |
| Action: Add to backlog or mark spec as PLANNED                    |
+------------------------------------------------------------------+

+======================================================================+
|                         DRIFT SUMMARY                                 |
+======================================================================+

| Category      | Clean | Drifted | % Clean |
|---------------|-------|---------|---------|
| Entities      |    11 |       1 |   91.7% |
| APIs          |     8 |       0 |  100.0% |
| Rules         |    22 |       2 |   91.7% |
| Constraints   |     6 |       0 |  100.0% |
|---------------|-------|---------|---------|
| TOTAL         |    47 |       3 |   94.0% |

Drift Score: 94.0% (Target: >95%)

Next Steps:
  1. Fix auto-fixable drift: /workflows:specs drift --fix
  2. Review business decisions for BR-007
  3. Plan implementation for BR-012
```

---

## Subcommand: Validate

```bash
/workflows:specs validate
/workflows:specs validate --strict
/workflows:specs validate --category=api
/workflows:specs validate --output=report.md
```

Validates code against specifications.

### Output Example

```
+======================================================================+
|                      VALIDATION REPORT                                |
+======================================================================+

Running comprehensive validation...

PHASE 1: Spec Syntax Validation
+------------------------------------------------------------------+
| Validating YAML syntax and schema compliance...                   |
| Entities:    12/12 valid                                          |
| APIs:         8/8 valid                                           |
| Rules:       24/24 valid                                          |
| Constraints:  6/6 valid                                           |
| Result: ALL SPECS SYNTACTICALLY VALID                             |
+------------------------------------------------------------------+

PHASE 2: Implementation Completeness
+------------------------------------------------------------------+
| Checking if all specs have implementations...                     |
|                                                                   |
| Entities:                                                         |
|   12 specified, 12 implemented                                    |
|                                                                   |
| API Endpoints:                                                    |
|   35 specified, 35 implemented                                    |
|                                                                   |
| Business Rules:                                                   |
|   24 specified, 23 implemented, 1 missing                         |
|   Missing: BR-012 (Premium support routing)                       |
|                                                                   |
| Result: 98.3% IMPLEMENTATION COVERAGE                             |
+------------------------------------------------------------------+

PHASE 3: Test Coverage for Specs
+------------------------------------------------------------------+
| Checking test coverage for specified behavior...                  |
|                                                                   |
| Entity Tests:                                                     |
|   12 entities, 11 with unit tests (91.7%)                        |
|   Missing: Subscription entity tests                              |
|                                                                   |
| API Tests:                                                        |
|   35 endpoints, 32 with integration tests (91.4%)                |
|   Missing: POST /api/users/bulk, DELETE /api/webhooks/{id}...    |
|                                                                   |
| Rule Tests:                                                       |
|   24 rules, 20 with explicit tests (83.3%)                       |
|   Missing: BR-019, BR-020, BR-022, BR-024                        |
|                                                                   |
| Result: 88.8% TEST COVERAGE FOR SPECS                             |
+------------------------------------------------------------------+

PHASE 4: Constraint Enforcement
+------------------------------------------------------------------+
| Verifying architectural constraints...                            |
|                                                                   |
| AC-001 (Domain Independence):         PASS                        |
| AC-002 (Controller Layer Restriction): PASS                       |
| AC-003 (No Direct DB in Domain):       PASS                       |
| AC-004 (API Authentication):           PASS                       |
| AC-005 (Data Encryption):              PASS                       |
| AC-006 (Inheritance Limit):            PASS                       |
|                                                                   |
| Result: ALL CONSTRAINTS ENFORCED                                  |
+------------------------------------------------------------------+

+======================================================================+
|                      VALIDATION SUMMARY                               |
+======================================================================+

| Check                    | Status | Score   |
|--------------------------|--------|---------|
| Spec Syntax              | PASS   | 100.0%  |
| Implementation Coverage  | WARN   |  98.3%  |
| Test Coverage            | WARN   |  88.8%  |
| Constraint Enforcement   | PASS   | 100.0%  |
|--------------------------|--------|---------|
| OVERALL                  | WARN   |  96.8%  |

Validation Result: PASSED WITH WARNINGS

Warnings:
  1. BR-012 not implemented (planned for next sprint?)
  2. Test coverage below 90% target
  3. New entity 'Subscription' needs tests

Recommendations:
  - Add missing tests before next release
  - Implement or defer BR-012
  - Schedule test coverage improvement sprint
```

---

## Spec Directory Structure

```
.ai/specs/
├── index.yaml              # Master index of all specs
├── entities/               # Domain entity specifications
│   ├── User.yaml
│   ├── Order.yaml
│   └── ...
├── api/                    # API contract specifications
│   ├── users-api.yaml
│   ├── orders-api.yaml
│   └── ...
├── rules/                  # Business rules by category
│   ├── auth.yaml
│   ├── payments.yaml
│   ├── shipping.yaml
│   └── ...
├── constraints/            # Architectural constraints
│   └── architecture.yaml
└── history/               # Change history
    └── changelog.yaml
```

---

## Integration with Other Commands

| Command | Integration |
|---------|-------------|
| `/workflows:plan` | Uses specs to understand existing domain |
| `/workflows:work` | References specs during implementation |
| `/workflows:review` | Validates changes against specs |
| `/workflows:tdd` | Generates tests from spec requirements |
| `/workflows:validate` | Uses spec schemas for validation |
| `/workflows:comprehension` | Checks spec understanding |

### Automatic Spec Updates

When using `/workflows:work`, specs are automatically checked:

```markdown
## Pre-Work Spec Check

Before implementing, verifying specs...
- Entity specs: 12 (all current)
- Related rules: BR-003, BR-008 (verified)
- API contracts: Orders API (verified)

Proceeding with implementation...
```

### CI/CD Integration

```yaml
# .github/workflows/specs.yml
name: Spec Validation
on: [push, pull_request]

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Validate Specs
        run: |
          # Run spec validation
          ./scripts/validate-specs.sh

      - name: Check Drift
        run: |
          # Fail if drift detected
          ./scripts/check-drift.sh --strict
```

---

## Best Practices

### 1. Keep Specs Updated

```bash
# Run after every significant code change
/workflows:specs refresh
```

### 2. Review Drift Weekly

```bash
# Add to weekly review checklist
/workflows:specs drift
```

### 3. Use Annotations in Code

```typescript
/**
 * @entity User
 * @invariant Email must be unique
 * @rule BR-001 Email verification required
 */
export class User {
  // ...
}
```

### 4. Validate Before Merge

```bash
# In PR checklist
/workflows:specs validate --strict
```

### 5. Document Business Decisions

When drift is intentional, document why:

```yaml
# In rule spec
rule:
  id: BR-007
  description: "Refund within 14 days"
  history:
    - date: 2026-02-01
      change: "Reduced from 30 to 14 days"
      reason: "Business decision to align with industry standard"
      approved_by: "Product Owner"
```

---

## Related Commands

- `/workflows:plan` - Plan features using spec context
- `/workflows:validate` - Validate YAML specs against schemas
- `/workflows:interview` - Generate specs through guided interview
- `/workflows:comprehension` - Verify understanding of specs
- `/workflows:discover` - Initial project analysis
