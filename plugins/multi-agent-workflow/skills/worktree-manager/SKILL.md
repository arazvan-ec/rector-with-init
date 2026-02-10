---
name: worktree-manager
description: "Manages git worktrees for parallel multi-role development. This is the fallback provider for parallelization when Agent Teams is not available. Use directly only when you need fine-grained worktree control outside of /workflows:parallel."
hooks:
  PostToolUse:
    - matcher: Bash
      command: "echo '[worktree-manager] Worktree operation completed'"
---

# Worktree Manager Skill

Manage git worktrees for parallel development. This skill is the **worktrees provider implementation** for the parallelization capability.

## Provider Role

```
/workflows:parallel
    │
    ├── provider: agent-teams → TeammateTool (Opus 4.6+)
    │
    └── provider: worktrees → THIS SKILL (fallback, any model)
```

When `/workflows:parallel` resolves to the `worktrees` provider, it delegates to this skill. You can also invoke it directly for fine-grained worktree control.

## When to Use Directly

- You need worktrees outside of the parallel workflow
- You want to manage worktrees for non-standard configurations
- You're debugging worktree issues
- `/workflows:parallel` is not needed (single extra worktree)

## Why Worktrees?

```
Traditional: One working directory
├── Can't run backend and frontend simultaneously
├── Context switching requires stashing
└── Limited parallelization

With Worktrees: Multiple working directories
├── Backend worktree → Run backend server
├── Frontend worktree → Run frontend server
├── QA worktree → Run tests independently
└── True parallel development
```

## Commands

### Create Worktree

```bash
git worktree add ../project-backend feature/user-auth
git worktree add ../project-frontend feature/user-auth
git worktree add ../project-qa main
```

### List Worktrees

```bash
git worktree list
```

### Remove Worktree

```bash
git worktree remove ../project-backend
git worktree remove --force ../project-backend  # if dirty
```

### Prune Stale Worktrees

```bash
git worktree prune
```

## Parallel Development Setup

```bash
# 1. Create feature branch
git checkout -b feature/user-auth

# 2. Create worktrees for parallel work
git worktree add ../project-backend feature/user-auth
git worktree add ../project-frontend feature/user-auth

# 3. Start services in each worktree
# Terminal 1 (Backend):
cd ../project-backend && php -S localhost:8000 -t public/

# Terminal 2 (Frontend):
cd ../project-frontend && npm run dev
```

## Directory Structure

```
/home/user/
├── project/              # Main repo (Planner/QA)
│   └── .git/
├── project-backend/      # Backend worktree
│   └── .git → ../project/.git
└── project-frontend/     # Frontend worktree
    └── .git → ../project/.git
```

## Cleanup After Feature

```bash
git checkout main && git pull
git worktree remove ../project-backend
git worktree remove ../project-frontend
git worktree prune
git branch -d feature/user-auth
```

## Best Practices

1. **One worktree per role**: Keeps contexts separate
2. **Same branch**: All worktrees on same feature branch
3. **Frequent sync**: Pull before starting work
4. **Clean up**: Remove worktrees after merge
5. **Don't mix**: Don't do backend work in frontend worktree
