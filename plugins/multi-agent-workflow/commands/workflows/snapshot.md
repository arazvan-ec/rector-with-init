---
name: workflows:snapshot
description: "Save a session snapshot for context preservation and later restoration."
argument_hint: --name="checkpoint-name" [--feature=<feature-name>]
---

# Multi-Agent Workflow: Snapshot

> **Tier 3 (Automatic)**: This command is triggered automatically by `/workflows:work` when context usage exceeds ~70%. Only invoke manually before breaks or risky operations.

Create a complete snapshot of the current session state for preservation and later restoration. Essential for managing context window limits and enabling session continuity.

## Usage

```
/workflows:snapshot --name="domain-layer-complete"
/workflows:snapshot --name="pre-refactor" --feature=user-auth
/workflows:snapshot --name="end-of-day-jan-27"
```

## Why Snapshots Matter

1. **Context Window Limits**: Claude's context window is finite. Snapshots preserve state before context is lost.
2. **Session Continuity**: Resume work in new sessions without losing progress.
3. **Safe Experimentation**: Create snapshots before risky changes to enable rollback.
4. **Handoff Support**: Enable smooth transitions between roles or developers.
5. **Progress Preservation**: Never lose work due to session timeouts or crashes.

## What Gets Captured

A snapshot saves everything needed to restore context:

| Component | Description | Location |
|-----------|-------------|----------|
| **State File** | Current 50_state.md with all role statuses | `snapshot/state.md` |
| **Conversation Summary** | AI-generated summary of session work | `snapshot/conversation.md` |
| **Modified Files** | List of files changed in this session | `snapshot/modified_files.txt` |
| **Role Context** | Current role and workflow stage | `snapshot/context.yaml` |
| **Active Tasks** | In-progress and pending tasks from 30_tasks.md | `snapshot/tasks.md` |
| **Metadata** | Timestamp, branch, commit hash | `snapshot/metadata.yaml` |

## Execution Steps

### Step 1: Gather Current State

```bash
# Determine feature context (from argument or current directory)
FEATURE="${FEATURE:-$(basename $(pwd))}"
SNAPSHOT_NAME="${NAME:-snapshot-$(date +%Y%m%d-%H%M%S)}"
SNAPSHOT_DIR=".ai/snapshots/${SNAPSHOT_NAME}"

# Create snapshot directory
mkdir -p "${SNAPSHOT_DIR}"
```

### Step 2: Copy State File

```bash
# Find and copy current state file
if [ -f ".ai/project/features/${FEATURE}/50_state.md" ]; then
    cp ".ai/project/features/${FEATURE}/50_state.md" "${SNAPSHOT_DIR}/state.md"
else
    # Check for active feature context
    find .ai/project/features -name "50_state.md" -newer /tmp/session_start 2>/dev/null | head -1 | xargs -I {} cp {} "${SNAPSHOT_DIR}/state.md"
fi
```

### Step 3: Generate Conversation Summary

Create a summary of work done in this session:

```markdown
## Conversation Summary

**Session Date**: <timestamp>
**Duration**: <approximate session length>

### Work Completed
- <bulleted list of completed tasks>
- <key decisions made>
- <problems solved>

### Current Focus
- <what was being worked on when snapshot was created>

### Key Context
- <important technical decisions>
- <patterns established>
- <dependencies discovered>

### Unresolved Items
- <open questions>
- <blocked tasks>
- <items for follow-up>
```

Save to `${SNAPSHOT_DIR}/conversation.md`

### Step 4: Capture Modified Files

```bash
# List files modified in this session
git diff --name-only HEAD~10 > "${SNAPSHOT_DIR}/modified_files.txt"

# Also capture uncommitted changes
git status --porcelain >> "${SNAPSHOT_DIR}/modified_files.txt"

# Sort and deduplicate
sort -u "${SNAPSHOT_DIR}/modified_files.txt" -o "${SNAPSHOT_DIR}/modified_files.txt"
```

### Step 5: Save Role Context

Create `${SNAPSHOT_DIR}/context.yaml`:

```yaml
snapshot:
  name: "<snapshot-name>"
  created: "<ISO-8601 timestamp>"

session:
  role: "<current role: planner|backend|frontend|qa>"
  stage: "<workflow stage: planning|implementation|review|compound>"
  feature: "<feature name>"
  branch: "<current git branch>"

workflow:
  mode: "<roles|layers|stack>"
  phase: "<current phase number>"

environment:
  commit: "<current commit hash>"
  working_dir: "<absolute path>"
```

### Step 6: Extract Active Tasks

```bash
# Find tasks file
TASKS_FILE=".ai/project/features/${FEATURE}/30_tasks.md"

if [ -f "${TASKS_FILE}" ]; then
    # Extract IN_PROGRESS and PENDING tasks
    awk '/Status.*IN_PROGRESS/,/^##/' "${TASKS_FILE}" > "${SNAPSHOT_DIR}/tasks.md"
    awk '/Status.*PENDING/,/^##/' "${TASKS_FILE}" >> "${SNAPSHOT_DIR}/tasks.md"
fi
```

### Step 7: Create Metadata

Create `${SNAPSHOT_DIR}/metadata.yaml`:

```yaml
metadata:
  version: "1.0"
  created_at: "<ISO-8601 timestamp>"
  created_by: "<role or agent name>"

git:
  branch: "<branch name>"
  commit: "<commit hash>"
  dirty: <true|false>

statistics:
  files_modified: <count>
  tasks_pending: <count>
  tasks_in_progress: <count>

notes: |
  <optional notes about the snapshot>
```

### Step 8: Output Confirmation

```
Snapshot created: <snapshot-name>
Location: .ai/snapshots/<snapshot-name>/

Contents:
  - state.md           (role statuses and progress)
  - conversation.md    (session summary)
  - modified_files.txt (changed files list)
  - context.yaml       (role and workflow context)
  - tasks.md           (active and pending tasks)
  - metadata.yaml      (snapshot metadata)

To restore this snapshot in a new session:
  /workflows:restore --name="<snapshot-name>"

To list all available snapshots:
  /workflows:restore --list
```

## Snapshot Triggers

Create a snapshot when:

| Trigger | Reason |
|---------|--------|
| **Before major refactoring** | Safe rollback point |
| **After completing a phase** | Preserve milestone state |
| **Context feels heavy** | Responses slow or incomplete |
| **End of work session** | Resume tomorrow |
| **Before risky changes** | Experiment safely |
| **After 1-2 hours of work** | Regular preservation |
| **Before role handoff** | Enable smooth transitions |

## Automatic Snapshot Detection

The system suggests creating a snapshot when:

```
Warning: Context management recommended

Indicators detected:
- Session duration: >2 hours
- Files in context: >20
- Messages exchanged: >50
- Complex multi-file operations

Recommended action:
  /workflows:snapshot --name="auto-<timestamp>"

Continue without snapshot? (y/N)
```

## Snapshot Naming Conventions

Use descriptive names that indicate the state:

| Pattern | Example | Use Case |
|---------|---------|----------|
| `<phase>-complete` | `domain-layer-complete` | Milestone reached |
| `<date>-eod` | `2026-01-27-eod` | End of day |
| `pre-<action>` | `pre-refactor` | Before risky change |
| `<feature>-<role>-<status>` | `user-auth-backend-done` | Role completion |
| `checkpoint-<n>` | `checkpoint-3` | Sequential checkpoints |

## Example Snapshot

```
.ai/snapshots/domain-layer-complete/
├── state.md
│   └── (copy of 50_state.md showing Backend IN_PROGRESS at Domain layer)
├── conversation.md
│   └── (summary: "Implemented User entity, Email VO, Password VO...")
├── modified_files.txt
│   └── (src/Domain/Entity/User.php, src/Domain/ValueObject/Email.php, ...)
├── context.yaml
│   └── (role: backend, stage: implementation, phase: 1)
├── tasks.md
│   └── (BE-005: CreateUserUseCase - PENDING, BE-006: Repository - PENDING)
└── metadata.yaml
    └── (created: 2026-01-27T14:30:00Z, commit: abc123, files_modified: 12)
```

## Integration with Git

Snapshots can optionally be committed to git:

```bash
# Commit snapshot for team visibility
git add ".ai/snapshots/${SNAPSHOT_NAME}"
git commit -m "[snapshot] ${SNAPSHOT_NAME}: <brief description>"
git push origin feature/${FEATURE}
```

Or keep them local (default):

```bash
# Add to .gitignore if you want local-only snapshots
echo ".ai/snapshots/" >> .gitignore
```

## Related Commands

- `/workflows:restore` - Restore from a saved snapshot
- `/workflows:checkpoint` - Create a lightweight checkpoint (commits to git)
- `/workflows:status` - View current status without saving
- `/workflows:sync` - Synchronize with remote before snapshot
