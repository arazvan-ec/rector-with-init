---
name: git-sync
description: "Safely synchronizes local repository with remote, handling stashes, conflicts, and branch fallbacks."
hooks:
  PostToolUse:
    - matcher: Bash
      command: "echo '[git-sync] Sync step completed'"
  Stop:
    - command: "echo '[git-sync] Repository synchronized successfully.'"
---

# Git Sync Skill

Safely synchronize your local repository with remote, handling stashes and conflicts.

## What This Skill Does

- Stashes any uncommitted local changes
- Pulls latest changes from remote (feature branch or main)
- Restores local changes after pull
- Handles merge conflicts gracefully
- Reports sync status and recent commits

## When to Use

- **Before starting work** on a feature
- **When another role** pushes changes you need
- **Periodically** during long development sessions
- **When your state is stale** and you need updates

## How to Use

### Via Slash Command

```
/multi-agent-workflow:git-sync <feature-name>
```

### Via Script (if installed globally)

```bash
./workflow sync <feature-name>
```

### Manual Steps

```bash
# 1. Stash local changes
git stash --include-untracked

# 2. Pull from remote
git pull origin feature/<feature-name> || git pull origin main

# 3. Restore local changes
git stash pop

# 4. Check status
git status
```

## Conflict Resolution

If conflicts occur in `50_state.md`:

1. Each role has its own section - keep both
2. Merge sections manually:
   ```markdown
   ## Backend Engineer
   **Status**: COMPLETED  # Keep the more recent status
   ```
3. Commit the merge: `git add . && git commit -m "Merge state"`

## Safety Features

- **Auto-stash**: Never lose uncommitted work
- **Branch fallback**: Tries feature branch, falls back to main
- **Status report**: Shows what changed after sync

## Integration with Workflow

This skill is automatically invoked by:
- `/workflows:role` - Before loading role context
- `/workflows:checkpoint` - After creating checkpoint
- `/workflows:status` - To ensure fresh status
