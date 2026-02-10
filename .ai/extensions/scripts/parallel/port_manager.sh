#!/usr/bin/env bash
# Port Manager - Allocate ports for parallel dev servers
# Feature: workflow-improvements-2026 | Task: BE-009

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
PROJECT_DIR="$(dirname "$WORKFLOW_DIR")/project"

# Configuration
PORT_STATE_FILE="${PROJECT_DIR}/sessions/port_allocations.json"
PORT_RANGE_START="${PORT_RANGE_START:-3001}"
PORT_RANGE_END="${PORT_RANGE_END:-3010}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Ensure state directory exists
_ensure_state_dir() {
    mkdir -p "$(dirname "$PORT_STATE_FILE")"
}

# Initialize state file if not exists
_init_state_file() {
    _ensure_state_dir
    if [[ ! -f "$PORT_STATE_FILE" ]]; then
        echo '{}' > "$PORT_STATE_FILE"
    fi
}

# Read allocations from state file
_read_allocations() {
    _init_state_file
    cat "$PORT_STATE_FILE"
}

# Write allocations to state file
_write_allocations() {
    local data="$1"
    _ensure_state_dir
    echo "$data" > "$PORT_STATE_FILE"
}

# Check if port is in use
# @param $1 port - Port number
# @return bool - true if in use
port_in_use() {
    local port="${1:?Port required}"

    # Try multiple methods to check port availability
    if command -v ss &> /dev/null; then
        ss -tuln 2>/dev/null | grep -q ":${port} " && return 0
    elif command -v netstat &> /dev/null; then
        netstat -tuln 2>/dev/null | grep -q ":${port} " && return 0
    elif command -v lsof &> /dev/null; then
        lsof -i ":${port}" &>/dev/null && return 0
    else
        # Fallback: try to bind to the port
        if (echo >/dev/tcp/localhost/"$port") 2>/dev/null; then
            return 0
        fi
    fi

    return 1
}

# Find next available port
_find_available_port() {
    local start="${1:-$PORT_RANGE_START}"

    for ((port = start; port <= PORT_RANGE_END; port++)); do
        # Check if already allocated
        local allocations
        allocations=$(_read_allocations)

        if echo "$allocations" | grep -q "\"$port\""; then
            continue
        fi

        # Check if in use by system
        if port_in_use "$port"; then
            continue
        fi

        echo "$port"
        return 0
    done

    echo -e "${RED}Error: No available ports in range ${PORT_RANGE_START}-${PORT_RANGE_END}${NC}" >&2
    return 1
}

# Allocate port for a role
# @param $1 role - Agent role
# @return int - Allocated port number
port_allocate() {
    local role="${1:?Role required}"

    _init_state_file

    local allocations
    allocations=$(_read_allocations)

    # Check if role already has allocation
    local existing_port
    existing_port=$(echo "$allocations" | grep -oE "\"${role}\": [0-9]+" | grep -oE '[0-9]+' || echo "")

    if [[ -n "$existing_port" ]]; then
        echo "$existing_port"
        return 0
    fi

    # Find available port
    local port
    port=$(_find_available_port)

    if [[ -z "$port" ]]; then
        return 1
    fi

    # Update allocations
    if [[ "$allocations" == "{}" ]]; then
        allocations="{\"${role}\": ${port}}"
    else
        # Remove trailing } and add new entry
        allocations="${allocations%\}}, \"${role}\": ${port}}"
    fi

    _write_allocations "$allocations"

    echo -e "${GREEN}Port ${port} allocated for ${role}${NC}" >&2
    echo "$port"
}

# Release port for a role
# @param $1 role - Agent role
port_release() {
    local role="${1:?Role required}"

    _init_state_file

    local allocations
    allocations=$(_read_allocations)

    # Check if role has allocation
    if ! echo "$allocations" | grep -q "\"${role}\":"; then
        echo -e "${YELLOW}No port allocated for ${role}${NC}" >&2
        return 0
    fi

    # Remove allocation (simple approach - rebuild JSON)
    local new_allocations="{"
    local first=true

    # Parse and rebuild without the role
    while IFS=': ' read -r key value; do
        key=$(echo "$key" | tr -d '"{}, ')
        value=$(echo "$value" | tr -d '}, ')

        [[ -z "$key" ]] && continue
        [[ "$key" == "$role" ]] && continue

        if ! $first; then
            new_allocations+=", "
        fi
        first=false
        new_allocations+="\"${key}\": ${value}"
    done < <(echo "$allocations" | tr ',' '\n' | grep -E '"[^"]+": [0-9]+')

    new_allocations+="}"

    _write_allocations "$new_allocations"

    echo -e "${GREEN}Port released for ${role}${NC}"
}

# Get port for role
# @param $1 role - Agent role
# @return int - Port number or empty
port_get() {
    local role="${1:?Role required}"

    _init_state_file

    local allocations
    allocations=$(_read_allocations)

    local port
    port=$(echo "$allocations" | grep -oE "\"${role}\": [0-9]+" | grep -oE '[0-9]+' || echo "")

    echo "$port"
}

# List all allocated ports
# @return JSON - Port allocations
port_list() {
    _init_state_file
    _read_allocations
}

# Release all ports
port_release_all() {
    _ensure_state_dir
    echo '{}' > "$PORT_STATE_FILE"
    echo -e "${GREEN}All ports released${NC}"
}

# Display port status
port_status() {
    _init_state_file

    echo "=== Port Allocations ==="
    echo "Range: ${PORT_RANGE_START}-${PORT_RANGE_END}"
    echo ""

    local allocations
    allocations=$(_read_allocations)

    if [[ "$allocations" == "{}" ]]; then
        echo "No ports currently allocated."
        return 0
    fi

    echo "Allocated:"

    # Parse allocations
    while IFS=': ' read -r key value; do
        key=$(echo "$key" | tr -d '"{}, ')
        value=$(echo "$value" | tr -d '}, ')

        [[ -z "$key" ]] && continue

        local status="available"
        if port_in_use "$value"; then
            status="in use"
        fi

        printf "  %-12s : %s (%s)\n" "$key" "$value" "$status"
    done < <(echo "$allocations" | tr ',' '\n' | grep -E '"[^"]+": [0-9]+')
}

# Usage information
usage() {
    cat << EOF
Port Manager - Allocate ports for parallel dev servers

Usage:
  source port_manager.sh

Functions:
  port_allocate <role>     Allocate port for role (returns port number)
  port_release <role>      Release port for role
  port_get <role>          Get allocated port for role
  port_list                List all allocations (JSON)
  port_release_all         Release all ports
  port_in_use <port>       Check if port is in use (exit code)
  port_status              Display human-readable status

Configuration:
  PORT_RANGE_START  First port in range (default: ${PORT_RANGE_START})
  PORT_RANGE_END    Last port in range (default: ${PORT_RANGE_END})

Examples:
  # Allocate ports
  backend_port=\$(port_allocate "backend")   # 3001
  frontend_port=\$(port_allocate "frontend") # 3002

  # Check allocation
  port_get "backend"  # 3001

  # List all
  port_list | jq '.'

  # Release
  port_release "backend"
  port_release_all

State file: ${PORT_STATE_FILE}
EOF
}

# If script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        --help|-h)
            usage
            ;;
        allocate)
            port_allocate "${2:-}"
            ;;
        release)
            port_release "${2:-}"
            ;;
        get)
            port_get "${2:-}"
            ;;
        list)
            port_list
            ;;
        release-all)
            port_release_all
            ;;
        status)
            port_status
            ;;
        in-use)
            if port_in_use "${2:-}"; then
                echo "Port ${2} is in use"
                exit 0
            else
                echo "Port ${2} is available"
                exit 1
            fi
            ;;
        *)
            usage
            ;;
    esac
fi
