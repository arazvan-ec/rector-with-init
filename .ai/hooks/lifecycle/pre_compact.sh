#!/usr/bin/env bash
# pre_compact.sh - SDK Lifecycle Hook: Pre Compact
# Saves context summary before compaction and preserves critical state information
#
# This hook is called by Claude Code before context window compaction.
# It receives JSON input on stdin with the following structure:
# {
#   "session_id": "abc123",
#   "message_count": 50,
#   "token_count": 180000
# }

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AI_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
LOG_DIR="${AI_DIR}/logs"
CONTEXT_DIR="${LOG_DIR}/context_snapshots"
STATE_DIR="${AI_DIR}/project/features"

# Ensure directories exist
mkdir -p "$CONTEXT_DIR"

# Timestamp
TIMESTAMP=$(date -Iseconds)
DATE_STAMP=$(date +%Y%m%d_%H%M%S)

# Read JSON input from stdin
INPUT_JSON=$(cat)

# Parse JSON using jq
if command -v jq &> /dev/null; then
    SESSION_ID=$(echo "$INPUT_JSON" | jq -r '.session_id // "unknown"')
    MESSAGE_COUNT=$(echo "$INPUT_JSON" | jq -r '.message_count // 0')
    TOKEN_COUNT=$(echo "$INPUT_JSON" | jq -r '.token_count // 0')
else
    SESSION_ID="unknown"
    MESSAGE_COUNT="0"
    TOKEN_COUNT="0"
fi

# Find active feature's state files
find_active_state_file() {
    find "$STATE_DIR" -name "50_state.md" -type f 2>/dev/null | \
        xargs ls -t 2>/dev/null | \
        head -1
}

find_active_tasks_file() {
    find "$STATE_DIR" -name "30_tasks.md" -type f 2>/dev/null | \
        xargs ls -t 2>/dev/null | \
        head -1
}

# Create context snapshot before compaction
create_context_snapshot() {
    local snapshot_dir="${CONTEXT_DIR}/${SESSION_ID}_${DATE_STAMP}"
    mkdir -p "$snapshot_dir"

    # Save state file
    local state_file
    state_file=$(find_active_state_file)
    if [[ -n "$state_file" && -f "$state_file" ]]; then
        cp "$state_file" "${snapshot_dir}/50_state.md"
    fi

    # Save tasks file
    local tasks_file
    tasks_file=$(find_active_tasks_file)
    if [[ -n "$tasks_file" && -f "$tasks_file" ]]; then
        cp "$tasks_file" "${snapshot_dir}/30_tasks.md"
    fi

    # Extract current role/status from state file
    local current_role="unknown"
    local current_status="unknown"
    local current_checkpoint="none"

    if [[ -n "$state_file" && -f "$state_file" ]]; then
        current_role=$(grep -E "^## .* Engineer|^## Planner|^## QA" "$state_file" | head -1 | sed 's/## //' || echo "unknown")
        current_status=$(grep -E "^\*\*Status\*\*:" "$state_file" | head -1 | sed 's/.*: //' || echo "unknown")
        current_checkpoint=$(grep -E "^\*\*Checkpoint\*\*:" "$state_file" | head -1 | sed 's/.*: //' || echo "none")
    fi

    # Create compaction metadata
    cat > "${snapshot_dir}/compaction_meta.json" << EOF
{
    "timestamp": "${TIMESTAMP}",
    "session_id": "${SESSION_ID}",
    "message_count": ${MESSAGE_COUNT},
    "token_count": ${TOKEN_COUNT},
    "current_role": "${current_role}",
    "current_status": "${current_status}",
    "current_checkpoint": "${current_checkpoint}",
    "type": "pre_compact_snapshot"
}
EOF

    # Create human-readable summary
    cat > "${snapshot_dir}/CONTEXT_SUMMARY.md" << EOF
# Context Snapshot Before Compaction

**Created**: ${TIMESTAMP}
**Session**: ${SESSION_ID}
**Messages**: ${MESSAGE_COUNT}
**Tokens**: ${TOKEN_COUNT}

## Current State

- **Role**: ${current_role}
- **Status**: ${current_status}
- **Checkpoint**: ${current_checkpoint}

## Files Preserved

- 50_state.md - Role status and progress
- 30_tasks.md - Task breakdown (if exists)

## Resume Instructions

To resume from this snapshot after compaction:

1. Read CONTEXT_SUMMARY.md for current state
2. Read 50_state.md for detailed progress
3. Read 30_tasks.md for remaining tasks
4. Continue from the current checkpoint

EOF

    echo "[PRE_COMPACT HOOK] Context snapshot created: $snapshot_dir"
    echo "[${TIMESTAMP}] session=${SESSION_ID} messages=${MESSAGE_COUNT} tokens=${TOKEN_COUNT} PRE_COMPACT snapshot=${snapshot_dir}" >> "${LOG_DIR}/session_$(date +%Y%m%d).log"
}

# Cleanup old snapshots (keep last 10)
cleanup_old_snapshots() {
    local count
    count=$(find "$CONTEXT_DIR" -maxdepth 1 -type d -name "${SESSION_ID}_*" 2>/dev/null | wc -l)

    if [[ $count -gt 10 ]]; then
        find "$CONTEXT_DIR" -maxdepth 1 -type d -name "${SESSION_ID}_*" | \
            sort | \
            head -n $((count - 10)) | \
            xargs rm -rf 2>/dev/null || true
    fi
}

# Main hook logic
main() {
    # Create context snapshot
    create_context_snapshot

    # Cleanup old snapshots
    cleanup_old_snapshots

    exit 0
}

main
