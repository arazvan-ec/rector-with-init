# Problem Statement: Context Engineering v2

## What We're Building
Completing the 4 remaining improvements identified by comparing the plugin (v2.4.0) against Martin Fowler's "Context Engineering for Coding Agents" article. v2.4.0 implemented `context: fork` and portable governance hooks, but left 4 key areas unaddressed.

## Why It's Needed
- CLAUDE.md + framework_rules.md combined = ~980 lines always-loaded (~6000-8000 tokens wasted per session)
- Duplicated content between both files wastes tokens and creates maintenance burden
- No scoped rules by file-type pattern despite Claude Code support
- Overuse of MANDATORY/CRITICAL/MUST creates "urgency fatigue" — the model treats all rules as equally important (or ignores them all)

## Who Benefits
- Plugin users: faster sessions, less token waste, better compliance
- Plugin maintainers: single source of truth, easier updates

## Constraints
- Technical: Must remain compatible with Claude Code plugin system
- Files in `core/` are framework (immutable for users, but we're the authors)
- Must not break existing skills/agents that reference CLAUDE.md patterns

## Success Criteria
1. CLAUDE.md reduced from ~514 to ≤200 lines
2. Zero content duplicated between CLAUDE.md and framework_rules.md
3. At least 3 scoped rule files with file-type glob patterns
4. MANDATORY/CRITICAL/MUST usage reduced by ≥60%
5. framework_rules.md reduced by ≥40% (dedup + moved to scoped rules)
