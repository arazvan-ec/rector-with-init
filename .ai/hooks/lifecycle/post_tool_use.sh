#!/usr/bin/env bash
# post_tool_use.sh - SDK Lifecycle Hook: Post Tool Use
# Auto-updates state files after tool completion and creates audit trail
#
# This hook is called by Claude Code after each tool execution.
# It receives JSON input on stdin with the following structure:
# {
#   "tool_name": "Edit",
#   "tool_input": { "file_path": "/path/to/file", ... },
#   "tool_result": { ... },
#   "session_id": "abc123"
# }

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AI_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
LOG_DIR="${AI_DIR}/logs"
STATE_DIR="${AI_DIR}/project/features"

# Ensure directories exist
mkdir -p "$LOG_DIR"

# Timestamp for logging
TIMESTAMP=$(date -Iseconds)
AUDIT_LOG="${LOG_DIR}/audit_$(date +%Y%m%d).json"

# Read JSON input from stdin
INPUT_JSON=$(cat)

# Parse JSON using jq
if ! command -v jq &> /dev/null; then
    exit 0  # Skip if jq not available
fi

# Extract fields from input
TOOL_NAME=$(echo "$INPUT_JSON" | jq -r '.tool_name // "unknown"')
TOOL_INPUT=$(echo "$INPUT_JSON" | jq -c '.tool_input // {}')
TOOL_RESULT=$(echo "$INPUT_JSON" | jq -c '.tool_result // {}')
SESSION_ID=$(echo "$INPUT_JSON" | jq -r '.session_id // "unknown"')

# Extract file path if present
FILE_PATH=$(echo "$TOOL_INPUT" | jq -r '.file_path // .path // empty' 2>/dev/null || echo "")

# Create audit log entry
create_audit_entry() {
    local entry
    entry=$(jq -n \
        --arg ts "$TIMESTAMP" \
        --arg session "$SESSION_ID" \
        --arg tool "$TOOL_NAME" \
        --arg file "$FILE_PATH" \
        --argjson input "$TOOL_INPUT" \
        '{
            timestamp: $ts,
            session_id: $session,
            tool_name: $tool,
            file_path: $file,
            tool_input: $input
        }')
    echo "$entry" >> "$AUDIT_LOG"
}

# Find active feature's 50_state.md
find_active_state_file() {
    # Look for most recently modified 50_state.md
    find "$STATE_DIR" -name "50_state.md" -type f 2>/dev/null | \
        xargs ls -t 2>/dev/null | \
        head -1
}

# Update state file with tool activity (lightweight update)
update_state_file() {
    local state_file
    state_file=$(find_active_state_file)

    if [[ -z "$state_file" || ! -f "$state_file" ]]; then
        return 0  # No state file to update
    fi

    # Only update for significant tools
    case "$TOOL_NAME" in
        Edit|Write)
            # Add to modified files tracking if not already present
            if [[ -n "$FILE_PATH" ]]; then
                local modified_section="### Modified Files (Auto-tracked)"
                if ! grep -q "$modified_section" "$state_file" 2>/dev/null; then
                    echo -e "\n$modified_section\n- $FILE_PATH ($TIMESTAMP)" >> "$state_file"
                elif ! grep -q "$FILE_PATH" "$state_file" 2>/dev/null; then
                    sed -i "/$modified_section/a - $FILE_PATH ($TIMESTAMP)" "$state_file"
                fi
            fi
            ;;
        Bash)
            # Track test runs
            local command
            command=$(echo "$TOOL_INPUT" | jq -r '.command // ""')
            if echo "$command" | grep -qE "(phpunit|jest|pytest|npm test|yarn test)"; then
                local test_section="### Test Runs (Auto-tracked)"
                if ! grep -q "$test_section" "$state_file" 2>/dev/null; then
                    echo -e "\n$test_section\n- $TIMESTAMP: $command" >> "$state_file"
                fi
            fi
            ;;
    esac
}

# Main hook logic
main() {
    # Create audit entry for all tool uses
    create_audit_entry

    # Update state file for file modifications
    update_state_file

    exit 0
}

main
