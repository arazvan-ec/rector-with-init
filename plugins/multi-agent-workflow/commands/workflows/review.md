---
name: workflows:review
description: "Multi-agent review before merge. Validates implementation against specs, runs tests, and approves or rejects."
argument_hint: <feature-name> [--agent=<security|performance|ddd|code>]
---

# Multi-Agent Workflow: Review

Comprehensive review phase using specialized review agents.

## Flow Guard (prerequisite check)

Before executing, verify the flow has been followed:

```
PREREQUISITE CHECK:
  1. Does 50_state.md exist for this feature?
     - NO: STOP. This feature has not been planned or worked on.

  2. Is the implementation work COMPLETED?
     - Check backend status = COMPLETED (if applicable)
     - Check frontend status = COMPLETED or WAITING_API (if applicable)
     - If neither is COMPLETED: STOP. Run /workflows:work first.

  3. Is QA status already APPROVED?
     - YES: Review already passed. Confirm re-review with user.
     - NO: Proceed with review.

  If checks 1 or 2 fail, do NOT proceed.
```

## Automatic Operations (built into this command)

The following are executed automatically as part of `/workflows:review`:
- **Comprehension check** -- verifies understanding of generated code
- **SOLID score verification** -- checks >= 18/25 minimum
- **Spec validation** -- validates implementation against specs
- **Trust-level evaluation** -- applies appropriate scrutiny per file

You do NOT need to invoke these separately.

## Usage

```bash
# Full review (all agents)
/workflows:review user-authentication

# Specific review agent
/workflows:review user-authentication --agent=security
/workflows:review user-authentication --agent=performance
/workflows:review user-authentication --agent=ddd
/workflows:review user-authentication --agent=code
```

## Review Agents

| Agent | Focus | When to Use |
|-------|-------|-------------|
| **qa** (default) | Full review (includes SOLID) | Always |
| **solid** | SOLID compliance, patterns | All features (mandatory) |
| **security** | OWASP, vulnerabilities | Auth, payments, sensitive data |
| **performance** | Speed, optimization | High-traffic features |
| **ddd** | DDD compliance | Backend with business logic |
| **code** | Code quality, patterns | All features |

**Note**: SOLID review is ALWAYS included in the default `qa` review. Use `--agent=solid` for SOLID-only review.

## Philosophy

> "Trust = Passing Test Suite + Evidence"

Never say "it works" without:
- Test results with commands
- Screenshots/logs as evidence
- Explicit acceptance criteria verification

## Review Process

### Phase 1: API Testing (Backend Verification)

```bash
# Happy path
curl -X POST localhost:8000/api/users -d '{"email":"test@example.com",...}'
# Expected: 201 Created
# Actual: [document response]

# Validation errors
curl -X POST localhost:8000/api/users -d '{"email":"invalid"}'
# Expected: 400 Bad Request
# Actual: [document response]

# Edge cases
curl -X POST localhost:8000/api/users -d '{"email":"existing@email.com"}'
# Expected: 409 Conflict
# Actual: [document response]
```

### Phase 2: UI Testing (Frontend Verification)

```markdown
Test 1: Happy Path
- Steps: Open /register → Fill valid data → Submit
- Expected: Success toast, redirect to /dashboard
- Actual: [document result]
- Evidence: [screenshot description]

Test 2: Validation
- Steps: Enter invalid email → Submit
- Expected: Error message under field
- Actual: [document result]

Test 3: Responsive
- Mobile (375px): [document result]
- Tablet (768px): [document result]
- Desktop (1024px): [document result]
```

### Phase 3: Automated Test Execution

```bash
# Backend
php bin/phpunit tests/
# Result: X/Y tests passing
# Coverage: Z%

# Frontend
npm test
# Result: X/Y tests passing
# Coverage: Z%

# E2E
npm run test:e2e -- feature-name
# Result: X/Y scenarios passing
```

### Phase 4: Code Quality Review

```markdown
1. TDD Compliance (CRITICAL):
   - [ ] Git history shows tests before implementation
   - [ ] Red-Green-Refactor cycle followed

   Verification:
   git log --oneline | head -20
   # Expected: test commits before implementation commits

2. **SOLID Design Implementation (CRITICAL)**:

   > QA verifica que se IMPLEMENTÓ el diseño SOLID de 15_solutions.md,
   > NO diseña ni juzga si el diseño es correcto (eso es Phase 3).

   - [ ] Patterns from 15_solutions.md were correctly implemented
   - [ ] Class structure matches the design document
   - [ ] SOLID score matches or exceeds expected score from design

   Verification:
   # 1. Read expected design
   Read: .ai/project/features/${FEATURE_ID}/15_solutions.md

   # 2. Verify implementation matches design
   /workflow-skill:solid-analyzer --path=src --validate

   # 3. Compare actual vs expected
   # Expected from design: [X]/25
   # Actual: [Y]/25
   # If actual < expected: REJECT - implementation doesn't match design

   SOLID Checklist (verify design was followed):
   - [ ] **S** - SRP: Classes structured as designed in 15_solutions.md
   - [ ] **O** - OCP: Patterns (Strategy, Decorator) implemented as specified
   - [ ] **L** - LSP: Inheritance used as designed
   - [ ] **I** - ISP: Interfaces match design (size, roles)
   - [ ] **D** - DIP: Dependencies injected as specified in design

3. DDD Compliance (Backend):
   - [ ] Domain layer has no infrastructure dependencies
   - [ ] Entities have behavior, not just getters
   - [ ] Value objects are immutable

4. Test Coverage:
   - Backend: >80% required, actual: [X]%
   - Frontend: >70% required, actual: [X]%

5. Code Style:
   - Backend: ./vendor/bin/php-cs-fixer fix --dry-run
   - Frontend: npm run lint
```

### Phase 5: Acceptance Criteria Validation

For each criterion in FEATURE_X.md:

```markdown
Criterion 1: "User can register with email/name/password"
- [✓] PASS
- Evidence: curl test returned 201, user in database
- Screenshot: [description]

Criterion 2: "System validates email format"
- [✓] PASS
- Evidence: Invalid email → 400 with error details

Criterion 3: "User redirected after registration"
- [✓] PASS
- Evidence: Browser redirected to /dashboard
```

## Issue Reporting Template

```markdown
## Issue #1: [Title]

**Severity**: CRITICAL | MAJOR | MINOR
**Type**: Backend | Frontend | Integration
**Status**: OPEN

**Steps to Reproduce**:
1. [Step 1]
2. [Step 2]

**Expected**: [What should happen]
**Actual**: [What happened]

**Evidence**:
- Error log: [paste log]
- Screenshot: [description]

**Root Cause** (if known): [hypothesis]
**Suggested Fix**: [recommendation]
**Location**: file.php:45

**Assigned To**: Backend Engineer | Frontend Engineer
**Priority**: P0 | P1 | P2
```

## Decision Criteria

### APPROVED - All must be true:
- ✅ All acceptance criteria met (with evidence)
- ✅ No critical/major bugs (P0/P1)
- ✅ All automated tests passing
- ✅ **SOLID design was implemented** (matches 15_solutions.md)
- ✅ Code meets quality standards
- ✅ Documentation complete

### REJECTED - Any of these:
- ❌ Any acceptance criterion fails
- ❌ Critical or major bug found
- ❌ Automated tests failing
- ❌ **Implementation doesn't match SOLID design** (15_solutions.md)
- ❌ Security vulnerability present
- ❌ Code quality below standards

> **Note**: QA rejects if implementation doesn't match the SOLID design.
> If the design itself was wrong, that's a Phase 3 issue, not QA's responsibility.

## QA Report Template

```markdown
# QA Report: ${FEATURE_ID}

**Reviewer**: Claude QA
**Date**: 2026-01-16
**Status**: APPROVED | REJECTED

## Summary
[1-2 sentence summary]

## Test Results
- API Tests: X/Y passing
- UI Tests: X/Y passing
- Unit Tests: X/Y passing (Z% coverage)
- E2E Tests: X/Y passing

## SOLID Design Implementation
- **Expected Score** (from 15_solutions.md): [X]/25
- **Actual Score**: [Y]/25
- **Status**: ✅ MATCHES DESIGN | ❌ DOESN'T MATCH DESIGN
- **Patterns from Design**:
  - [ ] Strategy for [X] - Implemented: ✅/❌
  - [ ] Repository for [Y] - Implemented: ✅/❌
  - [ ] Value Object for [Z] - Implemented: ✅/❌
- **Design Deviations Found**: [None | List what wasn't implemented as designed]

## Acceptance Criteria
- [✓] Criterion 1 - Evidence: [...]
- [✓] Criterion 2 - Evidence: [...]
- [✗] Criterion 3 - Issue #1

## Issues Found
### Critical (blocks approval)
- Issue #1: [description]
- SOLID Issue: [if score <18]

### Minor (can fix later)
- Issue #2: [description]

## Decision
**Status**: REJECTED

**Reason**: Issue #1 blocks user registration flow / Implementation doesn't match design

**Must fix before approval**:
1. Fix Issue #1 (Backend)
2. [If design not followed]: Implement patterns as specified in 15_solutions.md

**Next steps**:
- Backend fixes Issue #1
- Run /workflow-skill:solid-analyzer --validate
- Re-review after fix pushed
```

## State Update

After review, update `50_state.md`:

```markdown
## QA / Reviewer
**Status**: APPROVED | REJECTED
**Review Date**: 2026-01-16
**SOLID Design Match**: ✅ Implementation matches 15_solutions.md
**Critical Issues**: 0 | [count]
**Minor Issues**: [count]

### Review Summary
- Acceptance Criteria: 5/5 passed
- Test Coverage: Backend 87%, Frontend 78%
- **SOLID Design**: Expected 24/25, Actual 23/25 ✅
- Patterns Implemented: Strategy ✅, Repository ✅, Value Object ✅
- Issues Found: 0 critical, 2 minor

### Decision
APPROVED - Feature ready for merge (design implemented correctly)
```

## Compound Effect

Good reviews compound:
- Issues found early save debugging time
- Documented patterns prevent future bugs
- Test evidence becomes regression prevention
- Quality standards improve over time
