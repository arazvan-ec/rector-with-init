# Karpathy-Inspired Coding Principles

**Source**: Adapted from [andrej-karpathy-skills](https://github.com/forrestchang/andrej-karpathy-skills)
**Version**: 1.0.0
**Integrated**: 2026-02-01

---

## Purpose

These principles address common failure modes in AI-assisted development, derived from Andrej Karpathy's observations about LLM limitations. They complement the Multi-Agent Workflow framework by adding explicit guardrails against:

- Silent assumptions and hidden confusion
- Overengineering and unnecessary complexity
- Collateral damage during code changes
- Vague instructions leading to vague results

---

## The Four Principles

### Principle 1: Think Before Coding

**Problem Addressed**: LLMs often make silent assumptions and proceed blindly when confused.

**Rule**: Before writing ANY code, explicitly state assumptions and clarify ambiguities.

#### Required Actions

1. **State Assumptions Explicitly**
   - Before implementation, list ALL assumptions being made
   - Include assumptions about inputs, outputs, edge cases, and dependencies
   - Document assumptions even when confidence is high

2. **Present Multiple Interpretations**
   - When request is ambiguous, present 2-3 possible interpretations
   - Ask user to confirm which interpretation is correct
   - Don't guess - ASK

3. **Push Back on Suboptimal Approaches**
   - If user suggests an approach that seems problematic, voice concern
   - Explain potential issues and suggest alternatives
   - Don't blindly follow instructions if they lead to poor outcomes

4. **Request Clarification When Confused**
   - If something doesn't make sense, STOP and ask
   - Never proceed with partial understanding
   - "I need clarification on X before I can proceed safely"

#### Assumptions Template

```markdown
## Pre-Implementation Assumptions

**Request**: [What user asked for]

**My Assumptions**:
1. [Assumption about scope]
2. [Assumption about inputs]
3. [Assumption about expected behavior]
4. [Assumption about edge cases]
5. [Assumption about integration points]

**Potential Ambiguities**:
- [ ] [Ambiguity 1] - My interpretation: [X]
- [ ] [Ambiguity 2] - My interpretation: [X]

**Questions Before Proceeding**:
1. [Question about unclear requirement]

**Confidence Level**: [HIGH/MEDIUM/LOW]
- If LOW: Must ask questions before proceeding
- If MEDIUM: State assumptions, proceed with verification checkpoints
- If HIGH: State assumptions, proceed
```

---

### Principle 2: Simplicity First

**Problem Addressed**: LLMs tend to overengineer solutions with unnecessary abstractions.

**Rule**: Implement ONLY what is explicitly requested. Less is more.

#### Required Actions

1. **Implement Only Requested Features**
   - Don't add "nice to have" features
   - Don't add "future-proofing" that wasn't asked for
   - If user asks for A, implement A - not A+B+C

2. **Avoid Unnecessary Abstractions**
   - Don't create interfaces for single implementations
   - Don't create factory patterns for simple objects
   - Don't add configuration for things that don't need it

3. **Refuse Speculative Flexibility**
   - Don't add parameters "just in case"
   - Don't create extensibility points without concrete need
   - YAGNI (You Aren't Gonna Need It) is law

4. **Apply the 50-Line Rule**
   - If solution can be done in 50 lines, don't write 200
   - Question any file over 100 lines - can it be simpler?
   - Complexity should match problem complexity

#### Simplicity Checklist

```markdown
## Simplicity Verification

Before marking code as complete:

- [ ] Did I implement ONLY what was requested?
- [ ] Are there features I added that weren't asked for?
- [ ] Could this be done with fewer abstractions?
- [ ] Is every class/function/file necessary?
- [ ] Would a junior developer understand this immediately?
- [ ] Can I delete anything and still meet requirements?

**Red Flags** (if any are YES, simplify):
- [ ] Created interface for single implementation
- [ ] Added configuration for non-configurable things
- [ ] Created utility class for one-time operation
- [ ] Added "extensibility" without concrete use case
- [ ] Code is 3x longer than minimum necessary
```

---

### Principle 3: Surgical Changes

**Problem Addressed**: LLMs often make unnecessary changes to surrounding code.

**Rule**: Touch ONLY the code essential to the task. Preserve everything else.

#### Required Actions

1. **Minimal Diff Principle**
   - Change only what's necessary
   - Don't "improve" code you weren't asked to improve
   - Don't refactor adjacent code unless requested

2. **Match Existing Style**
   - Follow existing code conventions exactly
   - Use same naming patterns as surrounding code
   - Match indentation, quotes, semicolons, etc.

3. **Handle Unrelated Issues Carefully**
   - If you notice unrelated dead code: MENTION IT, don't delete it
   - If you notice unrelated bugs: REPORT them, don't fix them
   - Only remove code that YOUR changes made orphan

4. **Preserve Working Code**
   - If it works, don't touch it
   - Don't "clean up" working tests
   - Don't reorganize imports unless required

#### Surgical Changes Checklist

```markdown
## Change Impact Verification

Before committing changes:

- [ ] Every changed line is necessary for this task
- [ ] No "drive-by" improvements or cleanups
- [ ] Existing style conventions are preserved
- [ ] No reformatting of unchanged code
- [ ] Only MY orphaned code was removed

**Diff Review Questions**:
1. For each changed file: Was this file required?
2. For each changed line: Was this change necessary?
3. For each deletion: Was this code orphaned by MY changes?

**Unrelated Issues Found** (report, don't fix):
- [ ] Issue 1: [description] - Location: [file:line]
- [ ] Issue 2: [description] - Location: [file:line]
```

---

### Principle 4: Goal-Driven Execution

**Problem Addressed**: Vague instructions lead to vague results without clear success criteria.

**Rule**: Transform every request into testable success criteria before implementation.

#### Required Actions

1. **Define Success Criteria First**
   - Before coding, write down what "done" looks like
   - Make criteria specific and testable
   - Get user confirmation on criteria

2. **Transform Vague to Specific**
   - "Fix the bug" → "Write test that reproduces bug, then make it pass"
   - "Make it faster" → "Reduce response time from X to Y ms"
   - "Add login" → "User can authenticate with email/password and receive JWT"

3. **Create Verification Commands**
   - Every implementation should have a command to verify it works
   - Provide "how to test this" with every change
   - Prefer automated tests over manual verification

4. **Iterate Toward Measurable Goals**
   - Don't just write code - verify it achieves the goal
   - Run tests after each significant change
   - Continue until success criteria are met

#### Success Criteria Template

```markdown
## Success Criteria Definition

**Request**: [Original user request]

**Transformed into Testable Goals**:
1. [ ] [Specific, testable criterion 1]
2. [ ] [Specific, testable criterion 2]
3. [ ] [Specific, testable criterion 3]

**Verification Method**:
- Test command: `[command to verify]`
- Expected output: [what success looks like]

**Acceptance Test**:
```
[pseudocode or actual test that proves success]
```

**Definition of Done**:
- [ ] All success criteria pass
- [ ] Verification command succeeds
- [ ] No regressions in existing tests
```

---

## Integration with Multi-Agent Workflow

### Where These Principles Apply

| Principle | Workflow Phase | Integration Point |
|-----------|---------------|-------------------|
| Think Before Coding | ROUTE + PLAN | Pre-routing checklist, planning templates |
| Simplicity First | WORK | Self-review checklist, code review criteria |
| Surgical Changes | WORK | Pre-commit checklist, diff review |
| Goal-Driven Execution | PLAN + WORK | Success criteria in feature specs, TDD |

### Enhanced Self-Check Protocol

Before ANY code change, verify:

```markdown
## Karpathy-Enhanced Self-Check

### Think Before Coding
- [ ] Assumptions are explicitly stated
- [ ] Ambiguities have been clarified (or questions asked)
- [ ] I would push back if approach seems wrong

### Simplicity First
- [ ] Implementing ONLY what was requested
- [ ] No unnecessary abstractions planned
- [ ] Solution is minimum viable complexity

### Surgical Changes
- [ ] Will touch only essential code
- [ ] Know existing style conventions
- [ ] Will not "improve" unrelated code

### Goal-Driven Execution
- [ ] Success criteria are defined
- [ ] Know how to verify completion
- [ ] Have testable acceptance criteria
```

---

## Expected Outcomes

When properly applied, you should observe:

1. **Fewer unnecessary diff changes** - PRs are focused and minimal
2. **Simpler initial code** - No over-engineering or speculation
3. **Clarifying questions before implementation** - No blind assumptions
4. **Cleaner pull requests** - Easy to review, clear purpose
5. **Higher confidence in changes** - Testable success criteria met

---

## Relationship to Framework Rules

These principles **complement** (not replace) the framework rules:

| Framework Rule | Karpathy Principle | Relationship |
|----------------|-------------------|--------------|
| Mandatory Routing | Think Before Coding | Both require clarification before action |
| Comprehension Debt | Simplicity First | Simpler code = less debt |
| TDD | Goal-Driven Execution | Tests ARE the success criteria |
| Self-Review | Surgical Changes | Review ensures minimal changes |

---

## Quick Reference Card

```
BEFORE CODING:
  - List assumptions
  - Define success criteria
  - Ask if confused

WHILE CODING:
  - Only what was asked
  - Minimal changes
  - Match existing style

BEFORE COMMIT:
  - Verify success criteria
  - Check diff minimality
  - No drive-by improvements
```

---

**Remember**: Give the AI success criteria and watch it go. Don't micromanage implementation steps - define what "done" looks like and verify it's achieved.
