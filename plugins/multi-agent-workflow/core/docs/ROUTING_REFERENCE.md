# Routing Reference

Detailed routing guidance for the workflow router. This file is loaded on-demand when deeper routing context is needed.

For the core routing protocol, see `CLAUDE.md`.

## Clarifying Question Templates

### For Features
- Can you describe the functionality in 2-3 sentences?
- Who will use this functionality?
- Backend, frontend, or both?
- Does it connect to external APIs?
- Does it handle sensitive data?

### For Bugs
- What is happening now vs what should happen?
- How can I reproduce the error?
- Does it always occur or is it intermittent?
- Are there specific error messages?

### For Refactoring
- Which files/modules are involved?
- Why is this change needed?
- Are there existing tests?

## Workflow Selection Decision Matrix

| User Need | Ask About | Route To |
|-----------|-----------|----------|
| New functionality | Complexity, stack, integrations | `/workflows:plan` |
| Bug fix | Reproducibility, error messages | `bug-reproducer` then `/workflows:work` |
| Refactoring | Scope, motivation, tests | Depends on scope |
| Investigation | What to learn, where to look | Research agents |
| Code review | What to review, concerns | `/workflows:review` |
| Documentation | Scope, audience | Direct work |

## Confidence Scoring

Calculate confidence (0-100%) based on:
- Clear keywords: +20
- File references: +15
- Action verbs: +15
- Expected behavior described: +20
- Vague language: -25

If confidence < 60%, ask clarifying questions before routing.

## Self-Correction Protocol

If you realize you started work without proper routing:

1. Pause current work
2. Ask clarifying questions now
3. Confirm workflow selection with user
4. Then continue with proper context

## Router Verification Checklist

Before proceeding with routed work:

- [ ] Was the work type clearly identified?
- [ ] Were clarifying questions asked if needed?
- [ ] Is the chosen workflow appropriate for the task?
- [ ] Has trust level been considered for sensitive areas?
