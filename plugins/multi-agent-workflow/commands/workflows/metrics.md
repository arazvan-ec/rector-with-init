---
name: workflows:metrics
description: "Display workflow performance metrics and analytics for continuous improvement."
argument_hint: "[--feature=name] [--role=name] [--period=7d]"
---

# Multi-Agent Workflow: Metrics

> **Tier 3 (Automatic)**: Metrics are collected automatically and analyzed as part of `/workflows:compound`. Only invoke this command manually for standalone performance analysis.

Analyze workflow performance to identify bottlenecks, patterns, and improvement opportunities.

## Usage

```
/workflows:metrics
/workflows:metrics --feature=user-auth
/workflows:metrics --role=backend --period=30d
/workflows:metrics --feature=payment-flow --role=qa
```

## Options

| Option | Default | Description |
|--------|---------|-------------|
| `--feature` | all | Filter metrics by feature name |
| `--role` | all | Filter by role (planner, backend, frontend, qa) |
| `--period` | 7d | Time period (1d, 7d, 30d, 90d, all) |

## Metrics Dashboard

### Time Spent Per Workflow Stage

Shows duration breakdown by stage and role:

```
Stage Durations (last 7 days)
═══════════════════════════════════════════════════════════════
Feature: user-authentication
  Planning     │████████████░░░░░░░░│ 2h 15m  (25%)
  Backend      │██████████████████░░│ 3h 45m  (42%)
  Frontend     │████████████░░░░░░░░│ 2h 30m  (28%)
  QA           │██░░░░░░░░░░░░░░░░░░│ 0h 25m  (5%)
               └────────────────────┘
  Total: 8h 55m

Average by Role:
  Planner   → 1h 45m per feature
  Backend   → 3h 20m per feature
  Frontend  → 2h 15m per feature
  QA        → 0h 35m per feature
```

### Checkpoint Pass/Fail Rates

Track checkpoint success across the workflow:

```
Checkpoint Results (last 7 days)
═══════════════════════════════════════════════════════════════
                    │ Pass │ Fail │ Rate  │ Avg Attempts │
────────────────────┼──────┼──────┼───────┼──────────────┤
Domain Layer        │  12  │   2  │ 85.7% │    1.4       │
Application Layer   │  10  │   4  │ 71.4% │    2.1       │
Infrastructure      │  11  │   3  │ 78.6% │    1.7       │
API Endpoints       │  13  │   1  │ 92.8% │    1.1       │
Component Structure │   9  │   5  │ 64.3% │    2.5       │
Form Logic          │  11  │   3  │ 78.6% │    1.6       │
API Integration     │   8  │   6  │ 57.1% │    3.2       │
Responsive Design   │  12  │   2  │ 85.7% │    1.3       │
────────────────────┴──────┴──────┴───────┴──────────────┘

Lowest Success: API Integration (57.1%) - Investigate
```

### Ralph Wiggum Pattern Tracking

Monitor auto-correction loop iterations:

```
Iteration Analysis (last 7 days)
═══════════════════════════════════════════════════════════════
Distribution of iterations to pass checkpoints:

  1 iteration  │████████████████████████████████│ 45 (52%)
  2 iterations │████████████████░░░░░░░░░░░░░░░░│ 22 (25%)
  3 iterations │████████░░░░░░░░░░░░░░░░░░░░░░░░│ 11 (13%)
  4 iterations │████░░░░░░░░░░░░░░░░░░░░░░░░░░░░│  5 (6%)
  5+ iterations│██░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░│  3 (3%)
  BLOCKED (10+)│░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░│  1 (1%)
               └────────────────────────────────┘

Average iterations: 1.8
Max iterations before pass: 7
Blocked checkpoints: 1

High-iteration checkpoints (needs attention):
  - API Integration: avg 3.2 iterations
  - Component Structure: avg 2.5 iterations
```

### BLOCKED Frequency and Resolution

Track blockages and resolution times:

```
Blocker Analysis (last 30 days)
═══════════════════════════════════════════════════════════════
Total Blocked Events: 8

By Reason:
  Test failures (unresolvable)  │ 3 │ avg 4.2h to resolve
  Missing API dependency        │ 2 │ avg 2.1h to resolve
  Unclear requirements          │ 2 │ avg 6.5h to resolve
  External service unavailable  │ 1 │ avg 1.0h to resolve

Resolution Methods:
  - Planner clarification: 3
  - Human intervention: 2
  - Architecture change: 2
  - Wait for dependency: 1

Average Time to Resolution: 3.8 hours
Longest Block: 12.5 hours (unclear requirements)
```

### Compound Learning Effectiveness

Measure knowledge capture and reuse:

```
Compound Learning (last 30 days)
═══════════════════════════════════════════════════════════════
Learnings Captured: 24
  - Pattern discoveries: 8
  - Anti-pattern identifications: 5
  - Rule updates: 6
  - Template improvements: 5

Reuse Rate:
  - Patterns applied in subsequent features: 67%
  - Rules preventing repeat issues: 12 instances
  - Time saved estimate: ~8.5 hours

Knowledge Graph Growth:
  Week 1: ████░░░░░░ 15 nodes
  Week 2: ██████░░░░ 28 nodes
  Week 3: ████████░░ 41 nodes
  Week 4: ██████████ 52 nodes

Most Valuable Learnings:
  1. "Repository pattern prevents N+1 queries" (applied 5x)
  2. "Form validation at VO level catches errors early" (applied 4x)
  3. "API contract tests prevent integration issues" (applied 3x)
```

## Execution Steps

### Step 1: Load Metrics Data

Read metrics from `.ai/metrics/`:

```bash
# Check for metrics data
ls -la .ai/metrics/

# Load workflow runs
cat .ai/metrics/workflow_runs.json

# Load stage metrics
cat .ai/metrics/stage_metrics.json
```

### Step 2: Apply Filters

Filter based on provided options:

```yaml
filters:
  feature: ${feature_param}  # null for all
  role: ${role_param}        # null for all
  period:
    value: ${period_param}
    start: calculated_date
    end: now
```

### Step 3: Calculate Metrics

Compute the following metrics:

1. **Time Metrics**
   - Total duration per stage
   - Average duration per role
   - Time distribution percentages

2. **Checkpoint Metrics**
   - Pass/fail counts
   - Success rates
   - Average attempts per checkpoint

3. **Iteration Metrics**
   - Distribution histogram
   - Average iterations
   - Blocked checkpoint count

4. **Blocker Metrics**
   - Frequency by reason
   - Resolution times
   - Resolution methods

5. **Compound Metrics**
   - Learnings captured
   - Reuse statistics
   - Knowledge growth rate

### Step 4: Generate Report

Output formatted report to console with:

- ASCII charts for visualization
- Summary statistics
- Actionable insights
- Recommendations for improvement

## Insights Generation

The metrics command automatically identifies:

### Bottlenecks
```
Bottleneck Detected: API Integration checkpoint
- Success rate: 57.1% (target: 80%)
- Avg iterations: 3.2 (target: <2)
- Recommendation: Review API contract validation process
```

### Trends
```
Trend Analysis:
- Backend checkpoint success improving: +12% over 30 days
- Frontend iteration count decreasing: -0.8 avg over 30 days
- Blocker frequency stable: 2 per week
```

### Anomalies
```
Anomaly Detected:
- Feature "payment-flow" took 2.3x longer than average
- Investigate: 8 iterations on security checkpoint
```

## Data Sources

Metrics are collected from:

1. **50_state.md** - Stage transitions and timestamps
2. **Checkpoint logs** - Pass/fail records and iterations
3. **Git history** - Commit timestamps and patterns
4. **compound_log.md** - Learning entries

## Integration with Collector

The metrics collector skill (`.ai/extensions/metrics/collector.md`) automatically captures data during workflow execution. No manual tracking required.

## Export Options

```bash
# Export to JSON
/workflows:metrics --export=json > metrics_report.json

# Export to CSV
/workflows:metrics --export=csv > metrics_report.csv
```

## Best Practices

1. **Review weekly**: Check metrics at least once per week
2. **Act on insights**: Address bottlenecks identified by metrics
3. **Track trends**: Compare metrics over time for improvement
4. **Share learnings**: Use compound metrics to spread knowledge
5. **Investigate blockers**: Reduce blocker frequency through prevention

## Related Commands

- `/workflows:compound` - Capture learnings for compound metrics
- `/workflows:checkpoint` - Creates checkpoint data points
- `/workflows:status` - Current status (metrics tracks history)
- `/workflows:progress` - Progress view (metrics shows patterns)
