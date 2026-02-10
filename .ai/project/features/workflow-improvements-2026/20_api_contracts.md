# API Contracts: Workflow Improvements 2026

> **Feature ID**: workflow-improvements-2026
> **Document**: 20_api_contracts.md
> **Status**: DRAFT

---

## 1. Internal APIs (Bash Scripts)

### 1.1 Progress Manager API

**File**: `.ai/workflow/harness/progress_manager.sh`

```bash
#!/usr/bin/env bash
# Progress Manager - Manages claude-progress.txt for session continuity
# Source: Anthropic's Agent Harness pattern

# Initialize progress tracking for a feature/role
# @param $1 feature_id - Feature identifier
# @param $2 role - Agent role (planner, backend, frontend, qa)
# @return void - Creates progress file
progress_init() {
    local feature_id="$1"
    local role="$2"
    # Creates .ai/project/sessions/claude-progress.txt
}

# Update progress for current task
# @param $1 task_id - Task identifier (e.g., BE-001)
# @param $2 status - Status (pending, in_progress, completed, blocked)
# @param $3 notes - Optional notes
# @return void - Updates progress file
progress_update() {
    local task_id="$1"
    local status="$2"
    local notes="${3:-}"
}

# Read current progress
# @return JSON - Current progress state
progress_read() {
    # Returns JSON:
    # {
    #   "session_id": "abc123",
    #   "feature": "workflow-improvements-2026",
    #   "role": "backend",
    #   "current_task": "BE-003",
    #   "completed": ["BE-001", "BE-002"],
    #   "blockers": []
    # }
}

# Save progress (explicit save, normally auto-saved)
# @return void
progress_save() {
    # Writes to claude-progress.txt
}

# Add note for next session
# @param $1 note - Note text
# @return void
progress_add_note() {
    local note="$1"
}

# Mark file as modified this session
# @param $1 filepath - Path to file
# @return void
progress_mark_file() {
    local filepath="$1"
}
```

**Usage Example**:
```bash
source .ai/workflow/harness/progress_manager.sh

progress_init "workflow-improvements-2026" "backend"
progress_update "BE-001" "in_progress" "Starting implementation"
progress_mark_file ".ai/workflow/harness/initializer.sh"
progress_update "BE-001" "completed"
progress_add_note "Next: implement error handling"
progress_save
```

---

### 1.2 Worktree Manager API

**File**: `.ai/workflow/parallel/worktree_manager.sh`

```bash
#!/usr/bin/env bash
# Worktree Manager - Git worktree automation for parallel agents
# Source: workmux, uzi patterns

# Create a new worktree for an agent role
# @param $1 role - Agent role
# @param $2 branch - Branch name (auto-generated if empty)
# @param $3 base_branch - Base branch (default: main)
# @return string - Path to worktree
worktree_create() {
    local role="$1"
    local branch="${2:-}"
    local base_branch="${3:-main}"
    # Creates .worktrees/{role}
    # Returns: /path/to/.worktrees/{role}
}

# List all active worktrees
# @return JSON - Array of worktree info
worktree_list() {
    # Returns JSON:
    # [
    #   {
    #     "role": "backend",
    #     "path": ".worktrees/backend",
    #     "branch": "feature/backend-impl",
    #     "status": "active",
    #     "port": 3001
    #   }
    # ]
}

# Get worktree status
# @param $1 role - Agent role
# @return JSON - Worktree status
worktree_status() {
    local role="$1"
    # Returns JSON:
    # {
    #   "exists": true,
    #   "clean": true,
    #   "uncommitted_changes": 0,
    #   "ahead": 2,
    #   "behind": 0
    # }
}

# Cleanup worktree
# @param $1 role - Agent role
# @param $2 force - Force cleanup (default: false)
# @return void
worktree_cleanup() {
    local role="$1"
    local force="${2:-false}"
}

# Sync worktree with main branch
# @param $1 role - Agent role
# @return void
worktree_sync() {
    local role="$1"
}
```

**Usage Example**:
```bash
source .ai/workflow/parallel/worktree_manager.sh

path=$(worktree_create "backend" "feature/auth-backend")
echo "Worktree created at: $path"

worktree_list | jq '.'
worktree_status "backend" | jq '.'
worktree_sync "backend"
worktree_cleanup "backend"
```

---

### 1.3 Tmux Orchestrator API

**File**: `.ai/workflow/parallel/tmux_orchestrator.sh`

```bash
#!/usr/bin/env bash
# Tmux Orchestrator - Manage tmux sessions for parallel agents

# Create orchestrated tmux session
# @param $1 session_name - Session name
# @param $2 roles - Comma-separated roles (backend,frontend,qa)
# @return void - Creates tmux session with panes
tmux_create_session() {
    local session_name="$1"
    local roles="$2"
}

# Attach to existing session
# @param $1 session_name - Session name
# @return void
tmux_attach() {
    local session_name="$1"
}

# Send command to specific pane
# @param $1 session_name - Session name
# @param $2 role - Role (pane identifier)
# @param $3 command - Command to execute
# @return void
tmux_send_command() {
    local session_name="$1"
    local role="$2"
    local command="$3"
}

# Get output from pane
# @param $1 session_name - Session name
# @param $2 role - Role
# @param $3 lines - Number of lines (default: 50)
# @return string - Pane output
tmux_get_output() {
    local session_name="$1"
    local role="$2"
    local lines="${3:-50}"
}

# Kill session
# @param $1 session_name - Session name
# @return void
tmux_kill_session() {
    local session_name="$1"
}
```

---

### 1.4 Port Manager API

**File**: `.ai/workflow/parallel/port_manager.sh`

```bash
#!/usr/bin/env bash
# Port Manager - Allocate ports for dev servers

# Allocate port for a role
# @param $1 role - Agent role
# @return int - Allocated port number
port_allocate() {
    local role="$1"
    # Returns: 3001, 3002, etc.
}

# Release port
# @param $1 role - Agent role
# @return void
port_release() {
    local role="$1"
}

# Check if port is in use
# @param $1 port - Port number
# @return bool - true if in use
port_in_use() {
    local port="$1"
}

# Get port for role
# @param $1 role - Agent role
# @return int - Port number or empty
port_get() {
    local role="$1"
}

# List all allocated ports
# @return JSON - Port allocations
port_list() {
    # Returns:
    # {
    #   "backend": 3001,
    #   "frontend": 3002,
    #   "qa": 3003
    # }
}
```

---

### 1.5 Spec Validator API

**File**: `.ai/workflow/specs/validator.sh`

```bash
#!/usr/bin/env bash
# Spec Validator - Validate YAML specs against JSON Schema

# Validate feature spec
# @param $1 spec_path - Path to spec.yaml
# @return JSON - Validation result
spec_validate() {
    local spec_path="$1"
    # Returns:
    # {
    #   "valid": true,
    #   "errors": [],
    #   "warnings": []
    # }
}

# Validate against implementation
# @param $1 spec_path - Path to spec.yaml
# @param $2 impl_path - Path to implementation
# @return JSON - Compliance result
spec_check_compliance() {
    local spec_path="$1"
    local impl_path="$2"
    # Returns:
    # {
    #   "compliant": false,
    #   "missing": ["FR-103"],
    #   "incomplete": ["FR-101"],
    #   "complete": ["FR-102"]
    # }
}

# Generate tasks from spec
# @param $1 spec_path - Path to spec.yaml
# @return markdown - Task breakdown
spec_generate_tasks() {
    local spec_path="$1"
}
```

---

### 1.6 TDD Enforcer API

**File**: `.ai/workflow/enforcement/tdd_enforcer.sh`

```bash
#!/usr/bin/env bash
# TDD Enforcer - Ensure test-driven development compliance

# Check if file has tests
# @param $1 filepath - Path to source file
# @return JSON - Test status
tdd_check_tests_exist() {
    local filepath="$1"
    # Returns:
    # {
    #   "has_tests": true,
    #   "test_file": "tests/Unit/UserTest.php",
    #   "coverage": 85
    # }
}

# Verify test-first commits
# @param $1 filepath - Path to source file
# @return JSON - TDD compliance
tdd_verify_order() {
    local filepath="$1"
    # Checks git history to verify tests committed before implementation
    # Returns:
    # {
    #   "compliant": true,
    #   "test_commit": "abc123",
    #   "impl_commit": "def456",
    #   "test_first": true
    # }
}

# Check for test deletion
# @param $1 commit - Commit hash to check
# @return JSON - Deletion status
tdd_check_deletion() {
    local commit="${1:-HEAD}"
    # Returns:
    # {
    #   "tests_deleted": false,
    #   "deleted_tests": []
    # }
}

# Pre-commit hook for TDD
# @return int - 0 if pass, 1 if fail
tdd_pre_commit() {
    # Runs all TDD checks
    # Returns exit code
}
```

---

### 1.7 Trust Evaluator API

**File**: `.ai/workflow/enforcement/trust_evaluator.sh`

```bash
#!/usr/bin/env bash
# Trust Evaluator - Assess trust level for files/tasks

# Get trust level for file
# @param $1 filepath - Path to file
# @return string - Trust level (high, medium, low)
trust_get_level() {
    local filepath="$1"
}

# Check if auto-approve allowed
# @param $1 filepath - Path to file
# @return bool - true if auto-approve allowed
trust_can_auto_approve() {
    local filepath="$1"
}

# Get supervision requirement
# @param $1 filepath - Path to file
# @return string - Supervision type
trust_get_supervision() {
    local filepath="$1"
    # Returns: "minimal", "code_review_required", "pair_programming"
}

# Evaluate task trust
# @param $1 task_type - Type of task
# @return JSON - Trust assessment
trust_evaluate_task() {
    local task_type="$1"
    # Returns:
    # {
    #   "level": "medium",
    #   "auto_approve": false,
    #   "supervision": "code_review_required",
    #   "reason": "Feature implementation requires review"
    # }
}
```

---

### 1.8 Context Manager API

**File**: `.ai/workflow/enforcement/context_manager.sh`

```bash
#!/usr/bin/env bash
# Context Manager - Optimize context window usage

# Generate code skeleton
# @param $1 filepath - Path to file
# @return string - Skeleton representation
context_generate_skeleton() {
    local filepath="$1"
    # Returns condensed code structure without implementation
}

# Check context usage
# @return JSON - Context stats
context_check_usage() {
    # Returns:
    # {
    #   "estimated_tokens": 85000,
    #   "threshold": 100000,
    #   "usage_percent": 85,
    #   "recommend_compaction": true
    # }
}

# Get priority files
# @param $1 context - Current context description
# @return JSON - Prioritized files
context_prioritize_files() {
    local context="$1"
    # Returns files ordered by relevance
}

# Suggest clear
# @return bool - true if should clear
context_should_clear() {
    # Checks if /clear should be recommended
}
```

---

### 1.9 Compound Tracker API

**File**: `.ai/workflow/enforcement/compound_tracker.sh`

```bash
#!/usr/bin/env bash
# Compound Tracker - Capture and track learnings

# Capture learning
# @param $1 type - Learning type (bug, pattern, anti_pattern)
# @param $2 description - What happened
# @param $3 solution - How it was solved
# @param $4 rule - Prevention rule
# @return void
compound_capture() {
    local type="$1"
    local description="$2"
    local solution="$3"
    local rule="$4"
}

# Update CLAUDE.md with learnings
# @return void
compound_update_claude_md() {
    # Appends new learnings to CLAUDE.md
}

# Get metrics
# @return JSON - Compound metrics
compound_get_metrics() {
    # Returns:
    # {
    #   "first_pass_rate": 55,
    #   "context_reuse": 35,
    #   "regression_rate": 10
    # }
}

# Track event
# @param $1 event - Event type
# @param $2 metadata - JSON metadata
# @return void
compound_track_event() {
    local event="$1"
    local metadata="$2"
}
```

---

## 2. CLI Commands

### 2.1 /workflows:parallel

**File**: `plugins/multi-agent-workflow/commands/workflows/parallel.md`

```markdown
---
name: workflows:parallel
description: "Launch multiple agents in parallel with git worktree isolation"
argument_hint: <feature-id> [--roles=backend,frontend,qa]
---

# Usage
/workflows:parallel auth-system
/workflows:parallel auth-system --roles=backend,frontend

# Arguments
- feature-id: Feature to work on
- --roles: Comma-separated roles (default: backend,frontend,qa)

# What it does
1. Creates git worktrees for each role
2. Allocates ports for dev servers
3. Launches tmux session with panes
4. Starts Claude Code in each pane with role context

# Output
tmux session "workflow-{feature-id}" created
- Pane 0: backend (port 3001)
- Pane 1: frontend (port 3002)
- Pane 2: qa (port 3003)

Attach with: tmux attach -t workflow-{feature-id}
```

### 2.2 /workflows:interview

**File**: `plugins/multi-agent-workflow/commands/workflows/interview.md`

```markdown
---
name: workflows:interview
description: "Interactive spec capture through structured questions"
argument_hint: <feature-id>
---

# Usage
/workflows:interview user-profile

# What it does
1. Asks structured questions about the feature
2. Captures requirements, constraints, acceptance criteria
3. Generates YAML spec from responses
4. Creates feature workspace with spec.yaml

# Interview Flow
1. What is the primary goal?
2. Who are the users?
3. What are the acceptance criteria?
4. What APIs are needed?
5. What are the constraints?
6. What similar features exist?
7. What are the risks?
```

### 2.3 /workflows:progress

**File**: `plugins/multi-agent-workflow/commands/workflows/progress.md`

```markdown
---
name: workflows:progress
description: "View or update session progress"
argument_hint: [--show | --update <task> <status>]
---

# Usage
/workflows:progress --show
/workflows:progress --update BE-001 completed

# What it does
- --show: Display current progress from claude-progress.txt
- --update: Update task status
- No args: Show summary
```

### 2.4 /workflows:trust

**File**: `plugins/multi-agent-workflow/commands/workflows/trust.md`

```markdown
---
name: workflows:trust
description: "Check or evaluate trust level for files/tasks"
argument_hint: <filepath | --task type>
---

# Usage
/workflows:trust src/Security/AuthService.php
/workflows:trust --task payment_integration

# Output
File: src/Security/AuthService.php
Trust Level: LOW
Auto-approve: NO
Supervision: pair_programming
Reason: Security-critical code requires human oversight
```

---

## 3. Hook Contracts

### 3.1 Pre-commit TDD Hook

**File**: `.ai/workflow/hooks/pre_commit_tdd.sh`

```bash
#!/usr/bin/env bash
# Pre-commit hook for TDD enforcement

# Input: Staged files from git
# Output: Exit 0 if pass, exit 1 if fail

# Checks:
# 1. New source files have corresponding test files
# 2. No test files were deleted
# 3. Test coverage meets minimum threshold

# Example output on failure:
# ERROR: TDD Violation
# - src/User.php has no test file (expected: tests/Unit/UserTest.php)
# - tests/OrderTest.php was deleted
# Commit blocked. Write tests first.
```

### 3.2 Session Start Hook

**File**: `.ai/workflow/hooks/session_start.sh`

```bash
#!/usr/bin/env bash
# Session start hook - restore context

# Triggered: When Claude Code session starts
# Actions:
# 1. Read claude-progress.txt if exists
# 2. Display progress summary
# 3. Suggest next actions
# 4. Check for blockers

# Output example:
# === Session Restored ===
# Feature: workflow-improvements-2026
# Role: backend
# Last task: BE-003 (60% complete)
#
# Next steps:
# 1. Complete cleanup_worktree() function
# 2. Add error handling
#
# Files to review:
# - .ai/workflow/parallel/worktree_manager.sh
```

---

## 4. MCP Server Contracts

### 4.1 GitHub MCP

**Config**: `.ai/workflow/integrations/mcp/github.yaml`

```yaml
name: github
version: "1.0"
description: "GitHub integration for code search and PR management"

capabilities:
  - search_code
  - read_pr
  - list_issues
  - get_file_content

authentication:
  type: oauth2
  scopes:
    - repo
    - read:org

tools:
  - name: search_code
    description: "Search code in repository"
    parameters:
      query: string
      path: string (optional)
    returns: SearchResult[]

  - name: read_pr
    description: "Read pull request details"
    parameters:
      pr_number: integer
    returns: PullRequest

  - name: list_issues
    description: "List repository issues"
    parameters:
      state: "open" | "closed" | "all"
      labels: string[] (optional)
    returns: Issue[]
```

### 4.2 Semgrep MCP

**Config**: `.ai/workflow/integrations/mcp/semgrep.yaml`

```yaml
name: semgrep
version: "1.0"
description: "Static analysis for security and code quality"

capabilities:
  - scan_file
  - scan_directory
  - get_rules

authentication:
  type: api_key
  env_var: SEMGREP_API_KEY

tools:
  - name: scan_file
    description: "Scan single file for issues"
    parameters:
      path: string
      rules: string[] (optional)
    returns: Finding[]

  - name: scan_directory
    description: "Scan directory recursively"
    parameters:
      path: string
      rules: string[] (optional)
      exclude: string[] (optional)
    returns: ScanResult
```

---

**Document Status**: COMPLETE
**Last Updated**: 2026-01-27
**Author**: Planner Agent
