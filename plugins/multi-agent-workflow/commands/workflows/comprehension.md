---
name: workflows:comprehension
description: "Evaluate comprehension debt and verify understanding of AI-generated code. Based on Addy Osmani's 80% Problem."
argument_hint: <feature-name> [--mode=<check|report|self-review>]
---

# Multi-Agent Workflow: Comprehension

> **Tier 3 (Automatic)**: Comprehension checks run automatically inside `/workflows:review` (quality gates). Only invoke this command manually for standalone comprehension audits.

Evaluates comprehension debt to ensure sustainable development velocity.

> *"It's trivially easy to review code that you can no longer write from scratch."* - Addy Osmani

## Usage

```bash
# Full comprehension check
/workflows:comprehension user-authentication

# Specific modes
/workflows:comprehension user-authentication --mode=check       # Quick assessment
/workflows:comprehension user-authentication --mode=report      # Full report
/workflows:comprehension user-authentication --mode=self-review # Self-review only
```

## Why This Matters

AI generates ~80% of code, creating **comprehension debt** - the gap between:
- Code you can **review and approve** (fast)
- Code you could **write from scratch** (slower)

If this gap grows unchecked, you lose control of your system.

## Process

### Phase 1: Self-Review Assessment

**Ask the implementing agent to critique its own code with "fresh context":**

```markdown
## Self-Review Checklist

Forget you wrote this. Review it as a skeptical senior developer.

### Code Critique
- [ ] Would I write this the same way manually?
- [ ] Are there abstractions I don't fully understand?
- [ ] Did I copy patterns without understanding why?
- [ ] Are there "magic" values or logic I can't justify?
- [ ] What would a skeptical reviewer ask about this?

### Assumption Validation
- [ ] What assumptions did I make? List them.
- [ ] Did I validate these assumptions or just proceed?
- [ ] What could go wrong that I haven't considered?

### Simplification Check
- [ ] Is this the simplest solution?
- [ ] Did I over-engineer? (YAGNI violations)
- [ ] Could this be 50% shorter while doing the same thing?

### Output Required
List at least:
- 3 potential improvements
- 2 questions a reviewer would ask
- 1 thing that could be simpler
```

### Phase 2: Knowledge Test

**Verify understanding WITHOUT looking at the code:**

```markdown
## Knowledge Test: ${FEATURE_NAME}

Answer these questions from memory (no peeking at code):

1. **Core Logic**: What does this code do? (one sentence)
   Answer: ___

2. **Data Flow**: How does data move through the system?
   Answer: ___

3. **Edge Case**: What happens when [specific edge case]?
   Answer: ___

4. **Modification**: How would you add [hypothetical feature]?
   Answer: ___

5. **Failure Modes**: What could make this code fail?
   Answer: ___

## Scoring
- 5/5: Could rewrite from scratch
- 4/5: Could modify confidently
- 3/5: Could maintain with docs
- 2/5: Need help to modify
- 1/5: Only know it "works"

**Score**: ___/5

⚠️ **Score < 3 = BLOCKED** - Cannot proceed until comprehension improves
```

### Phase 3: Decision Documentation Audit

**Verify DECISIONS.md captures the "why":**

```markdown
## Decision Documentation Check

For each significant decision in DECISIONS.md:

| Decision | Has "Why" | Trade-offs Documented | Revisit Conditions |
|----------|-----------|----------------------|-------------------|
| [Decision 1] | ✅/❌ | ✅/❌ | ✅/❌ |
| [Decision 2] | ✅/❌ | ✅/❌ | ✅/❌ |

### Missing Decisions (add these):
- [ ] [Undocumented decision 1]
- [ ] [Undocumented decision 2]
```

### Phase 4: Debt Indicators Check

**Scan for comprehension debt symptoms:**

```markdown
## Debt Indicators

| Indicator | Found | Location | Action |
|-----------|-------|----------|--------|
| "Magic" code (works but not understood) | 0 | - | - |
| Patterns copied without understanding | 0 | - | - |
| Over-engineering (YAGNI violations) | 0 | - | - |
| Unexplained abstractions | 0 | - | - |
| Dead code accumulation | 0 | - | - |
| Assumption propagation | 0 | - | - |

### Severity Assessment
- 🟢 **LOW** (0-1 indicators): Healthy, proceed
- 🟡 **MEDIUM** (2-3 indicators): Monitor, address within sprint
- 🔴 **HIGH** (4+ indicators): STOP, reduce debt before continuing
```

## Comprehension Report Template

```markdown
# Comprehension Health Report

**Feature**: ${FEATURE_NAME}
**Date**: ${DATE}
**Evaluator**: Comprehension Guardian

---

## Overall Health: 🟢 HEALTHY | 🟡 AT RISK | 🔴 DEBT ACCUMULATING

---

## 1. Self-Review Results

| Aspect | Status | Notes |
|--------|--------|-------|
| Code critique completed | ✅/❌ | |
| Assumptions documented | ✅/❌ | |
| Simplification reviewed | ✅/❌ | |

**Improvements identified**: [count]
**Critical issues found**: [count]

---

## 2. Knowledge Test

| Question | Answered Correctly | Confidence |
|----------|-------------------|------------|
| Core Logic | ✅/❌ | High/Med/Low |
| Data Flow | ✅/❌ | High/Med/Low |
| Edge Cases | ✅/❌ | High/Med/Low |
| Modification | ✅/❌ | High/Med/Low |
| Failure Modes | ✅/❌ | High/Med/Low |

**Knowledge Score**: X/5

---

## 3. Decision Documentation

| Metric | Value |
|--------|-------|
| Decisions documented | X |
| With "why" explanation | X% |
| With trade-offs | X% |
| With revisit conditions | X% |

**Missing documentation**:
- [ ] [Item 1]

---

## 4. Debt Indicators

| Indicator | Count | Severity |
|-----------|-------|----------|
| Magic code | 0 | 🟢 |
| Copied patterns | 0 | 🟢 |
| Over-engineering | 0 | 🟢 |
| Unexplained abstractions | 0 | 🟢 |

**Debt Level**: 🟢 LOW

---

## 5. Recommendations

### Immediate Actions
1. [Action if needed]

### Knowledge Gaps to Address
1. [Gap]: [How to address]

### Process Improvements
1. [Suggestion]

---

## Verdict

- [ ] **APPROVED** - Comprehension healthy, proceed
- [ ] **CONDITIONAL** - Proceed but address [items] within [timeframe]
- [ ] **BLOCKED** - Cannot proceed until [requirement] met

---

**Next comprehension check**: [checkpoint/date]
```

## State Update

After comprehension check, update `50_state.md`:

```markdown
## Comprehension Tracking

**Debt Level**: 🟢 LOW
**Last Checkpoint**: 2026-01-28
**Knowledge Score**: 4/5
**Next Check Due**: After Backend checkpoint 3

### Debt Indicators
| Indicator | Count | Notes |
|-----------|-------|-------|
| "Magic" code incidents | 0 | |
| Patterns copied without understanding | 0 | |
| Over-engineering flags | 0 | |
| Unexplained abstractions | 0 | |

### Self-Review Status
| Role | Self-Review Done | Score | Issues Found |
|------|------------------|-------|--------------|
| Backend | ✅ Complete | 4/5 | 2 minor |
| Frontend | ⬜ Pending | - | - |

### Knowledge Gaps Identified
None - all areas understood.

### Recommended Actions
- Continue with current approach
```

## When to Run

| Trigger | Mode | Purpose |
|---------|------|---------|
| Every 3 TDD iterations | `--mode=check` | Quick debt assessment |
| Before marking COMPLETED | `--mode=self-review` | Verify self-critique done |
| Before marking APPROVED | `--mode=report` | Full comprehension report |
| After major refactoring | `--mode=report` | Verify understanding maintained |
| New team member onboarding | `--mode=report` | Assess knowledge transfer |

## Anti-Patterns to Detect

### 1. The Sycophantic Agent
Agent doesn't push back on incomplete instructions.

```
❌ "Add authentication" → "Done!"
✅ "Add authentication" → "Before proceeding: JWT or session? Password requirements? Token expiration?"
```

### 2. Abstraction Bloat
Over-complicated solutions for simple problems.

```
❌ 200 lines with factories, builders, strategies for simple CRUD
✅ 50 lines of straightforward code
```

### 3. Assumption Propagation
Building on unvalidated assumptions.

```
❌ "I assumed email is unique..." (500 lines later, assumption was wrong)
✅ "Before proceeding, I validated: email uniqueness required per spec"
```

### 4. Copy-Paste Architecture
Using patterns without understanding why.

**Detection**: "Why is this pattern used here?" → "I don't know, it was in another file"

## Integration with Other Commands

```bash
# Typical workflow
/workflows:work user-auth          # Implementation
/workflows:comprehension user-auth --mode=check  # Quick check every 3 iterations
/workflows:comprehension user-auth --mode=self-review  # Before COMPLETED
/workflows:review user-auth        # QA review
/workflows:comprehension user-auth --mode=report  # Before APPROVED
/workflows:compound user-auth      # Knowledge capture
```

## Compound Effect

Good comprehension practices compound:
- Understanding today prevents confusion tomorrow
- Documented decisions save future debugging
- Self-review habits catch issues early
- Knowledge tests reveal gaps before production

> **Reference**: `.ai/workflow/docs/COMPREHENSION_DEBT.md` for full methodology
