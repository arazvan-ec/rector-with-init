#!/bin/bash
# create_checkpoint.sh - Create a session checkpoint for context window management
#
# Usage: ./create_checkpoint.sh <role> <feature-id> [message]
#
# Creates a checkpoint file to preserve session state before context limit.

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

info() { echo -e "${BLUE}info${NC} $1"; }
success() { echo -e "${GREEN}success${NC} $1"; }
error() { echo -e "${RED}error${NC} $1"; }
warn() { echo -e "${YELLOW}warn${NC} $1"; }

# Validate arguments
ROLE="${1:-}"
FEATURE_ID="${2:-}"
MESSAGE="${3:-Session checkpoint}"

if [ -z "$ROLE" ] || [ -z "$FEATURE_ID" ]; then
    echo "Usage: $0 <role> <feature-id> [message]"
    echo ""
    echo "Arguments:"
    echo "  role        Role name (planner, backend, frontend, qa)"
    echo "  feature-id  Feature identifier"
    echo "  message     Optional checkpoint message"
    echo ""
    echo "Example:"
    echo "  $0 backend user-auth \"Completed domain layer\""
    exit 1
fi

# Validate role
case "$ROLE" in
    planner|backend|frontend|qa)
        ;;
    *)
        error "Invalid role: $ROLE"
        echo "Valid roles: planner, backend, frontend, qa"
        exit 1
        ;;
esac

# Setup paths
FEATURE_DIR=".ai/project/features/$FEATURE_ID"
CHECKPOINT_DIR="$FEATURE_DIR/checkpoints"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
CHECKPOINT_FILE="checkpoint_${ROLE}_${TIMESTAMP}.md"

# Create checkpoints directory if needed
mkdir -p "$CHECKPOINT_DIR"

info "Creating checkpoint for $ROLE on feature $FEATURE_ID..."

# Generate checkpoint content
cat > "$CHECKPOINT_DIR/$CHECKPOINT_FILE" << EOF
# Session Checkpoint: ${ROLE^^} - $FEATURE_ID

> This checkpoint was created to preserve session state before context window limit.
> Use this to resume work in a new session.

**Created**: $(date -Iseconds)
**Role**: $ROLE
**Feature**: $FEATURE_ID
**Message**: $MESSAGE

---

## Session Summary

### What Was Accomplished
<!-- Fill in what was completed in this session -->
-

### Current State
- **Last Checkpoint Completed**:
- **Tests Status**:
- **Coverage**: %

### Files Modified
<!-- List all files created or modified -->
-

---

## Context for Resume

### Key Decisions Made
<!-- Important architectural or implementation decisions -->
-

### Important Code Patterns
<!-- Reference code that should be followed -->
-

### Gotchas and Edge Cases
<!-- Things to remember that aren't obvious -->
-

---

## Resume Instructions

### 1. Read These Files First
\`\`\`
.ai/project/context.md                          # Project overview
.ai/workflow/roles/${ROLE}.md                   # Your role
.ai/project/features/${FEATURE_ID}/50_state.md  # Current state
\`\`\`

### 2. Current Task
<!-- What was being worked on -->


### 3. Next Steps
<!-- What needs to be done next -->
1.
2.
3.

### 4. Commands to Run
\`\`\`bash
# Sync with remote first
./.ai/workflow/scripts/git_sync.sh $FEATURE_ID

# Then continue implementation
\`\`\`

---

## Technical Context

### Code References
<!-- Specific files/lines that are relevant -->
-

### Test Commands
\`\`\`bash
# Add relevant test commands
\`\`\`

### Verification Commands
\`\`\`bash
# Add verification commands
\`\`\`

---

## Blockers (if any)

<!-- Document any blockers -->

---

## Notes for Next Session

<!-- Any additional notes -->

---

**Checkpoint Validity**: This checkpoint is valid for resuming within 24 hours.
After that, re-read context.md as project state may have changed.

**Resume Prompt**:
\`\`\`
I am resuming as $ROLE for feature $FEATURE_ID.
Please read the checkpoint at: $CHECKPOINT_DIR/$CHECKPOINT_FILE
Then continue from where we left off.
\`\`\`
EOF

success "Checkpoint created: $CHECKPOINT_DIR/$CHECKPOINT_FILE"

# Update 50_state.md with checkpoint info
STATE_FILE="$FEATURE_DIR/50_state.md"
if [ -f "$STATE_FILE" ]; then
    info "Updating 50_state.md with checkpoint reference..."

    # Check if Session Resume Info section exists
    if grep -q "## Session Resume Info" "$STATE_FILE"; then
        # Update the last session info using sed
        # This is a simplified update - in production you'd use a more robust method
        info "Session Resume Info section found - please update manually"
    fi
fi

echo ""
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}           Checkpoint Created                 ${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}IMPORTANT: Edit the checkpoint file to fill in details:${NC}"
echo -e "  ${GREEN}$CHECKPOINT_DIR/$CHECKPOINT_FILE${NC}"
echo ""
echo "Then commit and push:"
echo -e "  ${CYAN}git add $CHECKPOINT_DIR/$CHECKPOINT_FILE${NC}"
echo -e "  ${CYAN}git commit -m \"checkpoint($ROLE): $MESSAGE\"${NC}"
echo -e "  ${CYAN}git push${NC}"
echo ""
echo "To resume in a new session, start Claude and paste:"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
cat << EOF
I am resuming as $ROLE for feature $FEATURE_ID.
Please read the checkpoint at: $CHECKPOINT_DIR/$CHECKPOINT_FILE
Then continue from where we left off.
EOF
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
