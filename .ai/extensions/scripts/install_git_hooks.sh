#!/bin/bash

# install_git_hooks.sh - Instala los git hooks de validación
#
# Uso:
#   ./.ai/scripts/install_git_hooks.sh

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

info() { echo -e "${BLUE}ℹ${NC} $1"; }
success() { echo -e "${GREEN}✓${NC} $1"; }

echo ""
info "Installing git hooks..."

# Check if .git exists
if [ ! -d ".git" ]; then
    echo "Error: Not a git repository (no .git directory found)"
    exit 1
fi

# Create hooks directory if not exists
mkdir -p .git/hooks

# Detect hooks directory (either .ai/hooks/ or ./.ai/hooks/)
HOOKS_DIR=".ai/hooks"
if [ ! -d "$HOOKS_DIR" ]; then
    HOOKS_DIR="./.ai/hooks"
fi

# Install pre-commit hook
if [ -f "$HOOKS_DIR/pre-commit" ]; then
    cp "$HOOKS_DIR/pre-commit" .git/hooks/pre-commit
    chmod +x .git/hooks/pre-commit
    success "Installed: pre-commit hook"
else
    echo "Warning: $HOOKS_DIR/pre-commit not found"
fi

# Install other hooks if they exist
for hook in pre-push post-commit; do
    if [ -f "$HOOKS_DIR/$hook" ]; then
        cp "$HOOKS_DIR/$hook" ".git/hooks/$hook"
        chmod +x ".git/hooks/$hook"
        success "Installed: $hook hook"
    fi
done

echo ""
success "Git hooks installed successfully!"
echo ""
echo "Installed hooks will:"
echo "  • Validate YAML syntax before commits"
echo "  • Check 50_state.md format before commits"
echo "  • Prevent committing secrets or .env files"
echo "  • Run workflow validator before commits"
echo ""
echo "To bypass hooks (NOT recommended):"
echo "  git commit --no-verify"
echo ""
