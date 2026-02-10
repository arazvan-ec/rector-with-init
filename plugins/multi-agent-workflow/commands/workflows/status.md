---
name: workflows:status
description: "View the current status of all roles for a feature."
argument_hint: <feature-name>
---

# Multi-Agent Workflow: Status

Display the current status of all roles working on a feature.

## Usage

```
/workflows:status user-authentication
```

## What This Shows

- Status of each role (PENDING, IN_PROGRESS, COMPLETED, BLOCKED, etc.)
- Current checkpoints and progress
- Blockers and dependencies
- Next steps for each role

## Execution Steps

### Step 1: Read State File

```bash
cat .ai/project/features/$ARGUMENTS/50_state.md
```

### Step 2: Parse and Display Status

Extract and display status for each role in a summary format:

```
Feature: $ARGUMENTS
═══════════════════════════════════════

Role          Status         Checkpoint
────────────────────────────────────────
Planner       COMPLETED      Feature defined, tasks broken down
Backend       IN_PROGRESS    Domain layer complete
Frontend      WAITING_API    Components done, using mocks
QA            PENDING        Waiting for implementation

═══════════════════════════════════════

Blockers:
  - None currently

Dependencies:
  - Frontend waiting for Backend API
  - QA waiting for Backend + Frontend

Next Actions:
  - Backend: Complete Application layer
  - Frontend: Will integrate when Backend done
```

### Step 3: Show Recent Commits

```bash
echo "Recent activity:"
git log --oneline --all --since="24 hours ago" | grep "$ARGUMENTS" | head -10
```

## Status Values

| Status | Meaning |
|--------|---------|
| `PENDING` | Not started yet |
| `IN_PROGRESS` | Currently working |
| `BLOCKED` | Stuck, needs help |
| `WAITING_API` | Frontend waiting for Backend API |
| `COMPLETED` | Role finished their work |
| `APPROVED` | QA approved the feature |
| `REJECTED` | QA found issues, needs fixes |

## Using Status for Coordination

### As Planner
- Check if your planning is blocking others
- Resolve BLOCKED statuses from other roles

### As Backend
- Start when Planner is COMPLETED
- Push frequently so Frontend can integrate

### As Frontend
- Check Backend status before starting integration
- Use WAITING_API if mocking while Backend works

### As QA
- Start when both Backend and Frontend are COMPLETED
- Create detailed reports for REJECTED status

## Interpreting Status

```
Good Progress:
  Planner:  COMPLETED
  Backend:  IN_PROGRESS (50%)
  Frontend: IN_PROGRESS (using mocks)
  QA:       PENDING

Problem Detected:
  Planner:  COMPLETED
  Backend:  BLOCKED ← Needs attention!
  Frontend: WAITING_API
  QA:       PENDING

Ready for QA:
  Planner:  COMPLETED
  Backend:  COMPLETED
  Frontend: COMPLETED
  QA:       IN_PROGRESS ← Review happening
```
