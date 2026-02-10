# Backend Tasks: Workflow Improvements 2026

> **Feature ID**: workflow-improvements-2026
> **Document**: 30_tasks_backend.md
> **Role**: Backend Engineer
> **Methodology**: TDD (Test-Driven Development)

---

## Task Overview

| Phase | Tasks | Priority | Estimated Effort |
|-------|-------|----------|------------------|
| Phase 1 | BE-001 to BE-006 | HIGH | 1-2 weeks |
| Phase 2 | BE-007 to BE-012 | HIGH | 2-3 weeks |
| Phase 3 | BE-013 to BE-016 | MEDIUM | 2 weeks |
| Phase 4 | BE-017 to BE-020 | MEDIUM | 2 weeks |
| Phase 5 | BE-021 to BE-025 | LOW | 2-3 weeks |

---

## Phase 1: Quick Wins (Agent Harness Foundation)

### BE-001: Create Harness Module Structure

**Priority**: HIGH
**Reference**: `.ai/workflow/scripts/` (existing pattern)
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Create `.ai/workflow/harness/` directory
- Create placeholder scripts with correct permissions
- Follow existing script patterns (shebang, strict mode, usage function)

**Files to Create**:
```
.ai/workflow/harness/
├── initializer.sh
├── coder.sh
├── progress_manager.sh
└── state_serializer.sh
```

**TDD Approach**:
1. RED: Write test that checks directory exists and scripts are executable
2. GREEN: Create structure
3. REFACTOR: Ensure consistent formatting

**Acceptance Criteria**:
- [ ] Directory structure created
- [ ] All scripts have `#!/usr/bin/env bash` shebang
- [ ] All scripts have `set -euo pipefail`
- [ ] All scripts have `usage()` function
- [ ] All scripts are executable (chmod +x)

**Verification**:
```bash
test -d .ai/workflow/harness && echo "Directory exists"
ls -la .ai/workflow/harness/*.sh | grep -q "x" && echo "Scripts executable"
head -1 .ai/workflow/harness/*.sh | grep -q "bash" && echo "Correct shebang"
```

**Done When**: Structure exists, all scripts executable, follow conventions

**Escape Hatch**: If blocked after 10 iterations, document in DECISIONS.md why structure couldn't be created.

---

### BE-002: Implement progress_manager.sh

**Priority**: HIGH
**Reference**: `20_api_contracts.md` - Progress Manager API
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Implement all Progress Manager API functions
- Create/manage `claude-progress.txt`
- Support JSON output for `progress_read()`

**TDD Approach**:
1. RED: Write test for `progress_init()` - should create file
2. GREEN: Implement `progress_init()`
3. RED: Write test for `progress_update()` - should update task status
4. GREEN: Implement `progress_update()`
5. RED: Write test for `progress_read()` - should return JSON
6. GREEN: Implement `progress_read()`
7. REFACTOR: Clean up, add error handling

**Tests to Write** (in `tests/workflow/harness/`):
```bash
# test_progress_manager.sh
test_progress_init_creates_file()
test_progress_init_sets_feature_and_role()
test_progress_update_changes_status()
test_progress_update_adds_to_completed()
test_progress_read_returns_valid_json()
test_progress_add_note_appends_to_notes()
test_progress_mark_file_tracks_files()
test_progress_save_writes_to_disk()
```

**Acceptance Criteria**:
- [ ] `progress_init(feature, role)` creates progress file
- [ ] `progress_update(task, status)` updates task status
- [ ] `progress_read()` returns valid JSON
- [ ] `progress_add_note(note)` adds note for next session
- [ ] `progress_mark_file(path)` tracks modified files
- [ ] `progress_save()` persists to disk
- [ ] All tests pass
- [ ] Coverage > 80%

**Verification**:
```bash
# Run tests
./tests/workflow/harness/test_progress_manager.sh

# Manual verification
source .ai/workflow/harness/progress_manager.sh
progress_init "test-feature" "backend"
cat .ai/project/sessions/claude-progress.txt
progress_read | jq '.'
```

**Done When**: All API functions work, tests pass, JSON output valid

**Escape Hatch**: Document in DECISIONS.md if JSON parsing becomes complex.

---

### BE-003: Implement initializer.sh

**Priority**: HIGH
**Reference**: Anthropic Agent Harness pattern
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Detect if this is first session for feature
- Set up environment (create directories, initialize state)
- Create initial progress file
- Display welcome message with context

**TDD Approach**:
1. RED: Test that initializer detects first session
2. GREEN: Implement detection logic
3. RED: Test that initializer creates required directories
4. GREEN: Implement directory creation
5. RED: Test that initializer creates progress file
6. GREEN: Implement progress initialization
7. REFACTOR: Add helpful output messages

**Acceptance Criteria**:
- [ ] Detects first session (no existing progress file)
- [ ] Creates feature workspace if not exists
- [ ] Initializes progress file
- [ ] Displays context summary
- [ ] Idempotent (safe to run multiple times)

**Verification**:
```bash
./tests/workflow/harness/test_initializer.sh

# Manual test
rm -f .ai/project/sessions/claude-progress.txt
.ai/workflow/harness/initializer.sh "test-feature" "backend"
cat .ai/project/sessions/claude-progress.txt
```

**Done When**: First session setup works, progress initialized

---

### BE-004: Implement coder.sh

**Priority**: HIGH
**Reference**: Anthropic Agent Harness pattern
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Load existing progress on session start
- Display summary of previous work
- Suggest next actions based on state
- Handle blockers gracefully

**TDD Approach**:
1. RED: Test that coder loads existing progress
2. GREEN: Implement progress loading
3. RED: Test that coder displays summary
4. GREEN: Implement summary display
5. RED: Test that coder handles missing progress
6. GREEN: Implement fallback behavior

**Acceptance Criteria**:
- [ ] Loads progress from `claude-progress.txt`
- [ ] Displays clear summary of previous session
- [ ] Lists files modified in previous session
- [ ] Shows notes for this session
- [ ] Suggests next task to work on
- [ ] Handles missing progress gracefully

**Verification**:
```bash
./tests/workflow/harness/test_coder.sh

# Manual test
.ai/workflow/harness/coder.sh
```

**Done When**: Subsequent sessions restore context correctly

---

### BE-005: Implement trust_evaluator.sh

**Priority**: HIGH
**Reference**: `20_api_contracts.md` - Trust Evaluator API
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Load trust model from `trust_model.yaml`
- Evaluate file paths against patterns
- Determine trust level and supervision requirements
- Support task type evaluation

**TDD Approach**:
1. RED: Test trust level for test file (should be high)
2. GREEN: Implement pattern matching
3. RED: Test trust level for security file (should be low)
4. GREEN: Implement low trust patterns
5. RED: Test supervision requirement
6. GREEN: Implement supervision mapping

**Tests**:
```bash
test_trust_test_file_is_high()      # *.test.ts -> high
test_trust_security_file_is_low()   # *security* -> low
test_trust_normal_file_is_medium()  # src/User.ts -> medium
test_trust_auto_approve_high_only() # Only high trust can auto-approve
test_trust_supervision_matches_level()
```

**Acceptance Criteria**:
- [ ] `trust_get_level(path)` returns correct level
- [ ] `trust_can_auto_approve(path)` returns boolean
- [ ] `trust_get_supervision(path)` returns supervision type
- [ ] `trust_evaluate_task(type)` evaluates task types
- [ ] Configuration loaded from YAML

**Verification**:
```bash
./tests/workflow/enforcement/test_trust_evaluator.sh

# Manual test
source .ai/workflow/enforcement/trust_evaluator.sh
trust_get_level "src/Security/AuthService.php"  # Should return "low"
trust_get_level "tests/Unit/UserTest.php"       # Should return "high"
```

**Done When**: Trust levels correctly evaluated from config

---

### BE-006: Create trust_model.yaml Configuration

**Priority**: HIGH
**Reference**: Addy Osmani's 70% Problem
**Methodology**: Standard (config file)
**Max Iterations**: 5

**Requirements**:
- Define high/medium/low trust contexts
- Include file patterns and task types
- Document each level's implications

**Content**:
```yaml
version: "1.0"

trust_levels:
  high:
    description: "AI can work autonomously"
    auto_approve: true
    supervision: "minimal"
    contexts:
      - pattern: "*.test.ts"
      - pattern: "*.test.php"
      - pattern: "*.spec.ts"
      - pattern: "docs/**"
      - pattern: "README.md"
      - pattern: "CHANGELOG.md"
      - task_type: "boilerplate"
      - task_type: "documentation"
      - task_type: "unit_tests"

  medium:
    description: "AI works, human reviews"
    auto_approve: false
    supervision: "code_review_required"
    contexts:
      - pattern: "src/**"
      - pattern: "lib/**"
      - task_type: "feature_implementation"
      - task_type: "api_endpoints"
      - task_type: "ui_components"

  low:
    description: "AI suggests, human implements"
    auto_approve: false
    supervision: "pair_programming"
    escalation: true
    contexts:
      - pattern: "**/*security*/**"
      - pattern: "**/*auth*/**"
      - pattern: "**/*payment*/**"
      - pattern: "**/*credential*/**"
      - pattern: ".env*"
      - pattern: "**/config/secrets*"
      - task_type: "security"
      - task_type: "authentication"
      - task_type: "payment"
      - task_type: "migration"
      - task_type: "infrastructure"
```

**Acceptance Criteria**:
- [ ] File created at `.ai/workflow/trust_model.yaml`
- [ ] All three trust levels defined
- [ ] Patterns cover common cases
- [ ] Documentation clear
- [ ] Validated against schema

**Done When**: Config file complete and valid

---

## Phase 2: Parallel Agents

### BE-007: Create Parallel Module Structure

**Priority**: HIGH
**Reference**: BE-001 pattern
**Methodology**: TDD
**Max Iterations**: 10

**Files to Create**:
```
.ai/workflow/parallel/
├── worktree_manager.sh
├── tmux_orchestrator.sh
├── port_manager.sh
└── monitor.sh
```

**Acceptance Criteria**:
- [ ] Directory created
- [ ] All scripts have correct structure
- [ ] All scripts executable

**Done When**: Structure ready for implementation

---

### BE-008: Implement worktree_manager.sh

**Priority**: HIGH
**Reference**: `20_api_contracts.md` - Worktree Manager API
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Create/manage git worktrees in `.worktrees/`
- Support create, list, status, cleanup, sync operations
- Handle errors gracefully (uncommitted changes, etc.)

**TDD Tests**:
```bash
test_worktree_create_creates_directory()
test_worktree_create_checks_out_branch()
test_worktree_list_returns_json()
test_worktree_status_detects_changes()
test_worktree_cleanup_removes_worktree()
test_worktree_cleanup_fails_with_uncommitted()
test_worktree_sync_pulls_from_main()
```

**Acceptance Criteria**:
- [ ] `worktree_create(role, branch)` creates isolated worktree
- [ ] `worktree_list()` returns JSON array
- [ ] `worktree_status(role)` shows clean/dirty state
- [ ] `worktree_cleanup(role)` removes worktree safely
- [ ] `worktree_sync(role)` updates from main
- [ ] Handles edge cases (already exists, uncommitted changes)

**Verification**:
```bash
./tests/workflow/parallel/test_worktree_manager.sh

# Manual test
source .ai/workflow/parallel/worktree_manager.sh
worktree_create "backend" "feature/test"
ls -la .worktrees/
worktree_list | jq '.'
worktree_cleanup "backend"
```

**Done When**: All worktree operations work reliably

---

### BE-009: Implement port_manager.sh

**Priority**: MEDIUM
**Reference**: `20_api_contracts.md` - Port Manager API
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Allocate ports from configured range (3001-3010)
- Track allocations in state file
- Check if port is in use before allocating
- Release ports on cleanup

**TDD Tests**:
```bash
test_port_allocate_returns_free_port()
test_port_allocate_increments()
test_port_in_use_detects_listening()
test_port_release_frees_port()
test_port_list_shows_allocations()
```

**Acceptance Criteria**:
- [ ] Allocates ports sequentially from range
- [ ] Checks port availability before allocating
- [ ] Tracks allocations persistently
- [ ] Releases ports correctly

**Done When**: Port allocation works without conflicts

---

### BE-010: Implement tmux_orchestrator.sh

**Priority**: HIGH
**Reference**: `20_api_contracts.md` - Tmux Orchestrator API
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Create tmux session with multiple panes
- Configure panes for each agent role
- Support sending commands to specific panes
- Handle session lifecycle

**TDD Tests**:
```bash
test_tmux_create_session_creates_session()
test_tmux_create_session_has_correct_panes()
test_tmux_send_command_executes()
test_tmux_get_output_captures_text()
test_tmux_kill_session_cleans_up()
```

**Acceptance Criteria**:
- [ ] `tmux_create_session(name, roles)` creates session
- [ ] Panes created for each role
- [ ] Can send commands to specific panes
- [ ] Can capture pane output
- [ ] Clean session termination

**Done When**: tmux orchestration works end-to-end

---

### BE-011: Implement monitor.sh

**Priority**: MEDIUM
**Reference**: Custom
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Monitor status of all parallel agents
- Detect blockers and errors
- Generate status report
- Support dashboard output

**TDD Tests**:
```bash
test_monitor_detects_all_agents()
test_monitor_reports_blockers()
test_monitor_generates_json_status()
```

**Acceptance Criteria**:
- [ ] Detects all running agents
- [ ] Reports status (active, blocked, completed)
- [ ] JSON output for programmatic use
- [ ] Human-readable dashboard mode

**Done When**: Can monitor parallel agents effectively

---

### BE-012: Create /workflows:parallel Command

**Priority**: HIGH
**Reference**: `20_api_contracts.md`
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Create command markdown file
- Integrate with worktree and tmux managers
- Handle cleanup on exit

**File**: `plugins/multi-agent-workflow/commands/workflows/parallel.md`

**Acceptance Criteria**:
- [ ] Command file created with correct format
- [ ] Launches parallel agents correctly
- [ ] Creates worktrees for each role
- [ ] Allocates ports
- [ ] Starts tmux session

**Done When**: Command launches parallel development successfully

---

## Phase 3: Spec-Driven Development

### BE-013: Create Specs Module Structure

**Priority**: MEDIUM
**Reference**: GitHub Spec Kit
**Methodology**: TDD
**Max Iterations**: 10

**Files to Create**:
```
.ai/workflow/specs/
├── schema/
│   ├── feature_spec.json
│   ├── api_contract.json
│   └── task_spec.json
├── templates/
│   ├── feature_spec.yaml
│   └── api_contract.yaml
├── validator.sh
└── interview.sh
```

**Acceptance Criteria**:
- [ ] Directory structure created
- [ ] JSON schemas valid
- [ ] Templates usable

**Done When**: Spec infrastructure ready

---

### BE-014: Implement JSON Schemas

**Priority**: MEDIUM
**Reference**: `15_data_model.md`
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Create JSON Schema for feature specs
- Create JSON Schema for API contracts
- Create JSON Schema for tasks
- Ensure schemas validate correctly

**Acceptance Criteria**:
- [ ] `feature_spec.json` validates feature specs
- [ ] `api_contract.json` validates API contracts
- [ ] `task_spec.json` validates task definitions
- [ ] Test specs validate against schemas

**Done When**: Schemas complete and tested

---

### BE-015: Implement validator.sh

**Priority**: MEDIUM
**Reference**: `20_api_contracts.md` - Spec Validator API
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Validate YAML specs against JSON Schema
- Check implementation compliance
- Generate validation reports

**Acceptance Criteria**:
- [ ] `spec_validate(path)` validates against schema
- [ ] `spec_check_compliance(spec, impl)` checks implementation
- [ ] Clear error messages for validation failures

**Done When**: Spec validation works reliably

---

### BE-016: Implement interview.sh

**Priority**: MEDIUM
**Reference**: `20_api_contracts.md`
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Interactive question flow
- Capture responses
- Generate YAML spec from responses

**Acceptance Criteria**:
- [ ] Asks structured questions
- [ ] Validates responses
- [ ] Generates valid YAML spec
- [ ] Creates feature workspace

**Done When**: Interview mode produces usable specs

---

## Phase 4: TDD Enforcement

### BE-017: Implement tdd_enforcer.sh

**Priority**: MEDIUM
**Reference**: `20_api_contracts.md` - TDD Enforcer API
**Methodology**: TDD (meta!)
**Max Iterations**: 10

**Requirements**:
- Check test file existence
- Verify test-first commits
- Detect test deletions
- Pre-commit integration

**TDD Tests**:
```bash
test_tdd_check_tests_exist_finds_test_file()
test_tdd_verify_order_detects_test_first()
test_tdd_verify_order_fails_impl_first()
test_tdd_check_deletion_detects_removed_tests()
test_tdd_pre_commit_blocks_violations()
```

**Acceptance Criteria**:
- [ ] Detects missing test files
- [ ] Verifies test-first commit order
- [ ] Blocks test deletion attempts
- [ ] Integrates with pre-commit hook

**Done When**: TDD violations blocked at commit time

---

### BE-018: Create Pre-commit TDD Hook

**Priority**: MEDIUM
**Reference**: Claude Code hooks
**Methodology**: Standard
**Max Iterations**: 5

**File**: `.ai/workflow/hooks/pre_commit_tdd.sh`

**Acceptance Criteria**:
- [ ] Hook blocks commits without tests
- [ ] Hook blocks test deletions
- [ ] Clear error messages
- [ ] Configurable strictness

**Done When**: Pre-commit enforces TDD

---

### BE-019: Implement context_manager.sh

**Priority**: MEDIUM
**Reference**: `20_api_contracts.md` - Context Manager API
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Generate code skeletons
- Check context usage
- Prioritize files by relevance
- Suggest /clear when appropriate

**Acceptance Criteria**:
- [ ] Generates useful code skeletons
- [ ] Estimates token usage
- [ ] Recommends compaction when needed
- [ ] Prioritizes recent/relevant files

**Done When**: Context optimization works

---

### BE-020: Implement compound_tracker.sh

**Priority**: MEDIUM
**Reference**: `20_api_contracts.md` - Compound Tracker API
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Capture learnings on triggers
- Update CLAUDE.md automatically
- Track compound metrics

**Acceptance Criteria**:
- [ ] Captures bug fixes as learnings
- [ ] Updates CLAUDE.md with new patterns
- [ ] Tracks first-pass success rate
- [ ] Generates metrics report

**Done When**: Learning capture automated

---

## Phase 5: Advanced Integration

### BE-021: Create MCP Integration Structure

**Priority**: LOW
**Reference**: MCP protocol
**Methodology**: Standard
**Max Iterations**: 5

**Files**:
```
.ai/workflow/integrations/mcp/
├── github.yaml
├── semgrep.yaml
└── auth.yaml
```

**Acceptance Criteria**:
- [ ] Config files created
- [ ] Auth configuration secure
- [ ] Documentation complete

**Done When**: MCP configs ready

---

### BE-022: Implement mcp_manager.sh

**Priority**: LOW
**Reference**: MCP best practices
**Methodology**: TDD
**Max Iterations**: 10

**Requirements**:
- Load MCP server configs
- Handle OAuth authentication
- Manage server lifecycle

**Acceptance Criteria**:
- [ ] Loads configs from YAML
- [ ] Handles OAuth flow
- [ ] Starts/stops MCP servers
- [ ] Error handling for auth failures

**Done When**: MCP servers manageable via script

---

### BE-023: Create GitHub Actions Templates

**Priority**: LOW
**Reference**: Claude Code GitHub Actions
**Methodology**: Standard
**Max Iterations**: 5

**Files**:
```
.ai/workflow/integrations/github_actions/
├── ai_review.yaml
└── spec_validation.yaml
```

**Acceptance Criteria**:
- [ ] AI review workflow triggers on PR
- [ ] Spec validation runs on push
- [ ] Workflows are reusable

**Done When**: CI/CD templates ready

---

### BE-024: Extend config.yaml

**Priority**: HIGH
**Reference**: `10_architecture.md`
**Methodology**: Standard
**Max Iterations**: 5

**Requirements**:
- Add harness configuration
- Add parallel configuration
- Add enforcement configuration
- Add integration configuration
- Maintain backward compatibility

**Acceptance Criteria**:
- [ ] All new config sections added
- [ ] Existing config unchanged
- [ ] Documentation updated
- [ ] Validation works

**Done When**: Config supports all new features

---

### BE-025: Create Session Start Hook

**Priority**: MEDIUM
**Reference**: Claude Code hooks
**Methodology**: Standard
**Max Iterations**: 5

**File**: `.ai/workflow/hooks/session_start.sh`

**Requirements**:
- Run on Claude Code session start
- Restore context from progress file
- Display helpful summary

**Acceptance Criteria**:
- [ ] Hook triggers on session start
- [ ] Loads progress correctly
- [ ] Shows clear summary
- [ ] Handles missing progress

**Done When**: Sessions restore context automatically

---

## Summary

**Total Tasks**: 25
**Phase 1 (Quick Wins)**: 6 tasks
**Phase 2 (Parallel)**: 6 tasks
**Phase 3 (Specs)**: 4 tasks
**Phase 4 (TDD)**: 4 tasks
**Phase 5 (Advanced)**: 5 tasks

**Critical Path**: BE-001 → BE-002 → BE-003 → BE-004 (harness foundation)

---

**Document Status**: COMPLETE
**Last Updated**: 2026-01-27
**Author**: Planner Agent
