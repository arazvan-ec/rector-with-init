---
name: shaper
description: "Collaborative shaping methodology to iterate on problem definition (requirements) and solution options (shapes) before planning. Separates problem from solution for better design decisions."
model: opus
context: fork
hooks:
  Stop:
    - command: "echo '[shaper] Shaping complete. Documents saved to .ai/project/features/'"
---

# Shaper Skill

Collaborative shaping methodology based on Ryan Singer's Shape Up framework. Separates problem definition from solution design, enabling iterative exploration before committing to implementation.

## What This Skill Does

- Captures requirements (R) independently of any specific solution
- Defines solution shapes (A, B, C...) as alternative approaches
- Runs fit checks (R x A) to verify solution coverage against requirements
- Manages spikes to investigate unknowns before committing
- Produces structured shaping documents for handoff to planning
- Prevents premature commitment to a solution before understanding the problem

## When to Use

- **New features with unclear scope**: When the user describes something but the boundaries are fuzzy
- **Complex features**: When there are multiple possible approaches worth comparing
- **Before `/workflows:plan`**: As a pre-planning phase to reduce retrabajo
- **Exploratory work**: When we need to research before designing
- **High-risk changes**: When wrong direction has high cost

## How to Use

```bash
# Start shaping a new feature
/multi-agent-workflow:shaper "description of what we want to build"

# Continue shaping an existing feature
/multi-agent-workflow:shaper --continue feature-name

# Show fit check
/multi-agent-workflow:shaper --fit-check

# Run a spike
/multi-agent-workflow:shaper --spike A2
```

## Integration with Workflow

Shaping fits between routing and planning:

```
/workflows:route → /workflows:shape → /workflows:plan → /workflows:work
```

The shaping phase produces a `01_shaped_brief.md` that feeds into the planner's Phase 1 (UNDERSTAND).

---

## Core Concepts

### Requirements (R)

Requirements define the PROBLEM space independently of any solution. They are numbered R0, R1, R2...

**Format**:
```markdown
| # | Requirement | Status |
|---|-------------|--------|
| R0 | Show a table of time zones | Core goal |
| R1 | Data must be accurate and fresh | Must-have |
| R2 | User can add/remove locales | Must-have |
| R3 | Natural language input | Nice-to-have |
```

**Status values**: Core goal, Undecided, Leaning yes, Leaning no, Must-have, Nice-to-have, Out

**Rules**:
- Requirements describe the PROBLEM, not the SOLUTION
- "Use React" is NOT a requirement (that's a solution choice)
- "User can see real-time data" IS a requirement
- Requirements should apply regardless of which solution (A, B, C) we choose

### Shapes (A, B, C...)

Shapes are mutually exclusive solution approaches. Each shape has parts that are its key mechanisms.

**Format**:
```markdown
## Shape A: Python TUI with local timezone library

| # | Part | Description | Flag |
|---|------|-------------|------|
| A1 | TUI shell | Textual-based terminal interface | |
| A2 | Timezone resolver | Local tz library for conversions | |
| A3 | LLM input parser | Ollama + tool calls for commands | :warning: |
```

**Rules**:
- Parts must be MECHANISMS (what we build), not intentions
- Flag `:warning:` means the part has unknowns that need spiking
- Flagged parts automatically FAIL fit checks (you can't claim what you don't know)
- A shape should have 3-8 parts at this level of detail

### Components and Alternatives

For complex shapes, parts can be decomposed:

- **Components** (C1, C2, C3...): Parts that combine within a shape
- **Alternatives** (C3-A, C3-B...): Sub-options where you pick exactly one

### Fit Check (R x A)

A binary decision matrix mapping requirements to shape coverage.

**Format**:
```markdown
## Fit Check: R x A

| Req | Description | A |
|-----|-------------|---|
| R0 | Show timezone table | :white_check_mark: A1+A2 |
| R1 | Accurate data | :white_check_mark: A2 |
| R2 | Add/remove locales | :white_check_mark: A3 |
| R3 | Natural language input | :x: A3 flagged |
```

**Rules**:
- Only checkmark or X. No warning symbols in fit checks.
- If a part is flagged (unknown), any R depending on it gets X
- Notes explain failures, not successes
- Run fit check AFTER updating shape or requirements

### Rotated Fit Check (A x R)

Shows the same data rotated: parts as rows, requirements as columns.

```markdown
## Fit Check: A x R

| Part | Description | R0 | R1 | R2 | R3 |
|------|-------------|----|----|----|----|
| A1 | TUI shell | :white_check_mark: | | | |
| A2 | Tz resolver | :white_check_mark: | :white_check_mark: | | |
| A3 | LLM parser | | | :warning: | :warning: |
```

This view reveals which parts carry the most risk and where coverage gaps exist.

### Spikes

Investigations to resolve unknowns. A spike produces information, not decisions.

**Spike document format**:
```markdown
# Spike: A2 - Timezone Resolution Approach

## Context
We need to resolve timezone names to UTC offsets accounting for DST.

## Questions
| # | Question | Answer |
|---|----------|--------|
| Q1 | Can we use a local library? | Yes - pytz handles all DST rules |
| Q2 | Do we need internet for accuracy? | No - OS tz database is sufficient |

## Findings
[Detailed technical findings...]

## Conclusion
[What we learned and how it affects the shape]
```

**Rules**:
- Questions ask about MECHANICS, not effort ("Can X do Y?" not "How long?")
- Acceptance describes information/understanding gained, not decisions
- Spike output is a separate file: `spike-{part-id}.md`
- After spike, update shape parts and re-run fit check

---

## Shaping Process

### Step 1: Frame the Problem

Create the initial frame:

```markdown
# Frame

## Problem
[What pain or need exists]

## Outcome
[What success looks like when the problem is solved]
```

### Step 2: Capture Requirements

Extract requirements from the user's description. Separate problem-space requirements from solution-space preferences.

### Step 3: Draft Shape A

Create an initial shape with parts. Flag unknowns honestly.

### Step 4: Run Fit Check

Verify R x A coverage. Identify gaps.

### Step 5: Iterate

Based on fit check results:
- **Spike unknowns**: Investigate flagged parts
- **Revise requirements**: Update R based on what we learn
- **Try new shapes**: Create B, C if A has fundamental problems
- **Detail parts**: Expand high-level parts into mechanisms

### Step 6: Declare Shape Ready

A shape is ready for planning when:
- All parts are unflagged (no unknowns)
- Fit check is all green
- No unresolved decisions
- User approves the approach

---

## Document Hierarchy

| Level | Document | Purpose |
|-------|----------|---------|
| Summary | Big Picture | Quick context for anyone joining |
| Working | `01_shaped_brief.md` | Ground truth for R, shapes, fit checks |
| Detail | `spike-*.md` | Investigation findings |
| Implementation | Handoff to `/workflows:plan` | Transition to planning phase |

Changes ripple in both directions between levels.

---

## Output Files

The shaper skill produces files in the feature directory:

```
.ai/project/features/${FEATURE_ID}/
├── 01_shaped_brief.md      # Main shaping document (R, shapes, fit checks)
├── spike-a2.md             # Spike documents (one per investigation)
├── spike-a5.md
└── ...
```

### 01_shaped_brief.md Structure

```markdown
# Shaped Brief: ${FEATURE_NAME}

## Frame
### Problem
[Pain/need]
### Outcome
[Success definition]

## Requirements
| # | Requirement | Status |
|---|-------------|--------|
| R0 | ... | Core goal |

## Shape A: [Name]
| # | Part | Description | Flag |
|---|------|-------------|------|
| A1 | ... | ... | |

## Fit Check: R x A
| Req | Description | A |
|-----|-------------|---|
| R0 | ... | :white_check_mark: |

## Decisions
| # | Decision | Status | Resolution |
|---|----------|--------|------------|
| D1 | Which tz library? | Resolved | pytz |

## Next Steps
- [ ] Spike A3 for LLM integration
- [ ] Confirm R4 with user
```

---

## Shorthand Commands

The shaper responds to these shorthand instructions:

| Command | Action |
|---------|--------|
| `show R` | Display current requirements |
| `show A` | Display current shape A (or B, C...) |
| `show R x A` | Show fit check (requirements vs shape) |
| `show A x R` | Show rotated fit check |
| `spike A2` | Create and execute a spike for part A2 |
| `add R` | Add a new requirement |
| `update A` | Update shape A based on new information |
| `try B` | Create alternative shape B |
| `detail A` | Expand shape A parts into more detail |
| `ready?` | Check if shape is ready for planning |

---

## Transition to Planning

When shaping is complete, the shaped brief feeds into `/workflows:plan`:

1. **R (requirements)** map to Phase 2 functional specs
2. **Shape parts** map to Phase 3 solution design
3. **Spike findings** provide technical context for the planner
4. **Fit check** provides verification that the plan covers all requirements

The planner reads `01_shaped_brief.md` as input for Step 0 (Load Context) and Phase 1 (Understand).

---

## Example: Shaping a Timezone TUI

```
User: I want a TUI that shows timezone tables with natural language input

Step 1 - Frame:
  Problem: Coordinating across timezones is painful
  Outcome: Clear hour-by-hour table, accurate data, NL control

Step 2 - Requirements:
  R0: Show a table of hours per locale (Core goal)
  R1: Accurate timezone data (Must-have)
  R2: Add/remove locales (Must-have)
  R3: Natural language input (Must-have)
  R4: Default timezone list (Nice-to-have)

Step 3 - Shape A: Python TUI + Local TZ + Ollama
  A1: TUI shell (Textual framework)
  A2: Timezone resolver (local tz library) [flagged]
  A3: LLM command parser (Ollama) [flagged]

Step 4 - Fit Check: R1 fails (A2 flagged), R3 fails (A3 flagged)

Step 5 - Spike A2: Local tz library handles DST correctly, no internet needed
         Spike A3: Ollama + tool calling works for parsing commands
         Update A: Remove flags, update R1

Step 6 - Fit Check: All green. Ready for planning.
```

---

## Related

- `/workflows:shape` - Command that invokes this skill
- `/multi-agent-workflow:breadboarder` - Next step: detail the shape into affordances
- `/workflows:plan` - Receives shaped brief as input
- `core/roles/planner.md` - Planner reads shaped brief
