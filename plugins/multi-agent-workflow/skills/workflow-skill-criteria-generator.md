---
name: workflow-skill-criteria-generator
description: "Generates and evaluates architecture criteria for features. MANDATORY for ALL tasks - use before ANY architectural decisions. <example>Context: Planning a new feature that could have multiple implementations.\\nuser: \"Let's design the payment system\"\\nassistant: \"Let me use workflow-skill-criteria-generator to define evaluation criteria first\"</example>"
model: inherit
---

# Criteria Generator Skill

Generates, evaluates, and documents architecture decision criteria. Good criteria lead to good architecture choices.

## Philosophy

> "No son las arquitecturas las que son buenas o malas, son los criterios los que hacen elegir la arquitectura correcta"

The quality of architectural decisions depends entirely on the quality of the criteria used to evaluate options. This skill helps:
- Generate relevant criteria for each feature context
- Evaluate multiple architectural options objectively
- Document WHY an architecture was chosen (not just WHAT)
- Enable future re-evaluation if context changes

## Base Criteria (Always Applied)

**IMPORTANT**: Before generating feature-specific criteria, ALWAYS load the base architecture quality criteria:

```
Read: plugins/multi-agent-workflow/core/architecture-quality-criteria.md
```

These **6 base criteria** are NON-NEGOTIABLE and apply to ALL features:

| ID | Base Criterion | What It Ensures |
|----|----------------|-----------------|
| C-BASE-01 | Escalabilidad Estructural | Añadir features sin reescribir (≤5 archivos) |
| C-BASE-02 | SOLID Compliance | Los 5 principios respetados |
| C-BASE-03 | Clean Code Metrics | Código legible y mantenible |
| C-BASE-04 | Responsabilidades Definidas | Separación de capas DDD |
| C-BASE-05 | Patrones Adecuados | Patrones que simplifican, no complican |
| C-BASE-06 | Invasividad de Cambios | Cambio fácil = pocos archivos |

### Key Principle: Change Impact

> "Una arquitectura es buena cuando un cambio fácil es poco invasivo"

| Tipo de Cambio | Archivos Máximos |
|----------------|------------------|
| Nuevo campo en entidad | ≤3 |
| Nueva validación | ≤2 |
| Nuevo endpoint CRUD | ≤4 |
| Cambio en UI de un campo | ≤1 |
| Cambio de proveedor externo | ≤1 |

If an architecture option violates these limits, it scores LOW on invasivity.

## When to Use

- Before creating architecture design (10_architecture.md)
- When multiple valid architectural approaches exist
- When stakeholders disagree on approach
- When inheriting a system and need to understand past decisions
- Proactively on any non-trivial feature

## Invocation

```bash
# Generate criteria for a feature (includes base criteria automatically)
/workflow-skill:criteria-generator --feature=<feature-id>

# Evaluate options against existing criteria
/workflow-skill:criteria-generator --evaluate --feature=<feature-id>

# Quick criteria generation with auto-suggest
/workflow-skill:criteria-generator --quick --feature=<feature-id>

# Generate criteria interactively (consult with dev)
/workflow-skill:criteria-generator --interview --feature=<feature-id>
```

## Criteria Structure

### Tier 1: Base Criteria (Always Applied)

From `architecture-quality-criteria.md`:
- Escalabilidad Estructural
- SOLID Compliance
- Clean Code Metrics
- Responsabilidades Definidas (Layer Separation)
- Patrones de Diseño Adecuados
- Invasividad de Cambios

### Tier 2: Feature-Specific Criteria

Added based on feature context and developer consultation.

## Feature-Specific Criteria Categories

### 1. Functional Fit Criteria

Questions about capability alignment:

| Criterion | Question | Weight |
|-----------|----------|--------|
| Feature completeness | Does this architecture support ALL required functionality? | Critical |
| Edge cases | How well does it handle edge cases and exceptions? | High |
| Extensibility | Can we add future requirements without major refactoring? | Medium |
| Data flow | Does data flow naturally through the system? | High |

### 2. Technical Quality Criteria

Questions about technical excellence:

| Criterion | Question | Weight |
|-----------|----------|--------|
| Testability | How easy is it to write and maintain tests? | Critical |
| Debuggability | When things fail, how quickly can we diagnose? | High |
| Performance | Does it meet latency/throughput requirements? | Context-dependent |
| Scalability | Can it handle 10x current load without redesign? | Context-dependent |
| Security | Does it minimize attack surface? | Critical for auth/payment |

### 3. Team & Process Criteria

Questions about human factors:

| Criterion | Question | Weight |
|-----------|----------|--------|
| Team expertise | Does the team know these patterns/tools? | High |
| Learning curve | How long to onboard new developers? | Medium |
| Cognitive load | Can a mid-level dev understand and modify it? | High |
| Parallelization | Can multiple devs work without conflicts? | Medium |

### 4. Operational Criteria

Questions about running in production:

| Criterion | Question | Weight |
|-----------|----------|--------|
| Deployment | How complex is the deployment process? | Medium |
| Monitoring | Can we observe system health easily? | High |
| Recovery | What happens when things fail? Recovery time? | Critical |
| Cost | Infrastructure and maintenance cost over time? | Context-dependent |

### 5. Strategic Criteria

Questions about long-term alignment:

| Criterion | Question | Weight |
|-----------|----------|--------|
| Tech stack fit | Does it align with our existing stack? | High |
| Vendor lock-in | How dependent on specific vendors? | Medium |
| Future migration | How hard to migrate if we change strategy? | Low |
| Industry alignment | Is this a well-supported pattern? | Medium |

## Generation Process

### Step 1: Context Analysis

```markdown
## Feature Context Analysis

### Feature Description
[Brief description of what we're building]

### Constraints
- Technical: [existing stack, performance requirements, etc.]
- Business: [timeline, budget, compliance, etc.]
- Team: [expertise, availability, preferences]

### Stakeholders
- Primary: [who uses this directly]
- Secondary: [who is affected indirectly]
- Technical: [who maintains this]
```

### Step 2: Criteria Selection

Based on context, select and weight criteria:

```markdown
## Selected Criteria for: {feature-id}

### Critical (Must have - deal breaker if not met)
1. **[Criterion]**: [Why critical for THIS feature]
2. **[Criterion]**: [Why critical for THIS feature]

### High Priority (Strong preference)
1. **[Criterion]**: [Why important]
2. **[Criterion]**: [Why important]

### Medium Priority (Nice to have)
1. **[Criterion]**: [Why relevant]

### Low Priority (Consider if tied)
1. **[Criterion]**: [Why included]
```

### Step 3: Question Generation

Generate specific questions for the dev to validate criteria:

```markdown
## Developer Consultation Questions

Before finalizing criteria, please confirm:

### About Constraints
1. What are the non-negotiable technical constraints?
2. What performance requirements exist (latency, throughput)?
3. Are there compliance or security requirements?

### About Priorities
4. Between development speed and long-term maintainability, which is more important NOW?
5. Is this feature expected to evolve significantly, or is scope fixed?
6. How critical is this feature? (revenue, user experience, operations)

### About Team
7. What technologies is the team most comfortable with?
8. Who will maintain this after initial development?
9. Are there patterns from similar features we should follow?

### About Trade-offs
10. If we had to choose: simpler code OR better performance?
11. If we had to choose: faster delivery OR more flexibility?
12. What would be the worst outcome to avoid?
```

### Step 4: Criteria Documentation

Generate the criteria document:

```markdown
## Architecture Criteria: {feature-id}

**Generated**: {date}
**Status**: DRAFT | VALIDATED | APPROVED

### Context Summary
[Brief context]

### Criteria Matrix

| ID | Criterion | Category | Weight | Rationale |
|----|-----------|----------|--------|-----------|
| C1 | Testability | Technical | Critical | TDD methodology requires easy testing |
| C2 | Team expertise | Team | High | Limited ramp-up time available |
| ... | ... | ... | ... | ... |

### Validation Questions Asked
[List of questions and dev's answers]

### Criteria Locked
- [ ] Reviewed with tech lead
- [ ] Reviewed with stakeholder
- [ ] Ready for architecture evaluation
```

## Evaluation Process

### Step 1: List Architecture Options

```markdown
## Architecture Options

### Option A: [Name]
**Description**: [Brief description]
**Pattern**: [e.g., Microservices, Monolith, Event-driven]
**Pros**: [Quick list]
**Cons**: [Quick list]

### Option B: [Name]
...

### Option C: [Name]
...
```

### Step 2: Score Against Criteria

```markdown
## Evaluation Matrix

| Criterion | Weight | Option A | Option B | Option C |
|-----------|--------|----------|----------|----------|
| Testability | 5 | 4 (20) | 5 (25) | 3 (15) |
| Performance | 3 | 5 (15) | 3 (9) | 4 (12) |
| Team expertise | 4 | 5 (20) | 2 (8) | 4 (16) |
| ... | | | | |
| **TOTAL** | | **55** | **42** | **43** |

### Scoring Guide
- 5: Excellent fit
- 4: Good fit
- 3: Acceptable
- 2: Poor fit
- 1: Unacceptable
```

### Step 3: Analysis and Recommendation

```markdown
## Analysis

### Winner: Option A
**Total Score**: 55/70 (79%)

### Why Option A and Not Others?

**Why not Option B?**
- Lower score on team expertise (2 vs 5)
- Would require 3+ weeks of learning curve
- Dev confirmed: "We need to deliver in 2 weeks"

**Why not Option C?**
- Testability score lower (3 vs 4)
- TDD is a project requirement per project_rules.md
- Would compromise our testing methodology

### Trade-offs Accepted
- Option A sacrifices some performance (score 4 vs Option B's 5)
- Accepted because: current load doesn't require optimization yet

### Risks Mitigated
- Option A has moderate vendor lock-in
- Mitigated by: using adapter pattern for external services
```

## Output Files

The skill generates:

```
.ai/project/features/{feature-id}/
├── 12_architecture_criteria.md   # Main criteria document
├── 12a_criteria_evaluation.md    # Evaluation matrix (if --evaluate)
└── 12b_criteria_interview.md     # Developer consultation log
```

## Interview Mode (--interview)

Interactive session with the developer:

```
╔════════════════════════════════════════════════════════════╗
║           ARCHITECTURE CRITERIA INTERVIEW                   ║
╚════════════════════════════════════════════════════════════╝

I'll help you define the right criteria for choosing the best
architecture for this feature. Let's start:

== Context Understanding ==

Q1: In one sentence, what is the core purpose of this feature?
> [dev input]

Q2: What's the expected lifespan of this feature?
   (1) Short-term/tactical (< 6 months)
   (2) Medium-term (6-18 months)
   (3) Long-term/strategic (> 18 months)
> [dev input]

== Constraints Discovery ==

Q3: What are the hard technical constraints?
   (Examples: must use existing DB, must integrate with X)
> [dev input]

Q4: What performance requirements exist?
   - Max latency: [dev input]
   - Min throughput: [dev input]
   - Data volume: [dev input]

== Priority Trade-offs ==

Q5: Rank these priorities for THIS feature (1=highest):
   [ ] Development speed
   [ ] Long-term maintainability
   [ ] Performance
   [ ] Flexibility/extensibility
> [dev input]

== Risk Tolerance ==

Q6: What would be the WORST outcome?
   (1) Feature doesn't perform under load
   (2) Feature is hard to modify later
   (3) Feature takes too long to build
   (4) Feature is hard for team to understand
> [dev input]

== Team Context ==

Q7: Who will maintain this after launch?
> [dev input]

Q8: Rate team's comfort with these patterns (1-5):
   - Event-driven: [dev input]
   - CQRS: [dev input]
   - Simple CRUD: [dev input]
   - Microservices: [dev input]

== Generating Criteria ==

Based on your answers, I've generated weighted criteria...

[Output criteria document]

Do you want to adjust any criteria weights? (y/n)
> [dev input]
```

## Integration with Workflow

### In Planning Phase

```bash
# Standard flow
/workflows:plan my-feature

# With criteria first (recommended for complex features)
/workflow-skill:criteria-generator --interview --feature=my-feature
/workflows:plan my-feature  # Now informed by criteria
```

### Automatic Trigger

The planner should automatically invoke criteria-generator when:
- Feature has multiple viable architectural approaches
- Feature touches auth, payment, or security
- Feature is marked as "strategic" or "long-term"
- Dev requests architecture comparison

## Best Practices

1. **Always consult the dev**: Criteria without stakeholder input are guesses
2. **Keep criteria countable**: "Good performance" is not a criterion; "<100ms p99 latency" is
3. **Document the WHY**: Future devs need to know why decisions were made
4. **Revisit when context changes**: Criteria may need updating
5. **Limit to 7-10 criteria**: More criteria = analysis paralysis

## Anti-Patterns to Avoid

| Anti-Pattern | Problem | Better Approach |
|--------------|---------|-----------------|
| "Best practice" cargo cult | Following patterns without understanding | Evaluate against YOUR context |
| Analysis paralysis | Too many criteria, never decide | Limit and prioritize |
| Gut feeling only | No documented reasoning | Document criteria explicitly |
| Ignoring team context | Perfect architecture team can't build | Weight team expertise highly |
| One-size-fits-all | Same criteria for all features | Customize per feature context |

## SOLID-Rigorous Mode

When SOLID compliance is critical (refactoring, new architecture), use strict SOLID evaluation:

### Invocation

```bash
# Enable SOLID-rigorous evaluation
/workflow-skill:criteria-generator --feature=<id> --solid-rigorous

# SOLID analysis before criteria generation
/workflow-skill:criteria-generator --feature=<id> --solid-first
```

### SOLID as Non-Negotiable Criterion

In SOLID-rigorous mode, the **C-BASE-02: SOLID Compliance** criterion is expanded to 5 sub-criteria:

| ID | Sub-Criterion | Weight | Pass Threshold |
|----|---------------|--------|----------------|
| C-SOLID-S | Single Responsibility | Critical | Score ≥4/5 |
| C-SOLID-O | Open/Closed | Critical | Score ≥4/5 |
| C-SOLID-L | Liskov Substitution | High | Score ≥3/5 |
| C-SOLID-I | Interface Segregation | High | Score ≥4/5 |
| C-SOLID-D | Dependency Inversion | Critical | Score ≥4/5 |

**Any option scoring <4 on Critical SOLID principles is automatically rejected.**

### SOLID Evaluation Matrix

For each architecture option, evaluate:

```markdown
## SOLID Evaluation: {Option Name}

### S - Single Responsibility
| Question | Answer | Score |
|----------|--------|-------|
| Can every class be described in ONE phrase without "and"? | Yes/No | /5 |
| Are all classes ≤200 lines? | Yes/No | /5 |
| Are all classes ≤7 public methods? | Yes/No | /5 |
| Does each class have ONE reason to change? | Yes/No | /5 |
**SRP Score**: {avg}/5

### O - Open/Closed
| Question | Answer | Score |
|----------|--------|-------|
| Can new types be added without modifying existing code? | Yes/No | /5 |
| Are there zero switch/if-else chains by type? | Yes/No | /5 |
| Is the design extensible via composition/inheritance? | Yes/No | /5 |
**OCP Score**: {avg}/5

### L - Liskov Substitution
| Question | Answer | Score |
|----------|--------|-------|
| Can any implementation replace another safely? | Yes/No | /5 |
| Do subtypes honor parent contracts? | Yes/No | /5 |
| Are there zero unexpected exceptions in overrides? | Yes/No | /5 |
**LSP Score**: {avg}/5

### I - Interface Segregation
| Question | Answer | Score |
|----------|--------|-------|
| Are all interfaces ≤5 methods? | Yes/No | /5 |
| Do all implementations use 100% of interface methods? | Yes/No | /5 |
| Are interfaces role-based? | Yes/No | /5 |
**ISP Score**: {avg}/5

### D - Dependency Inversion
| Question | Answer | Score |
|----------|--------|-------|
| Do all high-level modules depend on abstractions? | Yes/No | /5 |
| Does Domain have zero Infrastructure imports? | Yes/No | /5 |
| Are all dependencies injected (not instantiated)? | Yes/No | /5 |
**DIP Score**: {avg}/5

### Total SOLID Score: {sum}/25

| Threshold | Result |
|-----------|--------|
| ≥22/25 | SOLID-COMPLIANT |
| 18-21/25 | ACCEPTABLE (minor issues) |
| 14-17/25 | NEEDS WORK (refactor before approval) |
| <14/25 | REJECTED (violates SOLID) |
```

### Automatic Pattern Recommendation

When an option scores low on a SOLID principle, automatically recommend patterns:

```markdown
## SOLID Improvement Recommendations

### Option B scored 2/5 on OCP

**Detected Issue**: Switch statement by payment type in PaymentProcessor
**Recommended Pattern**: Strategy
**Implementation**:
```
PaymentProcessor
  → PaymentStrategyInterface
    → CreditCardStrategy
    → PayPalStrategy
    → BankTransferStrategy
```

**After applying pattern, expected OCP score**: 5/5
```

### Integration with solid-analyzer

```bash
# Automatic SOLID analysis of existing code before criteria generation
/workflow-skill:criteria-generator --feature=my-feature --solid-first

# This will:
# 1. Run /workflow-skill:solid-analyzer on relevant code paths
# 2. Include SOLID baseline in context
# 3. Generate criteria that address existing SOLID violations
# 4. Evaluate options with SOLID-rigorous scoring
```

### SOLID-Rigorous Output Template

```markdown
## Architecture Criteria: {feature-id} (SOLID-Rigorous)

**Mode**: SOLID-Rigorous (no option with SOLID <18/25 will be accepted)

### SOLID Baseline Analysis
**Current Code Score**: {X}/25
**Violations Found**: {count}
**Critical Violations**: {list}

### Criteria Matrix (SOLID-Weighted)

| ID | Criterion | Category | Weight | Notes |
|----|-----------|----------|--------|-------|
| C-SOLID-S | Single Responsibility | SOLID | Critical | Must score ≥4/5 |
| C-SOLID-O | Open/Closed | SOLID | Critical | Must score ≥4/5 |
| C-SOLID-L | Liskov Substitution | SOLID | High | Must score ≥3/5 |
| C-SOLID-I | Interface Segregation | SOLID | High | Must score ≥4/5 |
| C-SOLID-D | Dependency Inversion | SOLID | Critical | Must score ≥4/5 |
| C-FEAT-01 | {Feature criterion} | Functional | {weight} | {notes} |
| ... | ... | ... | ... | ... |

### Evaluation Matrix (SOLID-First)

| Criterion | Weight | Option A | Option B | Option C |
|-----------|--------|----------|----------|----------|
| **SOLID-S** | 5 | 5 (25) | 3 (15) | 4 (20) |
| **SOLID-O** | 5 | 5 (25) | 2 (10) | 5 (25) |
| **SOLID-L** | 4 | 4 (16) | 4 (16) | 4 (16) |
| **SOLID-I** | 4 | 5 (20) | 5 (20) | 3 (12) |
| **SOLID-D** | 5 | 5 (25) | 3 (15) | 5 (25) |
| SOLID Subtotal | | **111** | **76** ❌ | **98** |
| {Other criteria} | | ... | ... | ... |
| **TOTAL** | | **156** | **REJECTED** | **143** |

### Decision

**Winner**: Option A
**SOLID Score**: 24/25 (SOLID-COMPLIANT)
**Reason**: Highest SOLID compliance + best overall score

**Option B Rejected**: SOLID score 15/25 (below 18 threshold)
- OCP violation: switch by type
- DIP violation: concrete dependencies

**Option C Considered**: SOLID score 21/25 (ACCEPTABLE)
- ISP minor issue: one interface with 6 methods
- Recommendation: Split interface before implementation
```

### SOLID-Rigorous Workflow

```
1. ANALYZE existing code with /workflow-skill:solid-analyzer
   └─ Get SOLID baseline score

2. IDENTIFY violations requiring architectural fix
   └─ Map to patterns via solid-pattern-matrix.md

3. GENERATE architecture options that FIX violations
   └─ Use solid-architecture-generator agent

4. EVALUATE options with SOLID-rigorous criteria
   └─ Reject any option with SOLID <18/25

5. SELECT highest-scoring SOLID-compliant option
   └─ Document why others rejected

6. VALIDATE final architecture
   └─ Re-run solid-analyzer on proposed design
   └─ Must score ≥22/25 to approve
```

## Related

- `/workflows:plan` - Main planning workflow
- `/workflows:interview` - Feature specification interview
- `/workflows:solid-refactor` - SOLID-focused refactoring workflow
- `10_architecture.md` - Architecture design document
- `agents/roles/planner.md` - Planner role context
- `agents/design/solid-architecture-generator.md` - SOLID architecture generation
- `skills/workflow-skill-solid-analyzer.md` - Automated SOLID analysis
- `core/solid-pattern-matrix.md` - Violation → Pattern mapping
