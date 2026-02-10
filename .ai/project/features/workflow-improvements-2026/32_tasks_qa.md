# QA Tasks: Workflow Improvements 2026

> **Feature ID**: workflow-improvements-2026
> **Document**: 32_tasks_qa.md
> **Role**: QA / Reviewer
> **Methodology**: Verification & Validation

---

## Task Overview

| Phase | Tasks | Priority | Type |
|-------|-------|----------|------|
| Phase 1 | QA-001 to QA-004 | HIGH | Unit + Integration |
| Phase 2 | QA-005 to QA-008 | HIGH | Integration + E2E |
| Phase 3 | QA-009 to QA-011 | MEDIUM | Validation |
| Phase 4 | QA-012 to QA-014 | MEDIUM | Compliance |
| Phase 5 | QA-015 to QA-017 | LOW | Integration |

---

## Phase 1: Agent Harness Verification

### QA-001: Test Progress Manager Functionality

**Priority**: HIGH
**Type**: Unit + Integration
**Depends On**: BE-002

**Test Scenarios**:

```gherkin
Feature: Progress Manager

  Scenario: Initialize progress for new feature
    Given no progress file exists
    When I run progress_init "test-feature" "backend"
    Then a progress file should be created
    And it should contain feature "test-feature"
    And it should contain role "backend"

  Scenario: Update task status
    Given a progress file exists
    When I run progress_update "BE-001" "completed"
    Then the task status should be "completed"
    And BE-001 should be in the completed list

  Scenario: Read progress as JSON
    Given a progress file with multiple tasks
    When I run progress_read
    Then I should receive valid JSON
    And it should contain all task statuses

  Scenario: Add notes for next session
    Given a progress file exists
    When I run progress_add_note "Remember to test edge cases"
    Then the note should be in the notes section

  Scenario: Track modified files
    Given a progress file exists
    When I run progress_mark_file "src/User.php"
    Then the file should be in the modified files list
```

**Verification Commands**:
```bash
# Run unit tests
./tests/workflow/harness/test_progress_manager.sh

# Integration test
source .ai/workflow/harness/progress_manager.sh
progress_init "qa-test" "qa"
progress_update "QA-001" "in_progress"
progress_read | jq '.current_task'  # Should output "QA-001"
```

**Acceptance Criteria**:
- [ ] All scenarios pass
- [ ] JSON output is valid
- [ ] File persistence works
- [ ] Error handling for missing files

**Done When**: Progress manager works reliably in all scenarios

---

### QA-002: Test Session Continuity

**Priority**: HIGH
**Type**: Integration
**Depends On**: BE-002, BE-003, BE-004

**Test Scenarios**:

```gherkin
Feature: Session Continuity

  Scenario: First session initialization
    Given I start a new session for feature "test-feature"
    When the initializer runs
    Then it should create a progress file
    And display a welcome message
    And show the feature context

  Scenario: Resume existing session
    Given I have an existing progress file
    When I start a new session
    Then the coder agent should load the progress
    And display previous session summary
    And show notes from previous session
    And suggest the next task

  Scenario: Handle interrupted session
    Given I have a progress file with in_progress task
    When I start a new session after interruption
    Then it should detect the interrupted task
    And ask if I want to continue or restart
```

**Verification Steps**:
1. Create fresh progress file
2. Make changes, add notes
3. Simulate session end (new terminal)
4. Start new session
5. Verify context restored

**Acceptance Criteria**:
- [ ] First session sets up correctly
- [ ] Subsequent sessions restore context
- [ ] Notes carry over
- [ ] Interrupted tasks detected

**Done When**: Sessions can be resumed without data loss

---

### QA-003: Test Trust Evaluator

**Priority**: HIGH
**Type**: Unit
**Depends On**: BE-005, BE-006

**Test Scenarios**:

```gherkin
Feature: Trust Evaluator

  Scenario Outline: Evaluate file trust level
    Given the trust model is configured
    When I evaluate trust for "<file>"
    Then the trust level should be "<level>"

    Examples:
      | file                          | level  |
      | tests/Unit/UserTest.php       | high   |
      | docs/README.md                | high   |
      | src/User.php                  | medium |
      | src/Security/AuthService.php  | low    |
      | src/Payment/Processor.php     | low    |
      | .env.example                  | low    |

  Scenario: Auto-approve for high trust
    Given a high trust file "tests/Unit/Test.php"
    When I check auto-approve
    Then it should return true

  Scenario: No auto-approve for low trust
    Given a low trust file "src/Security/Auth.php"
    When I check auto-approve
    Then it should return false
    And supervision should be "pair_programming"
```

**Verification Commands**:
```bash
./tests/workflow/enforcement/test_trust_evaluator.sh

# Manual verification
source .ai/workflow/enforcement/trust_evaluator.sh
trust_get_level "tests/Unit/UserTest.php"  # high
trust_get_level "src/Security/Auth.php"     # low
trust_can_auto_approve "tests/Test.php"     # true
trust_can_auto_approve "src/Payment.php"    # false
```

**Acceptance Criteria**:
- [ ] All file patterns match correctly
- [ ] Trust levels assigned correctly
- [ ] Auto-approve logic works
- [ ] Supervision requirements accurate

**Done When**: Trust evaluation matches expected levels

---

### QA-004: Test Commands (progress, trust)

**Priority**: MEDIUM
**Type**: Functional
**Depends On**: FE-001, FE-002

**Test Scenarios**:

```gherkin
Feature: Workflow Commands

  Scenario: Show progress command
    Given a progress file exists
    When I run /workflows:progress --show
    Then I should see the current progress
    And it should be formatted clearly

  Scenario: Update progress command
    Given a progress file exists
    When I run /workflows:progress --update BE-001 completed
    Then the task status should change
    And confirmation should be displayed

  Scenario: Check trust command
    Given trust model is configured
    When I run /workflows:trust src/User.php
    Then I should see trust level
    And supervision requirements
    And recommendations
```

**Acceptance Criteria**:
- [ ] Commands execute without errors
- [ ] Output is clear and formatted
- [ ] Updates persist correctly

**Done When**: All commands work as documented

---

## Phase 2: Parallel Agents Verification

### QA-005: Test Worktree Manager

**Priority**: HIGH
**Type**: Unit + Integration
**Depends On**: BE-008

**Test Scenarios**:

```gherkin
Feature: Worktree Manager

  Scenario: Create worktree for role
    Given no worktree exists for "backend"
    When I run worktree_create "backend" "feature/auth"
    Then a worktree should be created at ".worktrees/backend"
    And it should be on branch "feature/auth"
    And it should be a valid git worktree

  Scenario: List worktrees
    Given worktrees exist for backend and frontend
    When I run worktree_list
    Then I should see JSON with both worktrees
    And each should have path, branch, and status

  Scenario: Cleanup worktree
    Given a clean worktree exists
    When I run worktree_cleanup "backend"
    Then the worktree directory should be removed
    And the git worktree should be unregistered

  Scenario: Cleanup fails with uncommitted changes
    Given a worktree with uncommitted changes
    When I run worktree_cleanup "backend"
    Then it should fail with error message
    And the worktree should remain intact

  Scenario: Sync worktree with main
    Given a worktree behind main branch
    When I run worktree_sync "backend"
    Then the worktree should merge main
    And be up to date
```

**Verification Commands**:
```bash
./tests/workflow/parallel/test_worktree_manager.sh

# Manual test
source .ai/workflow/parallel/worktree_manager.sh
worktree_create "qa-test" "feature/qa-test"
ls -la .worktrees/
worktree_list | jq '.'
worktree_cleanup "qa-test"
```

**Acceptance Criteria**:
- [ ] Worktrees created correctly
- [ ] Branch checkout works
- [ ] Listing returns valid JSON
- [ ] Cleanup handles dirty state
- [ ] Sync merges correctly

**Done When**: Worktree operations reliable

---

### QA-006: Test Port Manager

**Priority**: MEDIUM
**Type**: Unit
**Depends On**: BE-009

**Test Scenarios**:

```gherkin
Feature: Port Manager

  Scenario: Allocate ports sequentially
    Given no ports are allocated
    When I allocate for "backend"
    Then I should get port 3001
    When I allocate for "frontend"
    Then I should get port 3002

  Scenario: Detect port in use
    Given port 3001 is in use by another process
    When I allocate for "backend"
    Then I should get port 3002 instead

  Scenario: Release port
    Given port 3001 is allocated to "backend"
    When I release "backend"
    Then port 3001 should be available
```

**Acceptance Criteria**:
- [ ] Ports allocated sequentially
- [ ] In-use ports skipped
- [ ] Release works correctly

**Done When**: Port allocation reliable

---

### QA-007: Test Parallel Workflow E2E

**Priority**: HIGH
**Type**: E2E
**Depends On**: BE-007 to BE-012

**Test Scenario**:

```gherkin
Feature: Parallel Development E2E

  Scenario: Launch and monitor parallel agents
    Given a planned feature "qa-e2e-test"
    When I run /workflows:parallel qa-e2e-test --roles=backend,frontend
    Then worktrees should be created for each role
    And ports should be allocated
    And tmux session should be running
    When I check status
    Then all agents should show as active
    When I cleanup
    Then worktrees should be removed
    And ports should be released
    And tmux session should be terminated
```

**Verification Steps**:
1. Plan a test feature
2. Launch parallel with backend,frontend
3. Verify worktrees exist
4. Verify tmux session running
5. Check status command
6. Cleanup
7. Verify everything cleaned up

**Acceptance Criteria**:
- [ ] Full parallel workflow executes
- [ ] No conflicts between agents
- [ ] Cleanup is complete
- [ ] No orphaned resources

**Done When**: Parallel development works end-to-end

---

### QA-008: Test tmux Orchestrator

**Priority**: MEDIUM
**Type**: Integration
**Depends On**: BE-010

**Test Scenarios**:

```gherkin
Feature: tmux Orchestrator

  Scenario: Create session with panes
    When I create session "qa-test" with roles "backend,frontend"
    Then tmux session "qa-test" should exist
    And it should have 2 panes
    And each pane should be named

  Scenario: Send command to pane
    Given tmux session exists
    When I send "echo hello" to "backend" pane
    Then "hello" should appear in pane output

  Scenario: Kill session
    Given tmux session exists
    When I kill session
    Then session should not exist
```

**Acceptance Criteria**:
- [ ] Sessions created correctly
- [ ] Panes match roles
- [ ] Commands execute in correct panes
- [ ] Clean termination

**Done When**: tmux orchestration reliable

---

## Phase 3: Spec-Driven Verification

### QA-009: Test Spec Validator

**Priority**: MEDIUM
**Type**: Unit
**Depends On**: BE-015

**Test Scenarios**:

```gherkin
Feature: Spec Validator

  Scenario: Validate correct spec
    Given a valid feature spec YAML
    When I run spec_validate
    Then validation should pass
    And no errors should be reported

  Scenario: Detect missing required fields
    Given a spec missing "feature.id"
    When I run spec_validate
    Then validation should fail
    And error should mention "feature.id"

  Scenario: Detect invalid values
    Given a spec with priority "invalid"
    When I run spec_validate
    Then validation should fail
    And error should mention allowed values
```

**Acceptance Criteria**:
- [ ] Valid specs pass
- [ ] Missing fields detected
- [ ] Invalid values detected
- [ ] Clear error messages

**Done When**: Spec validation works correctly

---

### QA-010: Test Interview Mode

**Priority**: MEDIUM
**Type**: Functional
**Depends On**: BE-016

**Test Scenario**:

```gherkin
Feature: Interview Mode

  Scenario: Complete interview generates spec
    When I start interview for "qa-interview-test"
    And I answer all questions
    Then a feature workspace should be created
    And spec.yaml should exist
    And spec should contain my answers
    And spec should be valid against schema
```

**Acceptance Criteria**:
- [ ] Interview flow completes
- [ ] Answers captured correctly
- [ ] Valid YAML generated
- [ ] Workspace created

**Done When**: Interview produces usable specs

---

### QA-011: Test Spec Compliance Check

**Priority**: MEDIUM
**Type**: Integration
**Depends On**: BE-015

**Test Scenarios**:

```gherkin
Feature: Spec Compliance

  Scenario: Detect incomplete implementation
    Given a spec with 5 requirements
    And implementation covers only 3
    When I run spec_check_compliance
    Then it should report 2 missing requirements

  Scenario: Detect complete implementation
    Given a spec with 3 requirements
    And implementation covers all 3
    When I run spec_check_compliance
    Then it should report all complete
```

**Acceptance Criteria**:
- [ ] Missing requirements detected
- [ ] Complete implementation recognized
- [ ] Clear compliance report

**Done When**: Compliance checking works

---

## Phase 4: TDD Enforcement Verification

### QA-012: Test TDD Enforcer

**Priority**: HIGH
**Type**: Unit
**Depends On**: BE-017

**Test Scenarios**:

```gherkin
Feature: TDD Enforcer

  Scenario: Detect missing test file
    Given source file "src/User.php" exists
    And no test file exists
    When I run tdd_check_tests_exist "src/User.php"
    Then it should report no tests found

  Scenario: Verify test-first commit order
    Given test was committed before implementation
    When I run tdd_verify_order "src/User.php"
    Then it should report compliant

  Scenario: Detect implementation-first violation
    Given implementation was committed before test
    When I run tdd_verify_order "src/User.php"
    Then it should report non-compliant

  Scenario: Detect test deletion
    Given a commit that deletes test file
    When I run tdd_check_deletion
    Then it should detect the deletion
```

**Acceptance Criteria**:
- [ ] Missing tests detected
- [ ] Commit order verified
- [ ] Test deletion blocked
- [ ] Clear violation messages

**Done When**: TDD enforcement reliable

---

### QA-013: Test Pre-commit Hook

**Priority**: HIGH
**Type**: Integration
**Depends On**: BE-018

**Test Scenarios**:

```gherkin
Feature: Pre-commit TDD Hook

  Scenario: Block commit without tests
    Given I have new source file without test
    When I try to commit
    Then commit should be blocked
    And error should explain TDD requirement

  Scenario: Allow commit with tests
    Given I have source file with test
    When I try to commit
    Then commit should succeed

  Scenario: Block test deletion
    Given I try to delete a test file
    When I try to commit
    Then commit should be blocked
    And error should explain test deletion not allowed
```

**Acceptance Criteria**:
- [ ] Commits blocked appropriately
- [ ] Tests required for new code
- [ ] Test deletion blocked
- [ ] Clear error messages
- [ ] Bypass available when needed

**Done When**: Hook enforces TDD at commit time

---

### QA-014: Test Context Manager

**Priority**: MEDIUM
**Type**: Unit
**Depends On**: BE-019

**Test Scenarios**:

```gherkin
Feature: Context Manager

  Scenario: Generate code skeleton
    Given a complex source file
    When I run context_generate_skeleton
    Then I should get a condensed representation
    And it should include function signatures
    And it should not include implementations

  Scenario: Detect high context usage
    Given context is at 90% capacity
    When I check context usage
    Then it should recommend compaction
```

**Acceptance Criteria**:
- [ ] Skeletons are useful
- [ ] Token estimation works
- [ ] Compaction recommended appropriately

**Done When**: Context optimization works

---

## Phase 5: Integration Verification

### QA-015: Test MCP Integration

**Priority**: LOW
**Type**: Integration
**Depends On**: BE-021, BE-022

**Test Scenarios**:

```gherkin
Feature: MCP Integration

  Scenario: Connect to GitHub MCP
    Given GitHub credentials are configured
    When I connect to github MCP server
    Then connection should succeed
    And I should be able to search code

  Scenario: Run Semgrep scan
    Given Semgrep is configured
    When I scan a file
    Then I should get findings (if any)
```

**Acceptance Criteria**:
- [ ] MCP servers connect
- [ ] OAuth flow works
- [ ] Tools accessible

**Done When**: MCP integration functional

---

### QA-016: Test Compound Learning

**Priority**: LOW
**Type**: Integration
**Depends On**: BE-020

**Test Scenarios**:

```gherkin
Feature: Compound Learning

  Scenario: Capture bug fix learning
    Given I fix a bug
    When I run compound_capture "bug" "description" "solution" "rule"
    Then learning should be recorded
    And CLAUDE.md should be updated

  Scenario: Track metrics
    Given multiple sessions have completed
    When I get compound metrics
    Then I should see first-pass rate
    And context reuse rate
    And regression rate
```

**Acceptance Criteria**:
- [ ] Learnings captured
- [ ] CLAUDE.md updated
- [ ] Metrics tracked

**Done When**: Compound learning works

---

### QA-017: Full Workflow E2E Test

**Priority**: HIGH
**Type**: E2E
**Depends On**: All previous tasks

**Test Scenario**:

```gherkin
Feature: Full Workflow E2E

  Scenario: Complete feature development cycle
    # Phase 1: Planning
    Given I create a spec via interview
    And the spec is validated

    # Phase 2: Implementation
    When I launch parallel agents
    And backend implements their tasks
    And frontend implements their tasks

    # Phase 3: Review
    And QA reviews the implementation
    And all tests pass
    And TDD compliance is verified
    And spec compliance is verified

    # Phase 4: Completion
    And learnings are captured
    And metrics are updated

    Then the feature should be marked complete
    And all agents should be cleaned up
    And documentation should be updated
```

**Verification Steps**:
1. Create spec via interview
2. Launch parallel agents
3. Simulate backend work
4. Simulate frontend work
5. Run QA review
6. Verify all checks pass
7. Mark complete
8. Verify cleanup

**Acceptance Criteria**:
- [ ] Full cycle completes
- [ ] All enforcement passes
- [ ] Learnings captured
- [ ] Clean state at end

**Done When**: Complete workflow validated

---

## Test Infrastructure

### Test Framework

```
tests/
├── workflow/
│   ├── harness/
│   │   ├── test_progress_manager.sh
│   │   ├── test_initializer.sh
│   │   └── test_coder.sh
│   ├── parallel/
│   │   ├── test_worktree_manager.sh
│   │   ├── test_port_manager.sh
│   │   └── test_tmux_orchestrator.sh
│   ├── specs/
│   │   ├── test_validator.sh
│   │   └── test_interview.sh
│   ├── enforcement/
│   │   ├── test_trust_evaluator.sh
│   │   ├── test_tdd_enforcer.sh
│   │   └── test_context_manager.sh
│   └── e2e/
│       ├── test_parallel_workflow.sh
│       └── test_full_cycle.sh
└── run_all_tests.sh
```

### Test Runner

```bash
#!/usr/bin/env bash
# tests/run_all_tests.sh

set -euo pipefail

echo "Running all workflow tests..."

for test_file in tests/workflow/**/*.sh; do
    echo "Running: $test_file"
    bash "$test_file"
done

echo "All tests passed!"
```

---

## Summary

**Total Tasks**: 17
**Phase 1 (Harness)**: 4 tasks
**Phase 2 (Parallel)**: 4 tasks
**Phase 3 (Specs)**: 3 tasks
**Phase 4 (TDD)**: 3 tasks
**Phase 5 (Integration)**: 3 tasks

**Critical Tests**:
- QA-002: Session Continuity
- QA-007: Parallel Workflow E2E
- QA-013: Pre-commit Hook
- QA-017: Full Workflow E2E

---

**Document Status**: COMPLETE
**Last Updated**: 2026-01-27
**Author**: Planner Agent
