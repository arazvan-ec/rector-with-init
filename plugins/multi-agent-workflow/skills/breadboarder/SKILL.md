---
name: breadboarder
description: "Transform a shaped solution into affordance tables showing UI and Code affordances with wiring, then slice into vertical demoable increments. Use after shaping is complete."
model: opus
context: fork
hooks:
  Stop:
    - command: "echo '[breadboarder] Breadboard complete. Slices ready for implementation planning.'"
---

# Breadboarder Skill

Transforms a shaped solution into a concrete technical diagram (breadboard) and slices it into vertical, demoable implementation increments.

## What This Skill Does

- Maps shaped parts into Places, UI affordances, Code affordances, and Data stores
- Defines wiring between affordances (control flow and data flow)
- Generates Mermaid diagrams for visual inspection
- Slices the breadboard into vertical implementation increments (V1, V2...)
- Each slice is demoable end-to-end (not horizontal layers)

## When to Use

- **After shaping**: When a shape is selected and ready for detailing
- **Before planning**: To create vertical slices that become the implementation plan
- **Complex features**: When the system has multiple interacting parts
- **Understanding existing systems**: Map an existing codebase into affordance tables

## How to Use

```bash
# Breadboard from a shaped brief
/multi-agent-workflow:breadboarder feature-name

# Slice an existing breadboard
/multi-agent-workflow:breadboarder --slice feature-name

# Map an existing system
/multi-agent-workflow:breadboarder --map "description or path"
```

---

## Core Concepts

### Places

Bounded contexts of interaction. The "blocking test": if you can't interact with what's behind it, it's a different Place.

| # | Place | Description |
|---|-------|-------------|
| P1 | TUI Main Screen | Primary interface with table and input |
| P2 | Ollama Service | Local LLM for command parsing |
| P3 | Timezone Library | Local tz computation |

### Affordances

Things you can do (interact with) in each Place.

**UI Affordances** (U prefix): Inputs, buttons, displays, scroll regions
**Code Affordances** (N prefix): Methods, subscriptions, data stores, framework mechanisms

| # | Place | Component | Affordance | Control | Wires Out | Returns To |
|---|-------|-----------|-----------|---------|-----------|-----------|
| U1 | P1 | Table | Show hours grid | Display | | S1 |
| U2 | P1 | Input | Type NL command | Text field | N1 | U1 |
| N1 | P2 | Parser | Parse command | Method | N2, N3 | U1 |
| N2 | P3 | Resolver | Get tz offsets | Method | | S1 |

### Data Stores (S prefix)

State that persists and is read/written.

| # | Place | Store | Description |
|---|-------|-------|-------------|
| S1 | P1 | Active locales | List of currently displayed timezones |
| S2 | P1 | Time window | Currently displayed date range |

### Wiring

- **Wires Out** (solid arrows `-->`): Control flow (what triggers what)
- **Returns To** (dashed arrows `-.->`) : Data flow (where output goes)

---

## Breadboarding Process

### From Shaped Parts

1. **List Places**: Identify bounded contexts from shape parts
2. **List UI affordances**: For each Place, what can the user see/do?
3. **List Code affordances**: For each Place, what does the system do?
4. **List Data stores**: What state persists?
5. **Wire them**: Connect affordances with control flow and data flow
6. **Verify completeness**: Every U that displays data needs a data source. Every N must connect.

### Key Principles

- Every affordance name must be a concrete mechanism, not an intention
- Two flows: Navigation (Place to Place) and Data (state to displays)
- Every UI that displays data needs a data source (wire from a Store or Code affordance)
- Every Code affordance must connect (Wires Out or Returns To)
- Side effects need stores (browser URL, localStorage, etc.)
- Backend services are Places

---

## Mermaid Visualization

The breadboard is rendered as a Mermaid flowchart:

```
flowchart TB
    subgraph P1["TUI Main Screen"]
        U1["U1: Hours Grid"]:::ui
        U2["U2: NL Input"]:::ui
        S1[("S1: Active Locales")]:::store
        S2[("S2: Time Window")]:::store
    end

    subgraph P2["Ollama Service"]
        N1["N1: Parse Command"]:::code
    end

    subgraph P3["Timezone Library"]
        N2["N2: Resolve TZ"]:::code
    end

    U2 --> N1
    N1 --> N2
    N2 -.-> S1
    S1 -.-> U1

    classDef ui fill:#ffb6c1,stroke:#333
    classDef code fill:#d3d3d3,stroke:#333
    classDef store fill:#e6e6fa,stroke:#333
```

**Color scheme**:
- Pink (`#ffb6c1`): UI affordances
- Grey (`#d3d3d3`): Code affordances
- Lavender (`#e6e6fa`): Data stores
- Light blue (`#b3e5fc`): Chunks (collapsed subsystems)

---

## Slicing

### What is a Vertical Slice?

A subset of the breadboard that is **demoable end-to-end**. Each slice includes both UI and backend affordances, not just one layer.

### Slicing Process

1. **V1**: Identify the minimal demoable increment (smallest useful thing)
2. **V2-V9**: Layer additional capabilities, each adding visible functionality
3. **Assign affordances**: Each affordance belongs to exactly one slice
4. **Write demo statements**: What can you show at the end of each slice?
5. **Create per-slice tables**: Subset of the breadboard tables for each slice

### Slice Format

```markdown
## V1: Static Timezone Table

**Demo**: Show a table of hours for 3 default timezones for today.

| # | Place | Affordance | Type |
|---|-------|-----------|------|
| U1 | P1 | Hours Grid | UI |
| N2 | P3 | Resolve TZ | Code |
| S1 | P1 | Active Locales | Store |
| S2 | P1 | Time Window | Store |

---

## V2: Natural Language Commands

**Demo**: Type "add brazil" and see the table update with a new column.

| # | Place | Affordance | Type |
|---|-------|-----------|------|
| U2 | P1 | NL Input | UI |
| N1 | P2 | Parse Command | Code |
```

### Slice Color Palette (for Mermaid)

| Slice | Color | Hex |
|-------|-------|-----|
| V1 | Green | `#90EE90` |
| V2 | Blue | `#87CEEB` |
| V3 | Orange | `#FFD700` |
| V4 | Purple | `#DDA0DD` |
| V5 | Yellow | `#FFFACD` |
| V6 | Pink | `#FFB6C1` |

### Max 9 Slices

If a feature needs more than 9 slices, it should be split into separate features.

---

## Output Files

The breadboarder adds to the feature directory:

```
.ai/project/features/${FEATURE_ID}/
├── 01_shaped_brief.md       # (from shaper)
├── 02_breadboard.md          # Breadboard tables + Mermaid diagram
├── 03_slices.md              # Slice definitions with demo statements
├── spike-*.md                # (from shaper)
└── ...
```

### 02_breadboard.md Structure

```markdown
# Breadboard: ${FEATURE_NAME}

## Places
| # | Place | Description |
|---|-------|-------------|

## UI Affordances
| # | Place | Component | Affordance | Control | Wires Out | Returns To |
|---|-------|-----------|-----------|---------|-----------|-----------|

## Code Affordances
| # | Place | Component | Affordance | Control | Wires Out | Returns To |
|---|-------|-----------|-----------|---------|-----------|-----------|

## Data Stores
| # | Place | Store | Description |
|---|-------|-------|-------------|

## Breadboard Diagram
[Mermaid flowchart]
```

### 03_slices.md Structure

```markdown
# Slices: ${FEATURE_NAME}

## Summary
| Slice | Description | Affordances | Demo |
|-------|-------------|-------------|------|
| V1 | Static table | U1, N2, S1, S2 | Show default tz table |
| V2 | NL commands | U2, N1 | Type command, see update |

## V1: [Name]
**Demo**: [What to show]
[Affordance table for this slice]

## V2: [Name]
**Demo**: [What to show]
[Affordance table for this slice]

## Sliced Breadboard Diagram
[Mermaid flowchart with slice colors]
```

---

## Transition to Planning

Slices feed directly into `/workflows:plan`:

1. **Each slice V1, V2...** becomes a planning unit (can be a task group or mini-feature)
2. **Affordance tables** provide the concrete scope for each slice
3. **Demo statements** become acceptance criteria
4. **Slice order** becomes the implementation sequence
5. **V1 is always built first** to validate the approach early

The planner reads `02_breadboard.md` and `03_slices.md` to create task breakdowns that preserve the vertical nature of each slice.

---

## Related

- `/multi-agent-workflow:shaper` - Previous step: shape the problem and solution
- `/workflows:shape` - Command that orchestrates shaping + breadboarding
- `/workflows:plan` - Next step: detailed implementation planning
- `core/roles/planner.md` - Consumes breadboard and slices
