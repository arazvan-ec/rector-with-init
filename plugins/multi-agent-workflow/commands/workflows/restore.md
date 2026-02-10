---
name: workflows:restore
description: "Restore session context from a saved snapshot or list available snapshots."
argument_hint: --name="checkpoint-name" | --list [--verbose]
---

# Multi-Agent Workflow: Restore

Restore session context from a previously saved snapshot. Enables seamless session continuity across context window limits or new sessions.

## Usage

```
/workflows:restore --list
/workflows:restore --list --verbose
/workflows:restore --name="domain-layer-complete"
/workflows:restore --name="2026-01-27-eod" --dry-run
```

## Why Restore Matters

1. **New Session Continuity**: Start where you left off without re-reading everything.
2. **Context Reconstruction**: Quickly rebuild mental model from saved state.
3. **Role Handoffs**: Pick up work from another role or developer.
4. **Disaster Recovery**: Resume after unexpected session termination.
5. **Time Travel**: Return to a previous known-good state.

## Command Options

| Option | Description |
|--------|-------------|
| `--list` | Show all available snapshots |
| `--list --verbose` | Show snapshots with detailed metadata |
| `--name="<name>"` | Restore from specific snapshot |
| `--dry-run` | Preview restoration without applying changes |
| `--force` | Restore even if current state has uncommitted changes |

## Execution Steps: List Snapshots

### With `--list` Flag

```bash
# Find all snapshots
SNAPSHOTS_DIR=".ai/snapshots"

if [ ! -d "${SNAPSHOTS_DIR}" ]; then
    echo "No snapshots found."
    echo "Create one with: /workflows:snapshot --name=\"my-snapshot\""
    exit 0
fi

echo "Available Snapshots:"
echo "===================="
echo ""

for dir in "${SNAPSHOTS_DIR}"/*/; do
    if [ -d "$dir" ]; then
        NAME=$(basename "$dir")
        CREATED=$(grep "created_at:" "${dir}/metadata.yaml" 2>/dev/null | cut -d' ' -f2)
        ROLE=$(grep "role:" "${dir}/context.yaml" 2>/dev/null | cut -d' ' -f2)
        echo "  ${NAME}"
        echo "    Created: ${CREATED:-unknown}"
        echo "    Role: ${ROLE:-unknown}"
        echo ""
    fi
done
```

### Output Format (Standard)

```
Available Snapshots:
====================

  domain-layer-complete
    Created: 2026-01-27T14:30:00Z
    Role: backend

  pre-refactor
    Created: 2026-01-27T10:15:00Z
    Role: frontend

  checkpoint-1
    Created: 2026-01-26T18:00:00Z
    Role: planner

Use: /workflows:restore --name="<snapshot-name>" to restore
```

### Output Format (Verbose)

```
Available Snapshots:
====================

[1] domain-layer-complete
    Created:    2026-01-27T14:30:00Z
    Role:       backend
    Stage:      implementation
    Feature:    user-authentication
    Branch:     feature/user-auth
    Commit:     abc123def
    Files:      12 modified
    Tasks:      3 pending, 1 in-progress
    Notes:      Completed User entity and value objects

[2] pre-refactor
    Created:    2026-01-27T10:15:00Z
    Role:       frontend
    Stage:      implementation
    Feature:    user-authentication
    Branch:     feature/user-auth
    Commit:     789xyz456
    Files:      8 modified
    Tasks:      5 pending, 2 in-progress
    Notes:      Before LoginForm refactoring
```

## Execution Steps: Restore Snapshot

### Step 1: Validate Snapshot Exists

```bash
SNAPSHOT_NAME="${NAME}"
SNAPSHOT_DIR=".ai/snapshots/${SNAPSHOT_NAME}"

if [ ! -d "${SNAPSHOT_DIR}" ]; then
    echo "Error: Snapshot '${SNAPSHOT_NAME}' not found."
    echo ""
    echo "Available snapshots:"
    ls -1 .ai/snapshots/ 2>/dev/null || echo "  (none)"
    exit 1
fi
```

### Step 2: Check Current State

```bash
# Warn if there are uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    echo "Warning: You have uncommitted changes."
    echo ""
    git status --short
    echo ""
    echo "Options:"
    echo "  1. Commit or stash changes first"
    echo "  2. Use --force to proceed anyway"
    echo "  3. Create a snapshot of current state first"

    if [ "${FORCE}" != "true" ]; then
        exit 1
    fi
fi
```

### Step 3: Read Snapshot Context

```bash
# Load context from snapshot
echo "Loading snapshot: ${SNAPSHOT_NAME}"
echo ""

# Parse metadata
CREATED=$(grep "created_at:" "${SNAPSHOT_DIR}/metadata.yaml" | cut -d' ' -f2)
COMMIT=$(grep "commit:" "${SNAPSHOT_DIR}/metadata.yaml" | cut -d' ' -f2)
BRANCH=$(grep "branch:" "${SNAPSHOT_DIR}/context.yaml" | cut -d' ' -f2)
ROLE=$(grep "role:" "${SNAPSHOT_DIR}/context.yaml" | cut -d' ' -f2)
FEATURE=$(grep "feature:" "${SNAPSHOT_DIR}/context.yaml" | cut -d' ' -f2)
STAGE=$(grep "stage:" "${SNAPSHOT_DIR}/context.yaml" | cut -d' ' -f2)
```

### Step 4: Display Context Summary

Output a comprehensive summary for the new session:

```markdown
## Session Restored from Snapshot

**Snapshot**: <name>
**Created**: <timestamp>
**Time Since**: <duration since snapshot>

---

### Role Context

**Role**: <role>
**Feature**: <feature>
**Stage**: <stage>
**Branch**: <branch>

---

### Previous Session Summary

<contents of conversation.md>

---

### Current State

<contents of state.md, focused on current role>

---

### Active Tasks

<contents of tasks.md>

---

### Files Modified in Previous Session

<contents of modified_files.txt, first 20 files>

---

### Recommended Next Steps

1. **Sync with remote**: `/workflows:sync <feature>`
2. **Check status**: `/workflows:status <feature>`
3. **Continue work**: `/workflows:work <feature> --role=<role>`

---

### Quick Reference Files

To rebuild full context, read these files:

1. `.ai/project/features/<feature>/50_state.md` - Current state
2. `.ai/project/features/<feature>/30_tasks.md` - Task list
3. `.ai/project/features/<feature>/FEATURE.md` - Feature spec
4. Role definition: `plugins/multi-agent-workflow/core/roles/<role>.md`

---

Ready to continue. What would you like to work on?
```

### Step 5: Verify Git State (Optional)

```bash
# Check if we're on the expected branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

if [ "${CURRENT_BRANCH}" != "${BRANCH}" ]; then
    echo ""
    echo "Note: Snapshot was on branch '${BRANCH}'"
    echo "      Current branch is '${CURRENT_BRANCH}'"
    echo ""
    echo "To switch: git checkout ${BRANCH}"
fi

# Check if commit still exists
if ! git cat-file -e "${COMMIT}" 2>/dev/null; then
    echo ""
    echo "Warning: Original commit ${COMMIT} not found."
    echo "         Repository may have been rebased or force-pushed."
fi
```

### Step 6: Output Restoration Complete

```
Snapshot restored: <name>

Context Summary:
  Role:     <role>
  Feature:  <feature>
  Stage:    <stage>
  Tasks:    <n pending, m in-progress>

Files from previous session have been listed above.
State has been displayed for your review.

You are ready to continue working.

Quick commands:
  /workflows:status <feature>    - Check current status
  /workflows:work <feature>      - Continue implementation
  /workflows:sync <feature>      - Pull latest changes
```

## Dry Run Mode

With `--dry-run`, show what would be restored without applying:

```
Dry Run: Restore Snapshot '<name>'

Would restore:
  - Role context: <role> on <feature>
  - State from: <timestamp>
  - Branch: <branch> (commit: <hash>)

Files that were modified:
  <list of files>

Active tasks:
  <task list>

To actually restore, run:
  /workflows:restore --name="<name>"
```

## Handling Conflicts

### Scenario: Repository Has Diverged

```
Warning: Repository has diverged from snapshot state.

Snapshot commit: abc123 (2026-01-27)
Current HEAD:    xyz789 (2026-01-28)

The repository has 15 new commits since the snapshot.

Options:
1. Restore context only (recommended)
   - Read snapshot for context, work on current code
   - /workflows:restore --name="<name>" --context-only

2. Checkout snapshot commit (caution)
   - Return to exact code state
   - git checkout abc123

3. Cancel and review changes
   - git log abc123..HEAD --oneline
```

### Scenario: Snapshot is Stale

```
Warning: Snapshot is more than 7 days old.

Created: 2026-01-20
Age: 8 days

The project may have changed significantly.

Recommendations:
1. Review recent commits: git log --since="2026-01-20" --oneline
2. Check current state: /workflows:status <feature>
3. Create fresh snapshot after reviewing current state

Proceed with restore? (context will still be useful for reference)
```

## Integration with Workflow

After restoration, the typical flow is:

```
1. /workflows:restore --name="<snapshot>"
   ↓
2. Review the context summary displayed
   ↓
3. /workflows:sync <feature>
   (get any changes made since snapshot)
   ↓
4. /workflows:status <feature>
   (verify current state)
   ↓
5. /workflows:work <feature> --role=<role>
   (continue working)
```

## Automatic Context Loading

When restoring, the following files are automatically read and summarized:

1. **State File** (`state.md` from snapshot)
2. **Conversation Summary** (`conversation.md` from snapshot)
3. **Active Tasks** (`tasks.md` from snapshot)
4. **Modified Files** (first 20 from `modified_files.txt`)

This provides immediate context without manually reading multiple files.

## Related Commands

- `/workflows:snapshot` - Create a new snapshot
- `/workflows:checkpoint` - Create a lightweight git-based checkpoint
- `/workflows:status` - View current status
- `/workflows:sync` - Synchronize with remote repository
- `/workflows:role` - Switch to a specific role
