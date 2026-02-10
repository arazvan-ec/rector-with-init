# Solutions: context-engineering-v2

## Solution for SPEC-F01 + SPEC-F02: Slim CLAUDE.md + Eliminate Duplication

**Approach**: Rewrite CLAUDE.md as a compact "index + critical rules" file. Move all detailed content to existing reference docs.

**Content Architecture (new)**:
```
Always-loaded (CLAUDE.md ~200 lines):
├── Routing: compact 5-step protocol (no question templates)
├── Workflows: 4 phases as one-liner table
├── Commands: names and one-line descriptions only
├── Agents: category + count table only
├── Skills: category + names only
├── Context Activation Model table
├── Key Patterns: one-liner per pattern
├── Best Practices: numbered list (no explanations)
└── Version + links to detailed docs

On-demand (via reference docs):
├── core/docs/ROUTING_REFERENCE.md (question templates, decision matrix)
├── core/docs/KARPATHY_PRINCIPLES.md (already exists)
├── core/docs/CONTEXT_ENGINEERING.md (already exists)
├── core/docs/SESSION_CONTINUITY.md (already exists - snapshots, metrics)
├── core/docs/LIFECYCLE_HOOKS.md (already exists)
├── core/docs/MCP_INTEGRATION.md (already exists)
└── core/solid-pattern-matrix.md (already exists)
```

**What gets REMOVED from CLAUDE.md**:
- Self-check protocol checklist (~15 lines)
- Clarifying question templates (~25 lines)
- Workflow selection quick guide table (~15 lines) → moved to ROUTING_REFERENCE.md
- 3-phase planning ASCII diagram (~5 lines)
- SOLID constraint explanation + scoring tables (~30 lines)
- Ralph Wiggum code block (~8 lines) → one-liner reference
- Compound capture steps (~5 lines) → one-liner reference
- Commodore 64 context management (~5 lines) → reference to SESSION_CONTINUITY.md
- MCP configured servers table + usage (~25 lines) → reference to MCP_INTEGRATION.md
- Snapshot workflow details (~25 lines) → reference to SESSION_CONTINUITY.md
- Metrics details (~25 lines) → reference to SESSION_CONTINUITY.md
- Lifecycle hooks table + how hooks change workflow (~25 lines) → reference to LIFECYCLE_HOOKS.md
- Project structure tables (~15 lines) → reference to README.md
- Changelogs (~30 lines)

**New file to create**: `core/docs/ROUTING_REFERENCE.md` — detailed routing with question templates, decision matrix

**Files affected**: CLAUDE.md (rewrite), framework_rules.md (dedup)

---

## Solution for SPEC-F03: Scoped Rules

**Approach**: Extract domain-specific rules from framework_rules.md into scoped rule files.

**New scoped rule files**:
1. `core/rules/testing-rules.md` — TDD requirements, test conventions. Applies when working with test files.
2. `core/rules/security-rules.md` — Trust model, supervision calibration. Applies when touching auth/security/payment paths.
3. `core/rules/git-rules.md` — Git workflow, conflict management. Applies during git operations.

**What stays in framework_rules.md**: Core principles only (routing, roles, state, comprehension debt, workflow evolution, permissions).

---

## Solution for SPEC-F04: Reduce Urgency Fatigue

**Approach**: Audit every MANDATORY/CRITICAL/MUST/NEVER across CLAUDE.md and framework_rules.md. Keep emphatic language only for routing (the one truly critical rule). Reframe everything else.

**Reframing rules**:
| Original | Reframed |
|----------|----------|
| "MUST pass through router" | Keep as-is (truly critical) |
| "MUST run before ANY code change" | Remove (redundant with routing) |
| "CRITICAL - Apply Always" | "Apply to all work" |
| "MUST comply with SOLID" | "Solutions should comply with SOLID" |
| "NEVER assume what user wants" | "Avoid assumptions without clarification" |
| "NEVER skip routing" | Keep as-is (truly critical) |
| "MANDATORY CONSTRAINT" | "Design constraint" |

---

## Parallelization Strategy

```
T1 (CLAUDE.md rewrite + dedup) ──┐
                                  ├──▶ T3 (fatigue reduction on final files)
T2 (scoped rules extraction)  ──┘     │
                                       ▼
                                  T4 (metadata update)
```

T1 and T2 can run in parallel (different files).
T3 depends on T1+T2 (needs final content to audit).
T4 runs last (version bump).
