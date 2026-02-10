#!/bin/bash

# git_sync.sh - Sincroniza con remoto de forma segura
#
# Hace git pull con manejo automático de stash y conflictos
#
# Uso:
#   ./.ai/workflow/scripts/git_sync.sh [feature-id]

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

FEATURE_ID="${1:-}"

# Check if we're in a git repo
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    error "Not a git repository!"
    exit 1
fi

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
info "Syncing branch: $CURRENT_BRANCH"

# Check if there are local changes
if ! git diff --quiet || ! git diff --cached --quiet; then
    warn "You have local changes"
    info "Stashing local changes..."
    git stash push -m "Auto-stash before sync $(date +%Y-%m-%d_%H:%M:%S)"
    STASHED=true
    success "Changes stashed"
else
    STASHED=false
    info "Working directory is clean"
fi

# Fetch from remote
info "Fetching from remote..."
if git fetch origin "$CURRENT_BRANCH" 2>/dev/null; then
    success "Fetched from origin"
else
    warn "Could not fetch from remote (branch may not exist yet)"
fi

# Pull changes
info "Pulling changes..."
if git pull origin "$CURRENT_BRANCH" 2>&1; then
    success "Pulled changes from origin/$CURRENT_BRANCH"
else
    error "Pull failed"
    if [ "$STASHED" = true ]; then
        warn "Your changes are still stashed"
        echo "To recover: git stash pop"
    fi
    exit 1
fi

# Pop stash if we stashed
if [ "$STASHED" = true ]; then
    info "Applying stashed changes..."
    if git stash pop 2>&1; then
        success "Stashed changes applied"
    else
        error "Conflict when applying stashed changes!"
        echo ""
        warn "Resolve conflicts manually:"
        echo "  1. Fix conflicted files"
        echo "  2. git add [resolved-files]"
        echo "  3. git stash drop (when done)"
        exit 1
    fi
fi

# Show current state
echo ""
info "Current state:"
git status --short

# If feature-id provided, show its state
if [ -n "$FEATURE_ID" ]; then
    STATE_FILE="./.ai/project/features/$FEATURE_ID/50_state.md"
    if [ -f "$STATE_FILE" ]; then
        echo ""
        info "Feature state: $FEATURE_ID"
        echo ""
        # Show status of each role (extract from markdown)
        grep -E "^\*\*Status\*\*:" "$STATE_FILE" | head -4 || true
    fi
fi

success "Sync complete!"
