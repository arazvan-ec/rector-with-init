---
name: checkpoint
description: "Creates blocking checkpoints with auto-correction loops (Ralph Wiggum pattern) for quality-gated development."
hooks:
  PreToolUse:
    - matcher: Write
      command: "echo '[checkpoint] Updating state file...'"
  Stop:
    - command: "echo '[checkpoint] Checkpoint saved to 50_state.md'"
---

# Checkpoint Skill

Create blocking checkpoints with auto-correction loops for quality-gated development.

## What This Skill Does

- Validates that tests pass before allowing progress
- Implements the "Ralph Wiggum Pattern" (iterate until tests pass)
- Documents progress for session resumption
- Manages context window through strategic stopping points
- Provides escape hatch after max iterations

## The Ralph Wiggum Pattern

Instead of pushing forward blindly, iterate until quality gates pass:

```
while tests_failing and iterations < 10:
    1. Analyze error
    2. Fix code
    3. Re-run tests
    4. If pass → checkpoint complete
    5. If fail → loop again

if iterations >= 10:
    → Document blocker
    → Mark as BLOCKED
    → Wait for help
```

## When to Use

- **Completing a logical unit**: Domain layer, component, endpoint
- **After multiple fix iterations**: Tests finally passing
- **Context feels heavy**: Responses slowing down (check provider thresholds in `core/providers.yaml`)
- **Before breaks**: End of session, lunch, etc.
- **Time-based**: Every 1-2 hours (standard provider) or every 2-4 hours (advanced provider)

## How to Use

### Via Slash Command

```
/multi-agent-workflow:checkpoint <role> <feature> "<message>"
```

Example:
```
/multi-agent-workflow:checkpoint backend user-auth "Domain layer complete"
```

### Checkpoint Document Format

The checkpoint updates `50_state.md`:

```markdown
## Backend Engineer
**Status**: IN_PROGRESS
**Checkpoint**: Domain Layer Complete
**Timestamp**: 2026-01-16T14:30:00Z
**Tests**: 15/15 passing, 92% coverage
**Iterations**: 3 (took 3 attempts to pass)

### Resume Information
- **Completed**: User entity, Email VO, Password VO, Repository interface
- **Next Task**: CreateUserUseCase (Task BE-005)
- **Files to Read**:
  - backend.md (TDD section)
  - 30_tasks.md (Task BE-005)
  - src/Domain/Entity/User.php (reference)
```

## Checkpoint Types by Role

### Backend Checkpoints
1. **Domain Layer**: Entities, Value Objects, Domain Services
2. **Application Layer**: Use Cases, DTOs
3. **Infrastructure Layer**: Repositories, Controllers
4. **API Endpoints**: REST endpoints with tests

### Frontend Checkpoints
1. **Component Structure**: Props, state, TypeScript types
2. **Form Logic**: Validation, event handlers
3. **API Integration**: Real API or mocks
4. **Responsive Design**: Mobile, tablet, desktop
5. **Accessibility**: Lighthouse score >90

### QA Checkpoints
1. **API Testing**: All endpoints tested
2. **UI Testing**: All flows verified
3. **Test Execution**: All suites passing
4. **Acceptance Validation**: All criteria checked

## Escape Hatch

If stuck after 10 iterations:

```markdown
## Blocker: <Task Name>

**Checkpoint**: <checkpoint name>
**Iterations attempted**: 10
**Last error**: <exact error message>

**What was tried**:
1. <Approach 1> → <Result>
2. <Approach 2> → <Result>
...

**Root cause hypothesis**: <Why you think it's failing>

**Suggested alternatives**:
1. <Alternative 1>
2. <Alternative 2>

**Status**: BLOCKED - Needs Planner decision
```

## Context Management

Checkpoints help manage Claude's context window:

- **Signal to restart**: If context feels "heavy", checkpoint and start fresh
- **Clean sessions**: New session loads only checkpoint state
- **Focused work**: Each session handles one checkpoint group
