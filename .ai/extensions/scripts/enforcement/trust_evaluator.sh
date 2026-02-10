#!/usr/bin/env bash
# Trust Evaluator - Assess trust level for files and tasks
# Source: Addy Osmani's "70% Problem" framework
# Feature: workflow-improvements-2026 | Task: BE-005

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
TRUST_MODEL_FILE="${WORKFLOW_DIR}/trust_model.yaml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check if yq is available, fall back to grep-based parsing
HAS_YQ=false
if command -v yq &> /dev/null; then
    HAS_YQ=true
fi

# Simple pattern matching (glob-style)
# Supports: *, **, ?
_match_pattern() {
    local pattern="$1"
    local filepath="$2"

    # Convert glob pattern to regex
    local regex="$pattern"

    # Escape special regex characters except * and ?
    regex=$(echo "$regex" | sed 's/\./\\./g')
    regex=$(echo "$regex" | sed 's/\[/\\[/g')
    regex=$(echo "$regex" | sed 's/\]/\\]/g')

    # Convert glob patterns
    regex=$(echo "$regex" | sed 's/\*\*/.*/g')      # ** -> .*
    regex=$(echo "$regex" | sed 's/\*/[^\/]*/g')   # * -> [^/]*
    regex=$(echo "$regex" | sed 's/?/./g')          # ? -> .

    # Anchor the pattern
    regex="^${regex}$"

    # Test match
    if echo "$filepath" | grep -qE "$regex"; then
        return 0
    else
        return 1
    fi
}

# Get patterns for a trust level
_get_patterns_for_level() {
    local level="$1"

    if [[ ! -f "$TRUST_MODEL_FILE" ]]; then
        echo ""
        return
    fi

    # Extract patterns using grep (works without yq)
    local in_level=false
    local in_contexts=false

    while IFS= read -r line; do
        # Check if we're entering the target level
        if echo "$line" | grep -qE "^  ${level}:"; then
            in_level=true
            continue
        fi

        # Check if we're leaving the level
        if $in_level && echo "$line" | grep -qE "^  [a-z]+:$" && ! echo "$line" | grep -qE "^  ${level}:"; then
            in_level=false
            continue
        fi

        # Check if we're in contexts section
        if $in_level && echo "$line" | grep -qE "^    contexts:"; then
            in_contexts=true
            continue
        fi

        # Extract pattern if in contexts
        if $in_level && $in_contexts; then
            if echo "$line" | grep -qE '^\s+- pattern:'; then
                echo "$line" | sed 's/.*pattern: "\([^"]*\)".*/\1/' | sed 's/.*pattern: //' | tr -d '"'
            fi
            # Check if contexts section ended
            if echo "$line" | grep -qE "^    [a-z_]+:" && ! echo "$line" | grep -qE "^\s+-"; then
                in_contexts=false
            fi
        fi
    done < "$TRUST_MODEL_FILE"
}

# Get trust level for a file path
# @param $1 filepath - Path to evaluate
# @return string - Trust level (high, medium, low)
trust_get_level() {
    local filepath="${1:?Filepath required}"

    # Normalize path (remove leading ./)
    filepath="${filepath#./}"

    # Check low trust patterns first (most restrictive)
    local low_patterns
    low_patterns=$(_get_patterns_for_level "low")

    while IFS= read -r pattern; do
        [[ -z "$pattern" ]] && continue
        if _match_pattern "$pattern" "$filepath"; then
            echo "low"
            return 0
        fi
    done <<< "$low_patterns"

    # Check high trust patterns
    local high_patterns
    high_patterns=$(_get_patterns_for_level "high")

    while IFS= read -r pattern; do
        [[ -z "$pattern" ]] && continue
        if _match_pattern "$pattern" "$filepath"; then
            echo "high"
            return 0
        fi
    done <<< "$high_patterns"

    # Default to medium
    echo "medium"
}

# Check if auto-approve is allowed for a file
# @param $1 filepath - Path to evaluate
# @return bool - true if auto-approve allowed
trust_can_auto_approve() {
    local filepath="${1:?Filepath required}"

    local level
    level=$(trust_get_level "$filepath")

    if [[ "$level" == "high" ]]; then
        return 0  # true
    else
        return 1  # false
    fi
}

# Get supervision requirement for a file
# @param $1 filepath - Path to evaluate
# @return string - Supervision type
trust_get_supervision() {
    local filepath="${1:?Filepath required}"

    local level
    level=$(trust_get_level "$filepath")

    case "$level" in
        high)
            echo "minimal"
            ;;
        medium)
            echo "code_review_required"
            ;;
        low)
            echo "pair_programming"
            ;;
        *)
            echo "code_review_required"
            ;;
    esac
}

# Evaluate trust for a task type
# @param $1 task_type - Type of task
# @return JSON - Trust assessment
trust_evaluate_task() {
    local task_type="${1:?Task type required}"

    local level="medium"
    local auto_approve="false"
    local supervision="code_review_required"
    local reason="Default medium trust"

    # High trust task types
    case "$task_type" in
        boilerplate|documentation|unit_tests|formatting|refactoring_simple)
            level="high"
            auto_approve="true"
            supervision="minimal"
            reason="Task type '${task_type}' is safe for autonomous work"
            ;;
        feature_implementation|api_endpoints|ui_components|bug_fix|refactoring_complex)
            level="medium"
            auto_approve="false"
            supervision="code_review_required"
            reason="Task type '${task_type}' requires code review"
            ;;
        security|authentication|authorization|payment|migration|infrastructure|deployment|cryptography)
            level="low"
            auto_approve="false"
            supervision="pair_programming"
            reason="Task type '${task_type}' is high-risk and requires human oversight"
            ;;
        *)
            level="medium"
            auto_approve="false"
            supervision="code_review_required"
            reason="Unknown task type defaults to medium trust"
            ;;
    esac

    cat << EOF
{
  "task_type": "${task_type}",
  "level": "${level}",
  "auto_approve": ${auto_approve},
  "supervision": "${supervision}",
  "reason": "${reason}"
}
EOF
}

# Get full trust assessment for a file
# @param $1 filepath - Path to evaluate
# @return JSON - Full assessment
trust_assess() {
    local filepath="${1:?Filepath required}"

    local level
    level=$(trust_get_level "$filepath")

    local auto_approve="false"
    local supervision
    local escalation="false"
    local reason

    supervision=$(trust_get_supervision "$filepath")

    case "$level" in
        high)
            auto_approve="true"
            reason="File matches high-trust pattern"
            ;;
        medium)
            reason="File matches medium-trust pattern or is default"
            ;;
        low)
            escalation="true"
            reason="File matches low-trust pattern (security/auth/payment)"
            ;;
    esac

    cat << EOF
{
  "filepath": "${filepath}",
  "level": "${level}",
  "auto_approve": ${auto_approve},
  "supervision": "${supervision}",
  "escalation": ${escalation},
  "reason": "${reason}"
}
EOF
}

# Display human-readable trust evaluation
trust_display() {
    local filepath="${1:?Filepath required}"

    local level
    level=$(trust_get_level "$filepath")

    local supervision
    supervision=$(trust_get_supervision "$filepath")

    local can_auto
    if trust_can_auto_approve "$filepath"; then
        can_auto="YES"
    else
        can_auto="NO"
    fi

    echo ""
    echo -e "${BLUE}=== Trust Evaluation ===${NC}"
    echo -e "File: ${filepath}"
    echo ""

    case "$level" in
        high)
            echo -e "Trust Level: ${GREEN}HIGH${NC}"
            ;;
        medium)
            echo -e "Trust Level: ${YELLOW}MEDIUM${NC}"
            ;;
        low)
            echo -e "Trust Level: ${RED}LOW${NC}"
            ;;
    esac

    echo -e "Auto-approve: ${can_auto}"
    echo -e "Supervision: ${supervision}"

    echo ""
    echo -e "${BLUE}Recommendations:${NC}"

    case "$level" in
        high)
            echo "  - Safe to proceed autonomously"
            echo "  - Standard testing practices apply"
            ;;
        medium)
            echo "  - Request code review before merge"
            echo "  - Document any complex logic"
            echo "  - Ensure test coverage"
            ;;
        low)
            echo "  - Work with human reviewer present"
            echo "  - Document all decisions in DECISIONS.md"
            echo "  - Run security scan before commit"
            echo "  - Consider threat modeling"
            ;;
    esac
}

# Check multiple files and return lowest trust level
trust_check_batch() {
    local lowest_level="high"

    for filepath in "$@"; do
        local level
        level=$(trust_get_level "$filepath")

        case "$level" in
            low)
                lowest_level="low"
                break  # Can't get lower
                ;;
            medium)
                if [[ "$lowest_level" == "high" ]]; then
                    lowest_level="medium"
                fi
                ;;
        esac
    done

    echo "$lowest_level"
}

# Usage information
usage() {
    cat << EOF
Trust Evaluator - Assess trust level for files and tasks

Usage:
  source trust_evaluator.sh

Functions:
  trust_get_level <filepath>       Get trust level (high/medium/low)
  trust_can_auto_approve <path>    Check if auto-approve allowed (exit code)
  trust_get_supervision <path>     Get supervision requirement
  trust_evaluate_task <type>       Evaluate task type (returns JSON)
  trust_assess <filepath>          Full assessment (returns JSON)
  trust_display <filepath>         Human-readable display
  trust_check_batch <paths...>     Check multiple files, return lowest

Trust Levels:
  HIGH   - AI works autonomously (tests, docs, boilerplate)
  MEDIUM - AI works, human reviews (features, APIs)
  LOW    - AI suggests, human implements (security, payments)

Examples:
  trust_get_level "src/User.php"                    # medium
  trust_get_level "tests/UserTest.php"              # high
  trust_get_level "src/Security/AuthService.php"   # low

  if trust_can_auto_approve "README.md"; then
      echo "Safe to auto-approve"
  fi

  trust_display "src/Payment/Processor.php"
EOF
}

# If script is run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-}" in
        --help|-h)
            usage
            ;;
        --level)
            trust_get_level "${2:-}"
            ;;
        --supervision)
            trust_get_supervision "${2:-}"
            ;;
        --task)
            trust_evaluate_task "${2:-}"
            ;;
        --assess)
            trust_assess "${2:-}"
            ;;
        --display)
            trust_display "${2:-}"
            ;;
        "")
            usage
            ;;
        *)
            trust_display "$1"
            ;;
    esac
fi
