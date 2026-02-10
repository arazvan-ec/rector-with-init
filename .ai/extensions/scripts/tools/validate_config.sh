#!/bin/bash
# validate_config.sh - Validate project configuration
# Checks if config.yaml and required files exist and are valid
#
# Usage: ./validate_config.sh [path]
#
# Exit codes:
#   0 - Valid
#   1 - Invalid

set -e

PROJECT_PATH="${1:-.}"
PROJECT_PATH="$(cd "$PROJECT_PATH" && pwd)"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0
WARNINGS=0

error() {
    echo -e "${RED}ERROR${NC}: $1"
    ((ERRORS++))
}

warn() {
    echo -e "${YELLOW}WARN${NC}: $1"
    ((WARNINGS++))
}

success() {
    echo -e "${GREEN}OK${NC}: $1"
}

echo "Validating configuration at: $PROJECT_PATH"
echo "================================================"
echo ""

# Check .ai directory structure
if [ ! -d "$PROJECT_PATH/.ai" ]; then
    error "Missing .ai directory"
else
    success ".ai directory exists"
fi

# Check workflow directory
if [ ! -d "$PROJECT_PATH/.ai/workflow" ]; then
    error "Missing .ai/workflow directory"
else
    success ".ai/workflow directory exists"

    # Check required subdirectories
    for dir in roles rules scripts workflows; do
        if [ ! -d "$PROJECT_PATH/.ai/workflow/$dir" ]; then
            error "Missing .ai/workflow/$dir directory"
        else
            success ".ai/workflow/$dir exists"
        fi
    done
fi

# Check project directory
if [ ! -d "$PROJECT_PATH/.ai/project" ]; then
    warn "Missing .ai/project directory (run ai_consultant.py to create)"
else
    success ".ai/project directory exists"

    # Check config.yaml
    if [ ! -f "$PROJECT_PATH/.ai/project/config.yaml" ]; then
        warn "Missing config.yaml (run ai_consultant.py to create)"
    else
        success "config.yaml exists"

        # Validate YAML syntax (basic check)
        if command -v python3 &> /dev/null; then
            if python3 -c "import yaml; yaml.safe_load(open('$PROJECT_PATH/.ai/project/config.yaml'))" 2>/dev/null; then
                success "config.yaml is valid YAML"
            else
                error "config.yaml has invalid YAML syntax"
            fi
        fi
    fi

    # Check context.md
    if [ ! -f "$PROJECT_PATH/.ai/project/context.md" ]; then
        warn "Missing context.md (run ai_consultant.py to create)"
    else
        success "context.md exists"
    fi
fi

# Check required role files
echo ""
echo "Checking role files..."
for role in planner backend frontend qa; do
    if [ ! -f "$PROJECT_PATH/.ai/workflow/roles/${role}.md" ]; then
        error "Missing role file: ${role}.md"
    else
        success "Role file: ${role}.md"
    fi
done

# Check required rule files
echo ""
echo "Checking rule files..."
for rule in global_rules ddd_rules; do
    if [ ! -f "$PROJECT_PATH/.ai/workflow/rules/${rule}.md" ]; then
        error "Missing rule file: ${rule}.md"
    else
        success "Rule file: ${rule}.md"
    fi
done

# Check workflow files
echo ""
echo "Checking workflow files..."
for workflow in default task-breakdown implementation-only; do
    if [ ! -f "$PROJECT_PATH/.ai/workflow/workflows/${workflow}.yaml" ]; then
        error "Missing workflow file: ${workflow}.yaml"
    else
        success "Workflow file: ${workflow}.yaml"

        # Validate YAML syntax
        if command -v python3 &> /dev/null; then
            if python3 -c "import yaml; yaml.safe_load(open('$PROJECT_PATH/.ai/workflow/workflows/${workflow}.yaml'))" 2>/dev/null; then
                success "  └─ Valid YAML"
            else
                error "  └─ Invalid YAML syntax"
            fi
        fi
    fi
done

# Check scripts
echo ""
echo "Checking scripts..."
for script in ai_consultant.py workflow.sh tilix_start.sh git_sync.sh git_commit_push.sh validate_workflow.py; do
    if [ ! -f "$PROJECT_PATH/.ai/workflow/scripts/$script" ]; then
        error "Missing script: $script"
    else
        if [ -x "$PROJECT_PATH/.ai/workflow/scripts/$script" ]; then
            success "Script: $script (executable)"
        else
            warn "Script: $script (not executable)"
        fi
    fi
done

# Summary
echo ""
echo "================================================"
echo "Validation Summary"
echo "================================================"

if [ $ERRORS -gt 0 ]; then
    echo -e "${RED}Errors: $ERRORS${NC}"
fi

if [ $WARNINGS -gt 0 ]; then
    echo -e "${YELLOW}Warnings: $WARNINGS${NC}"
fi

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}All checks passed!${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}Configuration valid with warnings${NC}"
    exit 0
else
    echo -e "${RED}Configuration invalid${NC}"
    exit 1
fi
