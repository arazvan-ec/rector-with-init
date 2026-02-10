# Data Model: Workflow Improvements 2026

> **Feature ID**: workflow-improvements-2026
> **Document**: 15_data_model.md
> **Status**: DRAFT

---

## 1. Core Data Structures

### 1.1 Session Progress (claude-progress.txt)

```markdown
# Claude Progress File
# Auto-generated - Do not edit manually unless necessary

## Session Info
session_id: abc123
started: 2026-01-27T10:00:00Z
last_updated: 2026-01-27T14:30:00Z
feature: workflow-improvements-2026
role: backend

## Current State
status: IN_PROGRESS
current_task: "Implement worktree manager"
task_id: BE-003

## Completed This Session
- [x] BE-001: Created harness module structure
- [x] BE-002: Implemented progress_manager.sh

## In Progress
- [ ] BE-003: Implement worktree_manager.sh (60% complete)
  - [x] Created base script
  - [x] Implemented create_worktree()
  - [ ] Implement cleanup_worktree()
  - [ ] Add error handling

## Blockers
None

## Notes for Next Session
- cleanup_worktree() needs to check for uncommitted changes
- Consider adding force flag for emergencies
- Test with multiple concurrent worktrees

## Files Modified This Session
- .ai/workflow/harness/progress_manager.sh (created)
- .ai/workflow/parallel/worktree_manager.sh (in progress)

## Key Decisions Made
- Using git worktree linked mode for efficiency
- Worktrees stored in .worktrees/ directory

## Questions for Human
None currently
```

### 1.2 Agent File Format (.af)

```json
{
  "version": "1.0",
  "agent": {
    "id": "backend-001",
    "role": "backend",
    "created": "2026-01-27T10:00:00Z"
  },
  "memory": {
    "short_term": {
      "current_task": "BE-003",
      "recent_files": [
        ".ai/workflow/parallel/worktree_manager.sh"
      ],
      "recent_decisions": [
        "Use linked worktrees for efficiency"
      ]
    },
    "long_term": {
      "project_patterns": [
        "Scripts use bash with strict mode",
        "Error handling via set -e"
      ],
      "learned_preferences": [
        "Prefer functions over inline code",
        "Always add usage() function"
      ]
    }
  },
  "context": {
    "feature": "workflow-improvements-2026",
    "workspace": ".ai/project/features/workflow-improvements-2026",
    "worktree": ".worktrees/backend"
  },
  "state": {
    "status": "IN_PROGRESS",
    "iteration_count": 3,
    "max_iterations": 10
  },
  "tools": {
    "allowed": ["Bash", "Read", "Write", "Edit", "Grep", "Glob"],
    "mcp_servers": ["github", "semgrep"]
  }
}
```

### 1.3 Feature Spec (YAML Format)

```yaml
# .ai/project/features/{feature}/spec.yaml
version: "1.0"
feature:
  id: "workflow-improvements-2026"
  name: "Workflow Improvements 2026"
  priority: "high"
  status: "planning"

metadata:
  created: "2026-01-27"
  author: "planner"
  estimated_effort: "10-12 weeks"

objective:
  summary: "Enhance multi-agent workflow with 2026 best practices"
  business_value: |
    Increase developer productivity by 30%
    Reduce agent errors by 50%
    Enable parallel development

requirements:
  functional:
    - id: FR-101
      title: "Session continuity"
      description: "Agents can continue work across sessions"
      priority: high
      acceptance_criteria:
        - "claude-progress.txt created on session start"
        - "Progress restored on session resume"
        - "No data loss between sessions"
      test_coverage: required

    - id: FR-201
      title: "Parallel execution"
      description: "Multiple agents work in isolation"
      priority: high
      acceptance_criteria:
        - "Each agent has own worktree"
        - "No filesystem conflicts"
        - "Independent dev servers"
      test_coverage: required

  non_functional:
    - id: NFR-001
      type: performance
      requirement: "Worktree creation < 5 seconds"

    - id: NFR-002
      type: reliability
      requirement: "Session recovery > 95%"

contracts:
  internal:
    - name: "Progress Manager API"
      type: "bash"
      interface: |
        progress_init(feature, role)    -> void
        progress_update(task, status)   -> void
        progress_read()                 -> ProgressData
        progress_save()                 -> void

    - name: "Worktree Manager API"
      type: "bash"
      interface: |
        worktree_create(role, branch)   -> path
        worktree_list()                 -> WorktreeInfo[]
        worktree_cleanup(role)          -> void
        worktree_status(role)           -> Status

tasks:
  backend:
    - id: BE-001
      title: "Create harness module structure"
      reference: "Existing scripts in .ai/workflow/scripts/"
      methodology: TDD
      max_iterations: 10
      acceptance_criteria:
        - "Directory structure created"
        - "Base scripts have correct permissions"
      done_when: "Structure exists, scripts executable"

    - id: BE-002
      title: "Implement progress_manager.sh"
      reference: "FR-101"
      methodology: TDD
      max_iterations: 10
      acceptance_criteria:
        - "All Progress Manager API functions work"
        - "Tests pass with >80% coverage"
      done_when: "API complete, tests pass"

  qa:
    - id: QA-001
      title: "Test session continuity"
      type: "integration"
      acceptance_criteria:
        - "Start session, make progress"
        - "End session"
        - "Start new session"
        - "Progress restored correctly"

dependencies:
  external:
    - name: "git"
      version: ">=2.30"
      reason: "Worktree support"

    - name: "tmux"
      version: ">=3.0"
      reason: "Session management"

  internal:
    - feature: null
      reason: "No dependencies, foundational feature"

risks:
  - description: "Breaking existing workflows"
    probability: "medium"
    impact: "high"
    mitigation: "Comprehensive testing, backward compat"

timeline:
  phases:
    - name: "Phase 1: Quick Wins"
      duration: "1-2 weeks"
      deliverables:
        - "claude-progress.txt"
        - "Custom commands"
        - "Trust model"

    - name: "Phase 2: Parallel Agents"
      duration: "2-3 weeks"
      deliverables:
        - "Git worktrees"
        - "tmux integration"
        - "Monitoring"
```

---

## 2. Configuration Schemas

### 2.1 Feature Spec JSON Schema

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://workflow.local/schemas/feature_spec.json",
  "title": "Feature Specification",
  "type": "object",
  "required": ["version", "feature", "objective", "requirements"],
  "properties": {
    "version": {
      "type": "string",
      "pattern": "^\\d+\\.\\d+$"
    },
    "feature": {
      "type": "object",
      "required": ["id", "name", "priority", "status"],
      "properties": {
        "id": {
          "type": "string",
          "pattern": "^[a-z0-9-]+$"
        },
        "name": {
          "type": "string",
          "minLength": 3,
          "maxLength": 100
        },
        "priority": {
          "enum": ["critical", "high", "medium", "low"]
        },
        "status": {
          "enum": ["planning", "in_progress", "review", "completed", "blocked"]
        }
      }
    },
    "requirements": {
      "type": "object",
      "properties": {
        "functional": {
          "type": "array",
          "items": {
            "$ref": "#/$defs/requirement"
          }
        },
        "non_functional": {
          "type": "array",
          "items": {
            "$ref": "#/$defs/nfr"
          }
        }
      }
    },
    "contracts": {
      "type": "object",
      "properties": {
        "internal": {
          "type": "array",
          "items": {
            "$ref": "#/$defs/internal_contract"
          }
        },
        "api": {
          "type": "array",
          "items": {
            "$ref": "#/$defs/api_contract"
          }
        }
      }
    },
    "tasks": {
      "type": "object",
      "additionalProperties": {
        "type": "array",
        "items": {
          "$ref": "#/$defs/task"
        }
      }
    }
  },
  "$defs": {
    "requirement": {
      "type": "object",
      "required": ["id", "title", "description", "priority", "acceptance_criteria"],
      "properties": {
        "id": {"type": "string"},
        "title": {"type": "string"},
        "description": {"type": "string"},
        "priority": {"enum": ["critical", "high", "medium", "low"]},
        "acceptance_criteria": {
          "type": "array",
          "items": {"type": "string"},
          "minItems": 1
        },
        "test_coverage": {"enum": ["required", "recommended", "optional"]}
      }
    },
    "nfr": {
      "type": "object",
      "required": ["id", "type", "requirement"],
      "properties": {
        "id": {"type": "string"},
        "type": {"enum": ["performance", "reliability", "security", "usability"]},
        "requirement": {"type": "string"}
      }
    },
    "task": {
      "type": "object",
      "required": ["id", "title", "acceptance_criteria", "done_when"],
      "properties": {
        "id": {"type": "string"},
        "title": {"type": "string"},
        "reference": {"type": "string"},
        "methodology": {"enum": ["TDD", "BDD", "standard"]},
        "max_iterations": {"type": "integer", "minimum": 1, "maximum": 20},
        "acceptance_criteria": {
          "type": "array",
          "items": {"type": "string"}
        },
        "done_when": {"type": "string"}
      }
    },
    "internal_contract": {
      "type": "object",
      "required": ["name", "type", "interface"],
      "properties": {
        "name": {"type": "string"},
        "type": {"enum": ["bash", "python", "node"]},
        "interface": {"type": "string"}
      }
    },
    "api_contract": {
      "type": "object",
      "required": ["endpoint", "method", "request", "responses"],
      "properties": {
        "endpoint": {"type": "string"},
        "method": {"enum": ["GET", "POST", "PUT", "PATCH", "DELETE"]},
        "request": {"type": "object"},
        "responses": {"type": "object"}
      }
    }
  }
}
```

### 2.2 Trust Model Schema

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://workflow.local/schemas/trust_model.json",
  "title": "Trust Model Configuration",
  "type": "object",
  "required": ["version", "trust_levels"],
  "properties": {
    "version": {"type": "string"},
    "trust_levels": {
      "type": "object",
      "properties": {
        "high": {"$ref": "#/$defs/trust_level"},
        "medium": {"$ref": "#/$defs/trust_level"},
        "low": {"$ref": "#/$defs/trust_level"}
      },
      "required": ["high", "medium", "low"]
    }
  },
  "$defs": {
    "trust_level": {
      "type": "object",
      "required": ["description", "auto_approve", "supervision", "contexts"],
      "properties": {
        "description": {"type": "string"},
        "auto_approve": {"type": "boolean"},
        "supervision": {
          "enum": ["minimal", "code_review_required", "pair_programming"]
        },
        "escalation": {"type": "boolean"},
        "contexts": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "pattern": {"type": "string"},
              "task_type": {"type": "string"}
            }
          }
        }
      }
    }
  }
}
```

---

## 3. State Transitions

### 3.1 Feature Status

```
PLANNING ──────────> IN_PROGRESS ──────────> REVIEW
    │                     │                    │
    │                     │                    │
    v                     v                    v
 BLOCKED <────────── BLOCKED <────────── BLOCKED
    │                     │                    │
    │                     │                    │
    v                     v                    v
PLANNING ──────────> IN_PROGRESS ──────────> REVIEW ──────────> COMPLETED
```

### 3.2 Agent Session Status

```
INITIALIZING ──────> ACTIVE ──────> PAUSED ──────> RESUMED ──────> COMPLETED
      │                │               │              │               │
      v                v               v              v               │
   FAILED           BLOCKED        EXPIRED        ACTIVE ────────────┘
```

### 3.3 Task Status

```
PENDING ──────> IN_PROGRESS ──────> COMPLETED
    │               │                   │
    │               v                   │
    │           BLOCKED ────────────────┘
    │               │
    v               v
 SKIPPED        FAILED
```

---

## 4. File System Structure

### 4.1 Complete Directory Structure

```
.ai/
├── project/
│   ├── config.yaml                    # Main config (extended)
│   ├── context.md                     # Project context
│   │
│   ├── sessions/                      # NEW: Session management
│   │   ├── claude-progress.txt        # Current progress
│   │   ├── agents/                    # Agent state files
│   │   │   ├── backend.af
│   │   │   ├── frontend.af
│   │   │   └── qa.af
│   │   └── history/                   # Past sessions
│   │       └── 2026-01-27_session1.log
│   │
│   ├── features/                      # Feature workspaces
│   │   └── {feature-id}/
│   │       ├── spec.yaml              # NEW: YAML spec
│   │       ├── 50_state.md            # State (backward compat)
│   │       ├── 30_tasks.md            # Tasks (backward compat)
│   │       └── DECISIONS.md           # Decisions
│   │
│   └── learnings/                     # NEW: Compound learning
│       ├── bugs.md                    # Bug patterns
│       ├── patterns.md                # Code patterns
│       └── anti_patterns.md           # What to avoid
│
└── workflow/
    ├── harness/                       # NEW: Agent harness
    │   ├── initializer.sh
    │   ├── coder.sh
    │   ├── progress_manager.sh
    │   └── state_serializer.sh
    │
    ├── parallel/                      # NEW: Parallel execution
    │   ├── worktree_manager.sh
    │   ├── tmux_orchestrator.sh
    │   ├── port_manager.sh
    │   └── monitor.sh
    │
    ├── specs/                         # NEW: Spec system
    │   ├── schema/
    │   │   ├── feature_spec.json
    │   │   ├── api_contract.json
    │   │   └── task_spec.json
    │   ├── templates/
    │   │   ├── feature_spec.yaml
    │   │   └── api_contract.yaml
    │   ├── validator.sh
    │   └── interview.sh
    │
    ├── enforcement/                   # NEW: Enforcement
    │   ├── tdd_enforcer.sh
    │   ├── trust_evaluator.sh
    │   ├── context_manager.sh
    │   └── compound_tracker.sh
    │
    ├── integrations/                  # NEW: Integrations
    │   ├── mcp/
    │   │   ├── github.yaml
    │   │   ├── semgrep.yaml
    │   │   └── auth.yaml
    │   └── github_actions/
    │       ├── ai_review.yaml
    │       └── spec_validation.yaml
    │
    ├── hooks/                         # NEW: Claude Code hooks
    │   ├── pre_commit_tdd.sh
    │   ├── post_edit_layer.sh
    │   └── session_start.sh
    │
    ├── scripts/                       # EXISTING (extended)
    │   ├── workflow.sh
    │   ├── git_sync.sh
    │   └── ...
    │
    ├── tools/                         # EXISTING (extended)
    │   └── ...
    │
    └── trust_model.yaml               # NEW: Trust configuration
```

---

## 5. Metrics Data Model

### 5.1 Compound Metrics

```yaml
# .ai/project/metrics/compound.yaml
version: "1.0"
period:
  start: "2026-01-01"
  end: "2026-01-31"

metrics:
  first_pass_success:
    description: "Tasks completed without iteration"
    baseline: 40
    current: 55
    target: 70
    unit: "percent"
    trend: "improving"

  context_reuse:
    description: "Context reused between features"
    baseline: 20
    current: 35
    target: 60
    unit: "percent"
    trend: "improving"

  regression_rate:
    description: "Bugs from new changes"
    baseline: 15
    current: 10
    target: 5
    unit: "percent"
    trend: "improving"

  session_recovery:
    description: "Successful session continuations"
    baseline: 70
    current: 85
    target: 95
    unit: "percent"
    trend: "improving"

history:
  - date: "2026-01-07"
    first_pass_success: 42
    context_reuse: 22
    regression_rate: 14

  - date: "2026-01-14"
    first_pass_success: 48
    context_reuse: 28
    regression_rate: 12
```

---

**Document Status**: COMPLETE
**Last Updated**: 2026-01-27
**Author**: Planner Agent
