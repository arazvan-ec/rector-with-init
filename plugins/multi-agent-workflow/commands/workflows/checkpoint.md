---
name: workflows:checkpoint
description: "Create a checkpoint to save progress, manage context, and enable session resumption."
argument_hint: <role> <feature-name> [message]
---

# Multi-Agent Workflow: Checkpoint

> **Tier 3 (Automatic)**: This command runs automatically inside `/workflows:work` at each logical checkpoint. Only invoke manually if you need to save progress outside the normal work flow.

Save your progress at a natural stopping point. Enables context management and session resumption.

## Usage

```
/workflows:checkpoint backend user-auth "Completed Domain Layer"
/workflows:checkpoint frontend dashboard "UserCard component done"
```

## Why Checkpoints Matter

1. **Context Management**: Claude's context window is limited. Checkpoints help manage it.
2. **Session Resumption**: If you need to restart, checkpoints show where you stopped.
3. **Progress Tracking**: Other roles can see your progress.
4. **Quality Gates**: Each checkpoint should have passing tests.

## Execution Steps

### Step 1: Verify Tests Pass

Before creating a checkpoint, ensure all relevant tests pass:

**Backend:**
```bash
php bin/phpunit tests/Unit/
php bin/phpunit tests/Integration/
```

**Frontend:**
```bash
npm test
npm run type-check
```

If tests fail, fix them before checkpointing.

### Step 2: Update State File

Update `50_state.md` with checkpoint information:

```markdown
## <Role> Engineer
**Status**: IN_PROGRESS
**Checkpoint**: <message>
**Timestamp**: <current-time>
**Tests**: All passing

### Checkpoint Details
- **Completed**: <what was completed>
- **Next Task**: <what comes next>
- **Files to Read on Resume**:
  - <relevant file 1>
  - <relevant file 2>
```

### Step 3: Commit and Push

```bash
git add .
git commit -m "[<role>][<feature>] Checkpoint: <message>"
git push origin feature/<feature>
```

### Step 4: Output Resume Instructions

```
Checkpoint created: <message>

To resume this work in a new session:

1. Start a new Claude session
2. Run: /workflows:role <role> <feature>
3. Claude will automatically read:
   - Your role definition
   - The 50_state.md with checkpoint info
   - Files listed in checkpoint

Your progress is saved. Safe to end session.
```

## Checkpoint Triggers

Create a checkpoint when:

- **Completing a logical unit**: Domain layer, a component, an endpoint
- **Tests pass after multiple iterations**: Good stopping point
- **Context feels heavy**: If responses slow down or become incomplete
- **Before a break**: End of day, lunch, etc.
- **After 1-2 hours**: Good practice for context management

## Ralph Wiggum Pattern

The checkpoint system follows the "Ralph Wiggum Pattern":

1. **Blocking Checkpoints**: Can't proceed until tests pass
2. **Auto-Correction Loop**: Up to 10 iterations to fix failures
3. **Escape Hatch**: If stuck after 10 iterations, document and mark BLOCKED

```
Checkpoint: Domain Layer

Iterations: 1
[Run tests] → FAIL
[Fix code] → Re-run tests

Iterations: 2
[Run tests] → PASS ✅
Checkpoint complete!
```

## Example Checkpoint Document

```markdown
## Backend Engineer
**Status**: IN_PROGRESS
**Checkpoint**: Domain Layer Complete
**Timestamp**: 2026-01-16T14:30:00Z
**Tests**: 15/15 passing, 92% coverage

### Checkpoint Details
- **Completed**:
  - User entity with validation
  - Email value object
  - Password value object (hashed)
  - UserRepository interface

- **Next Task**:
  - CreateUserUseCase (Application layer)
  - See 30_tasks.md Task BE-005

- **Files to Read on Resume**:
  - .ai/workflow/roles/backend.md (TDD section)
  - .ai/project/features/user-auth/30_tasks.md (Task BE-005)
  - backend/src/Domain/Entity/User.php (reference)

- **Context Notes**:
  - Email validation uses filter_var with FILTER_VALIDATE_EMAIL
  - Password hashing happens in UseCase, not Entity
  - Following existing src/Domain/Order/ as pattern
```
