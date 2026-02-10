---
name: spec-merger
description: "Merges feature specifications into project-level specs after completion. Handles ADD, MODIFY, CONFLICT, and SKIP strategies with manifest tracking."
context: fork
hooks:
  Stop:
    - command: "echo '[spec-merger] Spec merge complete. Review spec-manifest.yaml for changes.'"
---

# Spec Merger Skill

Merge feature specifications into project-level specifications after feature completion.

## What This Skill Does

- Compares feature specs (`12_specs.md`, `15_solutions.md`) with project specs
- Identifies new entities, endpoints, and rules to add
- Identifies modifications to existing specifications
- Merges changes while preserving manual edits
- Updates `spec-manifest.yaml` with timestamps and change history
- Maintains traceability between features and project specs

## When to Use

- **During `/workflows:compound`**: Capture feature learnings into project specs
- **After feature completion**: Consolidate specs before closing feature branch
- **Project documentation updates**: Sync project specs with implemented features
- **Knowledge transfer**: Ensure project specs reflect current system state
- **Audit preparation**: Maintain accurate project-level documentation

## How to Use

### Via Slash Command

```bash
# Merge specs from completed feature
/multi-agent-workflow:merge-specs <feature-id>

# Preview changes without applying
/multi-agent-workflow:merge-specs <feature-id> --dry-run

# Force merge (skip conflict prompts)
/multi-agent-workflow:merge-specs <feature-id> --force

# Merge to specific project spec file
/multi-agent-workflow:merge-specs <feature-id> --target=api-specs.md
```

### Example

```bash
/multi-agent-workflow:merge-specs user-authentication

# Output:
# Analyzing feature specs...
# Found: 5 new entities, 3 new endpoints, 2 rule modifications
# Merging into project specs...
# Updated: spec-manifest.yaml
# Complete!
```

## Merge Process

### Step 1: Load Feature Specifications

```bash
# Load feature specs
Read: .ai/project/features/${FEATURE_ID}/12_specs.md
Read: .ai/project/features/${FEATURE_ID}/15_solutions.md
Read: .ai/project/features/${FEATURE_ID}/20_api_contracts.md
```

### Step 2: Load Project Specifications

```bash
# Load project-level specs
Read: .ai/project/specs/entities.md
Read: .ai/project/specs/api-contracts.md
Read: .ai/project/specs/business-rules.md
Read: .ai/project/specs/spec-manifest.yaml
```

### Step 3: Diff Analysis

For each spec element, determine:
- **ADD**: New item not in project specs
- **MODIFY**: Item exists but has changes
- **CONFLICT**: Item exists with incompatible changes
- **SKIP**: Item already exists unchanged

### Step 4: Apply Merge Strategy

```
For each item:
  if NEW:
    → ADD to project specs
  elif MODIFIED:
    → MERGE with strategy (see below)
  elif CONFLICT:
    → PROMPT for resolution (or use --force)
  elif UNCHANGED:
    → SKIP (no action)
```

### Step 5: Update Manifest

Update `spec-manifest.yaml` with timestamps and change tracking.

## Merge Strategies

### Strategy 1: ADD (New Elements)

For entirely new specifications:

```markdown
## Before (project specs)
### Entities
- User
- Product

## After (merged)
### Entities
- User
- Product
- Order          ← NEW from feature/order-management
- OrderItem      ← NEW from feature/order-management
```

**Manifest Update**:
```yaml
entities:
  Order:
    added: "2026-02-03T14:30:00Z"
    source: "feature/order-management"
    spec_ref: "SPEC-F03"
  OrderItem:
    added: "2026-02-03T14:30:00Z"
    source: "feature/order-management"
    spec_ref: "SPEC-F04"
```

### Strategy 2: MODIFY (Existing Elements)

For modifications to existing specifications:

```markdown
## Before (project specs)
### User Entity
- id: UUID
- email: Email (unique)
- name: string

## After (merged)
### User Entity
- id: UUID
- email: Email (unique)
- name: string
- avatar: URL (nullable)      ← ADDED from feature/user-profiles
- preferences: JSON           ← ADDED from feature/user-profiles
```

**Manifest Update**:
```yaml
entities:
  User:
    created: "2025-06-15T10:00:00Z"
    modified: "2026-02-03T14:30:00Z"
    modifications:
      - date: "2026-02-03T14:30:00Z"
        source: "feature/user-profiles"
        changes:
          - "Added avatar field (SPEC-F02)"
          - "Added preferences field (SPEC-F03)"
```

### Strategy 3: CONFLICT Resolution

When feature specs conflict with project specs:

```markdown
## Project Spec
### POST /api/orders
- Request: { productId, quantity }
- Response: 201 Created

## Feature Spec (conflicts)
### POST /api/orders
- Request: { productId, quantity, couponCode }  ← Different!
- Response: 201 Created
```

**Resolution Options**:

| Option | Action | When to Use |
|--------|--------|-------------|
| `--keep-project` | Keep project version | Feature was experimental |
| `--keep-feature` | Use feature version | Feature is canonical |
| `--merge-both` | Combine both versions | Both are valid additions |
| `--prompt` | Ask user (default) | Need human decision |

**Conflict Output**:
```markdown
## CONFLICT: POST /api/orders

### Project Version:
Request: { productId, quantity }

### Feature Version:
Request: { productId, quantity, couponCode }

### Resolution Required:
1. [K]eep project version
2. [U]se feature version
3. [M]erge both (add couponCode as optional)
4. [S]kip (leave unresolved)

Choice: _
```

### Strategy 4: SKIP (No Changes)

When feature spec matches project spec exactly:

```bash
# Output
SKIP: User entity (no changes)
SKIP: POST /api/users (no changes)
```

## Output Format

### Merge Report

```markdown
# Spec Merge Report: ${FEATURE_ID}

**Date**: 2026-02-03T14:30:00Z
**Feature**: user-authentication
**Status**: COMPLETED

---

## Summary

| Action | Count | Elements |
|--------|-------|----------|
| ADD | 5 | Order, OrderItem, OrderStatus, POST /api/orders, GET /api/orders |
| MODIFY | 2 | User (added avatar), POST /api/users (added metadata) |
| CONFLICT | 1 | PUT /api/users/:id (resolved: merge-both) |
| SKIP | 8 | (unchanged items) |

---

## Added Elements

### Entities
| Entity | Source Spec | Description |
|--------|-------------|-------------|
| Order | SPEC-F01 | Order aggregate root |
| OrderItem | SPEC-F02 | Line item value object |
| OrderStatus | SPEC-F03 | Status enum value object |

### Endpoints
| Endpoint | Source Spec | Description |
|----------|-------------|-------------|
| POST /api/orders | SPEC-F04 | Create new order |
| GET /api/orders | SPEC-F05 | List user orders |

### Business Rules
| Rule | Source Spec | Description |
|------|-------------|-------------|
| BR-ORDER-001 | SPEC-F06 | Order total must be positive |
| BR-ORDER-002 | SPEC-F07 | Max 50 items per order |

---

## Modified Elements

### User Entity
**Before**:
```
- id, email, name
```

**After**:
```
- id, email, name, avatar, preferences
```

**Source**: SPEC-F02, SPEC-F03 (feature/user-profiles)

---

## Resolved Conflicts

### PUT /api/users/:id

**Conflict Type**: Request body schema mismatch

**Resolution**: merge-both

**Result**: Combined both versions (original fields + new metadata field)

---

## Files Updated

| File | Changes |
|------|---------|
| `.ai/project/specs/entities.md` | +3 entities, ~1 modified |
| `.ai/project/specs/api-contracts.md` | +2 endpoints, ~1 modified |
| `.ai/project/specs/business-rules.md` | +2 rules |
| `.ai/project/specs/spec-manifest.yaml` | Updated timestamps |

---

## Verification

```bash
# Verify merged specs are valid
/multi-agent-workflow:validate-specs

# View updated manifest
cat .ai/project/specs/spec-manifest.yaml
```
```

### spec-manifest.yaml Format

```yaml
# Spec Manifest - Auto-generated by spec-merger
# DO NOT EDIT MANUALLY

version: "1.0"
last_updated: "2026-02-03T14:30:00Z"
last_feature: "user-authentication"

entities:
  User:
    created: "2025-06-15T10:00:00Z"
    modified: "2026-02-03T14:30:00Z"
    source: "feature/user-management"
    spec_refs: ["SPEC-F01", "SPEC-F02"]
    modifications:
      - date: "2026-02-03T14:30:00Z"
        source: "feature/user-profiles"
        changes: ["Added avatar", "Added preferences"]

  Order:
    created: "2026-02-03T14:30:00Z"
    source: "feature/order-management"
    spec_refs: ["SPEC-F03"]

  OrderItem:
    created: "2026-02-03T14:30:00Z"
    source: "feature/order-management"
    spec_refs: ["SPEC-F04"]

endpoints:
  "POST /api/users":
    created: "2025-06-15T10:00:00Z"
    modified: "2026-01-20T09:15:00Z"
    source: "feature/user-management"
    spec_refs: ["SPEC-F05"]

  "POST /api/orders":
    created: "2026-02-03T14:30:00Z"
    source: "feature/order-management"
    spec_refs: ["SPEC-F06"]

  "GET /api/orders":
    created: "2026-02-03T14:30:00Z"
    source: "feature/order-management"
    spec_refs: ["SPEC-F07"]

business_rules:
  "BR-USER-001":
    description: "Email must be unique"
    created: "2025-06-15T10:00:00Z"
    source: "feature/user-management"

  "BR-ORDER-001":
    description: "Order total must be positive"
    created: "2026-02-03T14:30:00Z"
    source: "feature/order-management"

  "BR-ORDER-002":
    description: "Max 50 items per order"
    created: "2026-02-03T14:30:00Z"
    source: "feature/order-management"

merge_history:
  - date: "2026-02-03T14:30:00Z"
    feature: "user-authentication"
    adds: 5
    modifies: 2
    conflicts: 1
    resolution: "merge-both"

  - date: "2026-01-20T09:15:00Z"
    feature: "user-profiles"
    adds: 2
    modifies: 1
    conflicts: 0
```

## Integration with Compound Workflow

### During `/workflows:compound`

The spec-merger is automatically invoked during the compound phase:

```bash
/workflows:compound user-authentication

# Compound workflow steps:
# 1. Generate changelog (changelog-generator)
# 2. Capture patterns (pattern-capture)
# 3. >>> Merge specs (spec-merger) <<<
# 4. Update compound log
# 5. Archive feature
```

### Compound Integration Example

```markdown
## Compound Capture: user-authentication

### Changelog
- 15 commits, 12 files changed
- [See changelog]

### Patterns Captured
- Email value object pattern
- Registration form pattern

### Specs Merged
- 5 entities added to project specs
- 3 endpoints added to API contracts
- 2 business rules documented
- spec-manifest.yaml updated

### Compound Log Updated
- patterns/email-value-object.md created
- compound_log.md updated
```

## Preserving Manual Edits

The merger uses markers to preserve manual edits:

```markdown
### User Entity

<!-- AUTO-GENERATED: START -->
- id: UUID
- email: Email (unique)
- name: string
- avatar: URL (nullable)
<!-- AUTO-GENERATED: END -->

<!-- MANUAL: START -->
**Note**: Email validation uses RFC 5322 standard.
See: https://example.com/email-spec
<!-- MANUAL: END -->
```

**Rules**:
- Content between `AUTO-GENERATED` markers is updated
- Content between `MANUAL` markers is preserved
- Content outside markers is preserved
- New sections are appended at the end

## Common Scenarios

### Scenario 1: First Feature (Bootstrap)

```bash
# No project specs exist yet
/multi-agent-workflow:merge-specs user-management

# Creates:
# - .ai/project/specs/entities.md
# - .ai/project/specs/api-contracts.md
# - .ai/project/specs/business-rules.md
# - .ai/project/specs/spec-manifest.yaml
```

### Scenario 2: Incremental Feature

```bash
# Project specs exist, adding new feature
/multi-agent-workflow:merge-specs order-management

# Updates existing files with new content
```

### Scenario 3: Refactoring Feature

```bash
# Feature modifies existing specs
/multi-agent-workflow:merge-specs user-profile-refactor

# Handles modifications and potential conflicts
```

### Scenario 4: Dry Run Preview

```bash
# Preview without changes
/multi-agent-workflow:merge-specs order-management --dry-run

# Output shows what WOULD be merged:
# WOULD ADD: Order entity
# WOULD ADD: POST /api/orders
# WOULD MODIFY: User entity (add ordersCount)
# No files modified.
```

## Error Handling

### Missing Feature Specs

```bash
# Error: Feature specs not found
/multi-agent-workflow:merge-specs nonexistent-feature

# Output:
# ERROR: Feature 'nonexistent-feature' not found
# Expected: .ai/project/features/nonexistent-feature/12_specs.md
# Run: /workflows:plan nonexistent-feature
```

### Invalid Spec Format

```bash
# Error: Malformed specs
# Output:
# ERROR: Invalid spec format in 12_specs.md
# Line 45: Missing 'Acceptance Criteria' section for SPEC-F03
# Fix the spec file and retry.
```

### Merge Conflict Unresolved

```bash
# Error: Conflict requires resolution
# Output:
# ERROR: Unresolved conflict for PUT /api/users/:id
# Use --force to auto-resolve or resolve manually.
```

## Best Practices

1. **Always dry-run first**: Preview changes before applying
2. **Review conflicts carefully**: Don't blindly use `--force`
3. **Run after compound**: Integrate with `/workflows:compound`
4. **Keep manifest clean**: Don't manually edit `spec-manifest.yaml`
5. **Use markers**: Preserve manual edits with MANUAL markers
6. **Verify after merge**: Run `/multi-agent-workflow:validate-specs`

## Related

- `/workflows:compound` - Invokes spec-merger automatically
- `/multi-agent-workflow:analyze-spec` - Compare implementation vs specs
- `changelog-generator` - Generates feature changelog
- `spec-template.md` - Template for feature specs
- `spec-analyzer` - Validates implementation against specs
