---
name: mcp-connector
description: "Connects to MCP servers with validation, RBAC, error handling, and audit logging. Manages connection lifecycle and retries."
context: fork
hooks:
  PreToolUse:
    - matcher: "mcp__*"
      command: "echo '[mcp-connector] Pre-flight validation for MCP tool invocation'"
  PostToolUse:
    - matcher: "mcp__*"
      command: "echo '[mcp-connector] MCP tool invocation logged for audit'"
---

# MCP Connector Skill

Connect to and use MCP (Model Context Protocol) servers for external tool access with validation and error handling.

## What This Skill Does

- Validates MCP server availability before use
- Enforces role-based access control (RBAC)
- Provides standardized error handling for MCP operations
- Manages connection lifecycle and retries
- Logs all MCP tool invocations for audit

## When to Use

- **Database validation**: Before/after migrations, schema verification
- **GitHub integration**: Creating PRs, reading issues, adding comments
- **Slack notifications**: When BLOCKED or completing milestones
- **UI verification**: Screenshots, E2E testing, responsive checks

## How to Use

### Via Workflow Integration

MCP tools are automatically available when configured in `servers.yaml`. Use the naming convention:

```
mcp__<server>__<tool>
```

### Pre-Flight Validation

Before using any MCP tool, validate access:

```markdown
## MCP Pre-Flight Check

1. **Server available?**
   - Server: postgres
   - Status: Check if server is configured in servers.yaml

2. **Role allowed?**
   - Current role: backend
   - Server allowed_roles: [planner, backend, qa]
   - Result: ALLOWED

3. **Tool available?**
   - Tool: query
   - Available: Yes
   - Trust level: medium

4. **Proceed with MCP call**
```

### Connection Pattern

```markdown
## MCP Connection Flow

1. VALIDATE server configuration exists
2. VERIFY current role has access
3. CHECK tool exists and is allowed for role
4. EXECUTE tool with parameters
5. HANDLE response or error
6. LOG invocation for audit
```

## Tool Invocation Examples

### PostgreSQL Tools

```markdown
## List Tables

Tool: mcp__postgres__list_tables
Parameters: {}

Expected Response:
{
  "tables": ["users", "orders", "products", "migrations"]
}

## Describe Table

Tool: mcp__postgres__describe_table
Parameters:
  table_name: "users"

Expected Response:
{
  "name": "users",
  "columns": [
    {"name": "id", "type": "uuid", "nullable": false, "primary_key": true},
    {"name": "email", "type": "varchar(255)", "nullable": false, "unique": true},
    {"name": "created_at", "type": "timestamp", "nullable": false}
  ],
  "indexes": ["users_pkey", "users_email_unique"]
}

## Execute Query (Read-Only)

Tool: mcp__postgres__query
Parameters:
  sql: "SELECT id, email FROM users WHERE created_at > '2026-01-01' LIMIT 10"

Expected Response:
{
  "rows": [
    {"id": "uuid-1", "email": "user1@example.com"},
    {"id": "uuid-2", "email": "user2@example.com"}
  ],
  "row_count": 2,
  "execution_time_ms": 12
}
```

### GitHub Tools

```markdown
## Create Pull Request

Tool: mcp__github__create_pull_request
Parameters:
  title: "[backend][user-auth] Implement User entity and repository"
  body: |
    ## Summary
    - Added User entity with Email value object
    - Implemented UserRepository with Doctrine
    - Added comprehensive test coverage

    ## Test Plan
    - [x] Unit tests (15/15 passing)
    - [x] Integration tests (5/5 passing)
    - [ ] Code review

    Closes #42
  base: "develop"
  head: "feature/user-auth"
  draft: false

Expected Response:
{
  "number": 45,
  "url": "https://github.com/org/repo/pull/45",
  "state": "open"
}

## Create Issue

Tool: mcp__github__create_issue
Parameters:
  title: "[bug] Email validation fails for plus addresses"
  body: |
    ## Description
    Emails with plus signs (user+tag@example.com) are rejected.

    ## Steps to Reproduce
    1. Go to registration form
    2. Enter email: test+tag@example.com
    3. Submit form

    ## Expected
    Email should be accepted

    ## Actual
    Error: "Invalid email format"
  labels: ["bug", "backend"]

Expected Response:
{
  "number": 43,
  "url": "https://github.com/org/repo/issues/43"
}

## Add Comment

Tool: mcp__github__add_comment
Parameters:
  issue_number: 45
  body: |
    ## QA Review Complete

    :white_check_mark: All acceptance criteria verified
    :white_check_mark: No regressions found
    :white_check_mark: Performance acceptable

    **Approved for merge**

Expected Response:
{
  "id": 12345,
  "url": "https://github.com/org/repo/pull/45#issuecomment-12345"
}
```

### Slack Tools

```markdown
## Send Notification (BLOCKED)

Tool: mcp__slack__send_message
Parameters:
  channel: "#dev-workflow"
  text: |
    :rotating_light: *BLOCKED* - Backend on feature `user-auth`

    *Reason*: Database migration fails - unique constraint on nullable column
    *Error*: `ERROR: column "email" cannot have UNIQUE constraint with nullable`

    *Needs*:
    - Schema redesign to make email NOT NULL
    - OR migration to add constraint after data cleanup

    *Impact*: Blocking frontend integration

    cc: <@planner>

Expected Response:
{
  "ok": true,
  "channel": "C1234567890",
  "ts": "1643723400.000100"
}

## Send Notification (COMPLETED)

Tool: mcp__slack__send_message
Parameters:
  channel: "#dev-workflow"
  text: |
    :white_check_mark: *COMPLETED* - Backend finished `user-auth`

    *Summary*:
    - User entity with Email value object
    - UserRepository with Doctrine implementation
    - RegisterUserUseCase with validation
    - REST endpoint: POST /api/users

    *Tests*: 20/20 passing, 94% coverage
    *PR*: #45 ready for review

    Frontend can begin integration!

Expected Response:
{
  "ok": true,
  "channel": "C1234567890",
  "ts": "1643723500.000200"
}
```

### Puppeteer Tools

```markdown
## Navigate and Screenshot

Tool: mcp__puppeteer__navigate
Parameters:
  url: "http://localhost:3000/login"

Expected Response:
{
  "url": "http://localhost:3000/login",
  "status": 200,
  "title": "Login - MyApp"
}

---

Tool: mcp__puppeteer__screenshot
Parameters:
  path: ".ai/project/screenshots/login-desktop.png"
  viewport:
    width: 1920
    height: 1080
  full_page: false

Expected Response:
{
  "path": ".ai/project/screenshots/login-desktop.png",
  "size_bytes": 145230
}

## Fill Form and Submit

Tool: mcp__puppeteer__fill
Parameters:
  selector: "#email"
  value: "test@example.com"

---

Tool: mcp__puppeteer__fill
Parameters:
  selector: "#password"
  value: "testpassword123"

---

Tool: mcp__puppeteer__click
Parameters:
  selector: "button[type='submit']"

---

Tool: mcp__puppeteer__wait_for_selector
Parameters:
  selector: ".dashboard-welcome"
  timeout: 5000

Expected Response:
{
  "found": true,
  "visible": true,
  "text": "Welcome, test@example.com!"
}
```

## Error Handling

### Error Categories

| Error Type | Cause | Action |
|------------|-------|--------|
| `CONNECTION_FAILED` | Server not running | Retry with backoff, then skip |
| `PERMISSION_DENIED` | Role not allowed | Check role, escalate if needed |
| `TOOL_NOT_FOUND` | Invalid tool name | Verify tool name in servers.yaml |
| `VALIDATION_ERROR` | Invalid parameters | Check parameter format |
| `EXECUTION_TIMEOUT` | Tool took too long | Simplify request, increase timeout |
| `SECURITY_BLOCKED` | Blocked table/domain | Respect constraint, find alternative |

### Error Response Template

```markdown
## MCP Error: [ERROR_TYPE]

**Tool**: mcp__postgres__query
**Parameters**: { sql: "SELECT * FROM users_credentials" }

**Error**: SECURITY_BLOCKED
**Message**: Table 'users_credentials' is in blocked_tables list

**Resolution**:
1. This table is blocked for security reasons
2. If data is needed, request through secure API
3. For audit purposes, escalate to security team

**Fallback Action**: Continue without this data, note limitation in checkpoint
```

### Retry Logic

```markdown
## MCP Retry Strategy

Attempt 1: Execute tool
  - If success: Return result
  - If transient error: Wait 1000ms, retry

Attempt 2: Execute tool
  - If success: Return result
  - If transient error: Wait 2000ms, retry

Attempt 3: Execute tool
  - If success: Return result
  - If any error: Log failure, return error

Max attempts: 3
Backoff: Exponential (1s, 2s, 4s)
```

## Validation Checklist

Before each MCP invocation, verify:

```markdown
## MCP Validation Checklist

- [ ] Server is defined in servers.yaml
- [ ] Current role is in allowed_roles
- [ ] Tool exists for this server
- [ ] Tool is allowed for current role (check tool-level allowed_roles)
- [ ] Trust level is appropriate (low trust requires approval)
- [ ] Parameters are valid and complete
- [ ] Security constraints allow this operation
```

## Role-Specific Guidelines

### Planner

```markdown
## Planner MCP Usage

Allowed servers: postgres (read), github (full), slack (full)

Common operations:
- Read GitHub issues for requirements
- Create issues for discovered tasks
- Merge PRs after QA approval
- Send notifications for major decisions

Restrictions:
- Cannot use puppeteer (no browser automation needed)
```

### Backend

```markdown
## Backend MCP Usage

Allowed servers: postgres (read/write), github (PR), slack (notify)

Common operations:
- Validate database schema before migration
- Verify schema after migration
- Create PR when implementation complete
- Notify when BLOCKED

Restrictions:
- Cannot use puppeteer
- Cannot merge PRs (planner only)
```

### Frontend

```markdown
## Frontend MCP Usage

Allowed servers: github (PR), slack (notify), puppeteer (full)

Common operations:
- Create PR when implementation complete
- Capture screenshots for UI verification
- Run E2E tests with Puppeteer
- Notify when BLOCKED

Restrictions:
- Cannot access database directly
- Cannot merge PRs
```

### QA

```markdown
## QA MCP Usage

Allowed servers: postgres (read), github (comment), slack (notify), puppeteer (full)

Common operations:
- Verify data integrity with read queries
- Add review comments to PRs
- Capture E2E test screenshots
- Notify on approval/rejection

Restrictions:
- Cannot write to database
- Cannot create/merge PRs
- Can only comment on existing PRs
```

## Integration with Checkpoints

Document MCP usage in checkpoints:

```markdown
## Backend Checkpoint: Domain Layer Complete

**Status**: COMPLETED
**MCP Tools Used**:
- mcp__postgres__describe_table - Verified users table schema
- mcp__postgres__query - Confirmed 0 rows (clean state)
- mcp__github__create_pull_request - Created PR #45

**MCP Results**:
- Schema validation: PASSED
- PR created: https://github.com/org/repo/pull/45

**Next Steps**: Application layer implementation
```

## Logging and Audit

All MCP invocations are logged:

```
[TIMESTAMP] [ROLE] [SERVER] [TOOL] [STATUS] [DURATION]
[2026-02-01T10:15:30Z] [backend] [postgres] [describe_table] [SUCCESS] [45ms]
[2026-02-01T10:16:00Z] [backend] [github] [create_pull_request] [SUCCESS] [1230ms]
[2026-02-01T10:17:30Z] [backend] [slack] [send_message] [SUCCESS] [340ms]
```

## Related Documentation

- [MCP Integration Guide](/home/user/workflow/plugins/multi-agent-workflow/core/docs/MCP_INTEGRATION.md)
- [MCP Servers Configuration](/home/user/workflow/.ai/extensions/mcp/servers.yaml)
- [MCP Extension README](/home/user/workflow/.ai/extensions/mcp/README.md)
- [Trust Model](/home/user/workflow/.ai/extensions/trust/trust_model.yaml)
