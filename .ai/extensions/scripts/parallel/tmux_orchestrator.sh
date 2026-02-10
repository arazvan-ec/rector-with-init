#!/usr/bin/env bash
# Tmux Orchestrator - Manage tmux sessions for parallel agents
# Feature: workflow-improvements-2026 | Task: BE-010

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"

# Source dependencies
source "${SCRIPT_DIR}/worktree_manager.sh"
source "${SCRIPT_DIR}/port_manager.sh"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Check if tmux is available
_check_tmux() {
    if ! command -v tmux &> /dev/null; then
        echo -e "${RED}Error: tmux is not installed${NC}" >&2
        echo "Install with: apt install tmux (Debian/Ubuntu) or brew install tmux (macOS)" >&2
        return 1
    fi
}

# Check if session exists
_session_exists() {
    local session_name="$1"
    tmux has-session -t "$session_name" 2>/dev/null
}

# Generate session name from feature
_session_name() {
    local feature_id="${1:-workflow}"
    echo "workflow-${feature_id}"
}

# Create orchestrated tmux session
# @param $1 session_name - Session name
# @param $2 roles - Comma-separated roles (backend,frontend,qa)
# @param $3 feature_id - Feature being worked on
tmux_create_session() {
    local session_name="${1:?Session name required}"
    local roles="${2:-backend,frontend,qa}"
    local feature_id="${3:-$(basename "$(pwd)")}"

    _check_tmux

    if _session_exists "$session_name"; then
        echo -e "${YELLOW}Session '${session_name}' already exists${NC}"
        echo "Attach with: tmux attach -t ${session_name}"
        return 0
    fi

    echo -e "${BLUE}Creating tmux session: ${session_name}${NC}"

    # Convert roles to array
    IFS=',' read -ra role_array <<< "$roles"
    local num_roles=${#role_array[@]}

    # Create session with first role
    local first_role="${role_array[0]}"
    local first_worktree
    first_worktree=$(worktree_create "$first_role")
    local first_port
    first_port=$(port_allocate "$first_role")

    # Create session (detached)
    tmux new-session -d -s "$session_name" -n "$first_role" -c "$first_worktree"

    # Set pane title
    tmux select-pane -t "${session_name}:0.0" -T "$first_role"

    # Create additional panes for other roles
    for ((i = 1; i < num_roles; i++)); do
        local role="${role_array[$i]}"
        local worktree
        worktree=$(worktree_create "$role")
        local port
        port=$(port_allocate "$role")

        # Split window and create pane
        if (( i % 2 == 1 )); then
            tmux split-window -h -t "${session_name}:0" -c "$worktree"
        else
            tmux split-window -v -t "${session_name}:0" -c "$worktree"
        fi

        # Set pane title
        tmux select-pane -t "${session_name}:0.${i}" -T "$role"
    done

    # Balance panes
    tmux select-layout -t "${session_name}:0" tiled

    # Set up each pane with environment
    for ((i = 0; i < num_roles; i++)); do
        local role="${role_array[$i]}"
        local port
        port=$(port_get "$role")

        # Send setup commands to pane
        tmux send-keys -t "${session_name}:0.${i}" "# Role: ${role} | Port: ${port} | Feature: ${feature_id}" Enter
        tmux send-keys -t "${session_name}:0.${i}" "export AGENT_ROLE=${role}" Enter
        tmux send-keys -t "${session_name}:0.${i}" "export AGENT_PORT=${port}" Enter
        tmux send-keys -t "${session_name}:0.${i}" "export FEATURE_ID=${feature_id}" Enter
        tmux send-keys -t "${session_name}:0.${i}" "clear" Enter
    done

    # Select first pane
    tmux select-pane -t "${session_name}:0.0"

    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║          PARALLEL SESSION CREATED                           ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}Session: ${session_name}${NC}"
    echo -e "${CYAN}Feature: ${feature_id}${NC}"
    echo ""
    echo "Agents launched:"
    for ((i = 0; i < num_roles; i++)); do
        local role="${role_array[$i]}"
        local worktree
        worktree=$(worktree_path "$role")
        local port
        port=$(port_get "$role")
        printf "  [%d] %-10s @ %-30s :%s\n" "$i" "$role" "$worktree" "$port"
    done
    echo ""
    echo -e "${GREEN}Attach with:${NC}"
    echo "  tmux attach -t ${session_name}"
    echo ""
    echo -e "${BLUE}Tmux shortcuts:${NC}"
    echo "  Ctrl+b d       - Detach from session"
    echo "  Ctrl+b arrow   - Navigate between panes"
    echo "  Ctrl+b z       - Zoom current pane"
    echo "  Ctrl+b [       - Scroll mode (q to exit)"
}

# Attach to existing session
# @param $1 session_name - Session name
tmux_attach() {
    local session_name="${1:?Session name required}"

    _check_tmux

    if ! _session_exists "$session_name"; then
        echo -e "${RED}Error: Session '${session_name}' does not exist${NC}" >&2
        return 1
    fi

    tmux attach -t "$session_name"
}

# Send command to specific pane
# @param $1 session_name - Session name
# @param $2 role - Role (pane identifier)
# @param $3 command - Command to execute
tmux_send_command() {
    local session_name="${1:?Session name required}"
    local role="${2:?Role required}"
    local command="${3:?Command required}"

    _check_tmux

    if ! _session_exists "$session_name"; then
        echo -e "${RED}Error: Session '${session_name}' does not exist${NC}" >&2
        return 1
    fi

    # Find pane index by role
    local pane_index
    pane_index=$(tmux list-panes -t "${session_name}:0" -F "#{pane_index} #{pane_title}" 2>/dev/null | \
                 grep " ${role}$" | \
                 awk '{print $1}' || echo "")

    if [[ -z "$pane_index" ]]; then
        echo -e "${RED}Error: Pane for role '${role}' not found${NC}" >&2
        return 1
    fi

    tmux send-keys -t "${session_name}:0.${pane_index}" "$command" Enter
    echo -e "${GREEN}Command sent to ${role}${NC}"
}

# Get output from pane
# @param $1 session_name - Session name
# @param $2 role - Role
# @param $3 lines - Number of lines (default: 50)
# @return string - Pane output
tmux_get_output() {
    local session_name="${1:?Session name required}"
    local role="${2:?Role required}"
    local lines="${3:-50}"

    _check_tmux

    if ! _session_exists "$session_name"; then
        echo -e "${RED}Error: Session '${session_name}' does not exist${NC}" >&2
        return 1
    fi

    # Find pane index by role
    local pane_index
    pane_index=$(tmux list-panes -t "${session_name}:0" -F "#{pane_index} #{pane_title}" 2>/dev/null | \
                 grep " ${role}$" | \
                 awk '{print $1}' || echo "")

    if [[ -z "$pane_index" ]]; then
        echo -e "${RED}Error: Pane for role '${role}' not found${NC}" >&2
        return 1
    fi

    tmux capture-pane -t "${session_name}:0.${pane_index}" -p -S "-${lines}"
}

# Kill session and cleanup
# @param $1 session_name - Session name
# @param $2 cleanup_worktrees - Also cleanup worktrees (default: true)
tmux_kill_session() {
    local session_name="${1:?Session name required}"
    local cleanup_worktrees="${2:-true}"

    _check_tmux

    if ! _session_exists "$session_name"; then
        echo -e "${YELLOW}Session '${session_name}' does not exist${NC}"
    else
        tmux kill-session -t "$session_name"
        echo -e "${GREEN}Session '${session_name}' terminated${NC}"
    fi

    # Cleanup resources
    if [[ "$cleanup_worktrees" == "true" ]]; then
        echo -e "${BLUE}Cleaning up worktrees...${NC}"
        worktree_cleanup_all "true" 2>/dev/null || true
    fi

    echo -e "${BLUE}Releasing ports...${NC}"
    port_release_all

    echo -e "${GREEN}Cleanup complete${NC}"
}

# List sessions
tmux_list_sessions() {
    _check_tmux

    echo "=== Workflow Sessions ==="

    tmux list-sessions -F "#{session_name} (#{session_windows} windows, #{session_attached} attached)" 2>/dev/null | \
        grep "^workflow-" || echo "No workflow sessions running."
}

# Get session status
tmux_session_status() {
    local session_name="${1:?Session name required}"

    _check_tmux

    if ! _session_exists "$session_name"; then
        echo '{"exists": false}'
        return 1
    fi

    echo "{"
    echo "  \"exists\": true,"
    echo "  \"session\": \"${session_name}\","
    echo "  \"panes\": ["

    local first=true
    while IFS= read -r line; do
        local index title
        index=$(echo "$line" | awk '{print $1}')
        title=$(echo "$line" | awk '{print $2}')

        if ! $first; then
            echo ","
        fi
        first=false

        printf '    {"index": %s, "role": "%s"}' "$index" "$title"
    done < <(tmux list-panes -t "${session_name}:0" -F "#{pane_index} #{pane_title}" 2>/dev/null)

    echo ""
    echo "  ]"
    echo "}"
}

# Usage information
usage() {
    cat << EOF
Tmux Orchestrator - Manage tmux sessions for parallel agents

Usage:
  source tmux_orchestrator.sh

Functions:
  tmux_create_session <name> <roles> [feature]  Create session with panes
  tmux_attach <name>                             Attach to session
  tmux_send_command <name> <role> <cmd>          Send command to pane
  tmux_get_output <name> <role> [lines]          Get pane output
  tmux_kill_session <name> [cleanup]             Kill session
  tmux_list_sessions                             List workflow sessions
  tmux_session_status <name>                     Get session status (JSON)

Examples:
  # Create parallel session
  tmux_create_session "workflow-auth" "backend,frontend,qa" "auth-feature"

  # Attach to session
  tmux_attach "workflow-auth"

  # Send command to specific role
  tmux_send_command "workflow-auth" "backend" "npm run dev"

  # Get output from pane
  tmux_get_output "workflow-auth" "frontend" 100

  # Kill session and cleanup
  tmux_kill_session "workflow-auth"

Tmux shortcuts:
  Ctrl+b d       - Detach from session
  Ctrl+b arrow   - Navigate between panes
  Ctrl+b z       - Zoom current pane
  Ctrl+b [       - Scroll mode (q to exit)
  Ctrl+b "       - Split horizontally
  Ctrl+b %       - Split vertically
EOF
}

# If script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        --help|-h)
            usage
            ;;
        create)
            tmux_create_session "${2:-}" "${3:-backend,frontend,qa}" "${4:-}"
            ;;
        attach)
            tmux_attach "${2:-}"
            ;;
        send)
            tmux_send_command "${2:-}" "${3:-}" "${4:-}"
            ;;
        output)
            tmux_get_output "${2:-}" "${3:-}" "${4:-50}"
            ;;
        kill)
            tmux_kill_session "${2:-}" "${3:-true}"
            ;;
        list)
            tmux_list_sessions
            ;;
        status)
            tmux_session_status "${2:-}"
            ;;
        *)
            usage
            ;;
    esac
fi
