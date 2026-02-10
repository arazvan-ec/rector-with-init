---
name: workflows:criteria
description: "Define and evaluate architecture criteria before making design decisions. Use this to ensure the best architecture is chosen based on explicit, weighted criteria."
argument_hint: <feature-id> [--interview | --evaluate | --quick | --review]
---

# Multi-Agent Workflow: Criteria

> **Tier 3 (Automatic)**: Criteria evaluation runs automatically inside `/workflows:plan` (Phase 3: SOLID constraint). Only invoke this command manually for standalone architecture decision records.

Generate, evaluate, and document architecture decision criteria. Good criteria lead to good architecture choices.

## Usage

```bash
# Full criteria workflow with developer consultation
/workflows:criteria my-feature

# Interactive interview mode (recommended)
/workflows:criteria my-feature --interview

# Quick criteria generation (minimal interaction)
/workflows:criteria my-feature --quick

# Evaluate architecture options against existing criteria
/workflows:criteria my-feature --evaluate

# Review existing criteria
/workflows:criteria my-feature --review
```

## Philosophy

> "No son las arquitecturas las que son buenas o malas, son los criterios los que hacen elegir la arquitectura correcta"

This command answers the critical question: **"Why this architecture and not others?"**

Without explicit criteria:
- Decisions are based on gut feelings or familiarity
- Trade-offs are implicit and undocumented
- Future developers don't understand WHY
- Same debates repeat on future features

With explicit criteria:
- Decisions are objective and traceable
- Trade-offs are explicit and agreed upon
- Future developers can understand and revisit
- Patterns emerge for similar features

## Workflow Modes

### Mode 1: Full Interview (default)

```bash
/workflows:criteria my-feature --interview
```

Conducts a structured interview with the developer:

```
╔════════════════════════════════════════════════════════════╗
║           ARCHITECTURE CRITERIA INTERVIEW                   ║
║           Feature: my-feature                               ║
╚════════════════════════════════════════════════════════════╝

I'll help you define the right criteria for choosing the best
architecture for this feature.

═══ CONTEXT UNDERSTANDING ═══

Q1: In one sentence, what is the core purpose of this feature?
> Allow users to export their data in multiple formats

Q2: What is the expected lifespan of this feature?
   (1) Short-term/tactical (< 6 months)
   (2) Medium-term (6-18 months)
   (3) Long-term/strategic (> 18 months)
> 2

Q3: How critical is this feature?
   (1) Core - revenue/security impact
   (2) Important - significant user value
   (3) Standard - normal feature
> 2

═══ CONSTRAINTS DISCOVERY ═══

Q4: What are the hard technical constraints?
   (Examples: must use existing DB, must integrate with X, max latency)
> Must integrate with existing S3 storage, max 30s for large exports

Q5: What are the business constraints?
   (Examples: deadline, budget, compliance requirements)
> Need to ship in 2 weeks, GDPR compliance required

═══ PRIORITY TRADE-OFFS ═══

Q6: Rank these priorities for THIS feature (1=highest, 4=lowest):
   Development speed: ___
   Long-term maintainability: ___
   Performance: ___
   Flexibility/extensibility: ___
> 1, 3, 2, 4

Q7: If you had to choose ONE, which matters most?
   (1) Simpler code that's easier to understand
   (2) Better performance under load
   (3) More flexibility for future changes
> 1

═══ RISK TOLERANCE ═══

Q8: What would be the WORST outcome?
   (1) Feature doesn't perform under load
   (2) Feature is hard to modify later
   (3) Feature takes too long to build
   (4) Feature is hard for team to understand
> 3

═══ TEAM CONTEXT ═══

Q9: Who will maintain this after launch?
> Backend team (3 devs, mid-level)

Q10: Rate team's comfort with these patterns (1=unfamiliar, 5=expert):
   - Event-driven architecture: ___
   - CQRS pattern: ___
   - Simple service layer: ___
   - Queue-based processing: ___
> 2, 1, 5, 4

═══ GENERATING CRITERIA ═══

Based on your answers, here are the weighted criteria for my-feature:

## Architecture Criteria: my-feature

| ID | Criterion | Category | Weight | Rationale |
|----|-----------|----------|--------|-----------|
| C1 | Testability | Technical | Critical | TDD project requirement |
| C2 | Team expertise | Team | Critical | 2-week deadline, mid-level team |
| C3 | Development speed | Process | High | Tight deadline |
| C4 | Performance <30s | Technical | High | UX requirement for exports |
| C5 | GDPR compliance | Strategic | High | Business requirement |
| C6 | Maintainability | Technical | Medium | Will be maintained long-term |
| C7 | S3 integration | Technical | Medium | Existing infrastructure |

### Critical Criteria (Must score 4+ or option is rejected)
- C1: Testability
- C2: Team expertise

═══ VALIDATION ═══

Do these criteria look correct? (y/n/edit)
> y

Criteria saved to: .ai/project/features/my-feature/12_architecture_criteria.md

Next steps:
1. Continue with planning: /workflows:plan my-feature
2. Or evaluate options: /workflows:criteria my-feature --evaluate
```

### Mode 2: Evaluate Options

```bash
/workflows:criteria my-feature --evaluate
```

Evaluates architecture options against existing criteria:

```
╔════════════════════════════════════════════════════════════╗
║           ARCHITECTURE OPTIONS EVALUATION                   ║
║           Feature: my-feature                               ║
╚════════════════════════════════════════════════════════════╝

Loading criteria from: 12_architecture_criteria.md

═══ DEFINE OPTIONS ═══

How many architecture options do you want to evaluate? (2-4)
> 3

== Option A ==
Name: Simple Service Layer
Brief description: Single ExportService with sync processing
Pattern type: Traditional Service

== Option B ==
Name: Queue-Based Processing
Brief description: Background jobs with Redis queue
Pattern type: Job Queue

== Option C ==
Name: Event-Driven
Brief description: Event sourcing with async handlers
Pattern type: Event-Driven

═══ SCORING ═══

For each criterion, score each option (1-5):
- 5: Excellent fit
- 4: Good fit
- 3: Acceptable
- 2: Poor fit
- 1: Unacceptable

C1: Testability (Critical)
  Option A - Simple Service: > 4
  Option B - Queue-Based: > 4
  Option C - Event-Driven: > 3

C2: Team expertise (Critical)
  Option A - Simple Service: > 5
  Option B - Queue-Based: > 4
  Option C - Event-Driven: > 2

[... continues for all criteria ...]

═══ RESULTS ═══

## Evaluation Matrix

| Criterion (Weight) | A: Simple | B: Queue | C: Event |
|-------------------|-----------|----------|----------|
| Testability (5) | 4 (20) | 4 (20) | 3 (15) |
| Team expertise (5) | 5 (25) | 4 (20) | 2 (10) |
| Dev speed (4) | 5 (20) | 3 (12) | 2 (8) |
| Performance (4) | 2 (8) | 4 (16) | 4 (16) |
| GDPR (4) | 4 (16) | 4 (16) | 4 (16) |
| Maintainability (3) | 4 (12) | 4 (12) | 3 (9) |
| S3 integration (3) | 4 (12) | 4 (12) | 4 (12) |
| **TOTAL** | **113** | **108** | **86** |

## RECOMMENDATION: Option A - Simple Service Layer

**Score**: 113/140 (81%)

### Why Option A?
- Highest total score (113 vs 108 vs 86)
- Meets both Critical criteria (Testability: 4, Team expertise: 5)
- Best for development speed given 2-week deadline

### Why NOT Option B?
- Lower development speed score (3 vs 5)
- Timeline too tight for queue setup and testing
- Would add 3-4 days to implementation

### Why NOT Option C?
- Team expertise score DISQUALIFYING (2 on Critical criterion)
- Team rated themselves 1/5 on CQRS, 2/5 on event-driven
- Would require significant learning curve

### Trade-offs Accepted
- Option A has lower performance score (2 vs B's 4)
- Acceptable because: Large exports are async already, 30s limit is achievable

### Recommendation for Future
- If performance becomes critical, migrate to Option B
- Team should gain queue-based experience on lower-risk features first

═══ DECISION ═══

Accept this recommendation? (y/n/discuss)
> y

Saved to: .ai/project/features/my-feature/12a_criteria_evaluation.md
ADR saved to: .ai/project/features/my-feature/ADR-001-architecture-choice.md
```

### Mode 3: Quick Generation

```bash
/workflows:criteria my-feature --quick
```

Generates criteria with minimal interaction using smart defaults:

```
Analyzing feature context...
- Feature type: Data export
- Detected constraints: S3 integration, performance
- Project defaults: TDD, DDD compliance

Generated criteria (edit as needed):

| Criterion | Weight | Auto-detected Reason |
|-----------|--------|---------------------|
| Testability | Critical | Project TDD requirement |
| DDD compliance | High | Project DDD rules |
| Performance | High | Export feature pattern |
| Team expertise | High | Default for all features |

Saved to: .ai/project/features/my-feature/12_architecture_criteria.md

Review and customize with: /workflows:criteria my-feature --review
```

### Mode 4: Review Existing

```bash
/workflows:criteria my-feature --review
```

Displays and allows editing of existing criteria.

## Output Structure

```
.ai/project/features/{feature-id}/
├── 12_architecture_criteria.md      # Main criteria document
├── 12a_criteria_evaluation.md       # Options evaluation (if --evaluate)
├── 12b_criteria_consultation.md     # Interview log
└── ADR-001-architecture-choice.md   # Architecture Decision Record
```

## Criteria Document Template

```markdown
# Architecture Criteria: {feature-id}

**Status**: DRAFT | VALIDATED | APPROVED
**Generated**: {date}
**Consultation Date**: {date}

---

## Context Summary

**Feature Purpose**: [One sentence]
**Lifespan**: [Short/Medium/Long-term]
**Criticality**: [Core/Important/Standard]

### Constraints
- Technical: [list]
- Business: [list]
- Team: [list]

---

## Criteria Matrix

| ID | Criterion | Category | Weight | Rationale |
|----|-----------|----------|--------|-----------|
| C1 | ... | ... | Critical | ... |
| C2 | ... | ... | High | ... |
| ... | ... | ... | ... | ... |

### Weight Definitions
- **Critical**: Must score 4+ or option is disqualified
- **High**: Strong preference, heavily weighted
- **Medium**: Considered but not decisive
- **Low**: Tiebreaker only

---

## Consultation Summary

### Key Decisions
1. [Priority decision]: [rationale]
2. [Trade-off decision]: [rationale]

### Risk Tolerance
- Worst outcome to avoid: [description]
- Acceptable trade-offs: [list]

---

## Approval

- [ ] Tech lead reviewed
- [ ] Stakeholder validated
- [ ] Ready for architecture evaluation

---

## When to Revisit

Reconsider these criteria if:
- [ ] [Condition 1]
- [ ] [Condition 2]
```

## Integration with Planning

### Recommended Flow

```bash
# 1. Create feature spec
/workflows:interview feature

# 2. Define architecture criteria (THIS COMMAND)
/workflows:criteria my-feature --interview

# 3. Plan with criteria in mind
/workflows:plan my-feature

# 4. Evaluate architecture options
/workflows:criteria my-feature --evaluate

# 5. Document decision
# (Automatic when evaluation is accepted)
```

### Automatic Triggers

The workflow MAY automatically suggest running criteria when:
- Planning a feature with multiple viable architectures
- Feature touches auth, payment, or security
- Feature is marked as long-term or strategic
- Developer requests architecture comparison

## Best Practices

1. **Always consult**: Don't generate criteria in isolation
2. **Be specific**: "Good performance" is not a criterion; "<100ms p99" is
3. **Limit criteria**: 7-10 max to avoid analysis paralysis
4. **Document trade-offs**: Future devs need to know what was sacrificed
5. **Revisit criteria**: When context changes, criteria may need updating

## Common Mistakes

| Mistake | Problem | Fix |
|---------|---------|-----|
| Skip consultation | Criteria are assumptions | Always interview stakeholder |
| Too many criteria | Can't decide | Limit to 7-10, prioritize |
| No critical criteria | All options seem equal | Identify must-haves |
| Ignore team context | Theoretically good, practically bad | Weight team expertise |
| One-size-fits-all | Different features need different criteria | Customize per feature |

## SOLID-Rigorous Mode

For features requiring strict SOLID compliance (refactoring, architecture decisions):

```bash
# Add SOLID principles as critical criteria
/workflows:criteria my-feature --solid-rigorous

# Or combine with interview
/workflows:criteria my-feature --interview --solid-rigorous
```

This mode:
1. **Adds 5 SOLID sub-criteria** (S, O, L, I, D) as Critical/High weight
2. **Analyzes existing code** with `/workflow-skill:solid-analyzer` first
3. **Maps violations to patterns** via `core/solid-pattern-matrix.md`
4. **Rejects options** scoring <18/25 on SOLID automatically
5. **Recommends corrective patterns** (Strategy, Decorator, Ports & Adapters, etc.)

### When to Use SOLID-Rigorous

| Scenario | Use `--solid-rigorous`? |
|----------|------------------------|
| Refactoring existing code | **YES** - mandatory |
| Architecture redesign | **YES** - mandatory |
| New feature (greenfield) | Optional (recommended for critical features) |
| Bug fix | No (unless touches architecture) |

### SOLID Evaluation Matrix (added when `--solid-rigorous`)

```
| Criterion | Weight | Option A | Option B |
|-----------|--------|----------|----------|
| **C-SOLID-S** | Critical | 5 | 2 ❌ |
| **C-SOLID-O** | Critical | 5 | 3 |
| **C-SOLID-L** | High | 4 | 4 |
| **C-SOLID-I** | High | 5 | 5 |
| **C-SOLID-D** | Critical | 5 | 2 ❌ |
| SOLID Total | | 24/25 ✓ | 16/25 ❌ REJECTED |
```

See `core/solid-pattern-matrix.md` for violation → pattern mappings.

## Related Commands

- `/workflows:interview` - Create feature spec
- `/workflows:plan` - Main planning workflow
- `/workflows:solid-refactor` - Complete SOLID refactoring workflow
- `/workflow-skill:criteria-generator` - Direct skill invocation (supports `--solid-rigorous`)
- `/workflow-skill:solid-analyzer` - Automated SOLID violation detection
- `/workflows:validate` - Validate planning documents
