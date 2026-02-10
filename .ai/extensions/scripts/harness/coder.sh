#!/usr/bin/env bash
# Coder Agent - Resume existing sessions with full context
# Source: Anthropic's Agent Harness pattern
# Feature: workflow-improvements-2026 | Task: BE-004

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
PROJECT_DIR="$(dirname "$WORKFLOW_DIR")/project"

# Source progress manager
source "${SCRIPT_DIR}/progress_manager.sh"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Print colored message
print_color() {
    local color="$1"
    shift
    echo -e "${color}$*${NC}"
}

# Load progress from file
load_progress() {
    if ! progress_exists; then
        print_color "$RED" "Error: No active session found."
        print_color "$YELLOW" "Run initializer.sh first to start a new session."
        return 1
    fi

    # Read progress data
    local progress_json
    progress_json=$(progress_read)

    # Extract values
    FEATURE=$(echo "$progress_json" | grep -o '"feature": "[^"]*"' | cut -d'"' -f4)
    ROLE=$(echo "$progress_json" | grep -o '"role": "[^"]*"' | cut -d'"' -f4)
    STATUS=$(echo "$progress_json" | grep -o '"status": "[^"]*"' | cut -d'"' -f4)
    CURRENT_TASK=$(echo "$progress_json" | grep -o '"current_task": "[^"]*"' | cut -d'"' -f4)
    LAST_UPDATED=$(echo "$progress_json" | grep -o '"last_updated": "[^"]*"' | cut -d'"' -f4)

    return 0
}

# Display session restore banner
display_restore_banner() {
    echo ""
    print_color "$CYAN" "╔════════════════════════════════════════════════════════════╗"
    print_color "$CYAN" "║          SESSION RESTORED                                   ║"
    print_color "$CYAN" "╚════════════════════════════════════════════════════════════╝"
    echo ""
}

# Display previous session summary
display_previous_summary() {
    print_color "$BLUE" "=== Previous Session Summary ==="
    echo ""
    print_color "$GREEN" "Feature: ${FEATURE}"
    print_color "$GREEN" "Role: ${ROLE}"
    print_color "$GREEN" "Status: ${STATUS}"
    print_color "$GREEN" "Last Updated: ${LAST_UPDATED}"
    echo ""

    if [[ "$CURRENT_TASK" != "none" ]]; then
        print_color "$YELLOW" "Current Task: ${CURRENT_TASK}"
    fi
}

# Display completed tasks
display_completed_tasks() {
    print_color "$BLUE" "=== Completed This Feature ==="

    local progress_file
    progress_file=$(progress_file_path)

    local completed
    completed=$(grep -A 50 "## Completed This Session" "$progress_file" | grep "^\- \[x\]" || echo "")

    if [[ -n "$completed" ]]; then
        echo "$completed" | while read -r line; do
            print_color "$GREEN" "  $line"
        done
    else
        echo "  No tasks completed yet"
    fi
    echo ""
}

# Display notes from previous session
display_notes() {
    print_color "$BLUE" "=== Notes from Previous Session ==="

    local progress_file
    progress_file=$(progress_file_path)

    local notes
    notes=$(grep -A 20 "## Notes for Next Session" "$progress_file" | grep "^-" | head -10 || echo "")

    if [[ -n "$notes" ]]; then
        echo "$notes" | while read -r line; do
            print_color "$YELLOW" "  $line"
        done
    else
        echo "  No notes left"
    fi
    echo ""
}

# Display files modified
display_modified_files() {
    print_color "$BLUE" "=== Files Modified ==="

    local progress_file
    progress_file=$(progress_file_path)

    local files
    files=$(grep -A 30 "## Files Modified This Session" "$progress_file" | grep "^-" | head -15 || echo "")

    if [[ -n "$files" ]]; then
        echo "$files" | while read -r line; do
            echo "  $line"
        done
    else
        echo "  No files tracked yet"
    fi
    echo ""
}

# Check for blockers
check_blockers() {
    local progress_file
    progress_file=$(progress_file_path)

    local blockers
    blockers=$(grep -A 5 "## Blockers" "$progress_file" | grep -v "^#" | grep -v "^$" | head -1 || echo "none")

    if [[ "$blockers" != "none" && -n "$blockers" ]]; then
        print_color "$RED" "=== BLOCKER DETECTED ==="
        print_color "$RED" "  $blockers"
        echo ""
        print_color "$YELLOW" "Consider:"
        echo "  1. Resolving the blocker"
        echo "  2. Asking for help in DECISIONS.md"
        echo "  3. Clearing with: progress_set_blocker \"none\""
        echo ""
    fi
}

# Suggest next actions
suggest_next_actions() {
    print_color "$BLUE" "=== Suggested Actions ==="

    local workspace="${PROJECT_DIR}/features/${FEATURE}"

    # Check if there's a current task
    if [[ "$CURRENT_TASK" != "none" && "$STATUS" == "IN_PROGRESS" ]]; then
        print_color "$GREEN" "1. Continue working on: ${CURRENT_TASK}"
        echo "   Use: progress_update \"${CURRENT_TASK}\" \"completed\" when done"
        echo ""
    fi

    # Role-specific suggestions
    case "$ROLE" in
        backend)
            if [[ -f "${workspace}/30_tasks_backend.md" ]]; then
                echo "2. Check next task in: 30_tasks_backend.md"
                # Find first pending task
                local next_task
                next_task=$(grep -E "^### (BE|TASK)-[0-9]+" "${workspace}/30_tasks_backend.md" 2>/dev/null | head -1 || echo "")
                if [[ -n "$next_task" ]]; then
                    echo "   Next available: $next_task"
                fi
            fi
            echo "3. Remember: Write tests FIRST (TDD)"
            ;;
        frontend)
            if [[ -f "${workspace}/30_tasks_frontend.md" ]]; then
                echo "2. Check next task in: 30_tasks_frontend.md"
            fi
            echo "3. Check API contracts before implementing"
            ;;
        qa)
            echo "2. Review completed implementations"
            echo "3. Run test suites"
            echo "4. Update 50_state.md with findings"
            ;;
        planner)
            echo "2. Check for blocked agents in 50_state.md"
            echo "3. Resolve any open questions"
            echo "4. Review completed work"
            ;;
    esac

    echo ""
    echo "Progress commands:"
    echo "  progress_update <task> <status>  - Update task"
    echo "  progress_add_note <note>         - Leave note"
    echo "  progress_mark_file <path>        - Track file"
    echo "  progress_summary                 - View progress"
}

# Show git status for context
show_git_context() {
    print_color "$BLUE" "=== Git Context ==="

    # Current branch
    local branch
    branch=$(git branch --show-current 2>/dev/null || echo "unknown")
    echo "Branch: $branch"

    # Uncommitted changes count
    local changes
    changes=$(git status --porcelain 2>/dev/null | wc -l || echo "0")
    echo "Uncommitted changes: $changes files"

    # Recent commits
    echo "Recent commits:"
    git log --oneline -3 2>/dev/null | sed 's/^/  /' || echo "  Unable to read git log"
    echo ""
}

# Handle interrupted session
handle_interrupted_session() {
    if [[ "$STATUS" == "IN_PROGRESS" && "$CURRENT_TASK" != "none" ]]; then
        print_color "$YELLOW" "=== Interrupted Task Detected ==="
        print_color "$YELLOW" "Task ${CURRENT_TASK} was in progress."
        echo ""
        echo "Options:"
        echo "  1. Continue where you left off"
        echo "  2. Mark as blocked: progress_set_blocker \"reason\""
        echo "  3. Mark as completed: progress_update \"${CURRENT_TASK}\" \"completed\""
        echo ""
    fi
}

# Main resume function
resume_session() {
    # Load existing progress
    if ! load_progress; then
        return 1
    fi

    # Display restore banner
    display_restore_banner

    # Display summary
    display_previous_summary

    # Check for blockers
    check_blockers

    # Handle interrupted tasks
    handle_interrupted_session

    # Display completed tasks
    display_completed_tasks

    # Display notes
    display_notes

    # Display modified files
    display_modified_files

    # Show git context
    show_git_context

    # Suggest next actions
    suggest_next_actions

    print_color "$GREEN" ""
    print_color "$GREEN" "Session restored. Ready to continue!"
    print_color "$GREEN" "Progress file: $(progress_file_path)"
}

# Usage information
usage() {
    cat << EOF
Coder - Resume existing Claude agent sessions

Usage: $0

This script:
  1. Loads previous session progress
  2. Displays summary of completed work
  3. Shows notes from previous session
  4. Lists modified files
  5. Checks for blockers
  6. Suggests next actions

Requirements:
  - Existing progress file (created by initializer.sh)

For new sessions, use initializer.sh instead.

Example workflow:
  # First session
  ./initializer.sh my-feature backend

  # Later sessions
  ./coder.sh
EOF
}

# Main execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
        usage
        exit 0
    fi

    resume_session
fi
