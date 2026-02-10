---
name: workflows:progress
description: "View or update session progress tracking for long-running agent sessions"
argument_hint: [--show | --update <task> <status> | --note <text> | --file <path>]
---

# Multi-Agent Workflow: Progress

> **Tier 3 (Automatic)**: Progress is tracked automatically via `50_state.md` updates inside `/workflows:work`. Use `/workflows:status` to view progress. Only invoke this command manually for fine-grained task tracking in long sessions.

Track and manage session progress across context windows using the Agent Harness pattern.

## Usage

```bash
# Show current progress (default)
/workflows:progress
/workflows:progress --show

# Update task status
/workflows:progress --update BE-001 completed
/workflows:progress --update BE-002 in_progress "Starting implementation"

# Add note for next session
/workflows:progress --note "Remember to add error handling"

# Mark file as modified
/workflows:progress --file src/User.php
```

## What This Command Does

### --show (default)

Displays the current contents of `claude-progress.txt`:

```
=== Session Progress ===
Feature: workflow-improvements-2026
Role: backend
Status: IN_PROGRESS
Current Task: BE-003

=== Completed This Session ===
- [x] BE-001: Create harness module structure
- [x] BE-002: Implement progress_manager.sh

=== Notes for Next Session ===
- Complete cleanup_worktree() function
- Add error handling for edge cases

=== Files Modified ===
- .ai/workflow/harness/progress_manager.sh
- .ai/workflow/parallel/worktree_manager.sh
```

### --update <task> <status> [notes]

Updates the status of a task:

**Valid statuses:**
- `pending` - Not started
- `in_progress` - Currently working
- `completed` - Done
- `blocked` - Stuck, needs help

**Example:**
```bash
/workflows:progress --update BE-001 completed "All tests passing"
```

### --note <text>

Adds a note that will be displayed at the start of the next session:

```bash
/workflows:progress --note "API contract changed, update frontend"
```

### --file <path>

Marks a file as modified during this session for tracking:

```bash
/workflows:progress --file src/Domain/Entity/User.php
```

## Session Continuity

The progress system enables **session continuity** across context windows:

1. **First Session**: Run `/workflows:work` which calls `initializer.sh`
   - Creates progress file
   - Sets up feature workspace
   - Shows initial context

2. **Subsequent Sessions**: Progress is automatically restored
   - `coder.sh` loads previous progress
   - Shows what was completed
   - Displays notes from last session
   - Suggests next actions

3. **Between Sessions**: All state persists in `claude-progress.txt`
   - Tasks completed
   - Notes added
   - Files modified
   - Decisions made

## Implementation

This command executes:

```bash
HARNESS_DIR=".ai/workflow/harness"

case "$ARGUMENTS" in
    --show|"")
        source "${HARNESS_DIR}/progress_manager.sh"
        progress_summary
        ;;
    --update)
        source "${HARNESS_DIR}/progress_manager.sh"
        progress_update "$TASK_ID" "$STATUS" "$NOTES"
        ;;
    --note)
        source "${HARNESS_DIR}/progress_manager.sh"
        progress_add_note "$NOTE_TEXT"
        ;;
    --file)
        source "${HARNESS_DIR}/progress_manager.sh"
        progress_mark_file "$FILE_PATH"
        ;;
esac
```

## Progress File Location

```
.ai/project/sessions/claude-progress.txt
```

## JSON API

For programmatic access:

```bash
source .ai/workflow/harness/progress_manager.sh
progress_read | jq '.'
```

Returns:
```json
{
  "session_id": "abc123",
  "feature": "workflow-improvements-2026",
  "role": "backend",
  "status": "IN_PROGRESS",
  "current_task": "BE-003",
  "completed": ["BE-001", "BE-002"],
  "notes": "Complete cleanup function|Add error handling"
}
```

## Tips

1. **Update frequently**: Mark progress as you complete tasks
2. **Leave notes**: Future you will thank present you
3. **Track files**: Helps understand what changed
4. **Check status**: Run `/workflows:progress` to see where you are

## Related Commands

- `/workflows:work` - Start working (initializes progress)
- `/workflows:status` - Overall workflow status
- `/workflows:sync` - Sync with other agents

## Source

Based on Anthropic's [Agent Harness pattern](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents) for long-running agents.
