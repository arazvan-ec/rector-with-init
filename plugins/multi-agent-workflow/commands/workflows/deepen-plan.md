---
name: workflows:deepen-plan
description: "Enhance a plan with parallel research agents to add depth, best practices, and implementation details"
argument_hint: "[path to plan file]"
---

# Deepen Plan - Power Enhancement Mode

Enhance an existing plan (from `/workflows:plan`) with parallel research agents. Each major section gets dedicated research to find best practices, performance optimizations, and real-world implementation examples.

## Purpose

Transform a basic plan into a deeply grounded, production-ready specification with:
- Industry best practices and patterns
- Performance considerations and benchmarks
- Edge cases and error handling strategies
- Real-world code examples
- Relevant institutional learnings

## Usage

```bash
/workflows:deepen-plan docs/plans/2026-01-15-feat-auth-plan.md
/workflows:deepen-plan  # Will list recent plans if path not provided
```

## Plan File Discovery

If no path provided:
1. Check for recent plans: `ls -la docs/plans/`
2. Ask user to select from available plans
3. Do not proceed without valid plan file path

## Main Tasks

### 1. Parse and Analyze Plan Structure

Read the plan file and extract:
- [ ] Overview/Problem Statement
- [ ] Proposed Solution sections
- [ ] Technical Approach/Architecture
- [ ] Implementation phases/steps
- [ ] Code examples and file references
- [ ] Acceptance criteria
- [ ] Technologies/frameworks mentioned
- [ ] Domain areas (data models, APIs, UI, security, performance)

**Create a section manifest:**
```
Section 1: [Title] - [What to research]
Section 2: [Title] - [What to research]
...
```

### 2. Apply Available Skills

**Discover all available skills:**
```bash
# Project-local skills
ls .claude/skills/

# Plugin skills
ls ~/.claude/plugins/*/skills/

# User global skills
ls ~/.claude/skills/
```

**Match skills to plan content:**
For each skill, check if plan sections match the skill's domain.

**Spawn a sub-agent for EACH matched skill:**
```
Task general-purpose: "Use the [skill-name] skill at [skill-path].
1. Read the skill: cat [skill-path]/SKILL.md
2. Apply the skill to this plan section:
[plan section content]
3. Return the skill's recommendations"
```

**Launch ALL skill sub-agents in PARALLEL.**

### 3. Search Institutional Learnings

Search `docs/solutions/` for documented learnings relevant to the plan.

**Use the learnings-researcher agent:**
```
Task learnings-researcher: "Search docs/solutions/ for learnings relevant to:
[plan summary]

Return applicable patterns, gotchas, and recommendations."
```

### 4. Launch Per-Section Research Agents

For each major section, spawn dedicated research:

```
Task Explore: "Research best practices for: [section topic].
Find:
- Industry standards and conventions
- Performance considerations
- Common pitfalls to avoid
- Documentation and tutorials
Return concrete, actionable recommendations."
```

**Also research:**
- Framework documentation for mentioned technologies
- Recent (2024-2026) articles and best practices
- Security considerations if applicable

### 5. Run Review Agents on Plan

Run these agents against the plan:
- `pattern-recognition-specialist` - Identify potential patterns
- `code-simplicity-reviewer` - Ensure proposed solution isn't over-engineered
- `agent-native-reviewer` - If building agent features

### 6. Synthesize and Enhance

Collect outputs from all sources:
1. **Skill-based findings** - Code examples, patterns
2. **Institutional learnings** - Past solutions that apply
3. **Research agents** - Best practices, documentation
4. **Review agents** - Feedback on proposed approach

**For each finding, extract:**
- [ ] Concrete recommendations (actionable)
- [ ] Code patterns and examples
- [ ] Anti-patterns to avoid
- [ ] Performance considerations
- [ ] Security considerations
- [ ] Edge cases discovered

### 7. Write Enhanced Plan

For each section, add research insights:

```markdown
## [Original Section Title]

[Original content preserved]

### Research Insights

**Best Practices:**
- [Concrete recommendation 1]
- [Concrete recommendation 2]

**Performance Considerations:**
- [Optimization opportunity]
- [Benchmark or metric to target]

**Implementation Details:**
```[language]
// Concrete code example from research
```

**Edge Cases:**
- [Edge case 1 and handling]
- [Edge case 2 and handling]

**From Institutional Learnings:**
- [Relevant documented solution]
- [Gotcha to avoid]

**References:**
- [Documentation URL 1]
- [Documentation URL 2]
```

### 8. Add Enhancement Summary

At the top of the plan, add:

```markdown
## Enhancement Summary

**Deepened on:** [Date]
**Sections enhanced:** [Count]
**Research agents used:** [List]

### Key Improvements
1. [Major improvement 1]
2. [Major improvement 2]

### New Considerations Discovered
- [Important finding 1]
- [Important finding 2]

### Learnings Applied
- [docs/solutions/file1.md] - [Why relevant]
- [docs/solutions/file2.md] - [Why relevant]
```

### 9. Save Enhanced Plan

**Options:**
- Update in place (recommended)
- Create new file with `-deepened` suffix

## Quality Checks

Before finalizing:
- [ ] All original content preserved
- [ ] Research insights clearly marked
- [ ] Code examples are syntactically correct
- [ ] Links are valid
- [ ] No contradictions between sections
- [ ] Enhancement summary accurate

## Post-Enhancement Options

After writing enhanced plan:

**Question:** "Plan deepened at `[path]`. What next?"

**Options:**
1. **View diff** - Show changes
2. **Start `/workflows:work`** - Begin implementing
3. **Deepen further** - More research on specific sections
4. **Revert** - Restore original plan

## Example Enhancement

**Before (from /workflows:plan):**
```markdown
## Technical Approach

Use React Query for data fetching with optimistic updates.
```

**After (from /workflows:deepen-plan):**
```markdown
## Technical Approach

Use React Query for data fetching with optimistic updates.

### Research Insights

**Best Practices:**
- Configure `staleTime` and `cacheTime` based on data freshness
- Use `queryKey` factories for consistent cache invalidation
- Implement error boundaries around query-dependent components

**Performance Considerations:**
- Enable `refetchOnWindowFocus: false` for stable data
- Use `select` option to memoize data at query level
- Consider `placeholderData` for instant perceived loading

**Implementation Details:**
```typescript
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      retry: 2,
      refetchOnWindowFocus: false,
    },
  },
});
```

**Edge Cases:**
- Handle race conditions with `cancelQueries` on unmount
- Implement retry logic for transient network failures

**From Institutional Learnings:**
- docs/solutions/performance-issues/react-query-cache-stampede.md
  - Use `keepPreviousData` to prevent flicker on pagination

**References:**
- https://tanstack.com/query/latest/docs/react/guides/optimistic-updates
```

## The Compounding Philosophy

Deepening a plan is itself a compounding activity:
- Research done now saves debugging later
- Patterns discovered inform future plans
- Learnings applied prevent repeated mistakes

**Each deepened plan makes future plans easier to create.**
