# Frontend/Commands Tasks: Workflow Improvements 2026

> **Feature ID**: workflow-improvements-2026
> **Document**: 31_tasks_frontend.md
> **Role**: Frontend Engineer / Command Developer
> **Note**: This feature is infrastructure-focused, so "frontend" here means CLI commands and user-facing documentation

---

## Task Overview

| Phase | Tasks | Priority | Estimated Effort |
|-------|-------|----------|------------------|
| Phase 1 | FE-001 to FE-004 | HIGH | 1 week |
| Phase 2 | FE-005 to FE-008 | HIGH | 1-2 weeks |
| Phase 3 | FE-009 to FE-011 | MEDIUM | 1 week |
| Phase 4 | FE-012 to FE-014 | MEDIUM | 1 week |
| Phase 5 | FE-015 to FE-017 | LOW | 1 week |

---

## Phase 1: Quick Wins - Commands & Documentation

### FE-001: Create /workflows:progress Command

**Priority**: HIGH
**Reference**: `20_api_contracts.md`
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Create command markdown file
- Display current progress from `claude-progress.txt`
- Support update subcommand

**File**: `plugins/multi-agent-workflow/commands/workflows/progress.md`

**Content**:
```markdown
---
name: workflows:progress
description: "View or update session progress tracking"
argument_hint: [--show | --update <task> <status> | --note <text>]
---

# Multi-Agent Workflow: Progress

Track and manage session progress across context windows.

## Usage

# Show current progress
/workflows:progress --show

# Update task status
/workflows:progress --update BE-001 completed

# Add note for next session
/workflows:progress --note "Remember to test edge cases"


## What This Command Does

### --show (default)
Displays the current contents of `claude-progress.txt`:
- Session info (feature, role, start time)
- Current task and status
- Completed tasks this session
- Notes for next session
- Files modified

### --update <task> <status>
Updates the status of a task:
- Valid statuses: pending, in_progress, completed, blocked

### --note <text>
Adds a note that will be shown at the start of the next session.

## Output Example

=== Session Progress ===
Feature: workflow-improvements-2026
Role: backend
Session started: 2026-01-27T10:00:00Z

Current Task: BE-003 (in_progress)

Completed This Session:
  [x] BE-001: Create harness module structure
  [x] BE-002: Implement progress_manager.sh

Notes for Next Session:
  - Complete cleanup_worktree() function
  - Add error handling

Files Modified:
  - .ai/workflow/harness/progress_manager.sh
  - .ai/workflow/parallel/worktree_manager.sh
```

**Acceptance Criteria**:
- [ ] Command file created with correct format
- [ ] `--show` displays progress clearly
- [ ] `--update` modifies task status
- [ ] `--note` adds notes
- [ ] Error handling for missing progress file

**Done When**: Command works for all operations

---

### FE-002: Create /workflows:trust Command

**Priority**: HIGH
**Reference**: `20_api_contracts.md`
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Create command markdown file
- Evaluate trust level for files or tasks
- Display supervision requirements

**File**: `plugins/multi-agent-workflow/commands/workflows/trust.md`

**Content**:
```markdown
---
name: workflows:trust
description: "Check trust level and supervision requirements for files or tasks"
argument_hint: <filepath | --task <type>>
---

# Multi-Agent Workflow: Trust

Evaluate the trust level and determine supervision requirements.

## Usage

# Check trust for a file
/workflows:trust src/Security/AuthService.php

# Check trust for a task type
/workflows:trust --task payment_integration


## Trust Levels

| Level | Auto-Approve | Supervision | Use Cases |
|-------|--------------|-------------|-----------|
| HIGH | Yes | Minimal | Tests, docs, boilerplate |
| MEDIUM | No | Code review | Features, APIs, UI |
| LOW | No | Pair programming | Security, auth, payments |

## Output Example

=== Trust Evaluation ===
File: src/Security/AuthService.php

Trust Level: LOW
Auto-approve: NO
Supervision: pair_programming
Escalation: Required

Reason: File matches pattern "**/*security*/**"
        Security-critical code requires human oversight

Recommendation:
  - Work with human reviewer present
  - Document all security decisions
  - Run security scan before commit
```

**Acceptance Criteria**:
- [ ] Command evaluates file trust correctly
- [ ] Command evaluates task trust correctly
- [ ] Output is clear and actionable
- [ ] Recommendations provided

**Done When**: Trust evaluation accessible via command

---

### FE-003: Update CLAUDE.md with Learnings Section

**Priority**: HIGH
**Reference**: Compound Engineering pattern
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Add learnings repository section
- Add patterns section
- Add anti-patterns section
- Document compound learning triggers

**Changes to**: `plugins/multi-agent-workflow/CLAUDE.md`

**New Sections**:
```markdown
## Learnings Repository

This section is automatically updated when compound learning triggers fire.

### Bugs Found & Fixed
<!-- Auto-updated by compound_tracker.sh -->

### Patterns to Follow
<!-- Auto-updated by compound_tracker.sh -->

### Anti-patterns to Avoid
<!-- Auto-updated by compound_tracker.sh -->

## Compound Learning

Learnings are automatically captured when:
- A bug is fixed (captures: what, why, prevention)
- A test failure is resolved (captures: root cause, fix)
- Code review feedback is addressed (captures: pattern to follow)

To manually add a learning:
/workflows:compound --capture "description" --solution "how it was solved"
```

**Acceptance Criteria**:
- [ ] CLAUDE.md has learnings sections
- [ ] Format supports auto-updating
- [ ] Instructions clear for manual capture
- [ ] Existing content preserved

**Done When**: CLAUDE.md ready for compound learning

---

### FE-004: Create Quickstart for New Features

**Priority**: MEDIUM
**Reference**: Existing QUICKSTART.md
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Document new features in QUICKSTART.md
- Add section for each new capability
- Provide examples

**Updates to**: `QUICKSTART.md`

**New Sections**:
```markdown
## Session Continuity (NEW)

Sessions now automatically track progress:

# Progress is saved automatically
# To view: /workflows:progress --show
# Notes carry over between sessions


## Parallel Development (NEW)

Run multiple agents in parallel:

# Launch backend and frontend in parallel
/workflows:parallel auth-system --roles=backend,frontend

# Each agent gets isolated git worktree
# No filesystem conflicts!


## Trust-Calibrated Supervision (NEW)

AI supervision is calibrated by task risk:

# Check trust level for a file
/workflows:trust src/Security/AuthService.php

# High-risk files require human oversight
```

**Acceptance Criteria**:
- [ ] QUICKSTART.md updated with new features
- [ ] Examples are copy-pasteable
- [ ] Existing content preserved
- [ ] Flows logically

**Done When**: New users can discover new features

---

## Phase 2: Parallel Agents - Commands

### FE-005: Create /workflows:parallel Command

**Priority**: HIGH
**Reference**: `20_api_contracts.md`
**Methodology**: Standard
**Max Iterations**: 5

**File**: `plugins/multi-agent-workflow/commands/workflows/parallel.md`

**Full Content**:
```markdown
---
name: workflows:parallel
description: "Launch multiple agents in parallel with git worktree isolation"
argument_hint: <feature-id> [--roles=backend,frontend,qa] [--no-tmux]
---

# Multi-Agent Workflow: Parallel

Launch multiple AI agents working in parallel, each in an isolated environment.

## Usage

# Launch with default roles (backend, frontend, qa)
/workflows:parallel user-authentication

# Launch specific roles
/workflows:parallel payment-system --roles=backend,frontend

# Launch without tmux (manual management)
/workflows:parallel auth --roles=backend --no-tmux


## What This Command Does

### 1. Creates Git Worktrees
For each role, creates an isolated working directory:
- .worktrees/backend/ (branch: feature/{feature}-backend)
- .worktrees/frontend/ (branch: feature/{feature}-frontend)
- .worktrees/qa/ (branch: feature/{feature}-qa)

### 2. Allocates Ports
Each agent gets a dedicated port for dev servers:
- Backend: 3001
- Frontend: 3002
- QA: 3003

### 3. Launches tmux Session
Creates a tmux session with panes for each agent:
- Session name: workflow-{feature-id}
- One pane per role
- Each pane runs Claude Code with role context

### 4. Initializes Progress
Each agent starts with:
- Progress tracking enabled
- Role-specific context loaded
- Feature spec available

## Output

tmux session "workflow-user-authentication" created

Agents launched:
  [0] backend  @ .worktrees/backend  :3001
  [1] frontend @ .worktrees/frontend :3002
  [2] qa       @ .worktrees/qa       :3003

Attach with:
  tmux attach -t workflow-user-authentication

Monitor with:
  /workflows:status user-authentication


## Cleanup

When done, cleanup worktrees and session:

/workflows:parallel --cleanup user-authentication


## Tips

1. **Context Isolation**: Each agent only sees its worktree
2. **No Conflicts**: Agents can break builds independently
3. **Easy Review**: Each worktree has clean diff of one role's work
4. **Merge When Ready**: Push branches, create PRs, merge to main
```

**Acceptance Criteria**:
- [ ] Command creates worktrees correctly
- [ ] Ports allocated without conflicts
- [ ] tmux session launches
- [ ] Cleanup works
- [ ] Documentation complete

**Done When**: Parallel development launchable via command

---

### FE-006: Create /workflows:status Command Enhancement

**Priority**: MEDIUM
**Reference**: Existing status.md
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Add parallel agent status
- Show worktree status
- Show port allocations
- Show progress per agent

**Updates to**: `plugins/multi-agent-workflow/commands/workflows/status.md`

**New Output**:
```markdown
## Parallel Agent Status (NEW)

When running parallel agents:

=== Parallel Status: user-authentication ===

Agents:
  backend:
    Status: IN_PROGRESS
    Worktree: .worktrees/backend (clean)
    Port: 3001 (listening)
    Current Task: BE-003
    Progress: 60%

  frontend:
    Status: IN_PROGRESS
    Worktree: .worktrees/frontend (2 uncommitted)
    Port: 3002 (listening)
    Current Task: FE-002
    Progress: 40%

  qa:
    Status: PENDING
    Worktree: .worktrees/qa (clean)
    Port: 3003 (not started)
    Current Task: -
    Progress: 0%

Overall: 50% complete
```

**Acceptance Criteria**:
- [ ] Shows all parallel agents
- [ ] Worktree status displayed
- [ ] Port status shown
- [ ] Progress aggregated

**Done When**: Status shows parallel agent info

---

### FE-007: Create Monitor Dashboard Output

**Priority**: MEDIUM
**Reference**: monitor.sh
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Design dashboard layout
- Support terminal colors
- Auto-refresh option

**Sample Output**:
```
╔══════════════════════════════════════════════════════════════╗
║           WORKFLOW MONITOR: user-authentication              ║
╠══════════════════════════════════════════════════════════════╣
║ BACKEND          │ FRONTEND         │ QA                     ║
║ ───────────────  │ ───────────────  │ ───────────────        ║
║ Status: ACTIVE   │ Status: ACTIVE   │ Status: PENDING        ║
║ Task: BE-003     │ Task: FE-002     │ Task: -                ║
║ Progress: ██░░ 60%│ Progress: █░░░ 40%│ Progress: ░░░░ 0%     ║
║ Port: 3001 ✓     │ Port: 3002 ✓     │ Port: 3003 -           ║
║ Worktree: clean  │ Worktree: dirty  │ Worktree: clean        ║
╚══════════════════════════════════════════════════════════════╝
Last updated: 2026-01-27 14:30:00 | Press 'q' to quit
```

**Acceptance Criteria**:
- [ ] Dashboard layout clear
- [ ] Status color-coded
- [ ] Progress bars work
- [ ] Auto-refresh available

**Done When**: Dashboard usable for monitoring

---

### FE-008: Document Parallel Workflow in TUTORIAL.md

**Priority**: MEDIUM
**Reference**: Existing TUTORIAL.md
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Add parallel development tutorial section
- Step-by-step walkthrough
- Best practices

**New Section**:
```markdown
## Tutorial: Parallel Development

This tutorial shows how to run multiple agents in parallel.

### Step 1: Plan the Feature

/workflows:plan user-profile --workflow=default

### Step 2: Launch Parallel Agents

/workflows:parallel user-profile --roles=backend,frontend

### Step 3: Monitor Progress

# Attach to tmux session
tmux attach -t workflow-user-profile

# Or view status
/workflows:status user-profile

### Step 4: Review and Merge

Each agent works on its own branch. When done:
1. Review each branch's changes
2. Create PRs for each branch
3. Merge to main

### Tips for Parallel Development

1. **Plan thoroughly first** - clear specs reduce conflicts
2. **Define API contracts** - backend/frontend can work independently
3. **Regular syncs** - merge main periodically to avoid drift
4. **One role per worktree** - don't mix roles
```

**Acceptance Criteria**:
- [ ] Tutorial section complete
- [ ] Steps are clear
- [ ] Best practices included
- [ ] Examples realistic

**Done When**: Users can learn parallel development

---

## Phase 3: Spec-Driven - Commands

### FE-009: Create /workflows:interview Command

**Priority**: MEDIUM
**Reference**: `20_api_contracts.md`
**Methodology**: Standard
**Max Iterations**: 5

**File**: `plugins/multi-agent-workflow/commands/workflows/interview.md`

**Content**:
```markdown
---
name: workflows:interview
description: "Interactive spec capture through structured questions"
argument_hint: <feature-id>
---

# Multi-Agent Workflow: Interview

Capture feature requirements through interactive questions.

## Usage

/workflows:interview user-notifications

## Interview Flow

The interview asks structured questions to capture:

1. **Goal**: What is the primary objective?
2. **Users**: Who will use this feature?
3. **Acceptance Criteria**: How do we know it's done?
4. **API Requirements**: What endpoints are needed?
5. **UI Requirements**: What screens/components?
6. **Constraints**: Technical or business constraints?
7. **Dependencies**: What does this depend on?
8. **Risks**: What could go wrong?

## Output

After completing the interview:
- Creates `.ai/project/features/{feature-id}/`
- Generates `spec.yaml` from responses
- Creates initial `50_state.md`
- Suggests next steps

## Example Session

> /workflows:interview user-notifications

Starting interview for: user-notifications

Q1: What is the primary goal of this feature?
> Allow users to receive and manage notifications

Q2: Who are the target users?
> All registered users of the platform

Q3: What are the acceptance criteria?
> - Users can view list of notifications
> - Users can mark notifications as read
> - Users receive real-time updates

...

Interview complete!
Spec generated: .ai/project/features/user-notifications/spec.yaml

Next steps:
1. Review the spec: cat .ai/project/features/user-notifications/spec.yaml
2. Start planning: /workflows:plan user-notifications
```

**Acceptance Criteria**:
- [ ] Command triggers interview flow
- [ ] Questions are clear and structured
- [ ] Responses captured correctly
- [ ] Valid YAML spec generated
- [ ] Feature workspace created

**Done When**: Interview produces usable specs

---

### FE-010: Create YAML Spec Templates

**Priority**: MEDIUM
**Reference**: `15_data_model.md`
**Methodology**: Standard
**Max Iterations**: 5

**Files**:
- `.ai/workflow/specs/templates/feature_spec.yaml`
- `.ai/workflow/specs/templates/api_contract.yaml`

**Feature Spec Template**:
```yaml
# Feature Specification Template
# Fill in or delete optional sections

version: "1.0"

feature:
  id: "{FEATURE_ID}"
  name: "{FEATURE_NAME}"
  priority: high  # critical, high, medium, low
  status: planning  # planning, in_progress, review, completed, blocked

metadata:
  created: "{DATE}"
  author: "{AUTHOR}"
  estimated_effort: ""

objective:
  summary: |
    {ONE_PARAGRAPH_SUMMARY}

  business_value: |
    {WHY_THIS_MATTERS}

requirements:
  functional:
    - id: FR-001
      title: ""
      description: ""
      priority: high
      acceptance_criteria:
        - ""
      test_coverage: required  # required, recommended, optional

  non_functional:
    - id: NFR-001
      type: performance  # performance, reliability, security, usability
      requirement: ""

contracts:
  api:
    - endpoint: ""
      method: POST  # GET, POST, PUT, PATCH, DELETE
      authentication: required  # required, optional, none
      request:
        # Define request body schema
      responses:
        200:
          # Success response
        400:
          # Validation error
        # Add more as needed

tasks:
  backend:
    - id: BE-001
      title: ""
      methodology: TDD
      max_iterations: 10
      acceptance_criteria:
        - ""
      done_when: ""

  frontend:
    - id: FE-001
      title: ""
      methodology: TDD
      max_iterations: 10
      acceptance_criteria:
        - ""
      done_when: ""

  qa:
    - id: QA-001
      title: ""
      acceptance_criteria:
        - ""

dependencies:
  external: []
  internal: []

risks:
  - description: ""
    probability: medium  # low, medium, high
    impact: medium
    mitigation: ""
```

**Acceptance Criteria**:
- [ ] Templates are valid YAML
- [ ] All sections documented
- [ ] Placeholders clear
- [ ] Examples provided

**Done When**: Templates usable for spec creation

---

### FE-011: Document Spec-Driven Workflow

**Priority**: MEDIUM
**Reference**: GitHub Spec Kit
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Add spec-driven section to README
- Explain benefits
- Show example workflow

**New Section for README**:
```markdown
## Spec-Driven Development

The workflow supports structured YAML specifications that serve as the
"source of truth" for features.

### Why Specs?

1. **Clarity**: Reduce ambiguity that causes the "70% problem"
2. **Validation**: Automatically check implementation against spec
3. **Contracts**: API specs enable parallel development
4. **Documentation**: Specs become living documentation

### Creating Specs

Option 1: Interview Mode (recommended for complex features)
/workflows:interview my-feature

Option 2: From Template
cp .ai/workflow/specs/templates/feature_spec.yaml \
   .ai/project/features/my-feature/spec.yaml

Option 3: Manual (for experienced users)
# Create spec.yaml following the schema

### Validating Specs

# Validate spec syntax
/workflows:spec --validate my-feature

# Check implementation compliance
/workflows:spec --check-compliance my-feature
```

**Acceptance Criteria**:
- [ ] Benefits clearly explained
- [ ] All options documented
- [ ] Examples provided
- [ ] Validation covered

**Done When**: Spec-driven workflow documented

---

## Phase 4: TDD & Enforcement - Commands

### FE-012: Create /workflows:tdd Command

**Priority**: MEDIUM
**Reference**: tdd_enforcer.sh
**Methodology**: Standard
**Max Iterations**: 5

**File**: `plugins/multi-agent-workflow/commands/workflows/tdd.md`

**Content**:
```markdown
---
name: workflows:tdd
description: "Check TDD compliance for files or commits"
argument_hint: <filepath | --commit <hash>>
---

# Multi-Agent Workflow: TDD

Verify Test-Driven Development compliance.

## Usage

# Check if file has tests
/workflows:tdd src/User.php

# Check if tests were written first
/workflows:tdd --commit HEAD

# Check coverage
/workflows:tdd --coverage src/User.php

## Output

=== TDD Check: src/User.php ===

Test File: tests/Unit/UserTest.php ✓
Test Coverage: 85% ✓
Test-First: ✓ (tests committed before implementation)

Status: COMPLIANT
```

**Acceptance Criteria**:
- [ ] Checks test existence
- [ ] Verifies test-first
- [ ] Shows coverage
- [ ] Clear compliance status

**Done When**: TDD checkable via command

---

### FE-013: Update Hooks Documentation

**Priority**: MEDIUM
**Reference**: Claude Code hooks
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Document new hooks in README
- Explain configuration
- Show examples

**New Section**:
```markdown
## Hooks

The workflow uses Claude Code hooks for enforcement.

### Available Hooks

1. **pre_commit_tdd.sh**: Blocks commits without tests
2. **session_start.sh**: Restores progress on session start
3. **post_edit_layer.sh**: Validates DDD layer compliance

### Configuring Hooks

Hooks are configured in `.claude/hooks.json`:

{
  "hooks": {
    "PreCommit": [
      {"command": ".ai/workflow/hooks/pre_commit_tdd.sh"}
    ],
    "SessionStart": [
      {"command": ".ai/workflow/hooks/session_start.sh"}
    ]
  }
}

### Disabling Hooks

To temporarily disable a hook:
SKIP_TDD_CHECK=1 git commit -m "message"
```

**Acceptance Criteria**:
- [ ] All hooks documented
- [ ] Configuration explained
- [ ] Disable instructions clear

**Done When**: Hooks fully documented

---

### FE-014: Create Enforcement Dashboard

**Priority**: LOW
**Reference**: Custom
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Show TDD compliance across codebase
- Show trust levels for key files
- Show compound metrics

**Sample Output**:
```
╔══════════════════════════════════════════════════════════════╗
║                  ENFORCEMENT DASHBOARD                        ║
╠══════════════════════════════════════════════════════════════╣
║ TDD COMPLIANCE                                                ║
║ ───────────────────────────────────────────────────────────  ║
║ Files with tests: 45/52 (87%)                                ║
║ Test-first commits: 38/45 (84%)                              ║
║ Average coverage: 82%                                         ║
║                                                               ║
║ TRUST DISTRIBUTION                                            ║
║ ───────────────────────────────────────────────────────────  ║
║ High trust files: 120                                         ║
║ Medium trust files: 85                                        ║
║ Low trust files: 15                                           ║
║                                                               ║
║ COMPOUND METRICS                                              ║
║ ───────────────────────────────────────────────────────────  ║
║ First-pass success: 55% (target: 70%)                        ║
║ Context reuse: 35% (target: 60%)                             ║
║ Regression rate: 10% (target: 5%)                            ║
╚══════════════════════════════════════════════════════════════╝
```

**Acceptance Criteria**:
- [ ] Shows all enforcement metrics
- [ ] Progress toward targets visible
- [ ] Actionable insights

**Done When**: Dashboard shows enforcement status

---

## Phase 5: Integration - Commands

### FE-015: Create MCP Status Command

**Priority**: LOW
**Reference**: MCP integration
**Methodology**: Standard
**Max Iterations**: 5

**File**: `plugins/multi-agent-workflow/commands/workflows/mcp.md`

**Content**:
```markdown
---
name: workflows:mcp
description: "Manage MCP server connections"
argument_hint: [--status | --connect <server> | --disconnect <server>]
---

# Multi-Agent Workflow: MCP

Manage Model Context Protocol server connections.

## Usage

# Show MCP server status
/workflows:mcp --status

# Connect to a server
/workflows:mcp --connect github

# Disconnect from a server
/workflows:mcp --disconnect semgrep
```

**Acceptance Criteria**:
- [ ] Status shows connected servers
- [ ] Connect/disconnect works
- [ ] Auth status visible

**Done When**: MCP manageable via command

---

### FE-016: Update INDEX.md with New Commands

**Priority**: LOW
**Reference**: Existing INDEX.md
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Add all new commands to index
- Organize by category
- Update navigation

**New Entries**:
```markdown
## Commands Index

### Core Workflow
- `/workflows:plan` - Plan features
- `/workflows:work` - Start implementation
- `/workflows:review` - Review code
- `/workflows:compound` - Capture learnings

### Session Management (NEW)
- `/workflows:progress` - Track session progress
- `/workflows:parallel` - Launch parallel agents
- `/workflows:status` - View workflow status

### Quality & Enforcement (NEW)
- `/workflows:trust` - Check trust levels
- `/workflows:tdd` - Check TDD compliance
- `/workflows:spec` - Validate specifications

### Integration (NEW)
- `/workflows:mcp` - Manage MCP servers
- `/workflows:interview` - Interactive spec capture
```

**Acceptance Criteria**:
- [ ] All new commands listed
- [ ] Categories logical
- [ ] Links work

**Done When**: Index complete and navigable

---

### FE-017: Create Migration Guide

**Priority**: LOW
**Reference**: Existing workflows
**Methodology**: Standard
**Max Iterations**: 5

**File**: `docs/MIGRATION_2026.md`

**Content**:
```markdown
# Migration Guide: Workflow 2026

This guide helps you migrate from the previous workflow version.

## What's New

1. Session Continuity (claude-progress.txt)
2. Parallel Agents (git worktrees + tmux)
3. YAML Specs (structured specifications)
4. TDD Enforcement (pre-commit hooks)
5. Trust Model (calibrated supervision)

## Migration Steps

### Step 1: Update Configuration

Add new sections to `.ai/project/config.yaml`:

harness:
  progress_file: ".ai/project/sessions/claude-progress.txt"

parallel:
  manager: "tmux"

### Step 2: Install Dependencies

Ensure you have:
- tmux >= 3.0
- jq >= 1.6

### Step 3: Enable Hooks (Optional)

Copy hooks configuration:
cp .ai/workflow/hooks.json.example .claude/hooks.json

### Step 4: Migrate Existing Specs

Convert existing FEATURE_X.md to YAML:
/workflows:spec --convert .ai/project/features/my-feature/FEATURE_my-feature.md

## Backward Compatibility

All existing commands continue to work:
- /workflows:plan ✓
- /workflows:work ✓
- 50_state.md ✓
- Markdown specs ✓

New features are additive, not breaking.

## Getting Help

If you encounter issues:
1. Check the GLOSSARY.md for new terms
2. Review the QUICKSTART.md for new features
3. File an issue on GitHub
```

**Acceptance Criteria**:
- [ ] Migration steps clear
- [ ] Backward compat explained
- [ ] Dependencies listed
- [ ] Help resources provided

**Done When**: Users can migrate smoothly

---

## Summary

**Total Tasks**: 17
**Phase 1**: 4 tasks (commands, docs)
**Phase 2**: 4 tasks (parallel commands)
**Phase 3**: 3 tasks (spec commands)
**Phase 4**: 3 tasks (enforcement commands)
**Phase 5**: 3 tasks (integration commands)

---

**Document Status**: COMPLETE
**Last Updated**: 2026-01-27
**Author**: Planner Agent
