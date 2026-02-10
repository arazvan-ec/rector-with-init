# Dependencies: Workflow Improvements 2026

> **Feature ID**: workflow-improvements-2026
> **Document**: 35_dependencies.md
> **Status**: COMPLETE

---

## 1. External Dependencies

### 1.1 Required Tools

| Tool | Minimum Version | Purpose | Installation |
|------|-----------------|---------|--------------|
| **Git** | >= 2.30 | Worktree support | `apt install git` / `brew install git` |
| **tmux** | >= 3.0 | Session management | `apt install tmux` / `brew install tmux` |
| **jq** | >= 1.6 | JSON processing | `apt install jq` / `brew install jq` |
| **Claude Code CLI** | >= 2.0 | Agent execution | `npm install -g @anthropic-ai/claude-code` |

### 1.2 Version Verification

```bash
#!/usr/bin/env bash
# .ai/workflow/tools/check_dependencies.sh

echo "Checking dependencies..."

# Git
git_version=$(git --version | grep -oE '[0-9]+\.[0-9]+')
if [[ $(echo "$git_version >= 2.30" | bc) -eq 1 ]]; then
    echo "✓ Git $git_version"
else
    echo "✗ Git >= 2.30 required (found: $git_version)"
fi

# tmux
if command -v tmux &> /dev/null; then
    tmux_version=$(tmux -V | grep -oE '[0-9]+\.[0-9]+')
    if [[ $(echo "$tmux_version >= 3.0" | bc) -eq 1 ]]; then
        echo "✓ tmux $tmux_version"
    else
        echo "✗ tmux >= 3.0 required (found: $tmux_version)"
    fi
else
    echo "✗ tmux not installed"
fi

# jq
if command -v jq &> /dev/null; then
    jq_version=$(jq --version | grep -oE '[0-9]+\.[0-9]+')
    echo "✓ jq $jq_version"
else
    echo "✗ jq not installed"
fi

# Claude Code
if command -v claude &> /dev/null; then
    claude_version=$(claude --version 2>/dev/null || echo "unknown")
    echo "✓ Claude Code $claude_version"
else
    echo "✗ Claude Code CLI not installed"
fi
```

### 1.3 Optional Dependencies

| Tool | Purpose | When Needed |
|------|---------|-------------|
| **yq** | YAML processing | Spec validation |
| **ajv** | JSON Schema validation | Spec validation |
| **gh** | GitHub CLI | MCP integration |
| **semgrep** | Static analysis | Security scanning |

---

## 2. Internal Dependencies

### 2.1 Dependency Graph

```
                    ┌─────────────────────────────────┐
                    │         CONFIG LAYER            │
                    │   .ai/project/config.yaml       │
                    │   .ai/workflow/trust_model.yaml │
                    └────────────────┬────────────────┘
                                     │
         ┌───────────────────────────┼───────────────────────────┐
         │                           │                           │
         v                           v                           v
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│  HARNESS LAYER  │       │ PARALLEL LAYER  │       │   SPEC LAYER    │
│                 │       │                 │       │                 │
│ progress_mgr.sh │       │ worktree_mgr.sh │       │ validator.sh    │
│ initializer.sh  │       │ tmux_orch.sh    │       │ interview.sh    │
│ coder.sh        │       │ port_mgr.sh     │       │ templates/      │
│ state_serial.sh │       │ monitor.sh      │       │ schema/         │
└────────┬────────┘       └────────┬────────┘       └────────┬────────┘
         │                         │                         │
         └───────────────────┬─────┴─────────────────────────┘
                             │
                             v
                  ┌─────────────────────┐
                  │  ENFORCEMENT LAYER  │
                  │                     │
                  │ trust_evaluator.sh  │
                  │ tdd_enforcer.sh     │
                  │ context_manager.sh  │
                  │ compound_tracker.sh │
                  └──────────┬──────────┘
                             │
                             v
                  ┌─────────────────────┐
                  │   COMMANDS LAYER    │
                  │                     │
                  │ /workflows:progress │
                  │ /workflows:parallel │
                  │ /workflows:trust    │
                  │ /workflows:tdd      │
                  │ /workflows:interview│
                  └─────────────────────┘
```

### 2.2 Script Dependencies

| Script | Depends On | Used By |
|--------|------------|---------|
| `progress_manager.sh` | config.yaml | initializer.sh, coder.sh |
| `initializer.sh` | progress_manager.sh | workflow.sh |
| `coder.sh` | progress_manager.sh | workflow.sh |
| `worktree_manager.sh` | git | tmux_orchestrator.sh |
| `port_manager.sh` | - | tmux_orchestrator.sh |
| `tmux_orchestrator.sh` | worktree_manager.sh, port_manager.sh | /workflows:parallel |
| `trust_evaluator.sh` | trust_model.yaml | tdd_enforcer.sh |
| `tdd_enforcer.sh` | trust_evaluator.sh | pre_commit_tdd.sh |
| `validator.sh` | schema/*.json | /workflows:spec |

---

## 3. Task Dependencies

### 3.1 Phase 1 Dependencies

```
BE-001 (Structure)
    │
    ├──> BE-002 (progress_manager)
    │         │
    │         ├──> BE-003 (initializer)
    │         │
    │         └──> BE-004 (coder)
    │
    └──> BE-005 (trust_evaluator)
              │
              └──> BE-006 (trust_model.yaml)

FE-001 (progress command) ──depends──> BE-002
FE-002 (trust command) ──depends──> BE-005
```

### 3.2 Phase 2 Dependencies

```
BE-007 (Parallel structure)
    │
    ├──> BE-008 (worktree_manager)
    │         │
    │         └──> BE-011 (monitor)
    │
    ├──> BE-009 (port_manager)
    │
    └──> BE-010 (tmux_orchestrator)
              │
              ├──depends──> BE-008
              │
              └──depends──> BE-009

BE-012 (parallel command)
    │
    ├──depends──> BE-008
    ├──depends──> BE-009
    └──depends──> BE-010

FE-005 (parallel command) ──depends──> BE-012
```

### 3.3 Phase 3 Dependencies

```
BE-013 (Spec structure)
    │
    ├──> BE-014 (JSON schemas)
    │
    └──> BE-015 (validator)
              │
              └──depends──> BE-014

BE-016 (interview) ──depends──> BE-015

FE-009 (interview command) ──depends──> BE-016
```

### 3.4 Phase 4 Dependencies

```
BE-017 (tdd_enforcer)
    │
    ├──depends──> BE-005 (trust_evaluator)
    │
    └──> BE-018 (pre_commit hook)

BE-019 (context_manager) ──depends──> config.yaml

BE-020 (compound_tracker) ──depends──> config.yaml
```

### 3.5 Phase 5 Dependencies

```
BE-021 (MCP structure)
    │
    └──> BE-022 (mcp_manager)

BE-023 (GitHub Actions) ──depends──> BE-015

BE-024 (config.yaml) ──depends──> all previous configs

BE-025 (session hook) ──depends──> BE-002
```

---

## 4. QA Dependencies

| QA Task | Depends On Backend | Depends On Frontend |
|---------|-------------------|---------------------|
| QA-001 | BE-002 | - |
| QA-002 | BE-002, BE-003, BE-004 | - |
| QA-003 | BE-005, BE-006 | - |
| QA-004 | - | FE-001, FE-002 |
| QA-005 | BE-008 | - |
| QA-006 | BE-009 | - |
| QA-007 | BE-007 to BE-012 | FE-005 |
| QA-008 | BE-010 | - |
| QA-009 | BE-015 | - |
| QA-010 | BE-016 | FE-009 |
| QA-011 | BE-015 | - |
| QA-012 | BE-017 | - |
| QA-013 | BE-018 | - |
| QA-014 | BE-019 | - |
| QA-015 | BE-021, BE-022 | FE-015 |
| QA-016 | BE-020 | - |
| QA-017 | All BE tasks | All FE tasks |

---

## 5. Critical Path

The critical path for this feature is:

```
BE-001 → BE-002 → BE-003 → BE-004 → QA-002
            │
            └──> FE-001 → QA-004

Total: Harness foundation must be solid before other work
```

### 5.1 Blocking Dependencies

These tasks block multiple other tasks:

| Task | Blocks |
|------|--------|
| BE-001 | All Phase 1 tasks |
| BE-002 | BE-003, BE-004, FE-001, QA-001, QA-002 |
| BE-008 | BE-010, BE-011, BE-012, FE-005, QA-005, QA-007 |
| BE-015 | BE-016, BE-023, FE-009, QA-009, QA-010, QA-011 |

### 5.2 Parallelizable Tasks

These tasks can be worked on in parallel:

**Phase 1 Parallel Groups:**
- Group A: BE-003, BE-004 (after BE-002)
- Group B: BE-005, BE-006 (independent of Group A)
- Group C: FE-001, FE-002 (after respective BE tasks)

**Phase 2 Parallel Groups:**
- Group A: BE-008, BE-009 (after BE-007)
- Group B: FE-005, FE-006 (after BE-010)

---

## 6. Dependency Installation

### 6.1 Debian/Ubuntu

```bash
#!/usr/bin/env bash
# install_dependencies_debian.sh

sudo apt update
sudo apt install -y git tmux jq

# Optional
sudo apt install -y yq npm

# Claude Code
npm install -g @anthropic-ai/claude-code
```

### 6.2 macOS

```bash
#!/usr/bin/env bash
# install_dependencies_macos.sh

brew update
brew install git tmux jq

# Optional
brew install yq node

# Claude Code
npm install -g @anthropic-ai/claude-code
```

### 6.3 Verification Script

```bash
#!/usr/bin/env bash
# verify_installation.sh

echo "Verifying workflow dependencies..."

required_commands=("git" "tmux" "jq" "claude")
all_ok=true

for cmd in "${required_commands[@]}"; do
    if command -v "$cmd" &> /dev/null; then
        echo "✓ $cmd installed"
    else
        echo "✗ $cmd NOT installed"
        all_ok=false
    fi
done

if $all_ok; then
    echo ""
    echo "All dependencies installed!"
    exit 0
else
    echo ""
    echo "Some dependencies missing. Please install them."
    exit 1
fi
```

---

## 7. Compatibility Matrix

| Feature | Linux | macOS | Windows (WSL) |
|---------|-------|-------|---------------|
| Agent Harness | ✓ | ✓ | ✓ |
| Git Worktrees | ✓ | ✓ | ✓ |
| tmux Orchestration | ✓ | ✓ | ✓ |
| Port Manager | ✓ | ✓ | ✓ |
| MCP Integration | ✓ | ✓ | ✓ |
| Pre-commit Hooks | ✓ | ✓ | ✓ |

---

## 8. Known Issues

### 8.1 tmux on macOS

On some macOS versions, tmux may have issues with clipboard integration. Solution:
```bash
brew install reattach-to-user-namespace
# Add to .tmux.conf:
# set-option -g default-command "reattach-to-user-namespace -l bash"
```

### 8.2 Git Worktrees with Submodules

If the repository uses git submodules, worktrees may not initialize them automatically. Solution:
```bash
# After creating worktree
cd .worktrees/backend
git submodule update --init --recursive
```

### 8.3 Port Conflicts

If the default port range (3001-3010) conflicts with other services, configure in config.yaml:
```yaml
parallel:
  port_range:
    start: 4001
    end: 4010
```

---

**Document Status**: COMPLETE
**Last Updated**: 2026-01-27
**Author**: Planner Agent
