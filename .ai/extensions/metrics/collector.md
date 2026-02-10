# Metrics Collector Skill

Automatically collects workflow performance metrics during execution.

## Purpose

This skill integrates with the workflow system to capture performance data without manual intervention. It monitors state changes in `50_state.md` and records metrics to `.ai/metrics/`.

## What This Skill Does

- Monitors workflow state transitions
- Records timing for each stage and checkpoint
- Tracks iteration counts (Ralph Wiggum pattern)
- Captures blocked events and resolutions
- Logs compound learnings and their reuse

## Integration Points

### 1. Workflow Start Hook

When a workflow begins (via `/workflows:plan` or `/workflows:work`):

```yaml
trigger: workflow_start
actions:
  - create_workflow_run:
      id: generate_uuid()
      feature: ${feature_name}
      workflow_type: ${workflow_type}
      start: now()
      status: in_progress
      participants: []
  - store: .ai/metrics/workflow_runs.json
```

### 2. Stage Transition Hook

When `50_state.md` status changes:

```yaml
trigger: state_change
watch_file: .ai/project/features/${feature}/50_state.md
watch_pattern: "**Status**: `(\\w+)`"

actions:
  - on_status_change:
      from: [PENDING]
      to: [IN_PROGRESS]
      action: record_stage_start

  - on_status_change:
      from: [IN_PROGRESS]
      to: [COMPLETED, BLOCKED, WAITING_API]
      action: record_stage_end
```

#### Record Stage Start

```javascript
function record_stage_start(role, feature, workflow_id) {
  const stage_record = {
    id: generate_uuid(),
    workflow_id: workflow_id,
    stage: determine_stage(role),
    role: role,
    start: new Date().toISOString(),
    end: null,
    iterations: 1,
    status: 'in_progress'
  };

  append_to_file('.ai/metrics/stage_metrics.json', stage_record);

  // Update workflow participants
  update_workflow_participants(workflow_id, role);
}
```

#### Record Stage End

```javascript
function record_stage_end(role, feature, workflow_id, final_status) {
  const stage = get_active_stage(workflow_id, role);

  stage.end = new Date().toISOString();
  stage.duration_minutes = calculate_duration(stage.start, stage.end);
  stage.status = map_status(final_status);

  update_file('.ai/metrics/stage_metrics.json', stage);
}
```

### 3. Checkpoint Hook

When checkpoint validation occurs:

```yaml
trigger: checkpoint_validation
watch_pattern: "Checkpoint: (.+)"

actions:
  - record_checkpoint:
      stage_id: current_stage.id
      checkpoint: ${checkpoint_name}
      timestamp: now()
      passed: ${test_result}
      attempts: ${iteration_count}
```

#### Checkpoint Recording Logic

```javascript
function record_checkpoint(stage_id, checkpoint_name, passed, attempts, failure_reasons) {
  const checkpoint_record = {
    id: generate_uuid(),
    stage_id: stage_id,
    checkpoint: checkpoint_name,
    checkpoint_type: 'blocking',
    timestamp: new Date().toISOString(),
    passed: passed,
    attempts: attempts,
    failure_reasons: failure_reasons,
    resolution: passed ? {
      method: attempts > 1 ? 'auto_fix' : 'first_pass',
      time_to_resolve_minutes: calculate_resolution_time(attempts)
    } : null
  };

  append_to_file('.ai/metrics/checkpoint_results.json', checkpoint_record);

  // Update stage iteration count
  update_stage_iterations(stage_id, attempts);
}
```

### 4. Blocked Event Hook

When work is marked as BLOCKED:

```yaml
trigger: blocked_declaration
watch_pattern: "**Status**: `BLOCKED`"

actions:
  - record_blocked_event:
      stage_id: current_stage.id
      workflow_id: current_workflow.id
      reason: extract_blocker_reason()
      iterations_before_block: current_iterations
```

#### Blocked Event Recording

```javascript
function record_blocked_event(stage_id, workflow_id, reason, iterations) {
  const blocked_record = {
    id: generate_uuid(),
    stage_id: stage_id,
    workflow_id: workflow_id,
    blocked_at: new Date().toISOString(),
    reason: reason,
    reason_category: categorize_reason(reason),
    iterations_before_block: iterations,
    last_error: extract_last_error(),
    attempted_solutions: extract_attempted_solutions(),
    resolved_at: null,
    resolution: null
  };

  append_to_file('.ai/metrics/blocked_events.json', blocked_record);
}

function categorize_reason(reason) {
  const categories = {
    'test': 'test_failures_unresolvable',
    'api': 'missing_api_dependency',
    'requirement': 'unclear_requirements',
    'service': 'external_service_unavailable',
    'architecture': 'architecture_decision_needed',
    'approval': 'human_approval_required'
  };

  for (const [keyword, category] of Object.entries(categories)) {
    if (reason.toLowerCase().includes(keyword)) {
      return category;
    }
  }
  return 'other';
}
```

### 5. Block Resolution Hook

When BLOCKED status changes to another status:

```yaml
trigger: block_resolution
watch_pattern: status_change
condition: previous_status == 'BLOCKED'

actions:
  - update_blocked_event:
      resolved_at: now()
      resolution: extract_resolution_info()
```

### 6. Compound Learning Hook

When `/workflows:compound` captures learnings:

```yaml
trigger: compound_capture
command: /workflows:compound

actions:
  - record_learning:
      workflow_id: current_workflow.id
      category: ${learning_type}
      title: ${learning_title}
      description: ${learning_description}
```

## State File Monitoring

The collector watches `50_state.md` for these patterns:

```markdown
## Patterns Monitored

### Status Changes
Pattern: `**Status**: \`(\w+)\``
Captures: PENDING, IN_PROGRESS, BLOCKED, COMPLETED, etc.

### Checkpoint Entries
Pattern: `**Checkpoint**: (.+)`
Captures: Checkpoint name and associated data

### Iteration Counts
Pattern: `**Iterations**: (\d+)`
Captures: Number of auto-correction iterations

### Blocker Information
Pattern: `## Blocker: (.+)`
Captures: Blocker title and details

### Test Results
Pattern: `**Tests**: (\d+)/(\d+) passing`
Captures: Test pass/fail counts
```

## Data Storage

All metrics are stored in `.ai/metrics/`:

```
.ai/metrics/
├── workflow_runs.json      # Top-level workflow records
├── stage_metrics.json      # Stage-level details
├── checkpoint_results.json # Checkpoint pass/fail records
├── blocked_events.json     # Blocker tracking
└── compound_learnings.json # Knowledge capture records
```

### File Format

Each file contains a JSON array of records:

```json
{
  "version": "1.0",
  "updated_at": "2026-01-28T12:00:00Z",
  "records": [
    { "id": "...", ... },
    { "id": "...", ... }
  ]
}
```

## Automatic Collection Triggers

| Event | Trigger | Data Collected |
|-------|---------|----------------|
| Workflow start | `/workflows:plan`, `/workflows:work` | workflow_run record |
| Role activation | `/workflows:role` | stage start time |
| Status change | 50_state.md update | stage timing, status |
| Test run | Test command execution | checkpoint result |
| Iteration | Auto-correction loop | iteration count |
| Block declared | BLOCKED status | blocked_event record |
| Block resolved | Status change from BLOCKED | resolution details |
| Compound capture | `/workflows:compound` | learning record |
| Workflow complete | All roles COMPLETED | workflow end time |

## Manual Collection

For cases where automatic collection misses data:

```bash
# Record a checkpoint manually
/metrics:record checkpoint --stage=domain_layer --passed=true --attempts=2

# Record a blocked event
/metrics:record blocked --reason="Missing API specification"

# Record a learning
/metrics:record learning --category=pattern --title="..." --description="..."
```

## Data Integrity

### Validation Rules

1. **Workflow IDs**: All stage/checkpoint records must reference valid workflow_id
2. **Timestamps**: End time must be after start time
3. **Iterations**: Must be between 1 and 10
4. **Status transitions**: Must follow valid state machine

### Error Handling

```javascript
function safe_record(file, record) {
  try {
    validate_record(record);
    append_to_file(file, record);
  } catch (error) {
    log_error('metrics_collector', error);
    // Store in pending queue for retry
    append_to_file('.ai/metrics/.pending', {
      target_file: file,
      record: record,
      error: error.message,
      timestamp: new Date().toISOString()
    });
  }
}
```

## Performance Considerations

- **Async writes**: Metrics collection does not block workflow execution
- **Batch updates**: Multiple rapid changes are batched (100ms window)
- **File locking**: Prevents concurrent write conflicts
- **Size limits**: Files are rotated when they exceed 10MB

## Privacy and Security

- No sensitive data (credentials, tokens) is captured
- User identifiers are anonymized
- Metrics can be cleared with `/metrics:clear`
- Data stays local (not sent externally)
