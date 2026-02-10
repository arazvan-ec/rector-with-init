#!/usr/bin/env bash
# Worktree Manager - Git worktree automation for parallel agents
# Source: workmux, uzi patterns
# Feature: workflow-improvements-2026 | Task: BE-008

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
PROJECT_ROOT="$(cd "${WORKFLOW_DIR}/../.." && pwd)"

# Configuration
WORKTREE_BASE="${WORKTREE_BASE:-${PROJECT_ROOT}/.worktrees}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Ensure we're in a git repository
_ensure_git_repo() {
    if ! git rev-parse --git-dir > /dev/null 2>&1; then
        echo -e "${RED}Error: Not in a git repository${NC}" >&2
        return 1
    fi
}

# Get the main branch name (main or master)
_get_main_branch() {
    if git show-ref --verify --quiet refs/heads/main; then
        echo "main"
    elif git show-ref --verify --quiet refs/heads/master; then
        echo "master"
    else
        git branch --show-current
    fi
}

# Create a new worktree for an agent role
# @param $1 role - Agent role
# @param $2 branch - Branch name (auto-generated if empty)
# @param $3 base_branch - Base branch (default: main)
# @return string - Path to worktree
worktree_create() {
    local role="${1:?Role required}"
    local branch="${2:-}"
    local base_branch="${3:-$(_get_main_branch)}"

    _ensure_git_repo

    local worktree_path="${WORKTREE_BASE}/${role}"

    # Check if worktree already exists
    if [[ -d "$worktree_path" ]]; then
        echo -e "${YELLOW}Worktree already exists: ${worktree_path}${NC}"
        echo "$worktree_path"
        return 0
    fi

    # Generate branch name if not provided
    if [[ -z "$branch" ]]; then
        local feature_id
        feature_id=$(basename "$(pwd)" | tr '[:upper:]' '[:lower:]')
        branch="feature/${feature_id}-${role}"
    fi

    # Ensure base directory exists
    mkdir -p "$WORKTREE_BASE"

    # Check if branch exists
    if git show-ref --verify --quiet "refs/heads/${branch}"; then
        # Branch exists, use it
        echo -e "${BLUE}Using existing branch: ${branch}${NC}"
        git worktree add "$worktree_path" "$branch"
    else
        # Create new branch from base
        echo -e "${BLUE}Creating new branch: ${branch} from ${base_branch}${NC}"
        git worktree add -b "$branch" "$worktree_path" "$base_branch"
    fi

    echo -e "${GREEN}Worktree created: ${worktree_path}${NC}"
    echo "$worktree_path"
}

# List all active worktrees
# @return JSON - Array of worktree info
worktree_list() {
    _ensure_git_repo

    echo "["
    local first=true

    while IFS= read -r line; do
        if [[ -z "$line" ]]; then
            continue
        fi

        local path branch

        # Parse git worktree list output
        path=$(echo "$line" | awk '{print $1}')
        branch=$(echo "$line" | grep -oE '\[.*\]' | tr -d '[]' || echo "unknown")

        # Skip if not in our worktree directory
        if [[ ! "$path" =~ \.worktrees ]]; then
            continue
        fi

        # Get role from path
        local role
        role=$(basename "$path")

        # Check status
        local status="active"
        local uncommitted=0
        if [[ -d "$path" ]]; then
            uncommitted=$(cd "$path" && git status --porcelain 2>/dev/null | wc -l || echo "0")
            if [[ "$uncommitted" -gt 0 ]]; then
                status="dirty"
            fi
        fi

        if ! $first; then
            echo ","
        fi
        first=false

        cat << EOF
  {
    "role": "${role}",
    "path": "${path}",
    "branch": "${branch}",
    "status": "${status}",
    "uncommitted_changes": ${uncommitted}
  }
EOF
    done < <(git worktree list 2>/dev/null)

    echo ""
    echo "]"
}

# Get worktree status
# @param $1 role - Agent role
# @return JSON - Worktree status
worktree_status() {
    local role="${1:?Role required}"

    _ensure_git_repo

    local worktree_path="${WORKTREE_BASE}/${role}"

    if [[ ! -d "$worktree_path" ]]; then
        cat << EOF
{
  "exists": false,
  "error": "Worktree not found for role: ${role}"
}
EOF
        return 1
    fi

    local branch uncommitted ahead behind clean

    cd "$worktree_path"

    branch=$(git branch --show-current 2>/dev/null || echo "unknown")
    uncommitted=$(git status --porcelain 2>/dev/null | wc -l)

    # Get ahead/behind count
    ahead=$(git rev-list --count "@{u}..HEAD" 2>/dev/null || echo "0")
    behind=$(git rev-list --count "HEAD..@{u}" 2>/dev/null || echo "0")

    if [[ "$uncommitted" -eq 0 ]]; then
        clean="true"
    else
        clean="false"
    fi

    cat << EOF
{
  "exists": true,
  "path": "${worktree_path}",
  "branch": "${branch}",
  "clean": ${clean},
  "uncommitted_changes": ${uncommitted},
  "ahead": ${ahead},
  "behind": ${behind}
}
EOF

    cd - > /dev/null
}

# Cleanup worktree
# @param $1 role - Agent role
# @param $2 force - Force cleanup (default: false)
# @return void
worktree_cleanup() {
    local role="${1:?Role required}"
    local force="${2:-false}"

    _ensure_git_repo

    local worktree_path="${WORKTREE_BASE}/${role}"

    if [[ ! -d "$worktree_path" ]]; then
        echo -e "${YELLOW}Worktree not found: ${worktree_path}${NC}"
        return 0
    fi

    # Check for uncommitted changes
    local uncommitted
    uncommitted=$(cd "$worktree_path" && git status --porcelain 2>/dev/null | wc -l)

    if [[ "$uncommitted" -gt 0 && "$force" != "true" ]]; then
        echo -e "${RED}Error: Worktree has uncommitted changes (${uncommitted} files)${NC}" >&2
        echo -e "${YELLOW}Use force=true to cleanup anyway, or commit changes first.${NC}" >&2
        return 1
    fi

    # Get branch name before removing
    local branch
    branch=$(cd "$worktree_path" && git branch --show-current 2>/dev/null || echo "")

    # Remove worktree
    git worktree remove "$worktree_path" ${force:+--force}

    echo -e "${GREEN}Worktree removed: ${worktree_path}${NC}"

    # Optionally delete the branch
    if [[ -n "$branch" && "$branch" != "$(_get_main_branch)" ]]; then
        echo -e "${YELLOW}Branch '${branch}' still exists. Delete with: git branch -d ${branch}${NC}"
    fi
}

# Sync worktree with main branch
# @param $1 role - Agent role
# @return void
worktree_sync() {
    local role="${1:?Role required}"

    _ensure_git_repo

    local worktree_path="${WORKTREE_BASE}/${role}"

    if [[ ! -d "$worktree_path" ]]; then
        echo -e "${RED}Error: Worktree not found: ${worktree_path}${NC}" >&2
        return 1
    fi

    local main_branch
    main_branch=$(_get_main_branch)

    echo -e "${BLUE}Syncing ${role} with ${main_branch}...${NC}"

    cd "$worktree_path"

    # Fetch latest
    git fetch origin "$main_branch"

    # Check for uncommitted changes
    local uncommitted
    uncommitted=$(git status --porcelain 2>/dev/null | wc -l)

    if [[ "$uncommitted" -gt 0 ]]; then
        echo -e "${YELLOW}Warning: Uncommitted changes. Stashing...${NC}"
        git stash
        git merge "origin/${main_branch}" --no-edit
        git stash pop || true
    else
        git merge "origin/${main_branch}" --no-edit
    fi

    echo -e "${GREEN}Sync complete for ${role}${NC}"

    cd - > /dev/null
}

# Cleanup all worktrees
worktree_cleanup_all() {
    local force="${1:-false}"

    _ensure_git_repo

    if [[ ! -d "$WORKTREE_BASE" ]]; then
        echo -e "${YELLOW}No worktrees directory found.${NC}"
        return 0
    fi

    echo -e "${BLUE}Cleaning up all worktrees...${NC}"

    for dir in "${WORKTREE_BASE}"/*; do
        if [[ -d "$dir" ]]; then
            local role
            role=$(basename "$dir")
            worktree_cleanup "$role" "$force" || true
        fi
    done

    # Remove empty worktrees directory
    if [[ -d "$WORKTREE_BASE" ]] && [[ -z "$(ls -A "$WORKTREE_BASE" 2>/dev/null)" ]]; then
        rmdir "$WORKTREE_BASE"
        echo -e "${GREEN}Removed empty worktrees directory${NC}"
    fi
}

# Get worktree path for role
worktree_path() {
    local role="${1:?Role required}"
    echo "${WORKTREE_BASE}/${role}"
}

# Check if worktree exists
worktree_exists() {
    local role="${1:?Role required}"
    [[ -d "${WORKTREE_BASE}/${role}" ]]
}

# Usage information
usage() {
    cat << EOF
Worktree Manager - Git worktree automation for parallel agents

Usage:
  source worktree_manager.sh

Functions:
  worktree_create <role> [branch] [base]  Create worktree for role
  worktree_list                            List all worktrees (JSON)
  worktree_status <role>                   Get worktree status (JSON)
  worktree_cleanup <role> [force]          Remove worktree
  worktree_sync <role>                     Sync with main branch
  worktree_cleanup_all [force]             Remove all worktrees
  worktree_path <role>                     Get worktree path
  worktree_exists <role>                   Check if exists (exit code)

Examples:
  # Create worktrees for parallel development
  worktree_create "backend"
  worktree_create "frontend"
  worktree_create "qa"

  # List all worktrees
  worktree_list | jq '.'

  # Check status
  worktree_status "backend" | jq '.'

  # Sync with main
  worktree_sync "backend"

  # Cleanup when done
  worktree_cleanup "backend"
  worktree_cleanup_all

Worktrees are created in: ${WORKTREE_BASE}/
EOF
}

# If script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        --help|-h)
            usage
            ;;
        create)
            worktree_create "${2:-}" "${3:-}" "${4:-}"
            ;;
        list)
            worktree_list
            ;;
        status)
            worktree_status "${2:-}"
            ;;
        cleanup)
            worktree_cleanup "${2:-}" "${3:-}"
            ;;
        sync)
            worktree_sync "${2:-}"
            ;;
        cleanup-all)
            worktree_cleanup_all "${2:-}"
            ;;
        *)
            usage
            ;;
    esac
fi
