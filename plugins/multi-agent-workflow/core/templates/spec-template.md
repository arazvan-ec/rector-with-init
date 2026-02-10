# Specification Template

This template defines the structure for **functional specifications** (Phase 2).

**SOLID is NOT a spec** - it's a design CONSTRAINT applied in Phase 3 (Solutions).

---

## Template: 12_specs.md (Phase 2 Output)

```markdown
# Functional Specifications: ${FEATURE_ID}

**Created**: ${DATE}
**Status**: DRAFT | VALIDATED | APPROVED

---

## About This Document

This document defines **WHAT** the system must do (functional requirements).
**HOW** to implement (including SOLID compliance) is defined in `15_solutions.md`.

---

## Functional Specifications

### SPEC-F01: [Requirement Name]

**Description**: [What the system must do - from user perspective]

**Acceptance Criteria**:
- [ ] [Testable criterion 1]
- [ ] [Testable criterion 2]
- [ ] [Testable criterion 3]

**Verification Method**: [How to verify - test command, manual check, etc.]

**Priority**: Critical | High | Medium | Low

---

### SPEC-F02: [Requirement Name]

**Description**: [What the system must do]

**Acceptance Criteria**:
- [ ] [Testable criterion]

**Verification Method**: [How to verify]

**Priority**: Critical | High | Medium | Low

---

### SPEC-F03: [Requirement Name]

**Description**: [What the system must do]

**Acceptance Criteria**:
- [ ] [Testable criterion]

**Verification Method**: [How to verify]

**Priority**: Critical | High | Medium | Low

---

## Quality Specifications (Optional)

These are non-functional requirements that affect HOW we build.

### SPEC-Q01: Performance (if applicable)

**Description**: Performance requirements

**Acceptance Criteria**:
- [ ] Response time < [X]ms for [operation]
- [ ] Memory usage < [X]MB
- [ ] No N+1 queries

**Verification Method**: Performance tests / profiling

---

### SPEC-Q02: Testability

**Description**: Code must be testable

**Acceptance Criteria**:
- [ ] Unit test coverage ≥80%
- [ ] All public methods have tests

**Verification Method**: `./vendor/bin/phpunit --coverage`

---

## Specification Summary

| ID | Name | Type | Priority | Status |
|----|------|------|----------|--------|
| SPEC-F01 | [Name] | Functional | [Priority] | [ ] |
| SPEC-F02 | [Name] | Functional | [Priority] | [ ] |
| SPEC-F03 | [Name] | Functional | [Priority] | [ ] |
| SPEC-Q01 | Performance | Quality | Medium | [ ] |
| SPEC-Q02 | Testability | Quality | High | [ ] |

---

## What's NOT in This Document

❌ **SOLID compliance** - This is a design CONSTRAINT applied in Phase 3
❌ **Design patterns** - These are selected when designing solutions
❌ **Class structure** - This is part of the technical solution
❌ **Implementation details** - HOW belongs in `15_solutions.md`

---

## Approval

- [ ] Product Owner approved functional specs
- [ ] Specs describe WHAT, not HOW
- [ ] All specs are testable
- [ ] Ready for Phase 3 (Solutions)

**Approved By**: _______________
**Date**: _______________
```

---

## Template: 15_solutions.md (Phase 3 Output)

This is where SOLID becomes mandatory:

```markdown
# Solutions: ${FEATURE_ID}

**Created**: ${DATE}
**Status**: DRAFT | VALIDATED | APPROVED

---

## SOLID Constraint (MANDATORY)

> ⚠️ All solutions MUST comply with SOLID principles.

**SOLID Baseline** (from `/workflow-skill:solid-analyzer`):
- Current code score: [X/25 or N/A if greenfield]
- Violations found: [list or none]

**Target Score**: ≥22/25

---

## Solution for SPEC-F01: [Requirement Name]

**Approach**: [How to implement]

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| **S** - SRP | [How this solution respects SRP] | [Pattern or N/A] |
| **O** - OCP | [How this solution respects OCP] | [Pattern or N/A] |
| **L** - LSP | [How this solution respects LSP] | [Pattern or N/A] |
| **I** - ISP | [How this solution respects ISP] | [Pattern or N/A] |
| **D** - DIP | [How this solution respects DIP] | [Pattern or N/A] |

**Files to Create/Modify**:
- `path/to/File1.php` (Principle: SRP reason)
- `path/to/File2.php` (Principle: DIP reason)

**Expected SOLID Score**: [X/25]

---

## Solution for SPEC-F02: [Requirement Name]

**Approach**: [How to implement]

**SOLID Compliance**:

| Principle | How It's Addressed | Pattern Used |
|-----------|-------------------|--------------|
| ... | ... | ... |

**Files to Create/Modify**:
- ...

**Expected SOLID Score**: [X/25]

---

## Overall SOLID Score

| Solution | Score | Status |
|----------|-------|--------|
| SPEC-F01 | [X/25] | ✅/❌ |
| SPEC-F02 | [X/25] | ✅/❌ |
| **Total Expected** | **[X/25]** | **[Status]** |

**Threshold**: ≥18/25 to proceed, ≥22/25 to approve

---

## Patterns Used Summary

| Pattern | Used For | SOLID Addressed |
|---------|----------|-----------------|
| Strategy | [What need] | OCP, SRP |
| Repository | [What need] | DIP, SRP |
| Value Object | [What need] | SRP |
| ... | ... | ... |

---

## Approval

- [ ] All specs have solutions
- [ ] **SOLID compliance documented for each solution**
- [ ] **Expected SOLID score ≥22/25**
- [ ] Patterns justified with "why"
- [ ] Ready for task breakdown

**Approved By**: _______________
**Date**: _______________
```

---

## Summary: Phase 2 vs Phase 3

| Document | Phase | Contains | SOLID? |
|----------|-------|----------|--------|
| `12_specs.md` | Phase 2 | WHAT (functional requirements) | ❌ No |
| `15_solutions.md` | Phase 3 | HOW (technical design) | ✅ **YES** |

---

## Related

- `/workflows:plan` - Uses these templates
- `/workflow-skill:criteria-generator` - Generates 12_specs.md
- `/workflow-skill:solid-analyzer` - Validates SOLID in 15_solutions.md
- `core/solid-pattern-matrix.md` - Pattern recommendations
