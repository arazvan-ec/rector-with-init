#!/bin/bash

# git_commit_push.sh - Script inteligente de commit y push con validación
#
# Uso:
#   ./.ai/workflow/scripts/git_commit_push.sh [role] [feature-id] [message]
#
# Ejemplo:
#   ./.ai/workflow/scripts/git_commit_push.sh backend user-auth "Implement User entity and repository"

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info() { echo -e "${BLUE}ℹ${NC} $1"; }
success() { echo -e "${GREEN}✓${NC} $1"; }
error() { echo -e "${RED}✗${NC} $1"; }
warn() { echo -e "${YELLOW}⚠${NC} $1"; }

# Parse arguments
ROLE="${1:-unknown}"
FEATURE_ID="${2:-FEATURE_X}"
MESSAGE="${3}"

if [ -z "$MESSAGE" ]; then
    error "Usage: ./.ai/workflow/scripts/git_commit_push.sh [role] [feature-id] [message]"
    echo ""
    echo "Example:"
    echo "  ./.ai/workflow/scripts/git_commit_push.sh backend user-auth \"Implement User entity\""
    exit 1
fi

info "Git workflow for role: $ROLE, feature: $FEATURE_ID"

# Check if we're in a git repo
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    error "Not a git repository!"
    exit 1
fi

# Check current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
info "Current branch: $CURRENT_BRANCH"

# Expected branch format: feature/[feature-id] or claude/[feature-id]-*
EXPECTED_BRANCH_PREFIX="feature/$FEATURE_ID"

if [[ ! "$CURRENT_BRANCH" =~ ^(feature/|claude/) ]]; then
    warn "You're on branch '$CURRENT_BRANCH'"
    warn "Expected branch format: feature/$FEATURE_ID or claude/$FEATURE_ID-*"
    echo ""
    read -p "Do you want to create feature branch 'feature/$FEATURE_ID'? (y/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git checkout -b "feature/$FEATURE_ID"
        success "Created and switched to branch: feature/$FEATURE_ID"
    fi
fi

# Validate workflow before committing
info "Validating workflow..."
if [ -f "./.ai/workflow/scripts/validate_workflow.py" ]; then
    if python3 ./.ai/workflow/scripts/validate_workflow.py "$FEATURE_ID" 2>/dev/null; then
        success "Workflow validation passed"
    else
        error "Workflow validation failed!"
        echo ""
        warn "Fix validation errors before committing"
        exit 1
    fi
else
    warn "Validator not found, skipping validation"
fi

# Check if there are changes to commit
if git diff --quiet && git diff --cached --quiet; then
    warn "No changes to commit"
    exit 0
fi

# Show what will be committed
info "Files to be committed:"
git status --short

echo ""
read -p "Continue with commit? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    warn "Commit cancelled"
    exit 0
fi

# Stage all changes
git add -A
success "Staged all changes"

# Create commit message with role prefix
COMMIT_MESSAGE="[$ROLE][$FEATURE_ID] $MESSAGE"

# Commit
git commit -m "$COMMIT_MESSAGE"
success "Committed: $COMMIT_MESSAGE"

# Push to remote with retry logic
info "Pushing to remote..."
MAX_RETRIES=4
RETRY_DELAY=2

for i in $(seq 1 $MAX_RETRIES); do
    if git push origin "$CURRENT_BRANCH" 2>&1; then
        success "Pushed to origin/$CURRENT_BRANCH"

        # Show commit info
        echo ""
        info "Commit details:"
        git log -1 --oneline

        # Show remote status
        git remote -v | head -1

        exit 0
    else
        if [ $i -lt $MAX_RETRIES ]; then
            warn "Push failed, retrying in ${RETRY_DELAY}s... (attempt $i/$MAX_RETRIES)"
            sleep $RETRY_DELAY
            RETRY_DELAY=$((RETRY_DELAY * 2))
        else
            error "Push failed after $MAX_RETRIES attempts"
            echo ""
            warn "Your changes are committed locally but not pushed"
            warn "Try pushing manually: git push origin $CURRENT_BRANCH"
            exit 1
        fi
    fi
done
