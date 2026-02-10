---
name: workflows:sync
description: "Synchronize with remote repository to get changes from other roles."
argument_hint: <feature-name>
---

# Multi-Agent Workflow: Sync

> **Tier 3 (Automatic)**: Git sync runs automatically at the start of `/workflows:plan` and `/workflows:work`. Only invoke manually when you need to pull changes mid-session.

Pull latest changes from the remote repository to synchronize with other roles.

## Usage

```
/workflows:sync user-authentication
```

## Why Sync is Important

In multi-agent development, different Claude instances work on different parts:
- **Planner** creates feature definitions and task breakdowns
- **Backend** implements API and pushes code
- **Frontend** implements UI (may need Backend's API)
- **QA** reviews everyone's work

Each role needs to pull others' changes before starting work.

## Execution Steps

### Step 1: Stash Local Changes (if any)

```bash
git stash --include-untracked
```

### Step 2: Pull from Remote

```bash
# Try feature branch first
git pull origin feature/$ARGUMENTS 2>/dev/null || \
# Fall back to main
git pull origin main
```

### Step 3: Restore Local Changes

```bash
git stash pop 2>/dev/null || true
```

### Step 4: Report Status

```bash
echo "Sync complete for feature: $ARGUMENTS"
echo ""
echo "Latest commits:"
git log --oneline -5
echo ""
echo "Current state files:"
ls -la .ai/project/features/$ARGUMENTS/*.md 2>/dev/null || echo "No state files yet"
```

## When to Sync

- **Before starting work**: Always sync first
- **When state changes**: After another role updates 50_state.md
- **When blocked**: To check if blockers were resolved
- **Periodically**: Every 30-60 minutes during active development

## Handling Conflicts

If conflicts occur in `50_state.md`:

1. **Keep both versions**: Each role has its own section
2. **Merge manually**: Combine the state sections
3. **Commit the merge**: `git commit -m "Merge state from other roles"`

Example conflict resolution:

```markdown
<<<<<<< HEAD
## Backend Engineer
**Status**: IN_PROGRESS
=======
## Backend Engineer
**Status**: COMPLETED
>>>>>>> origin/feature-x

# Resolution: Keep the more recent (COMPLETED) status
## Backend Engineer
**Status**: COMPLETED
```

## Post-Sync Actions

After syncing, you should:
1. Read the updated `50_state.md` to see other roles' progress
2. Check for any BLOCKED statuses that need attention
3. Continue with your role's workflow
