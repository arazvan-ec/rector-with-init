#!/usr/bin/env bash
# Pre-commit Hook: TDD Enforcement
# Feature: workflow-improvements-2026 | Task: BE-018
#
# Install this hook:
#   cp .ai/workflow/hooks/pre_commit_tdd.sh .git/hooks/pre-commit
#   chmod +x .git/hooks/pre-commit
#
# Or symlink:
#   ln -sf ../../.ai/workflow/hooks/pre_commit_tdd.sh .git/hooks/pre-commit

set -euo pipefail

# Find script directory (handle symlinks)
HOOK_SOURCE="${BASH_SOURCE[0]}"
while [ -L "$HOOK_SOURCE" ]; do
    HOOK_DIR="$(cd -P "$(dirname "$HOOK_SOURCE")" && pwd)"
    HOOK_SOURCE="$(readlink "$HOOK_SOURCE")"
    [[ $HOOK_SOURCE != /* ]] && HOOK_SOURCE="$HOOK_DIR/$HOOK_SOURCE"
done
SCRIPT_DIR="$(cd -P "$(dirname "$HOOK_SOURCE")" && pwd)"

# Navigate to workflow directory
WORKFLOW_DIR="$(dirname "$SCRIPT_DIR")"
ENFORCEMENT_DIR="${WORKFLOW_DIR}/enforcement"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
TDD_STRICTNESS="${TDD_STRICTNESS:-medium}"
TDD_SKIP="${TDD_SKIP:-false}"

# Check if we should skip
if [[ "$TDD_SKIP" == "true" ]]; then
    echo -e "${YELLOW}TDD check skipped (TDD_SKIP=true)${NC}"
    exit 0
fi

# Check if commit message contains skip marker
if git log -1 --format="%B" 2>/dev/null | grep -qE '\[skip-tdd\]|\[tdd-skip\]'; then
    echo -e "${YELLOW}TDD check skipped ([skip-tdd] in message)${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║           PRE-COMMIT: TDD ENFORCEMENT                      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Source the TDD enforcer if available
if [[ -f "${ENFORCEMENT_DIR}/tdd_enforcer.sh" ]]; then
    source "${ENFORCEMENT_DIR}/tdd_enforcer.sh"
else
    echo -e "${YELLOW}Warning: TDD enforcer not found at ${ENFORCEMENT_DIR}/tdd_enforcer.sh${NC}"
    echo -e "${YELLOW}Skipping TDD checks.${NC}"
    exit 0
fi

# Run TDD checks
errors=0

# 1. Check for test deletions (always enforced)
echo -e "${BLUE}[1/2] Checking for test deletions...${NC}"
if ! tdd_check_deletion; then
    ((errors++))
    echo -e "${RED}BLOCKED: Test files cannot be deleted without explicit approval.${NC}"
    echo ""
    echo "To proceed, either:"
    echo "  1. Restore the deleted test files"
    echo "  2. Add [skip-tdd] to your commit message (requires justification)"
    echo ""
fi

# 2. Check test coverage for new/modified files
echo -e "${BLUE}[2/2] Checking test coverage...${NC}"

case "$TDD_STRICTNESS" in
    strict)
        if ! TDD_STRICTNESS=strict tdd_check_staged; then
            ((errors++))
        fi
        ;;
    medium)
        # In medium mode, we warn but don't block
        TDD_STRICTNESS=medium tdd_check_staged || true
        ;;
    relaxed)
        echo -e "${YELLOW}TDD checks relaxed - only test deletions blocked${NC}"
        ;;
esac

# Final result
echo ""
if [[ $errors -gt 0 ]]; then
    echo -e "${RED}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║           COMMIT BLOCKED: TDD VIOLATIONS                   ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo "Please address the TDD violations before committing."
    echo ""
    echo "Options:"
    echo "  1. Add missing test files"
    echo "  2. Run: TDD_STRICTNESS=relaxed git commit ..."
    echo "  3. Add [skip-tdd] to commit message (document reason)"
    echo ""
    exit 1
fi

echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║           TDD CHECK PASSED                                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

exit 0
