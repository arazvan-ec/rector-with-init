# Architecture Design: Workflow Improvements 2026

> **Feature ID**: workflow-improvements-2026
> **Document**: 10_architecture.md
> **Status**: DRAFT

---

## 1. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                    WORKFLOW IMPROVEMENTS 2026                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │   LAYER 1    │  │   LAYER 2    │  │   LAYER 3    │              │
│  │ Agent Harness│  │  Parallel    │  │  Spec-Driven │              │
│  │              │  │  Execution   │  │  Development │              │
│  │ - progress   │  │ - worktrees  │  │ - YAML specs │              │
│  │ - init agent │  │ - tmux       │  │ - interview  │              │
│  │ - state file │  │ - ports      │  │ - validation │              │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘              │
│         │                 │                 │                       │
│         └─────────────────┼─────────────────┘                       │
│                           │                                         │
│                    ┌──────┴───────┐                                 │
│                    │  LAYER 4     │                                 │
│                    │  Core Engine │                                 │
│                    │              │                                 │
│                    │ - TDD enforce│                                 │
│                    │ - Trust model│                                 │
│                    │ - Context mgr│                                 │
│                    │ - Compound   │                                 │
│                    └──────┬───────┘                                 │
│                           │                                         │
│                    ┌──────┴───────┐                                 │
│                    │  LAYER 5     │                                 │
│                    │ Integration  │                                 │
│                    │              │                                 │
│                    │ - MCP servers│                                 │
│                    │ - GitHub Act │                                 │
│                    │ - Hooks      │                                 │
│                    └──────────────┘                                 │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. Component Architecture

### 2.1 Agent Harness System

```
.ai/
├── workflow/
│   ├── harness/                      # NEW: Agent harness components
│   │   ├── initializer.sh            # First session setup
│   │   ├── coder.sh                  # Subsequent session handler
│   │   ├── progress_manager.sh       # Manage claude-progress.txt
│   │   └── state_serializer.sh       # Agent File (.af) support
│   │
│   └── scripts/
│       └── workflow.sh               # EXTEND: Add harness integration
│
└── project/
    └── sessions/                     # NEW: Session state storage
        ├── claude-progress.txt       # Cross-session progress
        └── agents/                   # Agent state files (.af)
```

### 2.2 Parallel Execution System

```
.ai/
└── workflow/
    ├── parallel/                     # NEW: Parallel execution
    │   ├── worktree_manager.sh       # Git worktree automation
    │   ├── tmux_orchestrator.sh      # tmux session management
    │   ├── port_manager.sh           # Dev server port allocation
    │   └── monitor.sh                # Agent status monitoring
    │
    └── scripts/
        └── tilix_start.sh            # DEPRECATE: Replace with tmux
```

### 2.3 Spec-Driven System

```
.ai/
├── workflow/
│   ├── specs/                        # NEW: Spec management
│   │   ├── schema/                   # JSON Schema definitions
│   │   │   ├── feature_spec.json     # Feature spec schema
│   │   │   ├── api_contract.json     # API contract schema
│   │   │   └── task_spec.json        # Task spec schema
│   │   │
│   │   ├── templates/                # Spec templates
│   │   │   ├── feature_spec.yaml     # Feature template
│   │   │   └── api_contract.yaml     # API template
│   │   │
│   │   ├── validator.sh              # Spec validation
│   │   └── interview.sh              # Interview mode
│   │
│   └── tools/
│       └── spec_generator.sh         # Generate specs from interview
│
└── project/
    └── features/
        └── {feature}/
            └── spec.yaml             # YAML spec (new format)
```

### 2.4 Enforcement System

```
.ai/
└── workflow/
    ├── enforcement/                  # NEW: Rule enforcement
    │   ├── tdd_enforcer.sh           # TDD compliance checker
    │   ├── trust_evaluator.sh        # Trust level assessment
    │   ├── context_manager.sh        # Context window optimization
    │   └── compound_tracker.sh       # Learning capture
    │
    └── hooks/                        # Claude Code hooks
        ├── pre_commit_tdd.sh         # TDD verification
        ├── post_edit_layer.sh        # DDD layer check
        └── session_start.sh          # Context injection
```

### 2.5 Integration System

```
.ai/
└── workflow/
    ├── integrations/                 # NEW: External integrations
    │   ├── mcp/                      # MCP server configs
    │   │   ├── github.yaml           # GitHub MCP config
    │   │   ├── semgrep.yaml          # Semgrep MCP config
    │   │   └── auth.yaml             # OAuth config
    │   │
    │   └── github_actions/           # CI/CD templates
    │       ├── ai_review.yaml        # AI-assisted review
    │       └── spec_validation.yaml  # Spec validation workflow
    │
    └── scripts/
        └── mcp_manager.sh            # MCP server management
```

---

## 3. Data Flow Architecture

### 3.1 Session Continuity Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Session   │     │  Progress   │     │   Session   │
│     N       │────>│    File     │────>│    N+1      │
└─────────────┘     └─────────────┘     └─────────────┘
      │                   │                   │
      │                   │                   │
      v                   v                   v
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Git Commits │     │ State File  │     │ Context     │
│ (History)   │     │ (50_state)  │     │ (Restored)  │
└─────────────┘     └─────────────┘     └─────────────┘
```

### 3.2 Parallel Agent Flow

```
                    ┌─────────────────┐
                    │  Orchestrator   │
                    │  (tmux master)  │
                    └────────┬────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
         v                   v                   v
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Worktree   │     │  Worktree   │     │  Worktree   │
│  Backend    │     │  Frontend   │     │    QA       │
│  :3001      │     │  :3002      │     │  :3003      │
└──────┬──────┘     └──────┬──────┘     └──────┬──────┘
       │                   │                   │
       v                   v                   v
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Agent State │     │ Agent State │     │ Agent State │
│ backend.af  │     │ frontend.af │     │    qa.af    │
└─────────────┘     └─────────────┘     └─────────────┘
```

### 3.3 Spec-Driven Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Interview  │────>│    YAML     │────>│ Validation  │
│    Mode     │     │    Spec     │     │   Engine    │
└─────────────┘     └──────┬──────┘     └──────┬──────┘
                           │                   │
                           v                   v
                    ┌─────────────┐     ┌─────────────┐
                    │   Tasks     │     │   Tests     │
                    │ Generation  │     │ Generation  │
                    └─────────────┘     └─────────────┘
```

---

## 4. Configuration Architecture

### 4.1 Main Configuration

```yaml
# .ai/project/config.yaml (EXTENDED)
version: "2.1"

# Existing config...

# NEW: Agent Harness Configuration
harness:
  progress_file: ".ai/project/sessions/claude-progress.txt"
  state_format: "af"  # Agent File format
  auto_restore: true
  max_history_entries: 50

# NEW: Parallel Execution Configuration
parallel:
  manager: "tmux"  # tmux | tilix (deprecated)
  worktree_base: ".worktrees"
  port_range:
    start: 3001
    end: 3010
  auto_cleanup: true

# NEW: Spec-Driven Configuration
specs:
  format: "yaml"
  schema_path: ".ai/workflow/specs/schema"
  templates_path: ".ai/workflow/specs/templates"
  validation: "strict"  # strict | warn | off

# NEW: Enforcement Configuration
enforcement:
  tdd:
    enabled: true
    require_test_first_commits: true
    prevent_test_deletion: true
    min_coverage: 80

  trust:
    default_level: "medium"
    contexts:
      high_trust:
        - "boilerplate"
        - "documentation"
        - "unit_tests"
      low_trust:
        - "security"
        - "authentication"
        - "payment"

  context:
    compaction_threshold: 100000
    skeleton_mode: true
    auto_clear_reminder: true

# NEW: Integration Configuration
integrations:
  mcp:
    enabled: true
    servers:
      - name: "github"
        config: ".ai/workflow/integrations/mcp/github.yaml"
      - name: "semgrep"
        config: ".ai/workflow/integrations/mcp/semgrep.yaml"
    auth:
      type: "oauth2"
      config: ".ai/workflow/integrations/mcp/auth.yaml"

  github_actions:
    enabled: true
    templates_path: ".ai/workflow/integrations/github_actions"

# NEW: Compound Learning Configuration
compound:
  auto_capture: true
  triggers:
    - "bug_fixed"
    - "test_failure_resolved"
    - "code_review_feedback"
  destinations:
    - "CLAUDE.md"
    - "rules/project_specific.md"
  metrics:
    track_first_pass_rate: true
    track_regression_rate: true
```

### 4.2 Trust Model Configuration

```yaml
# .ai/workflow/trust_model.yaml (NEW)
version: "1.0"

trust_levels:
  high:
    description: "AI can work autonomously"
    auto_approve: true
    supervision: "minimal"
    contexts:
      - pattern: "*.test.ts"
      - pattern: "*.test.php"
      - pattern: "docs/*"
      - pattern: "README.md"
      - task_type: "boilerplate"
      - task_type: "documentation"

  medium:
    description: "AI works, human reviews"
    auto_approve: false
    supervision: "code_review_required"
    contexts:
      - pattern: "src/**/!(security|auth|payment)/**"
      - task_type: "feature_implementation"
      - task_type: "api_endpoints"

  low:
    description: "AI suggests, human implements"
    auto_approve: false
    supervision: "pair_programming"
    escalation: true
    contexts:
      - pattern: "**/*security*"
      - pattern: "**/*auth*"
      - pattern: "**/*payment*"
      - pattern: "**/*credential*"
      - pattern: ".env*"
      - task_type: "migration"
      - task_type: "infrastructure"
```

---

## 5. Interface Definitions

### 5.1 New Commands

```
plugins/multi-agent-workflow/commands/workflows/
├── plan.md           # EXISTING
├── work.md           # EXISTING
├── review.md         # EXISTING
├── compound.md       # EXISTING
├── role.md           # EXISTING
├── sync.md           # EXISTING
├── status.md         # EXISTING
├── parallel.md       # NEW: Launch parallel agents
├── interview.md      # NEW: Interview mode for specs
├── progress.md       # NEW: Manage session progress
└── trust.md          # NEW: Check/set trust level
```

### 5.2 New Skills

```
plugins/multi-agent-workflow/skills/
├── consultant/           # EXISTING
├── checkpoint/           # EXISTING
├── git-sync/            # EXISTING
├── test-runner/         # EXISTING
├── coverage-checker/    # EXISTING
├── lint-fixer/          # EXISTING
├── worktree-manager/    # EXTEND: Full worktree support
├── commit-formatter/    # EXISTING
├── changelog-generator/ # EXISTING
├── layer-validator/     # EXISTING
├── spec-validator/      # NEW: Validate YAML specs
├── tdd-enforcer/        # NEW: TDD compliance
├── context-optimizer/   # NEW: Context management
└── learning-capturer/   # NEW: Compound learning
```

---

## 6. Security Architecture

### 6.1 MCP Security

```
┌─────────────────────────────────────────────────────┐
│                   MCP Security Layer                 │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌─────────────┐     ┌─────────────┐               │
│  │   OAuth     │────>│   Token     │               │
│  │   Flow      │     │   Manager   │               │
│  └─────────────┘     └─────────────┘               │
│                             │                       │
│                             v                       │
│                      ┌─────────────┐               │
│                      │  Permission │               │
│                      │   Check     │               │
│                      └──────┬──────┘               │
│                             │                       │
│         ┌───────────────────┼───────────────────┐  │
│         │                   │                   │  │
│         v                   v                   v  │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  │  Read Only  │     │ Read/Write  │     │   Admin     │
│  │  (Default)  │     │ (Elevated)  │     │ (Explicit)  │
│  └─────────────┘     └─────────────┘     └─────────────┘
│                                                      │
└─────────────────────────────────────────────────────┘
```

### 6.2 Credential Management

- All credentials stored in OS keychain or environment variables
- No credentials in config files
- Secrets rotated automatically via OAuth refresh
- Audit log for all MCP operations

---

## 7. Migration Strategy

### 7.1 Backward Compatibility

| Component | Strategy |
|-----------|----------|
| Current workflows | Keep working, new features optional |
| 50_state.md | Still used, progress.txt is supplement |
| Tilix | Supported but deprecated, migrate to tmux |
| Markdown specs | Still accepted, YAML preferred |

### 7.2 Migration Path

```
Phase 1: Add new features alongside existing
         ├── claude-progress.txt (new)
         └── 50_state.md (existing, still works)

Phase 2: New features become default
         ├── YAML specs (default)
         └── Markdown specs (fallback)

Phase 3: Deprecation warnings
         └── Tilix shows deprecation, suggests tmux

Phase 4: Full migration (future)
         └── Old patterns removed
```

---

## 8. Testing Strategy

### 8.1 Unit Tests

- Test each script independently
- Mock external dependencies (git, tmux)
- Test configuration parsing
- Test spec validation

### 8.2 Integration Tests

- Test full workflows end-to-end
- Test parallel agent execution
- Test session continuity
- Test MCP integration

### 8.3 Regression Tests

- Ensure existing workflows still work
- Test backward compatibility
- Performance benchmarks

---

## 9. Decisions Log

### ADR-001: Use tmux over Tilix

**Context**: Need reliable cross-platform terminal multiplexer
**Decision**: Use tmux as primary, deprecate Tilix
**Reason**: tmux is more scriptable, available on all platforms, better integration with worktrees
**Consequences**: Users need tmux installed, migration guide needed

### ADR-002: YAML over JSON for specs

**Context**: Need structured spec format
**Decision**: Use YAML with JSON Schema validation
**Reason**: YAML more readable, comments supported, JSON Schema for validation
**Consequences**: Need schema definitions, validation tooling

### ADR-003: Agent File format for state

**Context**: Need to serialize agent state
**Decision**: Adopt Agent File (.af) format from Letta
**Reason**: Industry standard emerging, portable, versioned
**Consequences**: Need serialization/deserialization, migration for existing state

---

**Document Status**: COMPLETE
**Last Updated**: 2026-01-27
**Author**: Planner Agent
