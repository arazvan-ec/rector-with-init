# Session Continuity Guide

**Version**: 2.0 (Provider-aware)
**Last Updated**: 2026-02-08

---

## Overview

Session continuity is the practice of preserving and restoring context across Claude sessions. This is essential because:

1. **Context windows are finite** — even with 1M tokens (Opus 4.6 beta), sessions eventually fill
2. **Sessions end unexpectedly** — Timeouts, crashes, or natural breaks
3. **Work spans multiple sessions** — Complex features take days or weeks
4. **Multiple agents collaborate** — Handoffs between roles require context transfer

This guide explains how to maintain continuity. Thresholds adapt to the active context_management provider (see `core/providers.yaml`).

---

## Proactive Context Management

### Provider-Aware Thresholds

Thresholds depend on the active context_management provider:

| Signal | Manual Snapshots (standard) | Compaction-Aware (advanced) |
|--------|----------------------------|----------------------------|
| Compact at capacity | 70% | 85% |
| Max files read | 20 | 50 |
| Max session duration | 2 hours | 4 hours |
| Max messages | 50 | 150 |
| Snapshot frequency | Every 30-45 min | At milestones only |

**Provider detection**: Read `core/providers.yaml` → `providers.context_management`. If `auto`, detect tier from model identity (advanced = Opus 4.6+, standard = everything else).

### The Capacity Rule

**Don't wait for auto-compact at 95%.** When context reaches the threshold for your provider:

1. **Option A**: Run `/compact` to summarize and reduce context
2. **Option B**: Create `/workflows:snapshot` and start fresh session

With the **compaction-aware provider** (Opus 4.6+), the Compaction API auto-summarizes server-side, so snapshots are primarily for role handoffs and session boundaries, not context exhaustion.

### Signs You Need to Act

| Signal | Standard (Opus 4.5) | Advanced (Opus 4.6+) |
|--------|---------------------|----------------------|
| Responses feel slower | `/compact` if >70% | `/compact` if >85% |
| Files read threshold | >15 files → snapshot | >40 files → snapshot |
| Duration threshold | >1.5 hours → checkpoint | >3 hours → checkpoint |
| Switching tasks | `/clear` between tasks | `/clear` between tasks |
| Complex debugging ahead | Check capacity first | Check capacity first |

### Quick Context Commands

```bash
/context              # Check current token usage
/compact              # Summarize and reduce context
/clear                # Fresh start (loses current context)
/skill:token-advisor  # Get optimization suggestions
```

### Proactive Session Strategy

```
Session Start
    │
    ├── Set mental timer: 1 hour
    │
    ├── Every 30 min: Quick /context check
    │
    ├── At 70% or 1 hour:
    │   ├── /workflows:snapshot --name="checkpoint"
    │   └── Decision: continue or fresh start?
    │
    └── Before complex task:
        └── /skill:token-advisor --quick
```

### Token-Efficient Habits

**Before reading files:**
- Use `grep` to find what you need first
- Read specific line ranges when possible
- Ask "do I really need the full file?"

**During work:**
- Filter command outputs (`--oneline`, `--stat`, `head`)
- Avoid re-reading files already in context
- Summarize findings instead of copying content

**For MCP users:**
- Disable unused MCP servers (they consume context even idle)
- Enable servers on-demand for specific tasks

---

## The Context Challenge

### Understanding Context Limits

Claude's context window is like working memory. As a session progresses:

```
Session Start:
[                                                              ]
Context: Empty, full capacity available

After 1 hour:
[############################################                  ]
Context: 70% used, responses still quick

After 2 hours:
[###########################################################   ]
Context: 95% used, responses may slow

Context Full:
[##############################################################]
Warning: Context limit approaching
         Quality may degrade
         Risk of losing information
```

### Symptoms of Context Exhaustion

Watch for these warning signs:

| Symptom | Indicator |
|---------|-----------|
| **Slower responses** | Noticeable delay in generation |
| **Incomplete answers** | Responses cut off or miss details |
| **Forgotten context** | Claude forgets earlier decisions |
| **Repetitive questions** | Asking about things already discussed |
| **Degraded quality** | Less precise or relevant outputs |

### The Commodore 64 Pattern

> **"Treat context like memory on a Commodore 64 - it's precious and limited."**

Principles:
1. **Load only what you need** - Don't read unnecessary files
2. **Checkpoint frequently** - Save state before it's lost
3. **Summarize, don't copy** - Condense information
4. **Unload when done** - Move completed work out of active context

---

## Snapshot/Restore System

### Creating Snapshots

Use `/workflows:snapshot` to preserve session state:

```bash
# Named snapshot (recommended)
/workflows:snapshot --name="domain-layer-complete"

# With feature context
/workflows:snapshot --name="backend-phase-1" --feature=user-auth

# Quick timestamped snapshot
/workflows:snapshot --name="checkpoint-$(date +%H%M)"
```

### What Gets Saved

A snapshot captures:

| Component | Purpose |
|-----------|---------|
| **State File** | Role statuses, progress, blockers |
| **Conversation Summary** | What was done, decisions made |
| **Modified Files List** | Files changed in session |
| **Role Context** | Current role, stage, workflow mode |
| **Active Tasks** | Pending and in-progress tasks |
| **Git Metadata** | Branch, commit, dirty state |

### Restoring Snapshots

Use `/workflows:restore` to resume:

```bash
# List available snapshots
/workflows:restore --list

# Restore specific snapshot
/workflows:restore --name="domain-layer-complete"

# Preview without applying
/workflows:restore --name="backend-phase-1" --dry-run
```

### Snapshot Storage

Snapshots are stored in `.ai/snapshots/`:

```
.ai/snapshots/
├── domain-layer-complete/
│   ├── state.md
│   ├── conversation.md
│   ├── modified_files.txt
│   ├── context.yaml
│   ├── tasks.md
│   └── metadata.yaml
├── pre-refactor/
│   └── ...
└── checkpoint-1/
    └── ...
```

---

## When to Create Snapshots

### Recommended Trigger Points

| Trigger | Rationale |
|---------|-----------|
| **Completing a phase** | Milestone reached, good restore point |
| **Before major changes** | Safe rollback if something breaks |
| **End of work session** | Resume tomorrow without loss |
| **After 1-2 hours** | Proactive context management |
| **Before role handoff** | Enable smooth transitions |
| **When context feels heavy** | Responses slowing down |
| **Before risky operations** | Safety net for experiments |

### Automatic Detection

The system warns when snapshots are recommended:

```
Context Management Advisory
---------------------------
Session indicators suggest creating a snapshot:

  - Duration: 2h 15m (threshold: 2h)
  - Files in context: 23 (threshold: 20)
  - Messages: 47 (threshold: 50)

Recommendation: /workflows:snapshot --name="auto-checkpoint"

[Continue] [Create Snapshot] [Dismiss]
```

### Snapshot Strategy by Work Type

**Feature Implementation:**
```
Start Feature
    └── snapshot: "feature-start"
        └── Complete Planning
            └── snapshot: "planning-done"
                └── Complete Domain Layer
                    └── snapshot: "domain-complete"
                        └── Complete Application Layer
                            └── snapshot: "application-complete"
                                └── Complete Infrastructure
                                    └── snapshot: "feature-ready-for-qa"
```

**Bug Investigation:**
```
Start Investigation
    └── snapshot: "pre-investigation"
        └── Root Cause Found
            └── snapshot: "root-cause-identified"
                └── Fix Applied
                    └── snapshot: "fix-applied"
```

**Refactoring:**
```
Start Refactoring
    └── snapshot: "pre-refactor" (critical!)
        └── Step 1 Complete
            └── snapshot: "refactor-step-1"
                └── Step 2 Complete
                    └── snapshot: "refactor-step-2"
                        └── All Tests Pass
                            └── snapshot: "refactor-complete"
```

---

## Best Practices

### Naming Conventions

Use descriptive, searchable names:

| Pattern | Example | Use Case |
|---------|---------|----------|
| `<phase>-complete` | `domain-layer-complete` | Milestones |
| `<date>-eod` | `2026-01-27-eod` | End of day |
| `pre-<action>` | `pre-refactor` | Before risky changes |
| `<feature>-<role>-<status>` | `auth-backend-done` | Role completion |
| `checkpoint-<n>` | `checkpoint-3` | Sequential saves |
| `investigate-<issue>` | `investigate-login-bug` | Bug hunts |

### Conversation Summaries

Write useful summaries in snapshots:

**Good Summary:**
```markdown
## Session Summary

### Completed
- Implemented User entity with email validation
- Created Password value object with bcrypt hashing
- Established repository interface pattern
- Fixed circular dependency in Domain layer

### Decisions Made
- Password hashing happens in UseCase, not Entity (separation of concerns)
- Email validation uses filter_var, not regex (simpler, standard)
- Repository returns null for not-found, not exception (Go-style)

### Current Focus
- Starting CreateUserUseCase implementation
- Need to decide on DTO vs direct entity construction

### Blockers
- None currently

### Next Steps
1. Create CreateUserRequest DTO
2. Implement CreateUserUseCase
3. Add validation in UseCase
4. Write unit tests
```

**Bad Summary:**
```markdown
## Summary
Did some stuff with users. Made progress.
```

### Git Integration

Decide on snapshot persistence:

**Team Visibility (Commit Snapshots):**
```bash
# Include in repository
git add .ai/snapshots/
git commit -m "[snapshot] domain-layer-complete"
git push
```

**Personal Only (Ignore Snapshots):**
```bash
# Add to .gitignore
echo ".ai/snapshots/" >> .gitignore
```

**Hybrid (Commit Important Only):**
```bash
# Keep .gitkeep, ignore contents
echo ".ai/snapshots/*" >> .gitignore
echo "!.ai/snapshots/.gitkeep" >> .gitignore
```

---

## Session Restoration Workflow

### Starting a New Session

1. **List available snapshots:**
   ```bash
   /workflows:restore --list --verbose
   ```

2. **Choose appropriate snapshot:**
   - Most recent for continuation
   - Specific milestone for rollback
   - Pre-change for recovery

3. **Restore and review:**
   ```bash
   /workflows:restore --name="domain-layer-complete"
   ```

4. **Sync with remote:**
   ```bash
   /workflows:sync user-auth
   ```

5. **Verify current state:**
   ```bash
   /workflows:status user-auth
   ```

6. **Continue work:**
   ```bash
   /workflows:work user-auth --role=backend
   ```

### Handling Stale Snapshots

When restoring old snapshots:

```
Snapshot Age Check
------------------
Snapshot: pre-refactor
Age: 5 days

Since this snapshot:
- 23 commits have been made
- 15 files have been modified
- 3 PRs have been merged

Recommendation:
1. Restore for context reference
2. Review git log for changes
3. Re-read current state files
4. Create fresh snapshot after orientation
```

### Cross-Role Handoffs

When one role hands off to another:

```
Role Handoff: Backend -> Frontend
---------------------------------

Backend creates snapshot:
  /workflows:snapshot --name="backend-api-complete"

Frontend restores and begins:
  /workflows:restore --name="backend-api-complete"

  Context includes:
  - API endpoints implemented
  - Request/response formats
  - Authentication requirements
  - Test data available
```

---

## Troubleshooting

### Snapshot Not Found

```
Error: Snapshot 'my-snapshot' not found.

Troubleshooting:
1. Check spelling: ls .ai/snapshots/
2. Check branch: snapshots may be on different branch
3. Check gitignore: snapshots may not be committed
```

### Restoration Conflicts

```
Warning: Current state conflicts with snapshot.

Options:
1. Stash current changes: git stash
2. Create snapshot of current state first
3. Use --force to overwrite (caution)
4. Restore context only, keep code
```

### Missing Context After Restore

If restored context feels incomplete:

1. **Read the full state file:**
   ```bash
   cat .ai/project/features/<feature>/50_state.md
   ```

2. **Review recent commits:**
   ```bash
   git log --oneline -20
   ```

3. **Check task files:**
   ```bash
   cat .ai/project/features/<feature>/30_tasks.md
   ```

4. **Re-read feature spec:**
   ```bash
   cat .ai/project/features/<feature>/FEATURE.md
   ```

---

## Integration with Workflow

### Checkpoint vs Snapshot

| Aspect | Checkpoint | Snapshot |
|--------|------------|----------|
| **Purpose** | Progress marker | Full state preservation |
| **Storage** | Git commit | Local files |
| **Content** | State update only | State + summary + files + context |
| **Use Case** | Frequent saves | Session boundaries |
| **Command** | `/workflows:checkpoint` | `/workflows:snapshot` |

### Recommended Flow

```
Session Start
    │
    ├── /workflows:restore (if continuing)
    │
    ├── Work Phase 1
    │   └── /workflows:checkpoint (commit to git)
    │
    ├── Work Phase 2
    │   └── /workflows:checkpoint
    │
    ├── Context Getting Heavy
    │   └── /workflows:snapshot --name="phase-2-done"
    │
    ├── Continue or New Session
    │   └── /workflows:restore --name="phase-2-done"
    │
    └── End Session
        └── /workflows:snapshot --name="eod-<date>"
```

---

## Summary

1. **Context is finite** - Manage it proactively
2. **Snapshot frequently** - At milestones, before risks, regularly
3. **Name descriptively** - You'll thank yourself later
4. **Summarize well** - Future you needs context
5. **Restore confidently** - The system handles the details
6. **Sync after restore** - Get latest changes
7. **Verify before continuing** - Check status first

---

## Quick Reference

```bash
# Create snapshot
/workflows:snapshot --name="descriptive-name"

# List snapshots
/workflows:restore --list

# Restore snapshot
/workflows:restore --name="snapshot-name"

# Full restoration flow
/workflows:restore --name="my-snapshot"
/workflows:sync my-feature
/workflows:status my-feature
/workflows:work my-feature --role=backend
```

---

**Related Documentation:**
- `PAIRING_PATTERNS.md` - How to work effectively with Claude
- `GIT_WORKFLOW.md` - Git practices for multi-agent work
- `COMPREHENSION_DEBT.md` - Managing code comprehension
- `WORKFLOW_DECISION_MATRIX.md` - Choosing the right workflow
