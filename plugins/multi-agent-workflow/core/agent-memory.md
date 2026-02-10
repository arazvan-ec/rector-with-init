# Agent Compound Memory System

> **Purpose**: Data-driven agent calibration based on real project history.
> **Philosophy**: Agents should learn from compound captures, not just follow static rules. The 70% boundary data tells agents WHERE to focus.

---

## Overview

The Agent Compound Memory system creates a feedback loop between `/workflows:compound` captures and agent behavior during `/workflows:review` and `/workflows:plan`.

```
Feature N completed
       │
       ▼
/workflows:compound
  ├── Captures patterns, anti-patterns, 70% boundary
  ├── Updates compound_log.md
  └── Updates compound-memory.md ◄── THIS IS THE FEEDBACK FILE
       │
       ▼
Next feature: /workflows:plan + /workflows:review
  └── Agents READ compound-memory.md
      └── Adjust intensity, focus areas, and warnings
```

---

## How It Works

### 1. Compound Writes Memory

After each `/workflows:compound`, Step 3 (anti-patterns & 70% boundary) feeds into `compound-memory.md`:

```markdown
## Known Pain Points

### Database Migrations (Severity: HIGH, Frequency: 3/5 features)
- **Pattern**: Migrations fail on edge cases with nullable foreign keys
- **70% boundary**: Usually hits at integration testing phase
- **Prevention**: Always test migrations with production-like data before marking COMPLETED
- **Affected agents**: ddd-compliance, performance-review

### Form State Management (Severity: MEDIUM, Frequency: 2/5 features)
- **Pattern**: React form state gets complex with conditional validation
- **70% boundary**: Hits during error handling implementation
- **Prevention**: Define all validation rules upfront in plan phase
- **Affected agents**: code-simplicity-reviewer, code-review-ts
```

### 2. Agents Read Memory

Review agents check `compound-memory.md` BEFORE starting their analysis. The memory adjusts their behavior:

**Security Review Agent** — if compound memory shows security issues:
```
compound-memory says: "JWT token refresh had race condition in 2/3 features"
→ Agent increases scrutiny on authentication flows
→ Explicitly checks for race conditions in token handling
→ Adds to report: "⚠️ Historical: This project has had JWT race conditions before"
```

**DDD Compliance Agent** — if compound memory shows layer violations:
```
compound-memory says: "Domain entities leaked Doctrine annotations in first 2 features"
→ Agent prioritizes checking Domain layer purity
→ Runs grep for framework imports in Domain/ first
→ Adds to report: "Historical pattern: Watch for Doctrine leaking into Domain"
```

**Performance Review Agent** — if compound memory shows N+1 queries:
```
compound-memory says: "N+1 queries found in 3/4 features, always in list endpoints"
→ Agent focuses on list/collection endpoints first
→ Checks for eager loading configuration
→ Adds to report: "⚠️ This project historically has N+1 issues in list endpoints"
```

### 3. Memory Structure

The `compound-memory.md` file follows this structure:

```markdown
# Agent Compound Memory

## Project Profile
[Auto-filled by quickstart, refined by compound]

## Known Pain Points
[Ranked by severity × frequency]

### [Pain Point Name] (Severity: X, Frequency: Y/Z features)
- **Pattern**: What happens
- **70% boundary**: Where in the flow it hits
- **Prevention**: What would have helped
- **Affected agents**: Which agents should care

## Historical Patterns
[What works well — agents should REINFORCE these]

### [Pattern Name] (Reliability: X/Y features)
- **Description**: What the pattern is
- **Why it works**: Root cause of success
- **Agents should**: Validate this pattern is still being followed

## Agent Calibration
[Explicit intensity overrides based on data]

| Agent | Default | Adjusted | Data Points | Reason |
|-------|---------|----------|-------------|--------|
| security-review | balanced | HIGH | 3 incidents | JWT + CORS issues |
| ddd-compliance | balanced | balanced | 0 incidents | Clean history |
| performance-review | balanced | HIGH | 4 N+1 queries | Recurring pattern |
| code-simplicity-reviewer | balanced | LOW | 1 minor | Team writes clean code |
```

---

## Integration with Existing Commands

### `/workflows:compound` — WRITES to memory

Add to **Step 3** (Identify Anti-Patterns & 70% Boundary):

```markdown
After documenting anti-patterns, UPDATE .ai/project/compound-memory.md:

1. Check if pain point already exists in memory:
   - YES: Increment frequency counter, update severity if worse
   - NO: Add new pain point entry

2. Check if successful pattern already exists:
   - YES: Increment reliability counter
   - NO: Add new pattern entry

3. Recalculate Agent Calibration table:
   - If agent has ≥2 related pain points → intensity = HIGH
   - If agent has 1 related pain point → intensity = default + warning
   - If agent has 0 related pain points → intensity = default
   - If agent has ≥3 successful patterns → can reduce intensity
```

### `/workflows:review` — READS from memory

Add to review execution (before invoking review agents):

```markdown
Before dispatching review agents:

1. READ .ai/project/compound-memory.md (if exists)
2. For each review agent being invoked:
   a. Check Agent Calibration table for intensity override
   b. Check Known Pain Points for areas this agent should focus on
   c. Include relevant pain points in agent's context as "Historical warnings"
3. Agent reports MUST include:
   - Section: "Compound Memory Checks"
   - For each relevant pain point: explicitly state if it was found or not
```

### `/workflows:plan` — READS from memory

Add to planning (Phase 2: Functional Specs):

```markdown
During planning, READ .ai/project/compound-memory.md:

1. Known Pain Points inform risk assessment:
   - HIGH severity + HIGH frequency → must be addressed in plan
   - Include specific prevention steps from memory

2. Historical Patterns inform solution design:
   - If a reliable pattern exists for this type of feature → reference it
   - If a pain point exists for this type of feature → plan mitigation

3. Add to plan output:
   - Section: "Known Risks (from compound memory)"
   - List relevant pain points with prevention steps
```

---

## Example: Full Cycle

### Feature 1: User Authentication

```
/workflows:compound captures:
  - Pain point: JWT token storage insecure (localStorage, not httpOnly cookie)
  - Pain point: Password validation regex too permissive
  - Pattern: Email value object with factory method → worked well

compound-memory.md updated:
  - security-review: adjusted to HIGH (2 security pain points)
  - ddd-compliance: reinforced (email VO pattern reliable)
```

### Feature 2: Password Reset (benefits from memory)

```
/workflows:plan reads memory:
  → "Known Risk: JWT storage. Previous feature had insecure storage."
  → Plan includes: "Use httpOnly secure cookies for reset tokens"

/workflows:review reads memory:
  → security-review runs at HIGH intensity
  → Explicitly checks: "Is reset token in httpOnly cookie? (historical issue)"
  → Report includes: "✓ Compound Memory Check: Token storage is secure (httpOnly cookie)"
```

### Feature 3: User Profile Edit (even more benefit)

```
/workflows:plan reads memory:
  → "Reliable pattern: Email value object with factory method"
  → Plan references existing Email VO instead of creating new one

/workflows:review reads memory:
  → ddd-compliance checks: "Is Email VO being reused? (proven pattern)"
  → security-review checks: "Token handling secure? (2 historical issues)"
  → Report: "✓ All compound memory checks passed"
```

---

## Memory Lifecycle

```
/workflows:quickstart
  └── Creates compound-memory.md (empty, with project profile)

/workflows:compound (after each feature)
  └── APPENDS pain points and patterns to compound-memory.md
  └── RECALCULATES agent calibration

/workflows:plan (each new feature)
  └── READS compound-memory.md for risk assessment

/workflows:review (each feature review)
  └── READS compound-memory.md for agent calibration
  └── Agents report on historical checks

Manual cleanup (optional)
  └── Team can PRUNE resolved pain points
  └── Team can PROMOTE patterns to rules
```

---

## Promotion: From Memory to Rules

When a pattern or anti-pattern becomes **universal** (applies to ≥5 features or 100% of recent features):

1. **Promote pattern to project rule**: Move from `compound-memory.md` to `rules/global_rules.md`
2. **Mark in memory**: `[PROMOTED to global_rules.md on DATE]`
3. **Agent calibration becomes permanent**: Intensity override moves to default

This prevents compound-memory.md from growing unbounded while ensuring the most important learnings become permanent.

---

## Constraints

1. **compound-memory.md must stay readable** — max 200 lines. Prune old/resolved items.
2. **Agents must not hallucinate memory** — if compound-memory.md doesn't exist or is empty, use default intensity.
3. **Memory is project-scoped** — each project has its own compound-memory.md. No cross-project contamination.
4. **Humans can override** — if the team disagrees with an auto-calibration, they can edit the file directly.
5. **Memory is versioned** — compound-memory.md is committed to git, so the team can review changes.
