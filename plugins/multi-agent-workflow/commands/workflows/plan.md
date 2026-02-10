---
name: workflows:plan
description: "Convert ideas into implementable strategies with detailed planning. The foundation of compound engineering (80% planning, 20% execution)."
argument_hint: <feature-name> [--workflow=default|task-breakdown] [--show-impact=true|false]
---

# Multi-Agent Workflow: Plan

The planning phase is the foundation of compound engineering. Invest 80% of effort here.

## Flow Guard (prerequisite check)

Before executing, verify the flow has been followed:

```
PREREQUISITE CHECK:
  1. Was this request routed via /workflows:route?
     - YES: Continue to planning
     - NO: STOP. Run /workflows:route first, then return here.

  2. If 50_state.md exists for this feature, is this a continuation?
     - YES (planner = IN_PROGRESS): Resume planning from last checkpoint
     - YES (planner = COMPLETED): Plan already exists. Confirm re-planning with user.
     - NO: Fresh start, proceed normally.

  If either check fails, do NOT proceed. Route first.
```

## Usage

```
/workflows:plan user-authentication
/workflows:plan payment-system --workflow=task-breakdown
/workflows:plan order-management --show-impact=true
```

## Flags

| Flag | Default | Description |
|------|---------|-------------|
| `--workflow` | `default` | Workflow type: `default` or `task-breakdown` |
| `--show-impact` | `true` | Show detailed integration impact analysis |

## Philosophy

> "Each unit of engineering work should make subsequent units easier—not harder"

> "El código de alta calidad cumple SOLID de forma rigurosa"

Good planning means:
- Engineers can start WITHOUT asking questions
- **Solutions are designed with SOLID compliance from the start**
- API contracts are complete enough to mock
- Every task has clear "definition of done"

---

## Shaping Integration (Optional Pre-Phase)

If `/workflows:shape` was run before planning, the following artifacts are available:

| File | Content | How Planner Uses It |
|------|---------|---------------------|
| `01_shaped_brief.md` | Frame, requirements (R), shape, fit check | Accelerates Phase 1 (Understand) and Phase 2 (Specs) |
| `02_breadboard.md` | Places, affordances, wiring diagram | Informs Phase 3 (Solutions) with concrete mechanisms |
| `03_slices.md` | Vertical slices with demo statements | Becomes the task group structure in Phase 3 |
| `spike-*.md` | Technical investigation findings | Provides context for solution decisions |

When shaped brief exists, the planner should:
1. **Phase 1**: Use the Frame as the problem statement (verify with user, don't re-derive)
2. **Phase 2**: Use Requirements (R) as foundation for functional specs (add formal structure)
3. **Phase 3**: Use Shape parts as starting point for solutions (add SOLID analysis)
4. **Tasks**: Use Slices (V1, V2...) to structure task groups vertically

---

## The Architecture-First Planning Process

> **"Every new feature is an INTEGRATION into existing architecture, not an isolated solution."**

```
┌─────────────────────────────────────────────────────────────────┐
│                    STEP 0: LOAD PROJECT SPECS                   │
│  Read existing specs → Understand current architecture          │
│  └── entities, api-contracts, business-rules, constraints       │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 1: UNDERSTAND                          │
│  Analyze request → Ask clarifying questions → Document problem  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 2: SPECS + INTEGRATION ANALYSIS        │
│  Define WHAT the system must do:                                │
│  ├── Task-specific specs (user requirements, acceptance criteria│
│  └── **Integration Analysis** (existing specs impact)           │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 3: SOLUTIONS + ARCHITECTURAL IMPACT    │
│  Design HOW to implement each spec:                             │
│  ├── Functional solutions (implementation approach)             │
│  ├── **CONSTRAINT: SOLID** (patterns + quality = mandatory)     │
│  └── **Architectural Impact** (layers, modules, change scope)   │
└─────────────────────────────────────────────────────────────────┘
```

### Key Distinction

| Phase 2: SPECS | Phase 3: SOLUTIONS |
|----------------|-------------------|
| **QUÉ** debe hacer | **CÓMO** hacerlo |
| Requisitos funcionales | Diseño técnico |
| "User can register" | "Use Strategy pattern for validators" |
| Del usuario/negocio | Del desarrollador/arquitecto |

**SOLID es un CONSTRAINT de diseño en Fase 3, no una spec funcional en Fase 2.**

---

## STEP 0: LOAD PROJECT SPECS (Architecture Context)

Before planning any new feature, load and understand the existing project architecture.

### Step 0.0: Check for Shaped Brief (if exists)

```bash
# Check if shaping was done before planning
SHAPED_BRIEF=".ai/project/features/${FEATURE_ID}/01_shaped_brief.md"
if [ -f "$SHAPED_BRIEF" ]; then
  echo "Shaped brief found. Using as input for planning."
  # Read shaped brief, breadboard, slices
  # Accelerate Phase 1 and Phase 2 with existing context
fi
```

### Step 0.1: Read Existing Specifications

```bash
# Load all existing project specs
SPECS_BASE=".ai/project/specs"

# Entities - the domain model
ls -la ${SPECS_BASE}/entities/*.yaml

# API Contracts - existing endpoints
ls -la ${SPECS_BASE}/api-contracts/*.yaml

# Business Rules - domain logic constraints
ls -la ${SPECS_BASE}/business-rules/*.yaml

# Architectural Constraints - system boundaries
ls -la ${SPECS_BASE}/architectural-constraints/*.yaml
```

### Step 0.2: Generate Specs Summary

```markdown
## Existing Project Specs Summary

### Entities (Domain Model)
| Entity | Properties | Relationships | Last Modified |
|--------|------------|---------------|---------------|
| User | id, email, name, role | has_many: Orders | 2026-01-15 |
| Order | id, status, total | belongs_to: User | 2026-01-20 |
| Product | id, name, price, stock | has_many: OrderItems | 2026-01-18 |

### API Contracts
| Endpoint | Method | Entity | Description |
|----------|--------|--------|-------------|
| /api/users | GET, POST | User | User CRUD |
| /api/orders | GET, POST | Order | Order management |
| /api/products | GET | Product | Product catalog |

### Business Rules
| Rule ID | Entity | Description |
|---------|--------|-------------|
| BR-001 | Order | Order total must be > 0 |
| BR-002 | User | Email must be unique |
| BR-003 | Product | Stock cannot be negative |

### Architectural Constraints
| Constraint | Type | Description |
|------------|------|-------------|
| AC-001 | Layer | Domain must not import Infrastructure |
| AC-002 | Security | Auth required for write operations |
| AC-003 | Performance | API response < 200ms |
```

### Step 0.3: Display Summary to User

When `--show-impact=true` (default), display this summary at the start of planning:

```
╔══════════════════════════════════════════════════════════════════╗
║                    PROJECT ARCHITECTURE CONTEXT                   ║
╠══════════════════════════════════════════════════════════════════╣
║  Entities:     12 defined (User, Order, Product, ...)            ║
║  Endpoints:    24 API contracts                                   ║
║  Rules:        18 business rules                                  ║
║  Constraints:   8 architectural constraints                       ║
╠══════════════════════════════════════════════════════════════════╣
║  New feature will be planned as INTEGRATION into this context    ║
╚══════════════════════════════════════════════════════════════════╝
```

---

## PHASE 1: UNDERSTAND (Entender el Problema)

### Step 1.1: Analyze the Request

```markdown
## Request Analysis

**Original Request**: [user's exact words]
**Request Type**: [feature | refactor | bugfix | architecture | investigation]
**Affected Areas**: [modules/services involved]
**Confidence Level**: [0-100%]
```

### Step 1.2: Ask Clarifying Questions (if confidence < 60%)

Before proceeding, ensure you understand:

| Question Category | Example Questions |
|-------------------|-------------------|
| **Functional Scope** | ¿Qué debe hacer exactamente? ¿Qué NO debe hacer? |
| **Users/Actors** | ¿Quién usará esta funcionalidad? |
| **Integration** | ¿Se conecta con APIs externas? ¿Qué sistemas afecta? |
| **Constraints** | ¿Hay restricciones de tiempo/tecnología/rendimiento? |
| **Success Criteria** | ¿Cómo sabremos que está completo y funciona? |

### Step 1.3: Document the Understood Problem

```markdown
## Problem Statement

### What We're Building
[Clear description of the feature/task]

### Why It's Needed
[Business justification]

### Who Benefits
[Users/stakeholders]

### Constraints
- Technical: [stack, performance, integrations]
- Business: [timeline, budget, compliance]
- Team: [skills, availability]

### Success Criteria
1. [Measurable criterion 1]
2. [Measurable criterion 2]
```

---

## PHASE 2: SPECS (Requisitos Funcionales)

Phase 2 defines **WHAT** the system must do - the functional requirements from the user's perspective.

### Functional Specs Only

```markdown
## Functional Specs: ${FEATURE_ID}

### SPEC-F01: [Functional Requirement 1]
**Description**: [What it must do - from user perspective]
**Acceptance Criteria**:
- [ ] [Testable criterion]
- [ ] [Testable criterion]
**Verification**: [How to test]

### SPEC-F02: [Functional Requirement 2]
**Description**: [What it must do]
**Acceptance Criteria**:
- [ ] [Testable criterion]
**Verification**: [How to test]

### SPEC-F03: [Functional Requirement 3]
...
```

### Examples of Good Specs (Functional)

| Good Spec (QUÉ) | Bad Spec (CÓMO) |
|-----------------|-----------------|
| "User can register with email and password" | "Use Strategy pattern" |
| "System validates email format" | "Create EmailValidator class" |
| "Login returns authentication token" | "Implement Repository pattern" |
| "Password must be ≥8 characters" | "Use DIP for dependencies" |

**Note**: SOLID, patterns, and technical decisions belong in Phase 3, not here.

### How to Generate Specs

```bash
# Generate functional specs interactively
/workflow-skill:criteria-generator --feature=${FEATURE_ID} --interview

# Output: .ai/project/features/${FEATURE_ID}/12_specs.md
```

### Integration Analysis (when --show-impact=true)

After defining functional specs, analyze how they integrate with existing specs.

#### Step 2.A: Identify Integration Points

```markdown
## Integration Analysis: ${FEATURE_ID}

### Entities Impact

#### EXTENDED (existing entities with new properties/methods)
| Entity | New Property/Method | Reason |
|--------|---------------------|--------|
| User | `subscription_tier` | Support premium features |
| Order | `discount_applied` | Apply subscription discounts |

#### MODIFIED (existing entities with changed behavior)
| Entity | Change | Impact |
|--------|--------|--------|
| User | Validation rules | Add tier validation |

#### NEW (entities created by this feature)
| Entity | Purpose | Relationships |
|--------|---------|---------------|
| Subscription | Track user subscription | belongs_to: User |
| SubscriptionPlan | Define available plans | has_many: Subscriptions |

### API Contracts Impact

#### EXTENDED (existing endpoints with new parameters/responses)
| Endpoint | Change | Backward Compatible |
|----------|--------|---------------------|
| GET /api/users/{id} | Add `subscription` to response | YES |
| POST /api/orders | Accept `discount_code` param | YES |

#### MODIFIED (existing endpoints with changed behavior)
| Endpoint | Change | Migration Required |
|----------|--------|-------------------|
| None | - | - |

#### NEW (endpoints created by this feature)
| Endpoint | Method | Purpose |
|----------|--------|---------|
| /api/subscriptions | GET, POST | Subscription CRUD |
| /api/subscription-plans | GET | List available plans |

### Business Rules Impact

#### CONFLICTS (potential conflicts with existing rules)
| New Rule | Existing Rule | Resolution |
|----------|---------------|------------|
| "Premium users get 20% discount" | BR-001 "Order total > 0" | Check after discount |

#### NEW (business rules added by this feature)
| Rule ID | Entity | Description |
|---------|--------|-------------|
| BR-010 | Subscription | Active subscription required for premium features |
| BR-011 | Order | Discount cannot exceed 50% of order total |
```

#### Step 2.B: Conflict Detection

Before proceeding to Phase 3, verify no unresolved conflicts:

```bash
# Check for spec conflicts
/workflow-skill:spec-validator --feature=${FEATURE_ID} --check-conflicts

# Output:
# ✅ No entity name conflicts
# ✅ No endpoint path conflicts
# ⚠️ 1 business rule conflict (BR-001 vs new discount rule)
# Action: Resolve conflict before proceeding
```

---

## PHASE 3: PLAN WITH SOLUTIONS

Phase 3 defines **HOW** to implement each spec. This is where **SOLID becomes mandatory**.

### The SOLID Constraint

> ⚠️ **MANDATORY CONSTRAINT**: All solutions MUST comply with SOLID principles.
>
> This is NOT optional. Every solution designed in Phase 3 must:
> 1. Be analyzed for SOLID compliance
> 2. Use appropriate design patterns
> 3. Achieve score ≥18/25 to proceed, ≥22/25 to approve

### Step 3.1: Analyze Existing Code (SOLID Baseline)

Before designing solutions, understand the current state:

```bash
# Get SOLID score of affected areas
/workflow-skill:solid-analyzer --path=src/relevant-module

# Output:
# - Current SOLID score: X/25
# - Violations found: [list]
# - Recommended patterns: [list]
```

### Step 3.2: Design Solutions with SOLID

For EACH functional spec, propose a solution that complies with SOLID:

```markdown
## Solutions: ${FEATURE_ID}

---

### Solution for SPEC-F01: User Registration

**Approach**: Create User entity with email validation

**SOLID Compliance**:
| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | User entity only holds data, validation in ValueObject | Value Object |
| **O** - OCP | New validators can be added without modifying User | Strategy |
| **L** - LSP | N/A for this solution | - |
| **I** - ISP | Small, focused interfaces | - |
| **D** - DIP | Repository interface in Domain | Repository |

**Files to Create**:
- `Domain/Entity/User.php` (SRP: only user data)
- `Domain/ValueObject/Email.php` (SRP: email rules)
- `Domain/Repository/UserRepositoryInterface.php` (DIP: abstraction)
- `Infrastructure/Repository/DoctrineUserRepository.php` (DIP: implementation)

**Expected SOLID Score**: 24/25

---

### Solution for SPEC-F02: User Login

**Approach**: Authentication service with token generation

**SOLID Compliance**:
| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | Auth logic separate from token generation | Extract Class |
| **O** - OCP | Token strategies can be added | Strategy |
| **D** - DIP | Inject token generator interface | DI |

**Files to Create**:
- `Application/Service/AuthenticationService.php`
- `Domain/Service/TokenGeneratorInterface.php`
- `Infrastructure/Service/JwtTokenGenerator.php`

**Expected SOLID Score**: 23/25
```

### Step 3.3: Pattern Selection Guide

When designing solutions, select patterns based on the need:

| If You Need... | Use Pattern | SOLID Addressed |
|----------------|-------------|-----------------|
| Multiple behaviors/algorithms | **Strategy** | OCP, SRP |
| Add functionality without modification | **Decorator** | OCP, SRP |
| Abstract object creation | **Factory Method** | DIP, OCP |
| Integrate external systems | **Adapter / Ports & Adapters** | DIP, OCP |
| Abstract data persistence | **Repository** | SRP, DIP |
| Chain of processing steps | **Chain of Responsibility** | SRP, OCP |
| Encapsulate validation rules | **Value Object** | SRP |
| Decouple layers | **Dependency Injection** | DIP |

See `core/solid-pattern-matrix.md` for complete mapping.

### Step 3.4: Verify SOLID Score

Before finalizing the plan:

```bash
# Validate proposed design achieves SOLID score
/workflow-skill:solid-analyzer --validate --design=15_solutions.md

# Must achieve:
# - ≥18/25 to proceed to implementation
# - ≥22/25 to approve for merge
```

### SOLID Score Thresholds

| Score | Grade | Action |
|-------|-------|--------|
| 22-25/25 | A - SOLID Compliant | ✅ Approve |
| 18-21/25 | B - Acceptable | ✅ Proceed with notes |
| 14-17/25 | C - Needs Work | ❌ Redesign before implementation |
| <14/25 | F - Rejected | ❌ Complete redesign required |

### Step 3.5: Architectural Impact Analysis (when --show-impact=true)

After designing solutions, analyze the architectural impact across the codebase.

#### Layers Affected

```markdown
## Architectural Impact: ${FEATURE_ID}

### Layer Analysis

| Layer | Impact Level | Changes Required |
|-------|--------------|------------------|
| **Domain** | HIGH | 2 new entities, 1 extended |
| **Application** | MEDIUM | 3 new use cases |
| **Infrastructure** | MEDIUM | 2 new repositories |
| **Presentation/API** | LOW | 2 new endpoints |

### Affected Layers Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ PRESENTATION                                                     │
│   [MODIFIED] UserController.php                                  │
│   [NEW] SubscriptionController.php                               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ APPLICATION                                                      │
│   [NEW] CreateSubscriptionUseCase.php                            │
│   [NEW] ApplyDiscountUseCase.php                                 │
│   [MODIFIED] CreateOrderUseCase.php                              │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ DOMAIN                                                           │
│   [EXTENDED] User.php (add subscription_tier)                    │
│   [NEW] Subscription.php                                         │
│   [NEW] SubscriptionPlan.php                                     │
│   [NEW] DiscountCalculator.php (Domain Service)                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ INFRASTRUCTURE                                                   │
│   [NEW] DoctrineSubscriptionRepository.php                       │
│   [NEW] StripePaymentGateway.php                                 │
│   [MODIFIED] migrations/Version20260201_subscriptions.php        │
└─────────────────────────────────────────────────────────────────┘
```

### Existing Modules Touched

| Module | Files Touched | Risk Level | Notes |
|--------|---------------|------------|-------|
| `src/User/` | 3 files | MEDIUM | Core entity modified |
| `src/Order/` | 2 files | LOW | Minor additions |
| `src/Subscription/` | 8 files | N/A | New module |
| `tests/` | 12 files | LOW | New tests + updates |

### Change Scope Estimation

```
╔══════════════════════════════════════════════════════════════════╗
║                    CHANGE SCOPE SUMMARY                          ║
╠══════════════════════════════════════════════════════════════════╣
║  Files to CREATE:     14                                         ║
║  Files to MODIFY:      6                                         ║
║  Files to DELETE:      0                                         ║
║  ────────────────────────────────────────────────                ║
║  Total files affected: 20                                        ║
║                                                                  ║
║  Estimated LOC added:   ~800                                     ║
║  Estimated LOC modified: ~120                                    ║
║  ────────────────────────────────────────────────                ║
║  Complexity: MEDIUM                                              ║
║  Estimated effort: 2-3 days                                      ║
╚══════════════════════════════════════════════════════════════════╝
```

### Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| User entity changes break existing tests | MEDIUM | HIGH | Run full test suite before merge |
| Payment gateway integration issues | LOW | HIGH | Use sandbox environment |
| Migration conflicts | LOW | MEDIUM | Coordinate with team |
```

---

## Complete Planning Workflow

```bash
# 0. STEP 0: Load Project Specs (Architecture Context)
SPECS_BASE=".ai/project/specs"
# Read existing entities, api-contracts, business-rules, architectural-constraints
# Display summary of existing architecture
# Output: Understanding of current system

# 1. Create workspace
FEATURE_ID="user-authentication"
mkdir -p .ai/project/features/${FEATURE_ID}

# 2. PHASE 1: Understand
# - Analyze request IN CONTEXT of existing specs
# - Ask clarifying questions if needed
# - Document problem statement
# Output: 00_problem_statement.md

# 3. PHASE 2: Specs + Integration Analysis
/workflow-skill:criteria-generator --feature=${FEATURE_ID} --interview
# - Define functional specs (WHAT the system must do)
# - Identify EXTENDED entities/endpoints
# - Identify MODIFIED entities/endpoints
# - Identify NEW entities/endpoints
# - Detect conflicts with existing specs
# Output: 12_specs.md, 13_integration_analysis.md

# 4. PHASE 3: Solutions with SOLID + Architectural Impact
/workflow-skill:solid-analyzer --path=src/relevant-path  # Get baseline
# - Design solutions using patterns
# - Verify SOLID score ≥22/25
# - Analyze layers affected
# - List modules touched
# - Estimate change scope (files affected)
# Output: 15_solutions.md (HOW with SOLID), 16_architectural_impact.md

# 5. Create task breakdown
# Each task includes SOLID requirements + integration notes
# Output: 30_tasks.md
```

---

## Task Template (includes SOLID)

Each task must include SOLID requirements:

```markdown
### Task BE-001: Create User Entity

**Role**: Backend Engineer
**Methodology**: TDD (Red-Green-Refactor)

**Functional Requirement** (from SPEC-F01):
- User entity with id, email, name, password

**SOLID Requirements** (from solution design):
- **SRP**: Entity only holds data, no business logic
- **DIP**: No infrastructure imports in Entity
- **Pattern**: Value Object for Email

**Tests to Write FIRST**:
- [ ] test_user_can_be_created_with_valid_data()
- [ ] test_email_value_object_validates_format()

**Acceptance Criteria**:
- [ ] Entity in src/Domain/Entity/
- [ ] Value Object in src/Domain/ValueObject/
- [ ] **SOLID score ≥4/5 for SRP**
- [ ] No Doctrine imports in Domain

**Reference**: src/Domain/Entity/Order.php (existing pattern)
```

---

## Planning Checklist

Before marking planning as COMPLETED:

### Step 0: Architecture Context
- [ ] Existing specs loaded (entities, api-contracts, business-rules)
- [ ] Architectural constraints reviewed
- [ ] Current architecture understood

### Phase 1: Understanding
- [ ] Problem is clearly understood and documented
- [ ] Clarifying questions asked if needed
- [ ] Constraints identified
- [ ] **Context of existing architecture considered**

### Phase 2: Specs + Integration
- [ ] All functional specs defined (testable)
- [ ] Specs describe WHAT, not HOW
- [ ] API endpoints fully specified
- [ ] Success criteria clear
- [ ] **EXTENDED entities/endpoints identified**
- [ ] **MODIFIED entities/endpoints identified**
- [ ] **NEW entities/endpoints identified**
- [ ] **Conflicts with existing specs resolved**

### Phase 3: Solutions + Architectural Impact
- [ ] **SOLID baseline analyzed** (current score)
- [ ] Each spec has a solution
- [ ] **Patterns selected** for SOLID compliance
- [ ] **Expected SOLID score ≥22/25**
- [ ] Tasks include SOLID requirements
- [ ] **Layers affected documented**
- [ ] **Existing modules touched listed**
- [ ] **Change scope estimated (files affected)**
- [ ] **Risk assessment completed**

### Final Check
- [ ] Can engineer start WITHOUT asking questions? YES
- [ ] Is "why this pattern?" explained? YES
- [ ] **Is integration with existing code clear? YES**
- [ ] **Is architectural impact understood? YES**

**If SOLID is not addressed in Phase 3, the plan is INCOMPLETE.**
**If integration analysis is missing, the plan treats feature as ISOLATED (anti-pattern).**

---

## Output Files

### For `default` workflow:
```
.ai/project/features/${FEATURE_ID}/
├── 00_problem_statement.md    # Phase 1: Understanding
├── 12_specs.md                # Phase 2: Functional specs (WHAT)
├── 13_integration_analysis.md # Phase 2: Integration with existing specs (NEW)
├── 15_solutions.md            # Phase 3: Solutions with SOLID (HOW)
├── 16_architectural_impact.md # Phase 3: Layers/modules affected (NEW)
├── 30_tasks.md                # Task breakdown
├── 50_state.md                # State tracking
└── FEATURE_${FEATURE_ID}.md   # Summary
```

### For `task-breakdown` workflow:
```
.ai/project/features/${FEATURE_ID}/
├── 00_problem_statement.md
├── 10_architecture.md
├── 12_specs.md                # Functional specs only
├── 13_integration_analysis.md # Integration analysis (NEW)
├── 15_solutions.md            # Solutions with SOLID patterns
├── 15_data_model.md
├── 16_architectural_impact.md # Architectural impact (NEW)
├── 20_api_contracts.md
├── 30_tasks_backend.md
├── 31_tasks_frontend.md
├── 32_tasks_qa.md
├── 35_dependencies.md
├── 50_state.md
└── FEATURE_${FEATURE_ID}.md
```

---

## Integration Analysis Output

When `--show-impact=true` (default), the planning process generates a comprehensive integration analysis.

### 13_integration_analysis.md Structure

```markdown
# Integration Analysis: ${FEATURE_ID}

## Summary
- Entities: X extended, Y modified, Z new
- Endpoints: X extended, Y modified, Z new
- Business Rules: X conflicts, Y new
- Status: [CLEAR | CONFLICTS_DETECTED]

## Entities Impact
### Extended
[List of existing entities that gain new properties/methods]

### Modified
[List of existing entities with changed behavior]

### New
[List of new entities created by this feature]

## API Contracts Impact
### Extended
[Existing endpoints with new parameters/responses]

### Modified
[Existing endpoints with changed behavior]

### New
[New endpoints created by this feature]

## Business Rules Impact
### Conflicts
[Potential conflicts with existing rules and resolutions]

### New
[New business rules added by this feature]

## Compatibility Assessment
- Backward Compatible: [YES | NO | PARTIAL]
- Migration Required: [YES | NO]
- Breaking Changes: [List if any]
```

### 16_architectural_impact.md Structure

```markdown
# Architectural Impact: ${FEATURE_ID}

## Summary
- Layers affected: [Domain, Application, Infrastructure, Presentation]
- Modules touched: X existing, Y new
- Change scope: X files to create, Y files to modify

## Layer Analysis
[Table showing each layer's impact level and changes required]

## Modules Touched
[Table of existing modules with files touched and risk level]

## Change Scope
- Files to CREATE: N
- Files to MODIFY: N
- Total files affected: N
- Estimated LOC: N added, N modified
- Complexity: [LOW | MEDIUM | HIGH]
- Estimated effort: [time estimate]

## Risk Assessment
[Table of risks with probability, impact, and mitigation strategies]
```

---

## Example: Complete Plan

```markdown
# Feature Plan: user-authentication

## STEP 0: Architecture Context

### Existing Specs Loaded
- Entities: 5 (Product, Order, OrderItem, Category, Inventory)
- API Contracts: 12 endpoints
- Business Rules: 8 rules
- Architectural Constraints: 4 constraints

### Relevant to This Feature
- No existing User entity (greenfield for auth)
- AC-002: "Auth required for write operations" - this feature enables it

## PHASE 1: Problem Statement

We need user authentication with email/password.
Users should register, login, and logout.

## PHASE 2: Specs + Integration Analysis

### Functional Specs (WHAT)

#### SPEC-F01: User Registration
- User can register with email and password
- Email must be unique
- Password must be ≥8 characters

#### SPEC-F02: User Login
- User can login with email and password
- Returns authentication token on success

#### SPEC-F03: User Logout
- User can invalidate their token

### Integration Analysis

#### Entities Impact
| Type | Entity | Details |
|------|--------|---------|
| NEW | User | Core auth entity |
| NEW | RefreshToken | Token management |
| EXTENDED | Order | Add `user_id` foreign key |

#### API Contracts Impact
| Type | Endpoint | Details |
|------|----------|---------|
| NEW | POST /api/auth/register | User registration |
| NEW | POST /api/auth/login | User login |
| NEW | POST /api/auth/logout | User logout |
| MODIFIED | POST /api/orders | Require auth header |

#### Business Rules Impact
| Type | Rule | Details |
|------|------|---------|
| NEW | BR-AUTH-01 | Email must be unique |
| NEW | BR-AUTH-02 | Password ≥8 characters |
| ENABLES | AC-002 | Auth now possible for write ops |

#### Conflicts
- None detected

## PHASE 3: Solutions + Architectural Impact

### SOLID Baseline
Current code: N/A for auth (greenfield)
Target score: ≥22/25

### Solution for SPEC-F01 & F02

**Patterns Selected**:
| Need | Pattern | SOLID |
|------|---------|-------|
| Email validation rules | Value Object | SRP |
| Password hashing strategies | Strategy | OCP |
| User persistence | Repository | DIP |
| Token generation | Factory Method | DIP |

**Class Design**:
```
Domain/
├── Entity/User.php           (SRP: data only)
├── ValueObject/Email.php     (SRP: validation)
├── Repository/UserRepositoryInterface.php (DIP)
└── Service/PasswordHasherInterface.php (DIP)

Application/
└── Service/AuthenticationService.php (SRP)

Infrastructure/
├── Repository/DoctrineUserRepository.php
└── Service/BcryptPasswordHasher.php
```

**Expected SOLID Score**: 24/25

### Architectural Impact

#### Layers Affected
| Layer | Impact | Files |
|-------|--------|-------|
| Domain | HIGH | 5 new files |
| Application | MEDIUM | 3 new files |
| Infrastructure | MEDIUM | 4 new files |
| Presentation | LOW | 1 new controller |

#### Modules Touched
| Module | Files | Risk |
|--------|-------|------|
| src/Auth/ (NEW) | 12 | N/A |
| src/Order/ | 2 | LOW |
| config/ | 2 | LOW |

#### Change Scope
- Files to CREATE: 14
- Files to MODIFY: 4
- Total: 18 files
- Estimated effort: 2 days
```

---

## Summary: Architecture-First Planning

| Step/Phase | Content | SOLID? | Integration? |
|------------|---------|--------|--------------|
| **Step 0: Load Specs** | Existing architecture context | ❌ No | ✅ **YES - CONTEXT** |
| Phase 1: Understand | Problem statement | ❌ No | ❌ No |
| **Phase 2: Specs** | Functional requirements (WHAT) | ❌ No | ✅ **YES - ANALYSIS** |
| **Phase 3: Solutions** | Technical design (HOW) | ✅ **YES - MANDATORY** | ✅ **YES - IMPACT** |

**SOLID is a design CONSTRAINT in Phase 3, not a functional SPEC in Phase 2.**

**Integration analysis ensures every feature is designed as an EXTENSION of existing architecture, not an isolated solution.**

### The Integration Mindset

```
❌ WRONG: "I'll create a new User service for authentication"
✅ RIGHT: "I'll EXTEND the existing architecture with User entity,
          MODIFY Order to require auth, and CREATE 3 new endpoints"
```

Every planning session should answer:
1. What EXISTING specs will this feature EXTEND?
2. What EXISTING specs will this feature MODIFY?
3. What NEW specs will this feature CREATE?
4. Are there any CONFLICTS with existing specs?
5. What is the ARCHITECTURAL IMPACT (layers, modules, files)?

---

## Related Commands

- `/workflow-skill:criteria-generator` - Generate functional specs
- `/workflow-skill:solid-analyzer` - Analyze SOLID compliance
- `/workflow-skill:spec-validator` - Validate specs and detect conflicts
- `/workflows:work` - Execute the plan
- `/workflows:review` - Review implementation

## Related Documentation

- `core/solid-pattern-matrix.md` - Violation → Pattern mapping
- `core/architecture-quality-criteria.md` - Quality metrics
- `skills/workflow-skill-solid-analyzer.md` - SOLID analysis tool

## Project Specs Location

```
.ai/project/specs/
├── entities/                  # Domain entities YAML specs
│   ├── user.yaml
│   ├── order.yaml
│   └── product.yaml
├── api-contracts/             # API endpoint contracts
│   ├── users.yaml
│   ├── orders.yaml
│   └── products.yaml
├── business-rules/            # Domain logic constraints
│   └── rules.yaml
└── architectural-constraints/ # System boundaries
    └── constraints.yaml
```
