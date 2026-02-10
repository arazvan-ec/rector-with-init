#!/bin/bash
# suggest_workflow.sh - Suggest optimal workflow based on feature complexity
# Analyzes feature requirements and suggests the best workflow
#
# Usage: ./suggest_workflow.sh [complexity_level]
#
# Arguments:
#   complexity_level: low, medium, high (optional, will be asked if not provided)
#
# Output: Suggested workflow and reasoning

set -e

# Colors
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

COMPLEXITY="${1:-}"

# If no complexity provided, show options
if [ -z "$COMPLEXITY" ]; then
    echo -e "${CYAN}Workflow Suggestion Tool${NC}"
    echo ""
    echo "Please specify the feature complexity:"
    echo ""
    echo "  ${GREEN}low${NC}     - Simple CRUD, minor UI changes, bug fixes"
    echo "            Single developer, < 1 day effort"
    echo ""
    echo "  ${GREEN}medium${NC}  - New feature with API + UI, some business logic"
    echo "            Multiple components, 1-3 days effort"
    echo ""
    echo "  ${GREEN}high${NC}    - Complex feature, multiple domains, integrations"
    echo "            Architectural decisions needed, > 3 days effort"
    echo ""
    echo "Usage: $0 <low|medium|high>"
    exit 0
fi

echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}         Workflow Recommendation             ${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

case "$COMPLEXITY" in
    low)
        echo -e "${GREEN}Recommended Workflow: default${NC}"
        echo ""
        echo "Reasoning:"
        echo "  - Low complexity features don't need exhaustive planning"
        echo "  - Planner creates basic specs, then implementation starts"
        echo "  - Full cycle (plan → code → review) in one workflow"
        echo ""
        echo "Characteristics of low complexity:"
        echo "  - Simple CRUD operations"
        echo "  - Minor UI changes"
        echo "  - Bug fixes"
        echo "  - Small refactoring"
        echo ""
        echo -e "${BLUE}Command:${NC}"
        echo "  workflow.sh start <feature-id> default"
        ;;

    medium)
        echo -e "${GREEN}Recommended Workflow: default${NC}"
        echo ""
        echo "Reasoning:"
        echo "  - Medium complexity benefits from planning but doesn't need"
        echo "    the full task-breakdown document set"
        echo "  - Planner provides API contracts and task breakdown"
        echo "  - Backend/Frontend can work in parallel with mocking"
        echo ""
        echo "Characteristics of medium complexity:"
        echo "  - New feature with API and UI"
        echo "  - Some business logic"
        echo "  - Multiple components"
        echo "  - 1-3 days of work"
        echo ""
        echo -e "${BLUE}Command:${NC}"
        echo "  workflow.sh start <feature-id> default"
        echo ""
        echo -e "${YELLOW}TIP:${NC} If you find the feature more complex during planning,"
        echo "      switch to task-breakdown workflow."
        ;;

    high)
        echo -e "${GREEN}Recommended Workflow: task-breakdown + implementation-only${NC}"
        echo ""
        echo "Reasoning:"
        echo "  - High complexity features need exhaustive planning"
        echo "  - task-breakdown generates 10 detailed documents"
        echo "  - Prevents rework from incomplete specifications"
        echo "  - implementation-only uses those docs for parallel coding"
        echo ""
        echo "Characteristics of high complexity:"
        echo "  - Multiple domains involved"
        echo "  - Complex business rules"
        echo "  - External integrations"
        echo "  - Architectural decisions needed"
        echo "  - > 3 days of work"
        echo ""
        echo -e "${BLUE}Commands (two phases):${NC}"
        echo ""
        echo "  Phase 1 - Planning:"
        echo "    workflow.sh start <feature-id> task-breakdown"
        echo ""
        echo "  Phase 2 - Implementation (after Phase 1 completes):"
        echo "    workflow.sh start <feature-id> implementation-only"
        echo ""
        echo -e "${YELLOW}TIP:${NC} Review the generated documents after Phase 1"
        echo "      before starting Phase 2."
        ;;

    *)
        echo -e "${YELLOW}Unknown complexity level: $COMPLEXITY${NC}"
        echo ""
        echo "Valid options: low, medium, high"
        exit 1
        ;;
esac

echo ""
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
