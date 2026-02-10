---
name: workflows:monitor
description: "Monitor status of parallel agents in real-time"
argument_hint: [--watch] [--json] [--diagnose]
---

# Multi-Agent Workflow: Monitor

> **Tier 3 (Automatic)**: Parallel agent monitoring is handled automatically by `/workflows:work --mode=roles`. Only invoke manually for real-time dashboard visualization.

Monitor the status of all parallel agents running in a workflow session.

## Usage

```bash
# Show dashboard (default)
/workflows:monitor

# Watch mode (auto-refresh every 5s)
/workflows:monitor --watch

# Get JSON status
/workflows:monitor --json

# Diagnose issues
/workflows:monitor --diagnose

# Summary report
/workflows:monitor --summary
```

## Dashboard Output

```
╔════════════════════════════════════════════════════════════════════╗
║               PARALLEL AGENT MONITOR                               ║
╚════════════════════════════════════════════════════════════════════╝

Last Updated: 2026-01-27 14:30:00

Active Agents:
─────────────────────────────────────────────────────────────────────
ROLE         STATUS     PORT     CURRENT TASK         WORKTREE
─────────────────────────────────────────────────────────────────────
backend      active     3001     BE-012               clean
frontend     active     3002     FE-005               dirty
qa           BLOCKED    3003     QA-003               clean
─────────────────────────────────────────────────────────────────────

Summary:
  Total:   3
  Active:  2
  Blocked: 1

⚠ BLOCKERS DETECTED:
  qa:
    Waiting for backend API to be ready

Port Allocations:
  "backend": 3001
  "frontend": 3002
  "qa": 3003

Tmux Sessions:
  workflow-auth-feature (3 windows)

Refresh: 5s | Press Ctrl+C to exit
```

## Watch Mode

Continuously monitor all agents:

```bash
/workflows:monitor --watch
```

This mode:
- Refreshes every 5 seconds (configurable via `MONITOR_REFRESH`)
- Clears screen between updates
- Shows real-time status changes
- Press Ctrl+C to exit

## JSON Output

Get machine-readable status:

```bash
/workflows:monitor --json
```

Returns:
```json
[
  {
    "role": "backend",
    "status": "active",
    "worktree": {
      "exists": true,
      "path": "/project/.worktrees/backend",
      "branch": "feature/auth-backend",
      "clean": true
    },
    "port": 3001,
    "progress": {
      "current_task": "BE-012",
      "status": "active"
    },
    "has_blockers": false
  }
]
```

## Diagnose Mode

Detect issues and get recommendations:

```bash
/workflows:monitor --diagnose
```

Checks for:
- Many uncommitted changes
- Blocked agents
- Port conflicts
- Stale worktrees

## Status Types

| Status | Color | Description |
|--------|-------|-------------|
| **active** | Green | Agent is working normally |
| **blocked** | Red | Agent is waiting/blocked |
| **inactive** | Yellow | Worktree exists but no activity |

## Blocker Detection

The monitor detects blockers by scanning progress files for:
- "blocked" keyword
- "waiting" keyword
- "dependency" keyword

Example progress file entry that triggers blocker:
```markdown
## Status
BLOCKED - Waiting for backend API endpoint

## Blockers
- Need /api/users endpoint from backend agent
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `MONITOR_REFRESH` | 5 | Refresh interval in seconds |
| `PORT_RANGE_START` | 3001 | First port in allocation range |
| `PORT_RANGE_END` | 3010 | Last port in allocation range |

## Implementation

This command executes:

```bash
source .ai/workflow/parallel/monitor.sh

case "$ARGUMENTS" in
    --watch)
        monitor_watch
        ;;
    --json)
        monitor_all_status
        ;;
    --diagnose)
        monitor_diagnose
        ;;
    --summary)
        monitor_summary
        ;;
    *)
        monitor_dashboard
        ;;
esac
```

## Programmatic Use

```bash
source .ai/workflow/parallel/monitor.sh

# Get all agent statuses
agents=$(monitor_all_status)
echo "$agents" | jq '.[].role'

# Get single agent status
status=$(monitor_agent_status "backend")
echo "$status" | jq '.has_blockers'

# Check for any blockers
if monitor_all_status | jq -e '.[] | select(.has_blockers == true)' > /dev/null; then
    echo "Some agents are blocked!"
fi
```

## Integration with CI/CD

Use the monitor in CI pipelines:

```yaml
# Check no agents are blocked before merge
- name: Check Agent Status
  run: |
    source .ai/workflow/parallel/monitor.sh
    blocked=$(monitor_all_status | jq '[.[] | select(.status == "blocked")] | length')
    if [[ "$blocked" -gt 0 ]]; then
      echo "Cannot merge: $blocked agents are blocked"
      exit 1
    fi
```

## Related Commands

- `/workflows:parallel` - Launch parallel agents
- `/workflows:progress` - Track session progress
- `/workflows:status` - Overall workflow status

## Source

Part of the Parallel Agents System implementing:
- [workmux](https://github.com/raine/workmux) patterns
- Anthropic Agent Harness patterns
