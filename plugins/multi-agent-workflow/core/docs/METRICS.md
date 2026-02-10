# Workflow Performance Metrics System

> "What gets measured gets managed." — Peter Drucker

This document explains the workflow performance metrics system, how it works, and how to use it for continuous improvement.

## Overview

The metrics system provides visibility into workflow performance by tracking:

- **Time spent** at each stage and role
- **Checkpoint success rates** and iteration patterns
- **Blocker frequency** and resolution effectiveness
- **Compound learning** capture and reuse

## Why Metrics Matter

### For Individual Sessions

- Identify which stages take longest
- Spot patterns in checkpoint failures
- Understand where iterations pile up

### For Team Improvement

- Compare performance across features
- Track improvement over time
- Identify systemic bottlenecks

### For Process Optimization

- Data-driven workflow adjustments
- Evidence for process changes
- ROI measurement for improvements

## Core Metrics Explained

### 1. Time Per Stage

**What it measures**: Duration from stage start to completion for each role.

**Why it matters**: Reveals where time is actually spent vs. where you think it's spent.

**Example insight**:
```
Discovery: Planning takes 40% of total time
Action: This is healthy! Validates 80/20 planning principle
```

**Warning signs**:
- Planning < 20% → Likely underthinking
- Single stage > 50% → Potential bottleneck
- High variance between features → Inconsistent estimation

### 2. Checkpoint Pass/Fail Rates

**What it measures**: Success rate for each checkpoint type.

**Why it matters**: Low pass rates indicate systemic issues with that checkpoint's prerequisites.

**Example insight**:
```
Discovery: API Integration checkpoint has 57% pass rate
Analysis: Frontend often starts before API is stable
Action: Enforce API contract freeze before frontend starts
```

**Healthy targets**:
- First-attempt pass rate: > 60%
- Overall pass rate: > 90%
- Average attempts: < 2.0

### 3. Ralph Wiggum Pattern Tracking

**What it measures**: Number of iterations needed to pass checkpoints.

**Why it matters**: High iteration counts indicate either:
- Unclear requirements
- Insufficient test coverage
- Complex integration points

**Example insight**:
```
Discovery: 25% of checkpoints need 3+ iterations
Analysis: Most are at layer boundaries
Action: Add integration test templates for boundaries
```

**Iteration distribution (healthy project)**:
```
1 iteration:  50-60%  (clean first pass)
2 iterations: 25-30%  (minor fixes)
3 iterations: 10-15%  (moderate issues)
4+ iterations: < 5%   (significant problems)
BLOCKED:       < 1%   (rare edge cases)
```

### 4. Blocked Frequency and Resolution

**What it measures**: How often work gets blocked and how it's resolved.

**Why it matters**: Blockers are the biggest time sinks. Understanding patterns prevents future blocks.

**Example insight**:
```
Discovery: 40% of blocks are "unclear requirements"
Analysis: Requirements ambiguity not caught in planning
Action: Add requirements checklist to planning phase
```

**Resolution method analysis**:
| Method | Typical Time | Prevention |
|--------|--------------|------------|
| Planner clarification | 2-4 hours | Better upfront specs |
| Human intervention | 4-8 hours | Earlier escalation |
| Architecture change | 1-2 days | More thorough design |
| Wait for dependency | Variable | Better dependency tracking |

### 5. Compound Learning Effectiveness

**What it measures**: Knowledge captured and its subsequent reuse.

**Why it matters**: The whole point of compound engineering is that work makes future work easier.

**Example insight**:
```
Discovery: Pattern "Repository prevents N+1" applied 5 times
Impact: Estimated 2 hours saved per application
ROI: 10 hours saved from 30 minute learning capture
```

**Effectiveness indicators**:
- Reuse rate > 50% → High-value learnings
- Reuse rate < 20% → Too specific or poorly documented
- Growing knowledge base → Compound effect working

## Using the Metrics Command

### Basic Usage

```bash
# View all metrics (last 7 days)
/workflows:metrics

# Filter by feature
/workflows:metrics --feature=user-authentication

# Filter by role
/workflows:metrics --role=backend

# Change time period
/workflows:metrics --period=30d

# Combine filters
/workflows:metrics --feature=payments --role=qa --period=90d
```

### Reading the Dashboard

The metrics command outputs five sections:

1. **Time Distribution** - Bar chart showing time per stage
2. **Checkpoint Results** - Table of pass/fail rates
3. **Iteration Analysis** - Histogram of iteration counts
4. **Blocker Analysis** - Breakdown of blocked events
5. **Compound Learning** - Knowledge capture stats

### Actionable Insights

The command automatically highlights:

- **Bottlenecks**: Stages exceeding time thresholds
- **Low success rates**: Checkpoints below 80%
- **High iteration counts**: Averages above 2.0
- **Frequent blockers**: Recurring blocker categories
- **Underutilized learnings**: Captured but not reused

## Data Collection

### Automatic Collection

The metrics collector (`collector.md`) automatically captures:

| Event | Data Captured |
|-------|---------------|
| Workflow start | Run ID, feature, timestamp |
| Role activation | Stage start, role |
| Status change | Duration, transitions |
| Checkpoint | Pass/fail, iterations |
| Block | Reason, category |
| Resolution | Method, time to resolve |
| Compound capture | Learning details |

### Manual Recording

For edge cases not automatically captured:

```bash
/metrics:record checkpoint --stage=domain --passed=true --attempts=2
/metrics:record blocked --reason="Waiting for API spec"
/metrics:record learning --title="..." --description="..."
```

## Analyzing Workflow Performance

### Weekly Review Process

1. **Run metrics command**
   ```bash
   /workflows:metrics --period=7d
   ```

2. **Check for anomalies**
   - Any checkpoint < 70% pass rate?
   - Any stage > 40% of total time?
   - More than 2 blockers?

3. **Investigate issues**
   - Drill into specific features
   - Look at iteration patterns
   - Review blocker categories

4. **Take action**
   - Update rules/templates
   - Add to compound learnings
   - Adjust process if needed

### Monthly Trend Analysis

```bash
# Compare last 4 weeks
/workflows:metrics --period=7d   # This week
/workflows:metrics --period=14d  # Include last week
/workflows:metrics --period=30d  # Full month

# Look for trends:
# - Is pass rate improving?
# - Is average iteration decreasing?
# - Are blockers becoming less frequent?
```

### Feature Retrospective

After completing a feature:

```bash
/workflows:metrics --feature=completed-feature

# Review:
# - Total time vs estimate
# - Which stages took longest
# - Checkpoint issues encountered
# - Blockers and resolutions
# - Learnings captured
```

## Example Insights and Actions

### Insight: High Iteration Count on Application Layer

```
Observation: Application layer checkpoints average 3.2 iterations
Analysis: Use cases often fail validation rules
Root cause: Domain layer tests don't cover edge cases
Action: Add edge case test template to domain layer checkpoint
Result: Application layer iterations dropped to 1.8
```

### Insight: Frequent API Integration Blocks

```
Observation: 35% of frontend blocks are "waiting for API"
Analysis: Frontend starts before API is stable
Root cause: No formal API readiness signal
Action: Add API_READY flag to state machine
Result: API integration blocks reduced by 60%
```

### Insight: Low Compound Learning Reuse

```
Observation: Only 20% of learnings are reused
Analysis: Learnings too specific or hard to find
Root cause: Poor categorization and search
Action: Improve learning templates, add tags
Result: Reuse rate increased to 55%
```

## Metrics Storage

Data is stored in `.ai/metrics/`:

```
.ai/metrics/
├── workflow_runs.json      # Workflow-level records
├── stage_metrics.json      # Stage timing and status
├── checkpoint_results.json # Checkpoint pass/fail
├── blocked_events.json     # Blocker tracking
└── compound_learnings.json # Knowledge capture
```

### Data Retention

- Default retention: 90 days
- Aggregated summaries: Kept indefinitely
- Can be exported for external analysis

### Privacy

- No sensitive data captured
- No external transmission
- Can be cleared: `/metrics:clear`

## Integration with Other Commands

| Command | Metrics Integration |
|---------|---------------------|
| `/workflows:plan` | Creates workflow_run record |
| `/workflows:work` | Creates stage_metrics records |
| `/workflows:checkpoint` | Creates checkpoint_results |
| `/workflows:status` | Reads current stage metrics |
| `/workflows:compound` | Creates compound_learnings |
| `/workflows:progress` | Uses stage timing data |

## Best Practices

### Do

- Review metrics weekly at minimum
- Act on insights, not just observe
- Share insights with team (if applicable)
- Update learnings when patterns emerge
- Use metrics to justify process changes

### Don't

- Obsess over individual data points
- Optimize for metrics over outcomes
- Ignore context when interpreting data
- Compare metrics across different project types
- Use metrics punitively

## Troubleshooting

### Missing Data

If metrics seem incomplete:

1. Check collector is active: `ls .ai/metrics/`
2. Verify state file updates: `cat .ai/project/features/*/50_state.md`
3. Run manual collection for missing events

### Inconsistent Timing

If durations seem wrong:

1. Check timezone consistency
2. Verify session boundaries are clear
3. Account for breaks/context switches

### Low Quality Insights

If insights aren't actionable:

1. Increase data collection period
2. Be more specific with filters
3. Look for patterns, not individual events

## Related Documentation

- [COMPREHENSION_DEBT.md](./COMPREHENSION_DEBT.md) - Context management
- [PAIRING_PATTERNS.md](./PAIRING_PATTERNS.md) - Multi-agent coordination
- [GIT_WORKFLOW.md](./GIT_WORKFLOW.md) - Version control integration
- [WORKFLOW_DECISION_MATRIX.md](./WORKFLOW_DECISION_MATRIX.md) - Workflow selection

## Summary

The metrics system transforms workflow execution from a black box into an observable, improvable process. By consistently measuring performance and acting on insights, each workflow execution contributes to making future work easier and faster.

> "Each unit of engineering work should make subsequent units easier—not harder."
> — The Compound Engineering Principle

Use metrics to ensure you're living up to this principle.
