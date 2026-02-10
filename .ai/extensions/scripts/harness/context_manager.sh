#!/usr/bin/env bash
# Context Manager - Optimize context window usage
# Feature: workflow-improvements-2026 | Task: BE-019

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
PROJECT_ROOT="$(cd "${WORKFLOW_DIR}/../.." && pwd)"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
CONTEXT_LIMIT="${CONTEXT_LIMIT:-100000}"  # Approximate token limit
CHARS_PER_TOKEN="${CHARS_PER_TOKEN:-4}"    # Average chars per token
SKELETON_MAX_LINES="${SKELETON_MAX_LINES:-50}"
RELEVANCE_WINDOW="${RELEVANCE_WINDOW:-24}"  # Hours for recency

# Track file access
declare -A FILE_ACCESS_TIMES
declare -A FILE_RELEVANCE

# Estimate token count for text
# @param $1 text - Text to estimate
# @return int - Estimated token count
_estimate_tokens() {
    local text="$1"
    local chars=${#text}
    echo $(( chars / CHARS_PER_TOKEN ))
}

# Estimate token count for a file
# @param $1 file_path - Path to file
# @return int - Estimated token count
context_estimate_file() {
    local file_path="${1:?File path required}"

    if [[ ! -f "$file_path" ]]; then
        echo "0"
        return
    fi

    local chars
    chars=$(wc -c < "$file_path" 2>/dev/null || echo "0")
    echo $(( chars / CHARS_PER_TOKEN ))
}

# Estimate total context used by files
# @param $@ files - List of files
# @return int - Total estimated tokens
context_estimate_total() {
    local total=0

    for file in "$@"; do
        local tokens
        tokens=$(context_estimate_file "$file")
        total=$((total + tokens))
    done

    echo "$total"
}

# Check if context is approaching limit
# @param $1 current_tokens - Current token count
# @return bool - true if should compact
context_should_compact() {
    local current_tokens="${1:?Token count required}"
    local threshold=$((CONTEXT_LIMIT * 80 / 100))  # 80% threshold

    [[ $current_tokens -gt $threshold ]]
}

# Generate code skeleton for a file
# @param $1 file_path - Path to file
# @param $2 max_lines - Maximum lines (default: 50)
# @return string - Skeleton code
context_generate_skeleton() {
    local file_path="${1:?File path required}"
    local max_lines="${2:-$SKELETON_MAX_LINES}"

    if [[ ! -f "$file_path" ]]; then
        echo "// File not found: $file_path"
        return 1
    fi

    local ext="${file_path##*.}"
    local basename
    basename=$(basename "$file_path")

    echo "// Skeleton: $file_path"
    echo "// (Full file available, showing structure only)"
    echo ""

    case "$ext" in
        ts|tsx|js|jsx)
            _skeleton_typescript "$file_path" "$max_lines"
            ;;
        py)
            _skeleton_python "$file_path" "$max_lines"
            ;;
        go)
            _skeleton_go "$file_path" "$max_lines"
            ;;
        sh|bash)
            _skeleton_bash "$file_path" "$max_lines"
            ;;
        php)
            _skeleton_php "$file_path" "$max_lines"
            ;;
        *)
            # Generic: show first/last lines with structure
            _skeleton_generic "$file_path" "$max_lines"
            ;;
    esac
}

# TypeScript/JavaScript skeleton
_skeleton_typescript() {
    local file="$1"
    local max_lines="$2"

    # Extract imports, exports, class/function declarations
    grep -E '^(import |export |class |interface |type |function |const [A-Z]|async function )' "$file" 2>/dev/null | \
        head -n "$max_lines" | \
        sed 's/{$/{ ... }/'
}

# Python skeleton
_skeleton_python() {
    local file="$1"
    local max_lines="$2"

    # Extract imports, class/function definitions
    grep -E '^(import |from |class |def |async def |@)' "$file" 2>/dev/null | \
        head -n "$max_lines" | \
        sed 's/:$/: .../'
}

# Go skeleton
_skeleton_go() {
    local file="$1"
    local max_lines="$2"

    # Extract package, imports, type/func declarations
    grep -E '^(package |import |type |func |var |const )' "$file" 2>/dev/null | \
        head -n "$max_lines" | \
        sed 's/{$/{ ... }/'
}

# Bash skeleton
_skeleton_bash() {
    local file="$1"
    local max_lines="$2"

    # Extract functions and key variables
    grep -E '^([a-zA-Z_]+\(\)|function [a-zA-Z_]+|[A-Z_]+[A-Z0-9_]*=|source |# [A-Z])' "$file" 2>/dev/null | \
        head -n "$max_lines"
}

# PHP skeleton
_skeleton_php() {
    local file="$1"
    local max_lines="$2"

    # Extract class, function, use declarations
    grep -E '^(namespace |use |class |interface |trait |function |public |private |protected )' "$file" 2>/dev/null | \
        head -n "$max_lines" | \
        sed 's/{$/{ ... }/'
}

# Generic skeleton
_skeleton_generic() {
    local file="$1"
    local max_lines="$2"
    local total_lines

    total_lines=$(wc -l < "$file" 2>/dev/null || echo "0")

    if [[ $total_lines -le $max_lines ]]; then
        cat "$file"
    else
        local head_lines=$((max_lines / 2))
        local tail_lines=$((max_lines / 2))

        head -n "$head_lines" "$file"
        echo ""
        echo "// ... ($((total_lines - max_lines)) lines omitted) ..."
        echo ""
        tail -n "$tail_lines" "$file"
    fi
}

# Calculate file relevance score
# @param $1 file_path - Path to file
# @return int - Relevance score (0-100)
_calculate_relevance() {
    local file_path="$1"
    local score=50  # Base score

    # Recency boost (modified recently)
    local mtime
    mtime=$(stat -c %Y "$file_path" 2>/dev/null || stat -f %m "$file_path" 2>/dev/null || echo "0")
    local now
    now=$(date +%s)
    local hours_old=$(( (now - mtime) / 3600 ))

    if [[ $hours_old -lt 1 ]]; then
        score=$((score + 30))
    elif [[ $hours_old -lt 24 ]]; then
        score=$((score + 20))
    elif [[ $hours_old -lt 168 ]]; then  # 1 week
        score=$((score + 10))
    fi

    # Path-based relevance
    local basename
    basename=$(basename "$file_path")

    # High relevance files
    [[ "$basename" == "CLAUDE.md" ]] && score=$((score + 20))
    [[ "$basename" == "README.md" ]] && score=$((score + 10))
    [[ "$file_path" == *"/src/"* ]] && score=$((score + 10))
    [[ "$file_path" == *"/lib/"* ]] && score=$((score + 10))

    # Lower relevance
    [[ "$file_path" == *"/node_modules/"* ]] && score=$((score - 30))
    [[ "$file_path" == *"/vendor/"* ]] && score=$((score - 30))
    [[ "$file_path" == *"/.git/"* ]] && score=0

    # Clamp to 0-100
    [[ $score -lt 0 ]] && score=0
    [[ $score -gt 100 ]] && score=100

    echo "$score"
}

# Prioritize files for context inclusion
# @param $@ files - List of files
# @return string - Sorted file list (most relevant first)
context_prioritize() {
    local scored_files=()

    for file in "$@"; do
        if [[ -f "$file" ]]; then
            local score
            score=$(_calculate_relevance "$file")
            scored_files+=("$score|$file")
        fi
    done

    # Sort by score descending
    printf '%s\n' "${scored_files[@]}" | sort -t'|' -k1 -nr | cut -d'|' -f2
}

# Get context usage summary
# @return string - Summary text
context_summary() {
    echo "=== Context Usage Summary ==="
    echo ""

    local total_tokens=0
    local file_count=0

    # Find recently accessed files
    while IFS= read -r file; do
        if [[ -f "$file" ]]; then
            local tokens
            tokens=$(context_estimate_file "$file")
            local relevance
            relevance=$(_calculate_relevance "$file")

            total_tokens=$((total_tokens + tokens))
            ((file_count++))

            printf "%-50s %6d tokens  (relevance: %d)\n" "${file:0:50}" "$tokens" "$relevance"
        fi
    done < <(find "$PROJECT_ROOT" -type f \( -name "*.ts" -o -name "*.js" -o -name "*.py" -o -name "*.sh" \) -mtime -1 2>/dev/null | head -20)

    echo ""
    echo "─────────────────────────────────────────────────────"
    echo "Files analyzed: $file_count"
    echo "Total estimated tokens: $total_tokens"
    echo "Context limit: $CONTEXT_LIMIT"
    echo "Usage: $((total_tokens * 100 / CONTEXT_LIMIT))%"
    echo ""

    if context_should_compact "$total_tokens"; then
        echo -e "${YELLOW}Recommendation: Consider using /clear to compact context${NC}"
    else
        echo -e "${GREEN}Context usage is within acceptable limits${NC}"
    fi
}

# Suggest context optimization
# @return string - Recommendations
context_suggest() {
    echo "=== Context Optimization Suggestions ==="
    echo ""

    local suggestions=()

    # Check for large files in recent context
    while IFS= read -r file; do
        local tokens
        tokens=$(context_estimate_file "$file")

        if [[ $tokens -gt 5000 ]]; then
            suggestions+=("Use skeleton for large file: $file ($tokens tokens)")
        fi
    done < <(find "$PROJECT_ROOT" -type f -mtime -1 -size +10k 2>/dev/null | head -10)

    # Check total context
    if [[ ${#suggestions[@]} -eq 0 ]]; then
        echo -e "${GREEN}No immediate optimizations needed${NC}"
    else
        for suggestion in "${suggestions[@]}"; do
            echo "  - $suggestion"
        done
    fi

    echo ""
    echo "General tips:"
    echo "  1. Use /clear when switching between tasks"
    echo "  2. Request skeleton views for large files"
    echo "  3. Focus on specific functions rather than entire files"
    echo "  4. Use Glob/Grep to find specific code vs reading entire files"
}

# Usage information
usage() {
    cat << EOF
Context Manager - Optimize context window usage

Usage:
  source context_manager.sh

Functions:
  context_estimate_file <path>       Estimate tokens for file
  context_estimate_total <files...>  Estimate total tokens
  context_should_compact <tokens>    Check if compaction needed
  context_generate_skeleton <path>   Generate code skeleton
  context_prioritize <files...>      Sort files by relevance
  context_summary                    Show context usage summary
  context_suggest                    Get optimization suggestions

Environment:
  CONTEXT_LIMIT       Approximate token limit (default: 100000)
  CHARS_PER_TOKEN     Chars per token estimate (default: 4)
  SKELETON_MAX_LINES  Max lines in skeleton (default: 50)

Examples:
  # Estimate file tokens
  tokens=\$(context_estimate_file "src/large_module.ts")
  echo "File uses ~\$tokens tokens"

  # Check if should compact
  if context_should_compact 80000; then
    echo "Consider using /clear"
  fi

  # Generate skeleton
  context_generate_skeleton "src/large_module.ts"

  # Prioritize files for reading
  context_prioritize src/*.ts | head -5

  # Get summary
  context_summary
EOF
}

# If script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        --help|-h)
            usage
            ;;
        estimate)
            context_estimate_file "${2:-}"
            ;;
        skeleton)
            context_generate_skeleton "${2:-}" "${3:-}"
            ;;
        prioritize)
            shift
            context_prioritize "$@"
            ;;
        summary)
            context_summary
            ;;
        suggest)
            context_suggest
            ;;
        *)
            context_summary
            ;;
    esac
fi
