# Git Workflow Rules

Rules that apply during git operations and version control management.

**Applies to**: Git operations, branch management, commit workflows, and multi-agent synchronization.

---

## Git as Synchronization

Git is the synchronization mechanism between agent instances.

- Pull before starting work on any task
- Push after completing tasks or at checkpoints
- Use clear, descriptive commit messages following conventional commits
- Avoid force push unless absolutely necessary and explicitly approved

## Branching

- Each feature gets its own branch from the latest `main` or `develop`
- Branch names follow the pattern: `feature/{feature-id}`, `fix/{issue}`, `refactor/{scope}`
- Delete branches after merge

## Commits

- Each commit should represent one logical change
- Commit messages follow conventional format: `type(scope): description`
- Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`
- Do not commit generated files (`.env`, `node_modules/`, `vendor/`, build artifacts)

## Conflict Management

### Git Conflicts
1. Pull before working
2. If conflict: stash current work, pull, stash pop, resolve manually
3. Do not use `--force` without consulting the team

### Design Conflicts
1. Report in `50_state.md` with `BLOCKED` status
2. The Planner role makes the decision
3. Document the decision in `DECISIONS.md`

## Multi-Agent Sync Protocol

When multiple agents work in parallel:

1. Each agent works on its own branch or worktree
2. Agents communicate state via `50_state.md` (committed and pushed)
3. Before starting work, always read other agents' state
4. Use `/workflows:sync` to merge work between agents
