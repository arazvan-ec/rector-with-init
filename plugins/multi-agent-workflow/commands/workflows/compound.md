---
name: workflows:compound
description: "Capture learnings after completing a feature to make future work easier. The compounding effect of engineering."
argument_hint: <feature-name>
---

# Multi-Agent Workflow: Compound

Capture insights from completed features to make future development easier.

## Philosophy

> "Each unit of engineering work should make subsequent units easier—not harder"
> — Compound Engineering Principle

The `/workflows:compound` command is the key differentiator of compound engineering.
Without it, you're just doing work. With it, work builds on work.

## The 70% Problem Awareness

> "AI helps you reach 70% quickly, but the remaining 30% is where real complexity lives."
> — Addy Osmani, Beyond Vibe Coding

When capturing learnings, pay special attention to **where the 70% ended and the 30% began**:

```
Feature Timeline:
├── 0-70%: Fast progress (AI excels)
│   └── Scaffolding, CRUD, happy paths
│
└── 70-100%: Slow progress (Human expertise needed)
    ├── Edge cases
    ├── Error handling
    ├── Security hardening
    └── Integration issues
```

**Questions to answer in compound capture:**
- Where did progress slow down? (The 70% boundary)
- What caused the "two-step-back" pattern? (Fixes introducing bugs)
- What would have prevented the slowdown if known earlier?
- What should future specs include to avoid this?

This awareness helps future planning account for the **real complexity**, not just the easy 70%.

## Flow Guard (prerequisite check)

Before executing, verify the flow has been followed:

```
PREREQUISITE CHECK:
  1. Does 50_state.md exist for this feature?
     - NO: STOP. This feature has not been through the workflow.

  2. Is QA status = APPROVED in 50_state.md?
     - NO (REJECTED): STOP. Review was rejected. Fix issues and re-review first.
     - NO (other): STOP. Review has not been completed. Run /workflows:review first.
     - YES: Proceed with compound capture.

  If either check fails, do NOT proceed.
```

## Usage

```bash
# After QA approval (includes automatic spec updates)
/workflows:compound user-authentication

# Disable automatic spec updates
/workflows:compound user-authentication --update-specs=false

# Only update specs (skip other compound steps)
/workflows:compound user-authentication --specs-only
```

## Flags

| Flag | Default | Description |
|------|---------|-------------|
| `--update-specs` | `true` | Automatically update project specs after feature completion |
| `--specs-only` | `false` | Only perform spec updates, skip pattern capture and other compound steps |

## When to Run

Run after:
- ✅ Feature is APPROVED by QA
- ✅ All tests passing
- ✅ Ready to merge

## What This Command Does

### Execution Strategy: Parallel Subagents

This command can launch multiple specialized subagents IN PARALLEL to maximize efficiency:

**Parallel Subagents (Optional - for thorough documentation):**

| Subagent | Task | Returns |
|----------|------|---------|
| **Context Analyzer** | Extract feature context, problem type, components | YAML frontmatter skeleton |
| **Solution Extractor** | Analyze investigation steps, find root cause | Solution content block |
| **Related Docs Finder** | Search docs/solutions/ for related documentation | Links and relationships |
| **Prevention Strategist** | Develop prevention strategies, test cases | Prevention/testing content |
| **Pattern Recognizer** | Identify reusable patterns and anti-patterns | Pattern documentation |

**Launch with:**
```
Task general-purpose: "Analyze the conversation history for this feature.
Extract: problem type, component affected, symptoms, and root cause.
Return YAML frontmatter for docs/solutions/"

Task general-purpose: "Search docs/solutions/ for related documentation.
Find: cross-references, similar issues, related patterns.
Return: list of related files and suggested links"
```

**Sequential Steps (Main Flow):**

### Step 1: Analyze Feature History

```bash
# Get all commits for the feature
git log --oneline feature/${FEATURE_ID}

# Get diff from base branch
git diff main...feature/${FEATURE_ID}

# Analyze files changed
git diff --stat main...feature/${FEATURE_ID}
```

### Step 2: Extract Patterns

Identify what went well:

```markdown
## Patterns Identified

### Pattern 1: Email Validation Value Object
**Where**: src/Domain/ValueObject/Email.php
**Why it worked**: Encapsulates validation, immutable, reusable
**Recommendation**: Use for all email fields in future entities

### Pattern 2: TDD for Use Cases
**Where**: tests/Application/CreateUserUseCaseTest.php
**Why it worked**: Found 3 bugs during Red phase before implementation
**Recommendation**: Always write use case tests first
```

### Step 3: Identify Anti-Patterns & The 70% Boundary

Document what should be avoided AND where progress slowed:

```markdown
## Anti-Patterns Found

### Anti-Pattern 1: Skipping Integration Tests
**Where**: Initial implementation had no API tests
**What happened**: 500 error found only during QA
**Cost**: 2 extra iterations to fix
**Rule**: Always write integration tests for new endpoints

### Anti-Pattern 2: Incomplete API Contract
**Where**: FEATURE_X.md missing error response formats
**What happened**: Frontend had to guess error handling
**Cost**: 3 back-and-forth messages to clarify
**Rule**: Always specify all error responses in contracts

## The 70% Boundary Analysis

### Where did the 70% end?
**Milestone**: Basic CRUD working, happy path tests passing
**Time spent**: 2 hours (40% of total)

### What made the 30% hard?
1. **Edge case**: Email already exists scenario
   - Not in original spec
   - Required new validation logic
   - Added 1 hour

2. **Security**: Password hashing integration
   - bcrypt config not documented
   - Trial and error with rounds
   - Added 45 minutes

3. **Integration**: Frontend form validation mismatch
   - Backend and frontend had different rules
   - Required sync meeting
   - Added 30 minutes

### What would have helped?
- [ ] Spec should include ALL error scenarios upfront
- [ ] Security requirements should reference existing patterns
- [ ] Validation rules should be in shared contract

### Prevention for future features
- Add "Error Scenarios" section to spec template
- Create validation rules library (shared between BE/FE)
- Document security patterns in project_specific.md
```

### Step 3b: Update Agent Compound Memory

After documenting anti-patterns and the 70% boundary, update `.ai/project/compound-memory.md`:

```markdown
# Update compound-memory.md with this feature's data

## For each anti-pattern/pain point found in Step 3:

1. Check if pain point already exists in compound-memory.md:
   - EXISTS: Increment frequency (e.g., "2/5 features" → "3/6 features"), update severity if worse
   - NEW: Add new entry under "Known Pain Points"

2. For each successful pattern found in Step 2:
   - EXISTS: Increment reliability counter
   - NEW: Add new entry under "Historical Patterns"

3. Recalculate Agent Calibration table:
   - ≥2 related pain points for an agent → intensity = HIGH
   - 1 related pain point → intensity = default + warning flag
   - 0 related pain points → intensity = default
   - ≥3 reliable good patterns → may LOWER intensity (team is consistent)

4. If a pain point or pattern has been present for ≥5 features:
   - PROMOTE to project rules (global_rules.md)
   - Mark in memory: "[PROMOTED to global_rules.md on ${DATE}]"
```

See `core/agent-memory.md` for the full Agent Compound Memory specification.

### Step 4: Update Project Rules

If patterns are generalizable, update rules:

```markdown
# Additions to global_rules.md

## Email Validation (Added from user-authentication feature)
All email fields must use the Email value object pattern:
- Create src/Domain/ValueObject/Email.php
- Validation in constructor
- Immutable (no setters)
- Reference: src/Domain/ValueObject/Email.php from user-auth feature
```

### Step 5: Create Compound Log Entry

Append to `.ai/project/compound_log.md`:

```markdown
# Compound Log

## 2026-01-16: user-authentication

### Summary
Implemented user registration with email/password authentication.
3 iterations to complete Domain layer, 2 for Application layer.

### Time Investment
- Planning: 2 hours (40%)
- Implementation: 2 hours (40%)
- Review: 30 minutes (10%)
- Compound: 30 minutes (10%)
- **Total**: 5 hours

### Learnings Captured

#### Patterns to Reuse
1. **Email Value Object** - Use for all email validation
   - File: src/Domain/ValueObject/Email.php
   - Tests: tests/Unit/Domain/ValueObject/EmailTest.php

2. **Registration Form Pattern** - Use for all auth forms
   - File: src/components/RegistrationForm.tsx
   - Tests: src/__tests__/RegistrationForm.test.tsx

#### Rules Updated
- global_rules.md: Added Email VO requirement
- ddd_rules.md: Added Value Object immutability check

#### Anti-Patterns Documented
1. Skipping integration tests → Added to QA checklist
2. Incomplete API contracts → Added template requirement

### Specs Updated
Records what project specifications were created or modified by this feature.

#### Entities
| Entity | Action | File |
|--------|--------|------|
| User | CREATED | .ai/project/specs/entities/user.md |
| EmailVO | CREATED | .ai/project/specs/entities/email-vo.md |

#### API Contracts
| Endpoint | Action | File |
|----------|--------|------|
| POST /api/users | CREATED | .ai/project/specs/api-contracts/users.md |
| GET /api/users/{id} | CREATED | .ai/project/specs/api-contracts/users.md |

#### Business Rules
| Rule ID | Action | File |
|---------|--------|------|
| BR-AUTH-001 | CREATED | .ai/project/specs/business-rules/authentication.md |
| BR-AUTH-002 | CREATED | .ai/project/specs/business-rules/authentication.md |
| BR-AUTH-003 | CREATED | .ai/project/specs/business-rules/authentication.md |

#### Spec Manifest Update
- Timestamp: 2026-01-16T14:30:00Z
- History entry added: Yes
- Files affected: 5

### Impact on Future Work
- Next auth feature (password reset) can reuse:
  - Email VO ✓
  - Form pattern ✓
  - Test structure ✓
- **Project specs now reflect**: User entity, users API, auth rules
- Estimated time savings: 30-40%

### Questions for Future
- Should we create a shared auth package?
- Is JWT refresh token pattern documented?
```

### Step 6: Update Feature Templates

If new templates discovered:

```bash
# Save successful patterns as templates
cp .ai/project/features/user-auth/FEATURE_user-auth.md \
   .ai/workflow/templates/FEATURE_auth_template.md
```

### Step 7: Spec Diff Analysis (NEW)

Compare feature specifications with existing project specs to identify changes:

```bash
# Feature spec files to analyze
FEATURE_SPECS=".ai/project/features/${FEATURE_ID}/12_specs.md"
FEATURE_SOLUTIONS=".ai/project/features/${FEATURE_ID}/15_solutions.md"

# Project spec directories
PROJECT_ENTITIES=".ai/project/specs/entities/"
PROJECT_API=".ai/project/specs/api-contracts/"
PROJECT_RULES=".ai/project/specs/business-rules/"
```

#### Spec Diff Report Generation

```markdown
## Spec Diff Report: ${FEATURE_NAME}

### New Entities Detected
| Entity | Source File | Status |
|--------|-------------|--------|
| User | 12_specs.md:45 | NEW |
| EmailVO | 15_solutions.md:23 | NEW |

### Modified Entities
| Entity | Changes | Source |
|--------|---------|--------|
| Account | +passwordHash field | 12_specs.md:67 |

### New API Endpoints
| Endpoint | Method | Source |
|----------|--------|--------|
| /api/users | POST | 15_solutions.md:89 |
| /api/users/{id} | GET | 15_solutions.md:95 |

### New Business Rules
| Rule ID | Description | Source |
|---------|-------------|--------|
| BR-AUTH-001 | Email must be unique | 12_specs.md:120 |
| BR-AUTH-002 | Password min 8 chars | 12_specs.md:125 |

### New Patterns Identified
| Pattern | Location | Reusability |
|---------|----------|-------------|
| Email Value Object | Domain/ValueObject | High |
| JWT Token Strategy | Infrastructure/Auth | Medium |
```

#### Diff Analysis Process

1. **Parse feature specs** (12_specs.md):
   - Extract entity definitions
   - Extract acceptance criteria
   - Extract business rules

2. **Parse feature solutions** (15_solutions.md):
   - Extract implementation patterns
   - Extract API contracts
   - Extract architectural decisions

3. **Compare with existing project specs**:
   - Check `.ai/project/specs/entities/` for existing entities
   - Check `.ai/project/specs/api-contracts/` for existing endpoints
   - Check `.ai/project/specs/business-rules/` for existing rules

4. **Generate diff report**:
   - NEW: Entity/endpoint/rule not in project specs
   - MODIFIED: Entity/endpoint/rule exists but changed
   - UNCHANGED: Already in project specs

### Step 8: Update Project Specs (NEW)

Automatically update project specifications to reflect the new feature state:

```bash
# Skip if --update-specs=false
if [[ "${UPDATE_SPECS}" == "false" ]]; then
    echo "Skipping spec updates (--update-specs=false)"
    exit 0
fi
```

#### 8.1 Update Entity Specs

```markdown
# .ai/project/specs/entities/user.md (created/updated)

---
entity: User
version: 1.0.0
created: 2026-01-16
last_updated: 2026-01-16
source_feature: user-authentication
---

## Entity: User

### Properties
| Property | Type | Required | Description |
|----------|------|----------|-------------|
| id | UUID | Yes | Unique identifier |
| email | Email (VO) | Yes | User email address |
| passwordHash | string | Yes | Bcrypt hashed password |
| createdAt | DateTime | Yes | Account creation timestamp |

### Invariants
- Email must be unique across all users
- Password hash must use bcrypt with cost factor >= 12

### Related Entities
- Profile (1:1)
- Session (1:N)
```

#### 8.2 Update API Contract Specs

```markdown
# .ai/project/specs/api-contracts/users.md (created/updated)

---
api_group: users
version: 1.0.0
created: 2026-01-16
last_updated: 2026-01-16
source_feature: user-authentication
---

## API Group: Users

### POST /api/users
**Purpose**: Create new user account

**Request Body**:
```json
{
  "email": "string (required)",
  "password": "string (required, min 8 chars)"
}
```

**Response 201**:
```json
{
  "id": "uuid",
  "email": "string",
  "createdAt": "ISO8601"
}
```

**Error Responses**:
- 400: Validation error
- 409: Email already exists
```

#### 8.3 Update Business Rules Specs

```markdown
# .ai/project/specs/business-rules/authentication.md (created/updated)

---
domain: authentication
version: 1.0.0
created: 2026-01-16
last_updated: 2026-01-16
source_feature: user-authentication
---

## Business Rules: Authentication

### BR-AUTH-001: Unique Email
- **Rule**: Each user email must be unique in the system
- **Enforcement**: Database unique constraint + application validation
- **Error**: "Email already registered"

### BR-AUTH-002: Password Requirements
- **Rule**: Password must be at least 8 characters
- **Enforcement**: Application validation
- **Error**: "Password must be at least 8 characters"

### BR-AUTH-003: Password Hashing
- **Rule**: Passwords must be hashed with bcrypt (cost >= 12)
- **Enforcement**: Domain service
- **Audit**: Security review required for changes
```

#### 8.4 Update Spec Manifest

```yaml
# .ai/project/specs/spec-manifest.yaml

version: "1.0"
last_updated: "2026-01-16T14:30:00Z"

entities:
  - name: User
    file: entities/user.md
    version: 1.0.0
    created: 2026-01-16
    source_feature: user-authentication

api_contracts:
  - group: users
    file: api-contracts/users.md
    version: 1.0.0
    created: 2026-01-16
    source_feature: user-authentication
    endpoints:
      - POST /api/users
      - GET /api/users/{id}

business_rules:
  - domain: authentication
    file: business-rules/authentication.md
    version: 1.0.0
    created: 2026-01-16
    source_feature: user-authentication
    rules:
      - BR-AUTH-001
      - BR-AUTH-002
      - BR-AUTH-003

history:
  - date: 2026-01-16
    feature: user-authentication
    changes:
      - "Added User entity"
      - "Added users API contract"
      - "Added authentication business rules"
```

#### Using the Spec-Merger Skill

```bash
# Invoke spec-merger skill for intelligent merging
/workflow-skill:spec-merger \
  --feature="${FEATURE_ID}" \
  --source-specs=".ai/project/features/${FEATURE_ID}/12_specs.md" \
  --source-solutions=".ai/project/features/${FEATURE_ID}/15_solutions.md" \
  --target-dir=".ai/project/specs/" \
  --mode=merge  # merge | overwrite | dry-run
```

The spec-merger skill:
1. Parses feature specs and solutions
2. Identifies new/modified specs
3. Merges changes preserving existing content
4. Updates manifest with timestamps
5. Creates backup before modifications

## Compound Checklist

- [ ] Analyzed git history for patterns
- [ ] Documented successful patterns (2-3)
- [ ] Documented anti-patterns (1-2)
- [ ] **Identified the 70% boundary** (where progress slowed)
- [ ] **Documented what made the 30% hard**
- [ ] **Listed preventions for future features**
- [ ] **Updated compound-memory.md** (Step 3b: pain points, patterns, agent calibration)
- [ ] Updated relevant project rules
- [ ] Added entry to compound_log.md
- [ ] Created/updated templates if applicable
- [ ] Estimated time savings for future work
- [ ] **Generated spec diff report** (Step 7)
- [ ] **Updated project specs** (Step 8, unless --update-specs=false)
  - [ ] Updated/created entity specs
  - [ ] Updated/created API contract specs
  - [ ] Updated/created business rules specs
  - [ ] Updated spec-manifest.yaml with history

## Output

After running compound:

```
Compound capture complete for: user-authentication

Patterns captured: 3
Anti-patterns documented: 2
Rules updated: 2 files
Templates created: 1

═══════════════════════════════════════════════════════════════
SPEC UPDATES (--update-specs=true)
═══════════════════════════════════════════════════════════════

Spec Diff Analysis:
  New entities detected: 2
  Modified entities: 1
  New API endpoints: 2
  New business rules: 3

Project Specs Updated:
  ✓ .ai/project/specs/entities/user.md (CREATED)
  ✓ .ai/project/specs/entities/email-vo.md (CREATED)
  ✓ .ai/project/specs/api-contracts/users.md (CREATED)
  ✓ .ai/project/specs/business-rules/authentication.md (CREATED)
  ✓ .ai/project/specs/spec-manifest.yaml (UPDATED)

Spec Manifest History Entry:
  - date: 2026-01-16
  - feature: user-authentication
  - changes: 4 files created, 1 file updated

═══════════════════════════════════════════════════════════════

Estimated future time savings: 30-40% on similar features

Next feature recommendation:
- Use Email VO pattern
- Follow RegistrationForm pattern
- Reference: .ai/project/compound_log.md
- Reference: .ai/project/specs/ (updated specs)

Compound log updated: .ai/project/compound_log.md
```

### Output with --specs-only

```
Spec-only update complete for: user-authentication

═══════════════════════════════════════════════════════════════
SPEC UPDATES
═══════════════════════════════════════════════════════════════

Spec Diff Analysis:
  New entities detected: 2
  Modified entities: 1
  New API endpoints: 2
  New business rules: 3

Project Specs Updated:
  ✓ .ai/project/specs/entities/user.md (CREATED)
  ✓ .ai/project/specs/entities/email-vo.md (CREATED)
  ✓ .ai/project/specs/api-contracts/users.md (CREATED)
  ✓ .ai/project/specs/business-rules/authentication.md (CREATED)
  ✓ .ai/project/specs/spec-manifest.yaml (UPDATED)

Note: Pattern capture and other compound steps skipped (--specs-only mode)
To run full compound: /workflows:compound user-authentication
```

### Output with --update-specs=false

```
Compound capture complete for: user-authentication

Patterns captured: 3
Anti-patterns documented: 2
Rules updated: 2 files
Templates created: 1

Spec updates: SKIPPED (--update-specs=false)
  To update specs manually: /workflows:compound user-authentication --specs-only

Estimated future time savings: 30-40% on similar features

Compound log updated: .ai/project/compound_log.md
```

## Compound Metrics

Track compounding effect over time:

```markdown
## Compound Metrics

| Feature | Planning | Implementation | Review | Compound | Total | Patterns |
|---------|----------|----------------|--------|----------|-------|----------|
| user-auth | 2h | 2h | 0.5h | 0.5h | 5h | 3 new |
| password-reset | 1h | 1h | 0.5h | 0.5h | 3h | 2 reused |
| profile-edit | 0.5h | 1h | 0.5h | 0.5h | 2.5h | 3 reused |

**Trend**: Each feature takes less time as patterns compound.
```

## The Compound Effect

```
Feature 1: 5 hours + 3 patterns captured
Feature 2: 3 hours (reused 2 patterns) + 2 new patterns
Feature 3: 2.5 hours (reused 4 patterns) + 1 new pattern
Feature 4: 2 hours (reused 5 patterns)

Total: 12.5 hours for 4 features
Without compounding: ~20 hours (5h each)
Time saved: 37.5%
```

## State Update

After compound capture:

```markdown
## Feature: user-authentication
**Status**: COMPLETED + COMPOUNDED
**Compound Date**: 2026-01-16
**Patterns Captured**: 3
**Rules Updated**: 2

### Compound Summary
- Email VO pattern documented
- RegistrationForm pattern documented
- Integration test requirement added to rules
- Estimated 30-40% time savings on similar features
```

## Best Practices

1. **Run immediately after QA approval** - Context is fresh
2. **Be specific about patterns** - Include file paths
3. **Quantify impact** - "Saved 2 iterations" not "was helpful"
4. **Update rules** - Make patterns enforceable
5. **Reference future work** - Connect to next features

## Compound Effect Over Time

```
Month 1: Establishing patterns (slower)
Month 2: Reusing patterns (faster)
Month 3: Pattern library mature (much faster)
Month 6: New features feel like "already done"
```

The compound effect is why planning matters: good planning creates good patterns, good patterns accelerate future work.

## Structured Documentation (docs/solutions/)

For permanent institutional knowledge, create files in `docs/solutions/` with YAML frontmatter:

### Directory Structure
```
docs/solutions/
├── performance-issues/
├── database-issues/
├── runtime-errors/
├── security-issues/
├── integration-issues/
├── ui-bugs/
├── logic-errors/
├── test-failures/
├── build-errors/
├── best-practices/
└── patterns/
    └── critical-patterns.md   # Must-know patterns for all work
```

### Frontmatter Schema
```yaml
---
title: "N+1 Query Fix for User Dashboard"
category: performance-issues
tags: [orm, n-plus-one, eager-loading, database]
module: UserDashboard
component: api
symptoms:
  - "Slow page load (>2s)"
  - "Multiple queries in logs"
root_cause: "Missing includes on association"
severity: high
date_discovered: 2026-01-15
feature_origin: user-authentication
---
```

### When to Create docs/solutions/ Entry

Create a structured solution document when:
- Problem took >30 minutes to diagnose
- Root cause was non-obvious
- Pattern applies to multiple modules
- Security or data integrity issue
- Performance degradation >50%

### Auto-Invoke Triggers

<auto_invoke>
<trigger_phrases>
- "that worked"
- "it's fixed"
- "working now"
- "problem solved"
- "tests passing"
- "QA approved"
</trigger_phrases>

<manual_override>
Use `/workflows:compound [feature]` to document immediately.
</manual_override>
</auto_invoke>

## Applicable Review Agents

After compound capture, these agents can enhance documentation:

### Quality Review
- **code-simplicity-reviewer**: Ensures solution is minimal
- **pattern-recognition-specialist**: Identifies anti-patterns

### Domain Experts
- **security-review**: Reviews security issues
- **performance-review**: Validates optimization approaches
- **ddd-compliance**: Checks architectural learnings

### Integration
This command integrates with:
- `/workflows:plan` - Learnings inform future plans
- `learnings-researcher` agent - Searches documented solutions
- `compound_log.md` - Quick reference for patterns
- `spec-merger` skill - Intelligent spec merging and conflict resolution
- `.ai/project/specs/` - Project specifications (entities, API contracts, business rules)
- `spec-manifest.yaml` - Central registry of all project specifications

### Spec Update Flow

```
Feature Complete
       │
       ▼
┌──────────────────┐
│ Spec Diff        │ Compare 12_specs.md & 15_solutions.md
│ Analysis         │ with existing project specs
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Generate Diff    │ Identify NEW, MODIFIED, UNCHANGED specs
│ Report           │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Update Specs     │ Use spec-merger skill
│ (if enabled)     │
└────────┬─────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌───────┐ ┌───────────┐
│Entities│ │API Contracts│
└───┬───┘ └─────┬─────┘
    │           │
    │     ┌─────┴─────┐
    │     │           │
    │     ▼           ▼
    │ ┌───────────┐ ┌──────────┐
    │ │Business   │ │Spec      │
    │ │Rules      │ │Manifest  │
    │ └───────────┘ └──────────┘
    │
    ▼
┌──────────────────┐
│ compound_log.md  │ Log specs_updated section
│ Entry            │
└──────────────────┘
```

### Project Specs Directory Structure

After compound with spec updates:

```
.ai/project/specs/
├── spec-manifest.yaml          # Central registry with history
├── entities/
│   ├── user.md                 # User entity spec
│   ├── email-vo.md             # Email value object spec
│   └── ...
├── api-contracts/
│   ├── users.md                # Users API endpoints
│   ├── auth.md                 # Auth API endpoints
│   └── ...
└── business-rules/
    ├── authentication.md       # Auth domain rules
    ├── user-management.md      # User domain rules
    └── ...
```
