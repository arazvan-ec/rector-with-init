#!/usr/bin/env bash
# Parallel Agent Monitor - Track status of parallel agents
# Feature: workflow-improvements-2026 | Task: BE-011

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
PROJECT_DIR="$(dirname "$WORKFLOW_DIR")/project"

# Source dependencies
source "${SCRIPT_DIR}/worktree_manager.sh"
source "${SCRIPT_DIR}/port_manager.sh"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m'
BOLD='\033[1m'

# Configuration
MONITOR_REFRESH="${MONITOR_REFRESH:-5}"
PROGRESS_DIR="${PROJECT_DIR}/sessions"

# Get agent status from progress file
# @param $1 role - Agent role
# @return JSON - Agent status
_get_agent_progress() {
    local role="$1"
    local progress_file="${PROGRESS_DIR}/${role}/claude-progress.txt"

    if [[ ! -f "$progress_file" ]]; then
        echo '{}'
        return
    fi

    local current_task=""
    local status="active"
    local last_update=""
    local notes=""

    # Parse progress file
    while IFS= read -r line; do
        case "$line" in
            "## Current Task"*)
                read -r current_task < <(sed -n '/^## Current Task/,/^##/{/^## Current Task/d;/^##/d;p}' "$progress_file" | head -1)
                ;;
            "## Status"*)
                read -r status < <(sed -n '/^## Status/,/^##/{/^## Status/d;/^##/d;p}' "$progress_file" | head -1)
                ;;
        esac
    done < "$progress_file"

    # Get last modification time
    if [[ -f "$progress_file" ]]; then
        last_update=$(stat -c %Y "$progress_file" 2>/dev/null || stat -f %m "$progress_file" 2>/dev/null || echo "0")
    fi

    cat << EOF
{
  "current_task": "${current_task:-unknown}",
  "status": "${status:-unknown}",
  "last_update": ${last_update:-0}
}
EOF
}

# Check if agent has blockers
# @param $1 role - Agent role
# @return bool - true if blocked
_has_blockers() {
    local role="$1"
    local progress_file="${PROGRESS_DIR}/${role}/claude-progress.txt"

    if [[ ! -f "$progress_file" ]]; then
        return 1
    fi

    grep -qi "blocked\|waiting\|dependency" "$progress_file" 2>/dev/null
}

# Get all active roles from worktrees
_get_active_roles() {
    if [[ ! -d "${WORKTREE_BASE:-}" ]]; then
        WORKTREE_BASE="$(cd "${WORKFLOW_DIR}/../.." && pwd)/.worktrees"
    fi

    if [[ ! -d "$WORKTREE_BASE" ]]; then
        return
    fi

    for dir in "$WORKTREE_BASE"/*; do
        if [[ -d "$dir" ]]; then
            basename "$dir"
        fi
    done
}

# Get status for single agent
# @param $1 role - Agent role
# @return JSON - Complete agent status
monitor_agent_status() {
    local role="${1:?Role required}"

    local worktree_status
    local port_info
    local progress_info
    local has_blockers="false"

    # Get worktree status
    if worktree_exists "$role" 2>/dev/null; then
        worktree_status=$(worktree_status "$role" 2>/dev/null || echo '{"exists": false}')
    else
        worktree_status='{"exists": false}'
    fi

    # Get port info
    local port
    port=$(port_get "$role" 2>/dev/null || echo "")

    # Get progress info
    progress_info=$(_get_agent_progress "$role")

    # Check blockers
    if _has_blockers "$role"; then
        has_blockers="true"
    fi

    # Determine overall status
    local overall_status="inactive"
    local worktree_exists
    worktree_exists=$(echo "$worktree_status" | grep -o '"exists": [^,}]*' | grep -o 'true\|false')

    if [[ "$worktree_exists" == "true" ]]; then
        if [[ "$has_blockers" == "true" ]]; then
            overall_status="blocked"
        else
            overall_status="active"
        fi
    fi

    cat << EOF
{
  "role": "${role}",
  "status": "${overall_status}",
  "worktree": ${worktree_status},
  "port": ${port:-null},
  "progress": ${progress_info},
  "has_blockers": ${has_blockers}
}
EOF
}

# Get status for all agents
# @return JSON - Array of agent statuses
monitor_all_status() {
    echo "["

    local first=true
    local roles
    roles=$(_get_active_roles)

    if [[ -z "$roles" ]]; then
        # Check port allocations as fallback
        local port_list
        port_list=$(port_list 2>/dev/null || echo "{}")

        roles=$(echo "$port_list" | grep -oE '"[^"]+":' | tr -d '":' || echo "")
    fi

    for role in $roles; do
        if ! $first; then
            echo ","
        fi
        first=false

        monitor_agent_status "$role"
    done

    echo ""
    echo "]"
}

# Display human-readable dashboard
# @param $1 session_name - Optional session name filter
monitor_dashboard() {
    local session_name="${1:-}"

    clear 2>/dev/null || true

    echo -e "${BOLD}${CYAN}"
    echo "╔════════════════════════════════════════════════════════════════════╗"
    echo "║               PARALLEL AGENT MONITOR                               ║"
    echo "╚════════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"

    local now
    now=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${BLUE}Last Updated: ${now}${NC}"
    echo ""

    # Get all agent statuses
    local roles
    roles=$(_get_active_roles)

    if [[ -z "$roles" ]]; then
        echo -e "${YELLOW}No active agents detected.${NC}"
        echo ""
        echo "Start parallel agents with:"
        echo "  /workflows:parallel <feature> --roles=backend,frontend,qa"
        return
    fi

    echo -e "${BOLD}Active Agents:${NC}"
    echo "─────────────────────────────────────────────────────────────────────"
    printf "%-12s %-10s %-8s %-20s %-15s\n" "ROLE" "STATUS" "PORT" "CURRENT TASK" "WORKTREE"
    echo "─────────────────────────────────────────────────────────────────────"

    local blocked_count=0
    local active_count=0
    local total_count=0

    for role in $roles; do
        ((total_count++))

        local status="inactive"
        local port=""
        local task="N/A"
        local worktree_clean="N/A"

        # Check worktree
        if worktree_exists "$role" 2>/dev/null; then
            local wt_status
            wt_status=$(worktree_status "$role" 2>/dev/null || echo '{}')
            worktree_clean=$(echo "$wt_status" | grep -o '"clean": [^,}]*' | grep -o 'true\|false' || echo "unknown")

            if [[ "$worktree_clean" == "true" ]]; then
                worktree_clean="${GREEN}clean${NC}"
            else
                worktree_clean="${YELLOW}dirty${NC}"
            fi

            status="active"
            ((active_count++))
        fi

        # Check port
        port=$(port_get "$role" 2>/dev/null || echo "")
        [[ -z "$port" ]] && port="-"

        # Check blockers
        if _has_blockers "$role"; then
            status="blocked"
            ((blocked_count++))
            ((active_count--))
        fi

        # Get current task from progress
        local progress_file="${PROGRESS_DIR}/${role}/claude-progress.txt"
        if [[ -f "$progress_file" ]]; then
            task=$(grep -A1 "^## Current Task" "$progress_file" 2>/dev/null | tail -1 | head -c 18 || echo "N/A")
            [[ -z "$task" ]] && task="N/A"
        fi

        # Color status
        local status_colored
        case "$status" in
            "active")
                status_colored="${GREEN}active${NC}"
                ;;
            "blocked")
                status_colored="${RED}BLOCKED${NC}"
                ;;
            *)
                status_colored="${YELLOW}inactive${NC}"
                ;;
        esac

        printf "%-12s ${status_colored}   %-8s %-20s " "$role" "$port" "$task"
        echo -e "$worktree_clean"
    done

    echo "─────────────────────────────────────────────────────────────────────"
    echo ""

    # Summary
    echo -e "${BOLD}Summary:${NC}"
    echo -e "  Total:   ${total_count}"
    echo -e "  Active:  ${GREEN}${active_count}${NC}"
    echo -e "  Blocked: ${RED}${blocked_count}${NC}"
    echo ""

    # Show blockers if any
    if [[ $blocked_count -gt 0 ]]; then
        echo -e "${RED}${BOLD}⚠ BLOCKERS DETECTED:${NC}"
        for role in $roles; do
            if _has_blockers "$role"; then
                local progress_file="${PROGRESS_DIR}/${role}/claude-progress.txt"
                echo -e "  ${YELLOW}${role}:${NC}"
                grep -i "blocked\|waiting\|dependency" "$progress_file" 2>/dev/null | head -3 | sed 's/^/    /'
            fi
        done
        echo ""
    fi

    # Port allocations
    echo -e "${BOLD}Port Allocations:${NC}"
    port_list 2>/dev/null | tr ',' '\n' | tr -d '{}' | sed 's/^/  /'
    echo ""

    # Tmux sessions
    echo -e "${BOLD}Tmux Sessions:${NC}"
    if command -v tmux &>/dev/null; then
        tmux list-sessions -F "  #{session_name} (#{session_windows} windows)" 2>/dev/null | grep "workflow-" || echo "  No workflow sessions"
    else
        echo "  tmux not available"
    fi
    echo ""

    echo -e "${BLUE}Refresh: ${MONITOR_REFRESH}s | Press Ctrl+C to exit${NC}"
}

# Watch mode - continuous monitoring
# @param $1 session_name - Optional session filter
monitor_watch() {
    local session_name="${1:-}"

    echo -e "${BLUE}Starting monitor (refresh every ${MONITOR_REFRESH}s)...${NC}"
    echo "Press Ctrl+C to stop"

    trap 'echo -e "\n${GREEN}Monitor stopped${NC}"; exit 0' INT

    while true; do
        monitor_dashboard "$session_name"
        sleep "$MONITOR_REFRESH"
    done
}

# Generate summary report
# @return string - Summary text
monitor_summary() {
    local roles
    roles=$(_get_active_roles)

    local total=0
    local active=0
    local blocked=0
    local completed=0

    for role in $roles; do
        ((total++))

        if _has_blockers "$role"; then
            ((blocked++))
        elif worktree_exists "$role" 2>/dev/null; then
            ((active++))
        fi
    done

    cat << EOF
Parallel Agent Summary
═════════════════════════
Total Agents:    $total
Active:          $active
Blocked:         $blocked
Completed:       $completed

Port Range:      ${PORT_RANGE_START:-3001}-${PORT_RANGE_END:-3010}
Worktree Base:   ${WORKTREE_BASE:-N/A}
EOF
}

# Detect issues and provide recommendations
# @return JSON - Issues and recommendations
monitor_diagnose() {
    echo "{"
    echo '  "issues": ['

    local first=true
    local roles
    roles=$(_get_active_roles)

    for role in $roles; do
        # Check for uncommitted changes
        if worktree_exists "$role" 2>/dev/null; then
            local wt_path
            wt_path=$(worktree_path "$role")
            local changes
            changes=$(cd "$wt_path" && git status --porcelain 2>/dev/null | wc -l || echo "0")

            if [[ "$changes" -gt 10 ]]; then
                if ! $first; then echo ","; fi
                first=false
                echo "    {\"role\": \"${role}\", \"issue\": \"many_uncommitted_changes\", \"count\": ${changes}}"
            fi
        fi

        # Check for blockers
        if _has_blockers "$role"; then
            if ! $first; then echo ","; fi
            first=false
            echo "    {\"role\": \"${role}\", \"issue\": \"blocked\"}"
        fi

        # Check port conflicts
        local port
        port=$(port_get "$role" 2>/dev/null || echo "")
        if [[ -n "$port" ]] && port_in_use "$port" 2>/dev/null; then
            if ! $first; then echo ","; fi
            first=false
            echo "    {\"role\": \"${role}\", \"issue\": \"port_conflict\", \"port\": ${port}}"
        fi
    done

    echo ""
    echo "  ],"
    echo '  "recommendations": ['

    first=true

    # General recommendations based on issues found
    if [[ -n "$roles" ]]; then
        if ! $first; then echo ","; fi
        first=false
        echo '    "Regularly commit changes to avoid losing work"'
    fi

    echo ""
    echo "  ]"
    echo "}"
}

# Usage information
usage() {
    cat << EOF
Parallel Agent Monitor - Track status of parallel agents

Usage:
  source monitor.sh

Functions:
  monitor_agent_status <role>    Get single agent status (JSON)
  monitor_all_status             Get all agents status (JSON)
  monitor_dashboard [session]    Display human-readable dashboard
  monitor_watch [session]        Continuous monitoring mode
  monitor_summary                Generate summary report
  monitor_diagnose               Detect issues and recommendations

Environment:
  MONITOR_REFRESH    Refresh interval in seconds (default: 5)

Examples:
  # View dashboard
  monitor_dashboard

  # Watch mode (auto-refresh)
  monitor_watch

  # Get JSON status
  monitor_all_status | jq '.'

  # Single agent status
  monitor_agent_status "backend" | jq '.'

  # Diagnose issues
  monitor_diagnose | jq '.'
EOF
}

# If script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        --help|-h)
            usage
            ;;
        status)
            if [[ -n "${2:-}" ]]; then
                monitor_agent_status "$2"
            else
                monitor_all_status
            fi
            ;;
        dashboard)
            monitor_dashboard "${2:-}"
            ;;
        watch)
            monitor_watch "${2:-}"
            ;;
        summary)
            monitor_summary
            ;;
        diagnose)
            monitor_diagnose
            ;;
        *)
            # Default: show dashboard
            monitor_dashboard
            ;;
    esac
fi
