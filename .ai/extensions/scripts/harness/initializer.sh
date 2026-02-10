#!/usr/bin/env bash
# Initializer Agent - First session setup for features
# Source: Anthropic's Agent Harness pattern
# Feature: workflow-improvements-2026 | Task: BE-003

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
NC='\033[0m' # No Color

# Print colored message
print_color() {
    local color="$1"
    shift
    echo -e "${color}$*${NC}"
}

# Check if this is a first session (no existing progress)
is_first_session() {
    ! progress_exists
}

# Create feature workspace if not exists
create_feature_workspace() {
    local feature_id="$1"
    local workspace="${PROJECT_DIR}/features/${feature_id}"

    if [[ ! -d "$workspace" ]]; then
        mkdir -p "$workspace"
        print_color "$GREEN" "Created feature workspace: ${workspace}"
    else
        print_color "$YELLOW" "Feature workspace already exists: ${workspace}"
    fi
}

# Load feature context
load_feature_context() {
    local feature_id="$1"
    local workspace="${PROJECT_DIR}/features/${feature_id}"

    echo ""
    print_color "$BLUE" "=== Feature Context ==="

    # Check for feature definition
    if [[ -f "${workspace}/FEATURE_${feature_id}.md" ]]; then
        print_color "$GREEN" "Feature definition found"
        # Extract objective
        grep -A 5 "## Objective" "${workspace}/FEATURE_${feature_id}.md" 2>/dev/null | head -6 || true
    elif [[ -f "${workspace}/spec.yaml" ]]; then
        print_color "$GREEN" "Feature spec found (YAML)"
    else
        print_color "$YELLOW" "No feature definition found - consider running /workflows:plan first"
    fi

    # Check for state file
    if [[ -f "${workspace}/50_state.md" ]]; then
        print_color "$GREEN" "State file found"
    fi

    # Check for tasks
    if [[ -f "${workspace}/30_tasks.md" ]] || [[ -f "${workspace}/30_tasks_backend.md" ]]; then
        print_color "$GREEN" "Task breakdown found"
    fi
}

# Display welcome message
display_welcome() {
    local feature_id="$1"
    local role="$2"

    echo ""
    print_color "$GREEN" "╔════════════════════════════════════════════════════════════╗"
    print_color "$GREEN" "║          WORKFLOW SESSION INITIALIZED                       ║"
    print_color "$GREEN" "╚════════════════════════════════════════════════════════════╝"
    echo ""
    print_color "$BLUE" "Feature: ${feature_id}"
    print_color "$BLUE" "Role: ${role}"
    print_color "$BLUE" "Started: $(date)"
    echo ""
}

# Load rules for role
load_role_rules() {
    local role="$1"

    echo ""
    print_color "$BLUE" "=== Role Guidelines ==="

    local role_file="${WORKFLOW_DIR}/roles/${role}.md"
    local plugin_role_file="$(dirname "$(dirname "$WORKFLOW_DIR")")/plugins/multi-agent-workflow/agents/roles/${role}.md"

    if [[ -f "$role_file" ]]; then
        print_color "$GREEN" "Role definition loaded from: ${role_file}"
    elif [[ -f "$plugin_role_file" ]]; then
        print_color "$GREEN" "Role definition loaded from plugin"
    else
        print_color "$YELLOW" "No role-specific rules found for: ${role}"
    fi
}

# Suggest first actions
suggest_first_actions() {
    local feature_id="$1"
    local role="$2"
    local workspace="${PROJECT_DIR}/features/${feature_id}"

    echo ""
    print_color "$BLUE" "=== Suggested First Actions ==="

    case "$role" in
        planner)
            echo "1. Review existing codebase patterns"
            echo "2. Define feature objectives and acceptance criteria"
            echo "3. Create API contracts"
            echo "4. Break down tasks for each role"
            ;;
        backend)
            if [[ -f "${workspace}/30_tasks_backend.md" ]]; then
                echo "1. Read task breakdown: ${workspace}/30_tasks_backend.md"
                echo "2. Start with first PENDING task"
                echo "3. Follow TDD: Write tests first!"
            else
                echo "1. Wait for planning to complete, or"
                echo "2. Run: /workflows:plan ${feature_id}"
            fi
            ;;
        frontend)
            if [[ -f "${workspace}/30_tasks_frontend.md" ]]; then
                echo "1. Read task breakdown: ${workspace}/30_tasks_frontend.md"
                echo "2. Check API contracts in feature definition"
                echo "3. Start with first PENDING task"
            else
                echo "1. Wait for planning to complete"
                echo "2. Review API contracts when available"
            fi
            ;;
        qa)
            echo "1. Review feature acceptance criteria"
            echo "2. Wait for implementation to begin"
            echo "3. Prepare test scenarios"
            ;;
        *)
            echo "1. Review feature definition"
            echo "2. Understand your responsibilities"
            echo "3. Coordinate with other roles"
            ;;
    esac

    echo ""
    echo "Progress is being tracked. Use these commands:"
    echo "  progress_update <task> <status>  - Update task progress"
    echo "  progress_add_note <note>         - Add note for later"
    echo "  progress_summary                 - View current progress"
}

# Main initialization function
initialize_session() {
    local feature_id="${1:?Feature ID required}"
    local role="${2:?Role required}"

    # Validate role
    local valid_roles="planner backend frontend qa"
    if [[ ! " $valid_roles " =~ " $role " ]]; then
        print_color "$YELLOW" "Warning: Non-standard role '${role}'. Proceeding anyway."
    fi

    # Check if first session
    if is_first_session; then
        print_color "$GREEN" "First session detected - initializing..."

        # Create workspace
        create_feature_workspace "$feature_id"

        # Initialize progress tracking
        progress_init "$feature_id" "$role"

        # Display welcome
        display_welcome "$feature_id" "$role"

        # Load context
        load_feature_context "$feature_id"

        # Load role rules
        load_role_rules "$role"

        # Suggest actions
        suggest_first_actions "$feature_id" "$role"

        print_color "$GREEN" ""
        print_color "$GREEN" "Session initialized successfully!"
        print_color "$GREEN" "Progress file: $(progress_file_path)"

    else
        print_color "$YELLOW" "Existing session detected."
        print_color "$YELLOW" "Use 'coder.sh' to resume, or delete progress file to start fresh."
        echo ""
        echo "Current progress:"
        progress_summary
    fi
}

# Usage information
usage() {
    cat << EOF
Initializer - First session setup for Claude agents

Usage: $0 <feature-id> <role>

Arguments:
  feature-id    Feature identifier (e.g., user-authentication)
  role          Agent role (planner, backend, frontend, qa)

Example:
  $0 user-authentication backend

This script:
  1. Creates feature workspace if needed
  2. Initializes progress tracking
  3. Displays feature context
  4. Loads role-specific guidelines
  5. Suggests first actions

For resuming sessions, use coder.sh instead.
EOF
}

# Main execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    if [[ $# -lt 2 ]]; then
        usage
        exit 1
    fi

    initialize_session "$1" "$2"
fi
