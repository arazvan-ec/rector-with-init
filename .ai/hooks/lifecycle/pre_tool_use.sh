#!/usr/bin/env bash
# pre_tool_use.sh - SDK Lifecycle Hook: Pre Tool Use
# Enforces trust model before file edits and logs all tool uses
#
# This hook is called by Claude Code before each tool execution.
# It receives JSON input on stdin with the following structure:
# {
#   "tool_name": "Edit",
#   "tool_input": { "file_path": "/path/to/file", ... },
#   "session_id": "abc123"
# }
#
# Exit codes:
#   0 = allow the tool to proceed
#   2 = block the tool execution
#
# Output JSON to control the decision:
# { "decision": "allow" } or { "decision": "block", "reason": "..." }

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AI_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
LOG_DIR="${AI_DIR}/logs"

# Ensure log directory exists
mkdir -p "$LOG_DIR"

# Timestamp for logging
TIMESTAMP=$(date -Iseconds)
LOG_FILE="${LOG_DIR}/tool_use_$(date +%Y%m%d).log"

# Low-trust paths that require pair review
LOW_TRUST_PATHS=(
    "auth/"
    "Auth/"
    "security/"
    "Security/"
    "payment/"
    "Payment/"
    "billing/"
    "checkout/"
    "session/"
    "credential/"
    "secret/"
    "migrations/"
    ".env"
)

# Read JSON input from stdin
INPUT_JSON=$(cat)

# Parse JSON using jq (required dependency)
if ! command -v jq &> /dev/null; then
    # If jq not available, allow by default and log warning
    echo "[${TIMESTAMP}] WARNING: jq not installed, skipping trust enforcement" >> "$LOG_FILE"
    echo '{"decision": "allow"}'
    exit 0
fi

# Extract fields from input
TOOL_NAME=$(echo "$INPUT_JSON" | jq -r '.tool_name // "unknown"')
TOOL_INPUT=$(echo "$INPUT_JSON" | jq -c '.tool_input // {}')
SESSION_ID=$(echo "$INPUT_JSON" | jq -r '.session_id // "unknown"')

# Extract file path from tool input
FILE_PATH=$(echo "$TOOL_INPUT" | jq -r '.file_path // .path // empty' 2>/dev/null || echo "")

# Check if path matches low-trust patterns
is_low_trust_path() {
    local filepath="$1"
    for pattern in "${LOW_TRUST_PATHS[@]}"; do
        if [[ "$filepath" == *"$pattern"* ]]; then
            return 0  # true - is low trust
        fi
    done
    return 1  # false - not low trust
}

# Log tool use
log_tool_use() {
    local tool="$1"
    local filepath="$2"
    local trust_level="$3"
    local action="$4"
    echo "[${TIMESTAMP}] session=${SESSION_ID} tool=${tool} file=${filepath:-N/A} trust=${trust_level} action=${action}" >> "$LOG_FILE"
}

# Main hook logic
main() {
    local trust_level="high"
    local decision="allow"
    local reason=""

    # Only check trust for file-modifying tools
    case "$TOOL_NAME" in
        Edit|Write|NotebookEdit)
            if [[ -n "$FILE_PATH" ]]; then
                if is_low_trust_path "$FILE_PATH"; then
                    trust_level="low"

                    # Check for pair review approval
                    if [[ "${PAIR_REVIEW_APPROVED:-}" != "true" ]]; then
                        decision="block"
                        reason="Low-trust path (${FILE_PATH}) requires PAIR_REVIEW_APPROVED=true. Paths matching auth/, security/, payment/ need human review."
                        log_tool_use "$TOOL_NAME" "$FILE_PATH" "$trust_level" "BLOCKED"
                    else
                        log_tool_use "$TOOL_NAME" "$FILE_PATH" "$trust_level" "APPROVED_WITH_REVIEW"
                    fi
                fi
            fi
            ;;
        Bash)
            # Check for potentially dangerous commands
            COMMAND=$(echo "$TOOL_INPUT" | jq -r '.command // ""')
            if echo "$COMMAND" | grep -qE "(rm -rf /|chmod 777|curl.*\| bash|wget.*\| sh)"; then
                trust_level="low"
                decision="block"
                reason="Potentially dangerous command detected: ${COMMAND}"
                log_tool_use "$TOOL_NAME" "command" "$trust_level" "BLOCKED"
            fi
            ;;
    esac

    # Log allowed tool uses
    if [[ "$decision" == "allow" ]]; then
        log_tool_use "$TOOL_NAME" "$FILE_PATH" "$trust_level" "ALLOWED"
    fi

    # Output decision as JSON
    if [[ "$decision" == "allow" ]]; then
        echo '{"decision": "allow"}'
        exit 0
    else
        echo "{\"decision\": \"block\", \"reason\": \"$reason\"}"
        exit 2
    fi
}

main
