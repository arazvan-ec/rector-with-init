# Functional Specs: context-engineering-v2

## SPEC-F01: Slim CLAUDE.md to Essential Guidance Only
**Description**: CLAUDE.md must contain only what every session needs. Detailed documentation moves to reference docs loaded on-demand.
**Acceptance Criteria**:
- [ ] CLAUDE.md ≤ 200 lines
- [ ] Contains: routing protocol (compact), command index (names only), agent/skill catalogs (names only), context activation model, key patterns (one-liner each)
- [ ] Does NOT contain: SOLID score tables, MCP server details, snapshot workflow details, metrics details, clarifying question templates, project structure trees, changelogs
- [ ] Moved content is accessible via reference docs or skills

## SPEC-F02: Eliminate Duplication Between CLAUDE.md and framework_rules.md
**Description**: Each piece of information exists in exactly one place. CLAUDE.md is the "index + critical guidance". framework_rules.md is the "detailed operational rules".
**Acceptance Criteria**:
- [ ] Routing protocol appears in ONE file only (CLAUDE.md keeps compact version, framework_rules.md references it)
- [ ] Karpathy principles appear in ONE file only (CLAUDE.md keeps one-liners, details stay in KARPATHY_PRINCIPLES.md)
- [ ] Trust model appears in ONE file only (framework_rules.md)
- [ ] Comprehension debt appears in ONE file only (framework_rules.md)
- [ ] No paragraph-level duplicated content between the two files

## SPEC-F03: Scoped Rules by File-Type Patterns
**Description**: Create rule files that only load when the agent is working with specific file types, reducing always-loaded context.
**Acceptance Criteria**:
- [ ] At least 3 scoped rule files created in `core/rules/`
- [ ] Each rule file specifies which file patterns it applies to (via naming or frontmatter)
- [ ] Content extracted from framework_rules.md where appropriate
- [ ] framework_rules.md reduced by removing content moved to scoped rules

## SPEC-F04: Reduce MANDATORY/CRITICAL Fatigue
**Description**: Reduce overuse of urgency language so that truly critical rules stand out. Reframe most rules as clear guidance rather than absolute commands.
**Acceptance Criteria**:
- [ ] MANDATORY/CRITICAL used in ≤ 3 places across CLAUDE.md (only for routing)
- [ ] MUST/NEVER reduced by ≥ 60% across both files
- [ ] Replaced with probability-boosting language: "prefer X", "always do X", "avoid Y"
- [ ] Truly critical rules (routing, no code without tests) remain emphatic
