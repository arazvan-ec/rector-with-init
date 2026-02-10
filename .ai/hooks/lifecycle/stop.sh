#!/usr/bin/env bash
# stop.sh - SDK Lifecycle Hook: Stop
# Auto-checkpoint when agent stops and verify no BLOCKED status left unhandled
#
# This hook is called by Claude Code when the agent session ends.
# It receives JSON input on stdin with the following structure:
# {
#   "session_id": "abc123",
#   "stop_reason": "user_request" | "error" | "timeout"
# }

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AI_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
LOG_DIR="${AI_DIR}/logs"
STATE_DIR="${AI_DIR}/project/features"
SNAPSHOT_DIR="${AI_DIR}/snapshots"

# Ensure directories exist
mkdir -p "$LOG_DIR" "$SNAPSHOT_DIR"

# Timestamp
TIMESTAMP=$(date -Iseconds)
DATE_STAMP=$(date +%Y%m%d_%H%M%S)

# Read JSON input from stdin
INPUT_JSON=$(cat)

# Parse JSON using jq
if command -v jq &> /dev/null; then
    SESSION_ID=$(echo "$INPUT_JSON" | jq -r '.session_id // "unknown"')
    STOP_REASON=$(echo "$INPUT_JSON" | jq -r '.stop_reason // "unknown"')
else
    SESSION_ID="unknown"
    STOP_REASON="unknown"
fi

# Find active feature's 50_state.md
find_active_state_file() {
    find "$STATE_DIR" -name "50_state.md" -type f 2>/dev/null | \
        xargs ls -t 2>/dev/null | \
        head -1
}

# Check for BLOCKED status in state files
check_for_blockers() {
    local blocked_files=()

    while IFS= read -r state_file; do
        if grep -q "Status.*BLOCKED" "$state_file" 2>/dev/null; then
            blocked_files+=("$state_file")
        fi
    done < <(find "$STATE_DIR" -name "50_state.md" -type f 2>/dev/null)

    if [[ ${#blocked_files[@]} -gt 0 ]]; then
        echo "[STOP HOOK WARNING] Found BLOCKED status in:"
        for f in "${blocked_files[@]}"; do
            echo "  - $f"
        done
        echo ""
        echo "Consider resolving blockers before ending session."

        # Log the warning
        echo "[${TIMESTAMP}] session=${SESSION_ID} BLOCKED_ON_STOP files=${blocked_files[*]}" >> "${LOG_DIR}/session_$(date +%Y%m%d).log"
    fi
}

# Create auto-checkpoint on stop
create_auto_checkpoint() {
    local state_file
    state_file=$(find_active_state_file)

    if [[ -z "$state_file" || ! -f "$state_file" ]]; then
        return 0
    fi

    local feature_dir
    feature_dir=$(dirname "$state_file")
    local feature_name
    feature_name=$(basename "$feature_dir")

    # Create checkpoint directory
    local checkpoint_dir="${SNAPSHOT_DIR}/auto_${feature_name}_${DATE_STAMP}"
    mkdir -p "$checkpoint_dir"

    # Copy state files
    cp "$state_file" "$checkpoint_dir/" 2>/dev/null || true

    # Copy tasks file if exists
    [[ -f "${feature_dir}/30_tasks.md" ]] && cp "${feature_dir}/30_tasks.md" "$checkpoint_dir/" || true

    # Create checkpoint metadata
    cat > "${checkpoint_dir}/checkpoint_meta.json" << EOF
{
    "timestamp": "${TIMESTAMP}",
    "session_id": "${SESSION_ID}",
    "stop_reason": "${STOP_REASON}",
    "feature": "${feature_name}",
    "type": "auto_stop_checkpoint"
}
EOF

    echo "[STOP HOOK] Auto-checkpoint created: $checkpoint_dir"
    echo "[${TIMESTAMP}] session=${SESSION_ID} AUTO_CHECKPOINT dir=${checkpoint_dir}" >> "${LOG_DIR}/session_$(date +%Y%m%d).log"
}

# Log session end
log_session_end() {
    echo "[${TIMESTAMP}] session=${SESSION_ID} reason=${STOP_REASON} SESSION_END" >> "${LOG_DIR}/session_$(date +%Y%m%d).log"
}

# Main hook logic
main() {
    # Log session end
    log_session_end

    # Check for blockers
    check_for_blockers

    # Create auto-checkpoint
    create_auto_checkpoint

    exit 0
}

main
