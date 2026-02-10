#!/usr/bin/env bash
# Progress Manager - Manages claude-progress.txt for session continuity
# Source: Anthropic's Agent Harness pattern
# Feature: workflow-improvements-2026 | Task: BE-002

set -euo pipefail

# Configuration
PROGRESS_DIR="${PROGRESS_DIR:-.ai/project/sessions}"
PROGRESS_FILE="${PROGRESS_DIR}/claude-progress.txt"

# Ensure directory exists
_ensure_dir() {
    mkdir -p "$PROGRESS_DIR"
}

# Get current timestamp in ISO format
_timestamp() {
    date -Iseconds 2>/dev/null || date +"%Y-%m-%dT%H:%M:%S"
}

# Initialize progress tracking for a feature/role
# @param $1 feature_id - Feature identifier
# @param $2 role - Agent role (planner, backend, frontend, qa)
progress_init() {
    local feature_id="${1:?Feature ID required}"
    local role="${2:?Role required}"

    _ensure_dir

    local session_id
    session_id=$(head -c 6 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 8)

    cat > "$PROGRESS_FILE" << EOF
# Claude Progress File
# Auto-generated - Do not edit manually unless necessary

## Session Info
session_id: ${session_id}
feature: ${feature_id}
role: ${role}
started: $(_timestamp)
last_updated: $(_timestamp)

## Current State
status: ACTIVE
current_task: none
task_progress: 0

## Completed This Session
<!-- Tasks completed will be listed here -->

## In Progress
<!-- Current task details -->

## Blockers
none

## Notes for Next Session
<!-- Add notes that should be seen in the next session -->

## Files Modified This Session
<!-- Files touched during this session -->

## Key Decisions Made
<!-- Important decisions to remember -->

## Questions for Human
<!-- Questions that need human input -->
EOF

    echo "Progress initialized for ${feature_id} (${role})"
}

# Update progress for current task
# @param $1 task_id - Task identifier (e.g., BE-001)
# @param $2 status - Status (pending, in_progress, completed, blocked)
# @param $3 notes - Optional notes
progress_update() {
    local task_id="${1:?Task ID required}"
    local status="${2:?Status required}"
    local notes="${3:-}"

    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo "Error: Progress file not found. Run progress_init first." >&2
        return 1
    fi

    # Update last_updated timestamp
    sed -i "s/^last_updated:.*$/last_updated: $(_timestamp)/" "$PROGRESS_FILE"

    # Update current task and status
    sed -i "s/^current_task:.*$/current_task: ${task_id}/" "$PROGRESS_FILE"
    sed -i "s/^status:.*$/status: ${status^^}/" "$PROGRESS_FILE"

    # If completed, add to completed section
    if [[ "${status,,}" == "completed" ]]; then
        local completed_marker="## Completed This Session"
        local entry="- [x] ${task_id}: ${notes:-Completed}"

        # Add entry after the completed section header
        sed -i "/${completed_marker}/a ${entry}" "$PROGRESS_FILE"
    fi

    # If in_progress, update the In Progress section
    if [[ "${status,,}" == "in_progress" ]]; then
        local progress_marker="## In Progress"
        local entry="- [ ] ${task_id}: ${notes:-In progress}"

        # Clear old in-progress and add new
        sed -i "/${progress_marker}/,/^## /{/^- \[ \]/d}" "$PROGRESS_FILE"
        sed -i "/${progress_marker}/a ${entry}" "$PROGRESS_FILE"
    fi

    echo "Progress updated: ${task_id} -> ${status}"
}

# Read current progress as JSON
progress_read() {
    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo '{"error": "Progress file not found"}'
        return 1
    fi

    local session_id feature role started last_updated status current_task

    session_id=$(grep "^session_id:" "$PROGRESS_FILE" | cut -d: -f2 | tr -d ' ')
    feature=$(grep "^feature:" "$PROGRESS_FILE" | cut -d: -f2 | tr -d ' ')
    role=$(grep "^role:" "$PROGRESS_FILE" | cut -d: -f2 | tr -d ' ')
    started=$(grep "^started:" "$PROGRESS_FILE" | cut -d: -f2- | tr -d ' ')
    last_updated=$(grep "^last_updated:" "$PROGRESS_FILE" | cut -d: -f2- | tr -d ' ')
    status=$(grep "^status:" "$PROGRESS_FILE" | cut -d: -f2 | tr -d ' ')
    current_task=$(grep "^current_task:" "$PROGRESS_FILE" | cut -d: -f2 | tr -d ' ')

    # Extract completed tasks
    local completed
    completed=$(grep -A 100 "## Completed This Session" "$PROGRESS_FILE" | \
                grep "^\- \[x\]" | \
                sed 's/^- \[x\] //' | \
                sed 's/:.*//' | \
                tr '\n' ',' | \
                sed 's/,$//' || echo "")

    # Extract notes
    local notes
    notes=$(grep -A 100 "## Notes for Next Session" "$PROGRESS_FILE" | \
            grep "^-" | \
            sed 's/^- //' | \
            head -5 | \
            tr '\n' '|' | \
            sed 's/|$//' || echo "")

    # Extract blockers
    local blockers
    blockers=$(grep "^blockers:" "$PROGRESS_FILE" | cut -d: -f2 | tr -d ' ')
    if [[ "$blockers" == "none" ]]; then
        blockers=""
    fi

    cat << EOF
{
  "session_id": "${session_id}",
  "feature": "${feature}",
  "role": "${role}",
  "started": "${started}",
  "last_updated": "${last_updated}",
  "status": "${status}",
  "current_task": "${current_task}",
  "completed": [$(echo "$completed" | sed 's/\([^,]*\)/"\1"/g')],
  "notes": "${notes}",
  "blockers": "${blockers}"
}
EOF
}

# Save progress explicitly (normally auto-saved on updates)
progress_save() {
    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo "Error: Progress file not found." >&2
        return 1
    fi

    sed -i "s/^last_updated:.*$/last_updated: $(_timestamp)/" "$PROGRESS_FILE"
    echo "Progress saved at $(_timestamp)"
}

# Add note for next session
# @param $1 note - Note text
progress_add_note() {
    local note="${1:?Note required}"

    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo "Error: Progress file not found." >&2
        return 1
    fi

    local notes_marker="## Notes for Next Session"
    local entry="- ${note}"

    sed -i "/${notes_marker}/a ${entry}" "$PROGRESS_FILE"
    sed -i "s/^last_updated:.*$/last_updated: $(_timestamp)/" "$PROGRESS_FILE"

    echo "Note added: ${note}"
}

# Mark file as modified this session
# @param $1 filepath - Path to file
progress_mark_file() {
    local filepath="${1:?Filepath required}"

    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo "Error: Progress file not found." >&2
        return 1
    fi

    local files_marker="## Files Modified This Session"
    local entry="- ${filepath}"

    # Check if already listed
    if grep -q "^- ${filepath}$" "$PROGRESS_FILE"; then
        return 0
    fi

    sed -i "/${files_marker}/a ${entry}" "$PROGRESS_FILE"
    echo "File marked: ${filepath}"
}

# Add decision made during session
# @param $1 decision - Decision description
progress_add_decision() {
    local decision="${1:?Decision required}"

    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo "Error: Progress file not found." >&2
        return 1
    fi

    local decisions_marker="## Key Decisions Made"
    local entry="- ${decision}"

    sed -i "/${decisions_marker}/a ${entry}" "$PROGRESS_FILE"
    echo "Decision recorded: ${decision}"
}

# Set blocker
# @param $1 blocker - Blocker description (or "none" to clear)
progress_set_blocker() {
    local blocker="${1:?Blocker required}"

    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo "Error: Progress file not found." >&2
        return 1
    fi

    local blockers_marker="## Blockers"

    # Remove old blocker line and add new
    sed -i "/${blockers_marker}/,/^## /{/^[^#]/d}" "$PROGRESS_FILE"
    sed -i "/${blockers_marker}/a ${blocker}" "$PROGRESS_FILE"

    if [[ "$blocker" != "none" ]]; then
        sed -i "s/^status:.*$/status: BLOCKED/" "$PROGRESS_FILE"
    fi

    echo "Blocker set: ${blocker}"
}

# Check if progress file exists
progress_exists() {
    [[ -f "$PROGRESS_FILE" ]]
}

# Get progress file path
progress_file_path() {
    echo "$PROGRESS_FILE"
}

# Display human-readable summary
progress_summary() {
    if [[ ! -f "$PROGRESS_FILE" ]]; then
        echo "No active progress tracking."
        return 1
    fi

    echo "=== Session Progress ==="
    grep "^feature:" "$PROGRESS_FILE"
    grep "^role:" "$PROGRESS_FILE"
    grep "^status:" "$PROGRESS_FILE"
    grep "^current_task:" "$PROGRESS_FILE"
    echo ""
    echo "=== Completed This Session ==="
    grep -A 20 "## Completed This Session" "$PROGRESS_FILE" | grep "^\- \[x\]" || echo "None yet"
    echo ""
    echo "=== Notes for Next Session ==="
    grep -A 10 "## Notes for Next Session" "$PROGRESS_FILE" | grep "^-" || echo "None"
}

# Usage information
usage() {
    cat << EOF
Progress Manager - Session continuity for Claude agents

Usage: source progress_manager.sh

Functions:
  progress_init <feature> <role>     Initialize progress tracking
  progress_update <task> <status>    Update task status
  progress_read                       Get progress as JSON
  progress_save                       Explicit save
  progress_add_note <note>           Add note for next session
  progress_mark_file <path>          Mark file as modified
  progress_add_decision <text>       Record a decision
  progress_set_blocker <text>        Set blocker (or "none")
  progress_exists                     Check if progress file exists
  progress_summary                    Display human-readable summary

Status values: pending, in_progress, completed, blocked

Example:
  source .ai/workflow/harness/progress_manager.sh
  progress_init "my-feature" "backend"
  progress_update "BE-001" "in_progress" "Starting implementation"
  progress_mark_file "src/User.php"
  progress_update "BE-001" "completed" "Implementation done"
  progress_add_note "Remember to add tests"
EOF
}

# If script is run directly (not sourced), show usage
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    usage
fi
