# Tasks: context-engineering-v2

## T1: Rewrite CLAUDE.md + Create ROUTING_REFERENCE.md + Dedup framework_rules.md
**Status**: PENDING
**Depends on**: None
**Files**: CLAUDE.md, core/docs/ROUTING_REFERENCE.md, core/rules/framework_rules.md
**Ralph Wiggum Check**: Count lines of CLAUDE.md ≤ 200, grep for duplicated paragraphs

## T2: Create Scoped Rules (testing, security, git)
**Status**: PENDING
**Depends on**: None (parallel with T1)
**Files**: core/rules/testing-rules.md, core/rules/security-rules.md, core/rules/git-rules.md, core/rules/framework_rules.md
**Ralph Wiggum Check**: Each file has frontmatter with description, framework_rules.md reduced

## T3: Reduce Urgency Fatigue Across All Modified Files
**Status**: PENDING
**Depends on**: T1, T2
**Files**: CLAUDE.md, core/rules/framework_rules.md, core/rules/*.md
**Ralph Wiggum Check**: Count MANDATORY/CRITICAL/MUST/NEVER across all files

## T4: Update plugin.json + CONTEXT_ENGINEERING.md
**Status**: PENDING
**Depends on**: T1, T2, T3
**Files**: plugin.json, core/docs/CONTEXT_ENGINEERING.md
**Ralph Wiggum Check**: Version bumped, docs reference new scoped rules
