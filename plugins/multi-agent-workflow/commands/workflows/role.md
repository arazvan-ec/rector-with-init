---
name: workflows:role
description: "Work as a specific role (planner, backend, frontend, qa) on a feature with full context and rules."
argument_hint: <role> <feature-name>
---

# Multi-Agent Workflow: Role

> **Tier 3 (Automatic)**: Role assignment is handled automatically by `/workflows:work --role=X`. Only invoke this command manually when you need to assume a role outside the normal work flow.

Assume a specialized role for feature development with complete context loading.

## Usage

```
/workflows:role planner user-authentication
/workflows:role backend payment-system
/workflows:role frontend dashboard-redesign
/workflows:role qa checkout-flow
```

## Arguments

- `role`: One of: `planner`, `backend`, `frontend`, `qa`
- `feature-name`: The feature to work on

## Roles Overview

| Role | Responsibilities | Can Write | Cannot Do |
|------|-----------------|-----------|-----------|
| **Planner** | Define features, create contracts, break down tasks | Feature docs, rules | Write code |
| **Backend** | Implement API, follow DDD, write tests | Backend code, tests | Frontend code |
| **Frontend** | Implement UI, mock APIs, responsive design | Frontend code, tests | Backend code |
| **QA** | Review code, run tests, validate criteria | Reports, test results | Fix code |

## Execution Steps

### Step 1: Parse Arguments

Extract role and feature-name from $ARGUMENTS.

### Step 2: Validate Role Exists

Ensure role is one of: planner, backend, frontend, qa

### Step 3: Load Role Context

Based on the role, load the appropriate agent definition:

**For Planner:**
```
Read: agents/roles/planner.md
Read: rules/global_rules.md
Read: rules/ddd_rules.md
```

**For Backend:**
```
Read: agents/roles/backend.md
Read: rules/global_rules.md
Read: rules/ddd_rules.md
```

**For Frontend:**
```
Read: agents/roles/frontend.md
Read: rules/global_rules.md
```

**For QA:**
```
Read: agents/roles/qa.md
Read: rules/global_rules.md
Read: rules/ddd_rules.md
```

### Step 4: Load Feature Context

```
Read: .ai/project/features/<feature-name>/50_state.md
Read: .ai/project/features/<feature-name>/FEATURE_*.md
Read: .ai/project/features/<feature-name>/30_tasks.md (if exists)
```

### Step 5: Git Sync

Before starting work, sync with remote:

```bash
git pull origin feature/<feature-name> || git pull origin main
```

### Step 6: Update State

Update 50_state.md to mark your role as IN_PROGRESS:

```markdown
## <Role Name>
**Status**: IN_PROGRESS
**Started**: <timestamp>
**Notes**: Beginning work on feature
```

### Step 7: Display Role Instructions

Output role-specific instructions based on the loaded agent definition.

## Role-Specific Workflows

### Planner Workflow
1. Read all rules (global, DDD, project-specific)
2. Analyze requirements
3. Define feature in FEATURE_X.md
4. Create task breakdown in 30_tasks.md
5. Create API contracts
6. Update state to COMPLETED
7. Commit and push

### Backend Workflow
1. Read rules and feature definition
2. Check that Planner is COMPLETED
3. Find reference code patterns
4. Implement with TDD (Red-Green-Refactor)
5. Follow DDD layers (Domain → Application → Infrastructure)
6. Run tests, ensure >80% coverage
7. Update state to COMPLETED
8. Commit and push

### Frontend Workflow
1. Read rules and feature definition
2. Check Backend state (use mocks if not ready)
3. Find reference component patterns
4. Implement with TDD
5. Test responsive design (375px, 768px, 1024px)
6. Run Lighthouse audit (score >90)
7. Update state to COMPLETED (or WAITING_API)
8. Commit and push

### QA Workflow
1. Read all rules and feature definition
2. Check Backend and Frontend are COMPLETED
3. Test API endpoints (curl/Postman)
4. Test UI flows in browser
5. Run automated test suites
6. Validate acceptance criteria
7. Create QA report
8. Update state to APPROVED or REJECTED
9. Commit and push

## Git Commit Pattern

After completing work:

```bash
git add .
git commit -m "[<role>][<feature-name>] <description>"
git push origin feature/<feature-name>
```

## Important Rules

- **One role per session**: Don't switch roles mid-session
- **Follow TDD**: Write tests before implementation (Backend/Frontend)
- **Document everything**: Update 50_state.md frequently
- **Commit at checkpoints**: Don't batch all commits at the end
- **If blocked**: Update state to BLOCKED with clear description
