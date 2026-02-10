---
name: workflows:shape
description: "Shape a feature before planning: separate problem from solution, explore alternatives, spike unknowns, breadboard, and slice into vertical demoable increments."
argument_hint: <feature-name> [--mode=full|quick] [--continue]
---

# Multi-Agent Workflow: Shape

The shaping phase bridges the gap between a vague idea and a detailed plan. It forces you to understand the problem before committing to a solution.

## Usage

```bash
# Full shaping workflow (recommended for complex features)
/workflows:shape payment-integration

# Quick shaping (skip breadboarding, good for simple features)
/workflows:shape user-preferences --mode=quick

# Continue shaping an existing feature
/workflows:shape payment-integration --continue
```

## Flags

| Flag | Default | Description |
|------|---------|-------------|
| `--mode` | `full` | `full` includes breadboarding + slicing. `quick` stops after fit check. |
| `--continue` | `false` | Resume shaping for a feature that already has `01_shaped_brief.md` |

## Philosophy

> "Separate the problem from the solution. Iterate on both. Commit to neither until you understand both."

> *Based on Ryan Singer's Shape Up methodology*

Good shaping means:
- Requirements (R) exist independently of any solution
- Multiple solutions (A, B, C) can be compared against the same R
- Unknowns are spiked before committing
- The solution is detailed enough to plan but abstract enough to allow flexibility

---

## When to Use Shape vs Plan Directly

| Situation | Use Shape? | Reason |
|-----------|-----------|--------|
| Complex new feature | **Yes** | Multiple approaches worth comparing |
| Feature with unclear scope | **Yes** | Need to define boundaries first |
| High-risk change | **Yes** | Wrong direction is expensive |
| Simple bug fix | No | Problem and solution are obvious |
| Well-defined small feature | No | Jump to `/workflows:plan` |
| Refactoring | Maybe | Shape if scope is unclear |

**Rule of thumb**: If you can write the plan in your head, skip shaping. If you're not sure which approach to take, shape first.

---

## The Shaping Process

```
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 1: FRAME                                │
│  Define problem + desired outcome                                │
│  Output: Frame section in 01_shaped_brief.md                     │
└─────────────────────────────────────────────────────────────────┘
                              |
                              v
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 2: REQUIREMENTS                         │
│  Extract R0, R1, R2... from user description                     │
│  Separate problem-space (keep) from solution-space (move to A)   │
│  Output: Requirements table in 01_shaped_brief.md                │
└─────────────────────────────────────────────────────────────────┘
                              |
                              v
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 3: SHAPE                                │
│  Draft Shape A with parts (mechanisms, not intentions)           │
│  Flag unknowns with :warning:                                    │
│  Output: Shape A in 01_shaped_brief.md                           │
└─────────────────────────────────────────────────────────────────┘
                              |
                              v
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 4: FIT CHECK                             │
│  Run R x A fit check                                             │
│  Identify gaps and failures                                      │
│  Output: Fit check matrix in 01_shaped_brief.md                  │
└─────────────────────────────────────────────────────────────────┘
                              |
                              v
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 5: ITERATE                               │
│  Spike unknowns → Update shape → Re-check fit                   │
│  Try alternative shapes if needed (B, C...)                      │
│  Revise requirements based on discoveries                        │
│  Output: Updated brief + spike-*.md files                        │
└─────────────────────────────────────────────────────────────────┘
                              |
                              v
              ┌───────────────────────────────┐
              │ --mode=quick stops here        │
              │ Proceed to /workflows:plan     │
              └───────────────────────────────┘
                              |
                              v  (--mode=full only)
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 6: BREADBOARD                            │
│  Map shape into Places, Affordances, Wiring                      │
│  Generate Mermaid diagram                                        │
│  Invoke: /multi-agent-workflow:breadboarder                      │
│  Output: 02_breadboard.md                                        │
└─────────────────────────────────────────────────────────────────┘
                              |
                              v
┌─────────────────────────────────────────────────────────────────┐
│                    PHASE 7: SLICE                                 │
│  Cut breadboard into vertical demoable increments (V1, V2...)    │
│  Each slice has UI + code + data (not horizontal layers)         │
│  Output: 03_slices.md                                            │
└─────────────────────────────────────────────────────────────────┘
                              |
                              v
┌─────────────────────────────────────────────────────────────────┐
│                    HANDOFF TO PLANNING                            │
│  Shaped brief + breadboard + slices → /workflows:plan            │
│  Slices become task groups in the plan                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## Execution Protocol

### Step 1: Create Workspace

```bash
FEATURE_ID="payment-integration"
mkdir -p .ai/project/features/${FEATURE_ID}
```

### Step 2: Invoke Shaper Skill

```bash
/multi-agent-workflow:shaper "${user_description}"
```

The shaper skill handles Phases 1-5 interactively with the user.

### Step 3: Interactive Iteration

During shaping, the user can use shorthand commands:

| Command | Action |
|---------|--------|
| `show R` | Display requirements |
| `show A` | Display shape A |
| `show R x A` | Show fit check |
| `spike A2` | Investigate part A2 |
| `add R` | Add requirement |
| `try B` | Create alternative shape |
| `ready?` | Check if ready for next phase |

### Step 4: Breadboard (if --mode=full)

```bash
/multi-agent-workflow:breadboarder ${FEATURE_ID}
```

### Step 5: Slice (if --mode=full)

```bash
/multi-agent-workflow:breadboarder --slice ${FEATURE_ID}
```

### Step 6: Handoff

When shaping is complete:

1. Update `50_state.md` with shaping status
2. Summarize shaped brief for the user
3. Recommend: proceed to `/workflows:plan ${FEATURE_ID}`

---

## Output Files

```
.ai/project/features/${FEATURE_ID}/
├── 01_shaped_brief.md        # Frame, requirements, shape, fit check
├── 02_breadboard.md           # Places, affordances, wiring, diagram (full mode)
├── 03_slices.md               # Vertical slices with demo statements (full mode)
├── spike-a2.md                # Spike investigations
├── spike-a5.md
├── 00_problem_statement.md    # (generated by /workflows:plan later)
├── 12_specs.md                # (generated by /workflows:plan later)
└── ...
```

---

## Integration with Planning

The shaped brief enriches the planning process:

| Shaping Output | Planning Input |
|----------------|---------------|
| Frame (Problem/Outcome) | Phase 1: Problem Statement |
| Requirements (R) | Phase 2: Functional Specs foundation |
| Shape parts | Phase 3: Solution design starting point |
| Spike findings | Phase 3: Technical context |
| Fit check | Verification that plan covers all R |
| Breadboard | Concrete scope for task breakdown |
| Slices (V1, V2...) | Task groups ordered by priority |

### What Shaping Replaces in Planning

With shaping, the planner can skip or accelerate:
- **Phase 1 (Understand)**: Already done in shaping frame
- **Phase 2 (Specs)**: Requirements provide foundation (but planner still adds formal specs)
- **Phase 3 (Solutions)**: Shape provides direction (planner adds SOLID analysis)

What shaping does NOT replace:
- SOLID compliance analysis
- Integration analysis with existing specs
- Detailed task breakdown
- API contract generation

---

## Shaping Checklist

Before marking shaping as complete:

- [ ] Problem is framed (not just described)
- [ ] Requirements are problem-space, not solution-space
- [ ] At least one shape drafted with concrete mechanisms
- [ ] All flagged unknowns have been spiked
- [ ] Fit check is all green for selected shape
- [ ] User has approved the direction
- [ ] (Full mode) Breadboard shows complete wiring
- [ ] (Full mode) Slices are vertical with demo statements

---

## Examples

### Example 1: Quick Shape for Simple Feature

```
User: "Add user preferences page"

Shape:
  R0: User can view their preferences (Core goal)
  R1: User can edit preferences (Must-have)
  R2: Preferences persist across sessions (Must-have)

  Shape A: Settings page with form
    A1: Preferences form (React form with fields)
    A2: Preferences API (GET/PUT endpoint)
    A3: Preferences storage (database column on User)

  Fit: All green. Ready for /workflows:plan.
```

### Example 2: Full Shape for Complex Feature

```
User: "Integrate Stripe payments"

Shape:
  R0-R8: Payment requirements (checkout, refunds, webhooks, etc.)

  Shape A: Direct Stripe API
    A1: Checkout flow (Stripe Elements)
    A2: Payment processing (Stripe API server-side)
    A3: Webhook handler (Stripe events)
    A4: Refund system [flagged]
    A5: Invoice generation [flagged]

  Spike A4: Stripe refund API is straightforward, partial refunds supported
  Spike A5: Stripe invoices or custom PDF? → Stripe invoices sufficient

  Breadboard: 3 Places (Checkout UI, API Server, Stripe)
              8 UI affordances, 12 Code affordances, 4 Data stores

  Slices:
    V1: One-time payment checkout (demo: buy a product)
    V2: Webhook processing (demo: payment confirmed in DB)
    V3: Refunds (demo: refund from admin panel)
    V4: Invoices (demo: download invoice PDF)
```

---

## Related Commands

- `/workflows:route` - Routes to shape when appropriate
- `/workflows:plan` - Next step after shaping
- `/workflows:work` - Execute the plan
- `/multi-agent-workflow:shaper` - The underlying shaping skill
- `/multi-agent-workflow:breadboarder` - The breadboarding skill

## Related Documentation

- `skills/shaper/SKILL.md` - Full shaping methodology
- `skills/breadboarder/SKILL.md` - Full breadboarding methodology
- `core/roles/planner.md` - How planner consumes shaped briefs
