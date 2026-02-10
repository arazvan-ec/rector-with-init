#!/usr/bin/env bash
# Compound Tracker - Capture learnings and track compound progress
# Feature: workflow-improvements-2026 | Task: BE-020
# Inspired by: Dan Shipper's Compound Engineering concept

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
PROJECT_ROOT="$(cd "${WORKFLOW_DIR}/../.." && pwd)"
PROJECT_DIR="$(dirname "$WORKFLOW_DIR")/project"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
LEARNINGS_FILE="${PROJECT_DIR}/learnings.yaml"
METRICS_FILE="${PROJECT_DIR}/metrics.json"
CLAUDE_MD="${PROJECT_ROOT}/CLAUDE.md"

# Initialize learnings file
_init_learnings() {
    mkdir -p "$(dirname "$LEARNINGS_FILE")"
    if [[ ! -f "$LEARNINGS_FILE" ]]; then
        cat > "$LEARNINGS_FILE" << EOF
# Compound Learnings
# Auto-captured patterns and learnings from AI sessions

version: "1.0"
created: $(date -Iseconds)

learnings: []

patterns: []

preferences: []
EOF
    fi
}

# Initialize metrics file
_init_metrics() {
    mkdir -p "$(dirname "$METRICS_FILE")"
    if [[ ! -f "$METRICS_FILE" ]]; then
        cat > "$METRICS_FILE" << EOF
{
  "version": "1.0",
  "created": "$(date -Iseconds)",
  "sessions": {
    "total": 0,
    "successful": 0,
    "failed": 0
  },
  "tasks": {
    "total": 0,
    "first_pass_success": 0,
    "required_iteration": 0
  },
  "bugs": {
    "total_fixed": 0,
    "by_category": {}
  },
  "patterns_captured": 0,
  "last_updated": "$(date -Iseconds)"
}
EOF
    fi
}

# Capture a learning from bug fix
# @param $1 description - What was learned
# @param $2 category - Category (bug_fix, pattern, preference, etc.)
# @param $3 file_context - Related file (optional)
compound_capture_learning() {
    local description="${1:?Description required}"
    local category="${2:-general}"
    local file_context="${3:-}"

    _init_learnings

    local timestamp
    timestamp=$(date -Iseconds)

    # Append to learnings file
    cat >> "$LEARNINGS_FILE" << EOF

  - timestamp: "$timestamp"
    category: "$category"
    description: |
      $description
    context: "$file_context"
EOF

    echo -e "${GREEN}Learning captured: ${description:0:50}...${NC}"

    # Update metrics
    _update_metrics "patterns_captured" "increment"
}

# Capture a bug fix pattern
# @param $1 error_type - Type of error fixed
# @param $2 fix_description - How it was fixed
# @param $3 prevention - How to prevent in future
compound_capture_bug_fix() {
    local error_type="${1:?Error type required}"
    local fix_description="${2:?Fix description required}"
    local prevention="${3:-}"

    _init_learnings

    local timestamp
    timestamp=$(date -Iseconds)

    # Record the bug fix
    cat >> "$LEARNINGS_FILE" << EOF

  - timestamp: "$timestamp"
    category: "bug_fix"
    error_type: "$error_type"
    fix: |
      $fix_description
    prevention: |
      $prevention
EOF

    echo -e "${GREEN}Bug fix pattern captured: ${error_type}${NC}"

    # Update metrics
    _update_metrics "bugs.total_fixed" "increment"
}

# Update CLAUDE.md with new pattern
# @param $1 section - Section to update (Common Patterns, Preferences, etc.)
# @param $2 content - Content to add
compound_update_claude_md() {
    local section="${1:?Section required}"
    local content="${2:?Content required}"

    if [[ ! -f "$CLAUDE_MD" ]]; then
        echo -e "${YELLOW}Warning: CLAUDE.md not found at ${CLAUDE_MD}${NC}"
        return 1
    fi

    # Check if section exists
    if grep -q "^## $section" "$CLAUDE_MD" 2>/dev/null; then
        # Find section and append
        local temp_file
        temp_file=$(mktemp)

        awk -v section="$section" -v content="$content" '
            /^## / { in_section = 0 }
            $0 ~ "^## " section { in_section = 1 }
            { print }
            in_section && /^$/ && !added {
                print content
                print ""
                added = 1
            }
        ' "$CLAUDE_MD" > "$temp_file"

        # Only update if content was added
        if grep -q "$content" "$temp_file" 2>/dev/null; then
            mv "$temp_file" "$CLAUDE_MD"
            echo -e "${GREEN}Updated CLAUDE.md section: ${section}${NC}"
        else
            rm "$temp_file"
            echo -e "${YELLOW}Could not update section, appending instead${NC}"
            echo "" >> "$CLAUDE_MD"
            echo "### $section Addition" >> "$CLAUDE_MD"
            echo "$content" >> "$CLAUDE_MD"
        fi
    else
        echo -e "${YELLOW}Section '$section' not found in CLAUDE.md${NC}"
    fi
}

# Track task completion metrics
# @param $1 task_id - Task ID
# @param $2 success - Was it successful (true/false)
# @param $3 iterations - Number of iterations needed
compound_track_task() {
    local task_id="${1:?Task ID required}"
    local success="${2:-true}"
    local iterations="${3:-1}"

    _init_metrics

    # Update metrics
    _update_metrics "tasks.total" "increment"

    if [[ "$success" == "true" ]]; then
        if [[ "$iterations" -eq 1 ]]; then
            _update_metrics "tasks.first_pass_success" "increment"
        else
            _update_metrics "tasks.required_iteration" "increment"
        fi
    fi

    echo -e "${GREEN}Task tracked: ${task_id} (iterations: ${iterations})${NC}"
}

# Track session metrics
# @param $1 success - Was session successful
compound_track_session() {
    local success="${1:-true}"

    _init_metrics

    _update_metrics "sessions.total" "increment"

    if [[ "$success" == "true" ]]; then
        _update_metrics "sessions.successful" "increment"
    else
        _update_metrics "sessions.failed" "increment"
    fi
}

# Update metrics file
_update_metrics() {
    local path="$1"
    local operation="${2:-increment}"

    _init_metrics

    if command -v jq &>/dev/null; then
        local temp_file
        temp_file=$(mktemp)

        case "$operation" in
            increment)
                jq --arg path "$path" '
                    getpath($path | split(".")) as $current |
                    setpath($path | split("."); ($current // 0) + 1) |
                    .last_updated = (now | todate)
                ' "$METRICS_FILE" > "$temp_file" 2>/dev/null && mv "$temp_file" "$METRICS_FILE"
                ;;
            set:*)
                local value="${operation#set:}"
                jq --arg path "$path" --arg value "$value" '
                    setpath($path | split("."); $value) |
                    .last_updated = (now | todate)
                ' "$METRICS_FILE" > "$temp_file" 2>/dev/null && mv "$temp_file" "$METRICS_FILE"
                ;;
        esac
    fi
}

# Get compound metrics report
# @return string - Metrics summary
compound_report() {
    _init_metrics

    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║           COMPOUND ENGINEERING METRICS                     ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""

    if [[ -f "$METRICS_FILE" ]] && command -v jq &>/dev/null; then
        local sessions_total sessions_success tasks_total first_pass bugs_fixed patterns

        sessions_total=$(jq -r '.sessions.total // 0' "$METRICS_FILE")
        sessions_success=$(jq -r '.sessions.successful // 0' "$METRICS_FILE")
        tasks_total=$(jq -r '.tasks.total // 0' "$METRICS_FILE")
        first_pass=$(jq -r '.tasks.first_pass_success // 0' "$METRICS_FILE")
        bugs_fixed=$(jq -r '.bugs.total_fixed // 0' "$METRICS_FILE")
        patterns=$(jq -r '.patterns_captured // 0' "$METRICS_FILE")

        echo "Sessions:"
        echo "  Total:      $sessions_total"
        echo "  Successful: $sessions_success"
        if [[ $sessions_total -gt 0 ]]; then
            echo "  Success %:  $((sessions_success * 100 / sessions_total))%"
        fi
        echo ""

        echo "Tasks:"
        echo "  Total:            $tasks_total"
        echo "  First Pass:       $first_pass"
        if [[ $tasks_total -gt 0 ]]; then
            echo "  First Pass Rate:  $((first_pass * 100 / tasks_total))%"
        fi
        echo ""

        echo "Learnings:"
        echo "  Bugs Fixed:       $bugs_fixed"
        echo "  Patterns Captured: $patterns"
        echo ""

        # Compound score (simplified)
        if [[ $tasks_total -gt 0 ]]; then
            local compound_score=$((first_pass * 100 / tasks_total + patterns * 5))
            echo -e "${CYAN}Compound Score: ${compound_score}${NC}"
            echo "(Higher is better - based on first-pass success + learnings)"
        fi
    else
        echo "No metrics data available yet."
        echo "Start tracking with:"
        echo "  compound_track_task 'TASK-001' true 1"
    fi

    echo ""
    echo "─────────────────────────────────────────────────────────────"
    echo "Files:"
    echo "  Learnings: $LEARNINGS_FILE"
    echo "  Metrics:   $METRICS_FILE"
}

# List recent learnings
# @param $1 count - Number of learnings to show (default: 10)
compound_list_learnings() {
    local count="${1:-10}"

    _init_learnings

    echo "=== Recent Learnings ==="
    echo ""

    if command -v yq &>/dev/null && yq --version 2>&1 | grep -q "mikefarah"; then
        yq eval ".learnings | .[-${count}:]" "$LEARNINGS_FILE" 2>/dev/null || \
            echo "No learnings recorded yet."
    elif command -v python3 &>/dev/null; then
        python3 << EOF
import yaml
try:
    with open('$LEARNINGS_FILE') as f:
        data = yaml.safe_load(f)
        learnings = data.get('learnings', [])[-$count:]
        for l in learnings:
            if l:
                print(f"- [{l.get('category', 'general')}] {l.get('description', 'N/A')[:60]}...")
except Exception as e:
    print("No learnings recorded yet.")
EOF
    else
        echo "Recent learnings (raw):"
        tail -30 "$LEARNINGS_FILE" 2>/dev/null || echo "No learnings recorded yet."
    fi
}

# Export learnings as markdown
# @return string - Markdown formatted learnings
compound_export_markdown() {
    _init_learnings

    echo "# Compound Learnings Export"
    echo ""
    echo "Generated: $(date)"
    echo ""

    if [[ -f "$LEARNINGS_FILE" ]] && command -v python3 &>/dev/null; then
        python3 << 'PYEOF'
import yaml
import sys

try:
    with open('LEARNINGS_FILE_PLACEHOLDER') as f:
        data = yaml.safe_load(f)

    print("## Bug Fixes")
    print("")
    for l in data.get('learnings', []):
        if l and l.get('category') == 'bug_fix':
            print(f"### {l.get('error_type', 'Bug')}")
            print(f"**Fix:** {l.get('fix', 'N/A')}")
            if l.get('prevention'):
                print(f"**Prevention:** {l.get('prevention')}")
            print("")

    print("## Patterns")
    print("")
    for l in data.get('learnings', []):
        if l and l.get('category') == 'pattern':
            print(f"- {l.get('description', 'N/A')}")
    print("")

    print("## General Learnings")
    print("")
    for l in data.get('learnings', []):
        if l and l.get('category') not in ['bug_fix', 'pattern']:
            print(f"- {l.get('description', 'N/A')}")

except Exception as e:
    print(f"Error: {e}", file=sys.stderr)
PYEOF
    else
        echo "Raw learnings file:"
        echo '```yaml'
        cat "$LEARNINGS_FILE" 2>/dev/null || echo "No learnings file found."
        echo '```'
    fi
}

# Usage information
usage() {
    cat << EOF
Compound Tracker - Capture learnings and track compound progress

Based on Dan Shipper's Compound Engineering concept:
"Each task you complete should make subsequent tasks easier."

Usage:
  source compound_tracker.sh

Functions:
  compound_capture_learning <desc> [category] [file]  Capture a learning
  compound_capture_bug_fix <type> <fix> [prevention]  Capture bug fix pattern
  compound_update_claude_md <section> <content>       Update CLAUDE.md
  compound_track_task <id> [success] [iterations]     Track task completion
  compound_track_session [success]                    Track session
  compound_report                                     Show metrics report
  compound_list_learnings [count]                     List recent learnings
  compound_export_markdown                            Export as markdown

Examples:
  # Capture a bug fix
  compound_capture_bug_fix "TypeError" "Added null check" "Always validate inputs"

  # Capture a pattern
  compound_capture_learning "Use early returns for validation" "pattern" "auth.ts"

  # Track task completion
  compound_track_task "BE-001" true 1    # First pass success
  compound_track_task "BE-002" true 3    # Took 3 iterations

  # Update CLAUDE.md
  compound_update_claude_md "Common Patterns" "- Always use strict mode in bash"

  # View metrics
  compound_report

Files:
  Learnings: $LEARNINGS_FILE
  Metrics:   $METRICS_FILE
EOF
}

# If script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        --help|-h)
            usage
            ;;
        capture)
            compound_capture_learning "${2:-}" "${3:-general}" "${4:-}"
            ;;
        bug-fix)
            compound_capture_bug_fix "${2:-}" "${3:-}" "${4:-}"
            ;;
        track-task)
            compound_track_task "${2:-}" "${3:-true}" "${4:-1}"
            ;;
        track-session)
            compound_track_session "${2:-true}"
            ;;
        report)
            compound_report
            ;;
        list)
            compound_list_learnings "${2:-10}"
            ;;
        export)
            compound_export_markdown
            ;;
        *)
            compound_report
            ;;
    esac
fi
