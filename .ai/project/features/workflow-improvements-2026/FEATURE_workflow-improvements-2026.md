# Feature: Workflow Improvements 2026

> **Feature ID**: workflow-improvements-2026
> **Priority**: HIGH
> **Status**: PLANNING
> **Created**: 2026-01-27
> **Workflow**: task-breakdown (exhaustive planning)

---

## Objective

Enhance the multi-agent workflow system with 25+ improvements based on exhaustive research of industry best practices from:
- Anthropic Engineering (Agent Harnesses)
- GitHub (Spec-Driven Development)
- Industry leaders (Addy Osmani, Kent Beck, Dan Shipper)
- Open source (CrewAI, workmux, MetaGPT)

**Goal**: Transform the workflow from "functional" to "state of the art 2026"

---

## Context

### Why This Feature?

The current workflow works but has gaps compared to industry best practices:

1. **Session Continuity**: No mechanism for long-running sessions across context windows
2. **Parallel Development**: Manual worktree management, potential conflicts
3. **Spec Quality**: Unstructured Markdown specs lead to "70% problem"
4. **TDD Enforcement**: Suggested but not enforced
5. **Trust Calibration**: No differentiation between high/low risk tasks
6. **Context Management**: No optimization strategies
7. **Learning Capture**: Manual, not automated

### Research Sources

Full research document: `.ai/project/research/WORKFLOW_IMPROVEMENTS_RESEARCH.md`

---

## Acceptance Criteria

### Phase 1: Quick Wins
- [ ] `claude-progress.txt` created and persists across sessions
- [ ] Agents can resume work with full context
- [ ] Trust levels correctly evaluated for files
- [ ] `/workflows:progress` command works
- [ ] `/workflows:trust` command works

### Phase 2: Parallel Agents
- [ ] Git worktrees automatically created per agent
- [ ] Ports allocated without conflicts
- [ ] tmux session orchestrates all agents
- [ ] `/workflows:parallel` command works
- [ ] Monitor shows all agent status

### Phase 3: Spec-Driven Development
- [ ] YAML specs validate against JSON Schema
- [ ] Interview mode captures structured requirements
- [ ] Spec compliance can be checked against implementation
- [ ] `/workflows:interview` command works

### Phase 4: TDD Enforcement
- [ ] Pre-commit hook blocks commits without tests
- [ ] Test deletion attempts blocked
- [ ] Test-first commit order verified
- [ ] `/workflows:tdd` command works

### Phase 5: Advanced Integration
- [ ] MCP servers configurable and connectable
- [ ] GitHub Actions templates work
- [ ] Compound learning automatically captured
- [ ] Full workflow E2E test passes

---

## API Contracts

### Internal APIs

See `20_api_contracts.md` for full specifications.

**Key APIs**:
- Progress Manager: `progress_init()`, `progress_update()`, `progress_read()`
- Worktree Manager: `worktree_create()`, `worktree_list()`, `worktree_cleanup()`
- Trust Evaluator: `trust_get_level()`, `trust_can_auto_approve()`
- TDD Enforcer: `tdd_check_tests_exist()`, `tdd_verify_order()`

### CLI Commands

| Command | Description | Phase |
|---------|-------------|-------|
| `/workflows:progress` | Track session progress | 1 |
| `/workflows:trust` | Check trust levels | 1 |
| `/workflows:parallel` | Launch parallel agents | 2 |
| `/workflows:interview` | Capture specs interactively | 3 |
| `/workflows:tdd` | Check TDD compliance | 4 |
| `/workflows:mcp` | Manage MCP servers | 5 |

---

## Task Breakdown Summary

### Backend Tasks (25 total)
See `30_tasks_backend.md` for details.

| Phase | Tasks | Key Deliverables |
|-------|-------|------------------|
| 1 | BE-001 to BE-006 | Harness scripts, trust evaluator |
| 2 | BE-007 to BE-012 | Parallel execution system |
| 3 | BE-013 to BE-016 | Spec validation system |
| 4 | BE-017 to BE-020 | TDD enforcement |
| 5 | BE-021 to BE-025 | MCP, config, hooks |

### Frontend/Commands Tasks (17 total)
See `31_tasks_frontend.md` for details.

| Phase | Tasks | Key Deliverables |
|-------|-------|------------------|
| 1 | FE-001 to FE-004 | Progress/trust commands, docs |
| 2 | FE-005 to FE-008 | Parallel commands, monitor |
| 3 | FE-009 to FE-011 | Interview command, templates |
| 4 | FE-012 to FE-014 | TDD command, hooks docs |
| 5 | FE-015 to FE-017 | MCP command, migration guide |

### QA Tasks (17 total)
See `32_tasks_qa.md` for details.

| Phase | Tasks | Key Deliverables |
|-------|-------|------------------|
| 1 | QA-001 to QA-004 | Harness verification |
| 2 | QA-005 to QA-008 | Parallel E2E tests |
| 3 | QA-009 to QA-011 | Spec validation tests |
| 4 | QA-012 to QA-014 | TDD compliance tests |
| 5 | QA-015 to QA-017 | Integration + Full E2E |

---

## Dependencies

### External
- Git >= 2.30 (worktrees)
- tmux >= 3.0 (session management)
- jq >= 1.6 (JSON processing)
- Claude Code >= 2.0 (agent execution)

### Internal
- Existing workflow scripts (will be extended)
- Plugin structure (will add commands)
- Rules system (will add rules)

See `35_dependencies.md` for full dependency graph.

---

## Architecture

### Directory Structure (New)

```
.ai/
├── project/
│   └── sessions/           # NEW: Session state
│       └── claude-progress.txt
│
└── workflow/
    ├── harness/            # NEW: Agent harness
    ├── parallel/           # NEW: Parallel execution
    ├── specs/              # NEW: Spec system
    ├── enforcement/        # NEW: TDD, trust
    ├── integrations/       # NEW: MCP, GH Actions
    └── hooks/              # NEW: Claude Code hooks
```

### Key Design Decisions

1. **tmux over Tilix**: More scriptable, cross-platform
2. **YAML over JSON specs**: More readable, comments supported
3. **Agent File format**: Industry standard for state serialization
4. **Backward compatibility**: All existing features continue to work

See `10_architecture.md` for full architecture design.

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Breaking existing workflows | Medium | High | Comprehensive testing |
| Complexity overwhelming | Medium | Medium | Phased rollout, docs |
| MCP protocol changes | Low | Medium | Abstract MCP layer |
| Performance degradation | Low | Medium | Benchmarking |

---

## Timeline

| Phase | Duration | Focus |
|-------|----------|-------|
| Phase 1 | 1-2 weeks | Quick wins (harness, trust) |
| Phase 2 | 2-3 weeks | Parallel agents |
| Phase 3 | 2-3 weeks | Spec-driven development |
| Phase 4 | 2 weeks | TDD enforcement |
| Phase 5 | 3-4 weeks | Advanced integration |

**Total**: 10-14 weeks

---

## Success Metrics

### Quantitative
- First-pass success rate: 40% → 70%
- Context reuse rate: 20% → 60%
- Regression rate: 15% → 5%
- Session recovery: 70% → 95%

### Qualitative
- Developers report less friction
- Agents require fewer clarifications
- Code quality improves
- Documentation is comprehensive

---

## References

### Planning Documents
- `00_requirements_analysis.md` - Full requirements
- `10_architecture.md` - Technical architecture
- `15_data_model.md` - Data structures
- `20_api_contracts.md` - API specifications
- `30_tasks_backend.md` - Backend tasks
- `31_tasks_frontend.md` - Frontend/commands tasks
- `32_tasks_qa.md` - QA tasks
- `35_dependencies.md` - Dependency graph

### Research
- `.ai/project/research/WORKFLOW_IMPROVEMENTS_RESEARCH.md`

### External Sources
- [Anthropic: Agent Harnesses](https://www.anthropic.com/engineering/effective-harnesses-for-long-running-agents)
- [GitHub: Spec-Driven Development](https://github.blog/ai-and-ml/generative-ai/spec-driven-development-with-ai-get-started-with-a-new-open-source-toolkit/)
- [Addy Osmani: The 70% Problem](https://addyosmani.com/blog/ai-coding-workflow/)
- [Every.to: Compound Engineering](https://every.to/chain-of-thought/compound-engineering-how-every-codes-with-agents)

---

## Next Steps

1. **Review this plan** for completeness
2. **Start Phase 1**: `/workflows:work workflow-improvements-2026 --role=backend`
3. **Or parallel implementation**: `/workflows:parallel workflow-improvements-2026`

---

**Document Status**: COMPLETE
**Last Updated**: 2026-01-27
**Author**: Planner Agent
