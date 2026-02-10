---
name: workflows:interview
description: "Create feature specs through guided interview questions"
argument_hint: [feature | api] [--quick <id>]
---

# Multi-Agent Workflow: Interview

Create specification files through an interactive guided interview.

## Usage

```bash
# Full feature spec interview
/workflows:interview feature

# API contract interview
/workflows:interview api

# Quick spec (minimal questions)
/workflows:interview --quick user-auth "User authentication feature"
```

## Feature Interview

The feature interview guides you through:

### 1. Basic Information
- Feature ID (auto-converted to kebab-case)
- Feature name (human-readable)
- Priority level

### 2. Objective
- Summary description
- Business value

### 3. Requirements
- Functional requirements
- Non-functional requirements

### 4. Tasks
- Backend tasks
- Frontend tasks
- QA tasks

### 5. Planning
- Estimated effort

## Example Session

```
╔════════════════════════════════════════════════════════════╗
║           FEATURE SPEC INTERVIEW                           ║
╚════════════════════════════════════════════════════════════╝

== Basic Information ==

What is the feature ID? (kebab-case identifier)
> user-profile
Saved: user-profile

What is the feature name? (human-readable)
> User Profile Management
Saved: User Profile Management

What is the priority?
Default: medium
Options: critical,high,medium,low
> high
Saved: high

== Objective ==

Briefly describe what this feature does:
> Allow users to view and edit their profile information
Saved: Allow users to view and edit their profile information

== Requirements ==

List the main functional requirements:
(Enter comma-separated values, or one per line. Empty line to finish)
> View profile
> Edit profile
> Upload avatar
>
Saved 3 items

== Generating Spec ==

Spec generated: .ai/project/features/user-profile/spec.yaml

Next steps:
  1. Review and edit: .ai/project/features/user-profile/spec.yaml
  2. Validate: /workflows:validate .ai/project/features/user-profile/spec.yaml
  3. Start planning: /workflows:plan user-profile
```

## Output Structure

The interview generates a complete feature spec:

```yaml
version: "1.0"

feature:
  id: "user-profile"
  name: "User Profile Management"
  priority: high
  status: planning

metadata:
  created: "2026-01-27"
  author: planner
  estimated_effort: "1-2 weeks"

objective:
  summary: |
    Allow users to view and edit their profile information

requirements:
  functional:
    - id: FR-101
      title: "View profile"
      description: |
        View profile
      priority: high
      acceptance_criteria:
        - "TODO: Define acceptance criteria"
      test_coverage: recommended

    # ... more requirements

tasks:
  backend:
    - id: BE-001
      title: "Create profile API"
      # ...

phases:
  - name: "Phase 1: Implementation"
    duration: "1-2 weeks"
    deliverables:
      - "Core functionality"
      - "Tests"
      - "Documentation"
```

## Quick Mode

For rapid spec creation with minimal interaction:

```bash
/workflows:interview --quick feature-id "Brief description"
```

This creates a basic spec with defaults that you can edit later.

## API Contract Interview

```bash
/workflows:interview api
```

Guides through:
- API name and description
- Base path
- Authentication type
- Endpoints

## Input Types

### Single Value
```
What is the priority?
> high
```

### Default Values
```
What is the priority?
Default: medium
> [Enter to accept default]
```

### Choice Questions
```
What is the priority?
Options: critical,high,medium,low
> medium
```

### List Input
```
List the requirements:
(Enter comma-separated values, or one per line. Empty line to finish)
> First requirement
> Second requirement
> Third, Fourth, Fifth
> [Empty line to finish]
```

## Validation

- **Required fields**: Cannot be left empty
- **Kebab-case**: Auto-converted (e.g., "My Feature" -> "my-feature")
- **Choice fields**: Must match one of the options

## Generated File Location

| Interview Type | Output Path |
|----------------|-------------|
| Feature | `.ai/project/features/{feature-id}/spec.yaml` |
| API | `.ai/project/contracts/{api-name}.yaml` |

## After Interview

1. **Review**: Open and review the generated spec
2. **Edit**: Add details, acceptance criteria, dependencies
3. **Validate**: Run `/workflows:validate <path>`
4. **Plan**: Start implementation with `/workflows:plan <feature-id>`

## Implementation

This command executes:

```bash
source .ai/workflow/specs/interview.sh

case "$ARGUMENTS" in
    feature)
        interview_feature
        ;;
    api)
        interview_api
        ;;
    --quick)
        interview_quick "$FEATURE_ID" "$DESCRIPTION"
        ;;
    *)
        interview_feature
        ;;
esac
```

## Programmatic Use

```bash
source .ai/workflow/specs/interview.sh

# Run interview programmatically
spec_path=$(interview_quick "my-feature" "Feature description")

# Access responses after interview
interview_feature
echo "Feature ID: ${RESPONSES[feature_id]}"
```

## Templates

The interview uses templates from:
```
.ai/workflow/specs/templates/
├── feature_spec.yaml    # Feature spec template
└── api_contract.yaml    # API contract template
```

You can also copy these templates directly instead of using the interview.

## Tips

1. **Be specific**: Clear descriptions lead to better specs
2. **Think tasks**: Break down work into actionable tasks
3. **Consider NFRs**: Don't forget performance and security
4. **Review output**: Always review and enhance the generated spec
5. **Use quick mode**: For simple features, quick mode is faster

## Related Commands

- `/workflows:validate` - Validate generated specs
- `/workflows:plan` - Plan feature implementation
- `/workflows:work` - Start working on a feature
- `/workflows:spec` - View spec contents
