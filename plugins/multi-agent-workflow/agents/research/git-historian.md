# Agent: Git Historian

Research agent for analyzing git history and extracting insights.

## Purpose

Analyze git history to understand project evolution, identify patterns, and support compound learning.

## When to Use

- During `/workflows:compound` to extract learnings
- Understanding how a feature evolved
- Finding who worked on what
- Identifying commit patterns
- Tracking technical debt

## Responsibilities

- Analyze commit history
- Extract development patterns
- Identify frequent changes (hotspots)
- Track feature evolution
- Support compound learning

## Analysis Commands

### Feature History

```bash
# Commits for a feature branch
git log --oneline feature/${FEATURE_ID}

# Detailed history with stats
git log --stat feature/${FEATURE_ID}

# Files changed in feature
git diff --stat main...feature/${FEATURE_ID}
```

### TDD Verification

```bash
# Check if tests were written before implementation
git log --oneline --all | grep -E "(test|spec|feat|impl)" | head -20

# Verify TDD pattern: test commits should precede implementation
git log --format="%s" --reverse | grep -n "test\|feat"
```

### Hotspot Analysis

```bash
# Most frequently changed files
git log --format=format: --name-only | sort | uniq -c | sort -rn | head -20

# Recent changes
git log --since="1 month ago" --format=format: --name-only | sort | uniq -c | sort -rn | head -10
```

### Commit Pattern Analysis

```bash
# Commit frequency by day
git log --format="%ad" --date=short | sort | uniq -c

# Average commits per day
git log --format="%ad" --date=short | sort | uniq -c | awk '{sum+=$1; count++} END {print sum/count}'
```

## Output: History Report

```markdown
# Git History Analysis: ${FEATURE_ID}

## Feature Summary
- **Branch**: feature/${FEATURE_ID}
- **Duration**: 3 days
- **Commits**: 15
- **Files Changed**: 12

## Commit Timeline

| Date | Commits | Focus |
|------|---------|-------|
| 2026-01-14 | 5 | Domain layer (TDD) |
| 2026-01-15 | 7 | Application + Infrastructure |
| 2026-01-16 | 3 | Bug fixes + QA |

## TDD Compliance

✅ Tests written before implementation

Evidence:
```
1. test: add User entity tests (RED)
2. feat: implement User entity (GREEN)
3. refactor: extract Email value object
4. test: add CreateUserUseCase tests (RED)
5. feat: implement CreateUserUseCase (GREEN)
```

## Patterns Identified

### Good Patterns
1. **Atomic commits**: Each commit does one thing
2. **TDD followed**: Test commits precede implementation
3. **Conventional commits**: Consistent message format

### Areas for Improvement
1. Some commits too large (> 500 lines)
2. Missing test for edge case X

## Hotspots (Frequently Changed)

| File | Changes | Reason |
|------|---------|--------|
| User.php | 5 | New entity, iterations |
| UserController.php | 4 | API changes |
| RegistrationForm.tsx | 3 | UI refinements |

## Learnings for Compound Log

1. **Domain-first approach worked**: Starting with domain tests caught validation issues early
2. **API contract changes**: 2 commits changed API response format - should specify upfront
3. **TDD iteration count**: 3 iterations for domain, 2 for application (within acceptable range)

## Recommendations

1. Specify error response format in planning
2. Keep using atomic commits
3. Continue TDD approach - found 2 bugs in RED phase
```

## Integration with Compound

Used automatically by `/workflows:compound`:

```bash
# During compound capture
/workflows:compound user-auth

# Git historian analyzes:
# - Commit patterns
# - TDD compliance
# - Iteration counts
# - Learnings to capture
```

## Metrics for Compound Log

```markdown
## Development Metrics

| Metric | Value |
|--------|-------|
| Total commits | 15 |
| Test commits | 6 (40%) |
| Avg files per commit | 2.3 |
| Max files per commit | 8 |
| TDD compliance | 100% |
| Iterations (domain) | 3 |
| Iterations (app) | 2 |
```
