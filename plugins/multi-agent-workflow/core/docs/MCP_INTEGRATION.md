# MCP Integration Guide

**Version**: 1.0.0
**Last Updated**: 2026-02-01

---

## Overview

Model Context Protocol (MCP) is a standard protocol that enables AI agents to interact with external tools and services. This guide explains how MCP is integrated into the Multi-Agent Workflow plugin and how agents can leverage MCP tools during workflow execution.

## What is MCP?

MCP (Model Context Protocol) provides a standardized way for AI models to:

- **Access external tools** (databases, APIs, browsers)
- **Execute actions** (create PRs, send notifications, run queries)
- **Retrieve information** (read issues, inspect schemas, capture screenshots)

In the context of this workflow plugin, MCP enables agents to:

1. Validate database migrations before deployment
2. Create pull requests automatically
3. Send Slack notifications when blocked
4. Verify UI implementation with automated screenshots

## Architecture

```
+----------------+     +------------------+     +------------------+
|   AI Agent     |---->|   MCP Client     |---->|   MCP Server     |
| (Claude Code)  |     | (Tool Router)    |     | (postgres, etc)  |
+----------------+     +------------------+     +------------------+
        |                      |                        |
        |                      |                        v
        |                      |              +------------------+
        |                      |              | External Service |
        |                      |              | (DB, GitHub, etc)|
        |                      |              +------------------+
        |                      |
        v                      v
+------------------------------------------+
|         servers.yaml Configuration       |
|  - Server definitions                    |
|  - Role-based access control             |
|  - Security constraints                  |
+------------------------------------------+
```

## Tool Naming Convention

MCP tools follow a strict naming convention:

```
mcp__<server>__<tool>
```

### Components

| Part | Description | Example |
|------|-------------|---------|
| `mcp__` | Prefix indicating MCP tool | Required |
| `<server>` | Server name from servers.yaml | `postgres`, `github` |
| `<tool>` | Tool name provided by server | `query`, `create_pull_request` |

### Examples

| Tool Name | Server | Tool | Purpose |
|-----------|--------|------|---------|
| `mcp__postgres__query` | postgres | query | Execute SQL query |
| `mcp__postgres__list_tables` | postgres | list_tables | List database tables |
| `mcp__github__create_pull_request` | github | create_pull_request | Create a PR |
| `mcp__github__create_issue` | github | create_issue | Create an issue |
| `mcp__slack__send_message` | slack | send_message | Send Slack message |
| `mcp__puppeteer__screenshot` | puppeteer | screenshot | Capture screenshot |

## How Agents Use MCP Tools

### Step 1: Check Available Servers

Before using MCP tools, verify which servers are available and which tools they provide:

```markdown
## Available MCP Servers

1. **postgres** - Database access
   - mcp__postgres__query
   - mcp__postgres__describe_table
   - mcp__postgres__list_tables

2. **github** - GitHub integration
   - mcp__github__create_pull_request
   - mcp__github__create_issue
   - mcp__github__add_comment

3. **slack** - Notifications
   - mcp__slack__send_message

4. **puppeteer** - Browser automation
   - mcp__puppeteer__navigate
   - mcp__puppeteer__screenshot
```

### Step 2: Verify Role Access

Not all roles can access all servers. Check `servers.yaml` for `allowed_roles`:

| Server | Planner | Backend | Frontend | QA |
|--------|:-------:|:-------:|:--------:|:--:|
| postgres | Yes | Yes | No | Yes |
| github | Yes | Yes | Yes | Yes |
| slack | Yes | Yes | Yes | Yes |
| puppeteer | No | No | Yes | Yes |

### Step 3: Invoke the Tool

When invoking an MCP tool, provide the required parameters:

```markdown
## Example: Backend validating migration

I'll use mcp__postgres__describe_table to verify the schema:

Tool: mcp__postgres__describe_table
Parameters:
  table_name: users

Expected result:
- Column: id (uuid, primary key)
- Column: email (varchar, unique)
- Column: created_at (timestamp)
```

### Step 4: Handle Results

MCP tools return structured results. Process them appropriately:

```markdown
## Result Processing

Tool returned:
{
  "columns": [
    {"name": "id", "type": "uuid", "nullable": false},
    {"name": "email", "type": "varchar(255)", "nullable": false},
    {"name": "created_at", "type": "timestamp", "nullable": false}
  ]
}

Verification: Schema matches migration file. Proceeding with implementation.
```

## Common Workflows with MCP

### Workflow 1: Database Migration Validation (Backend)

```markdown
## Pre-Implementation: Validate Current Schema

1. List tables to understand current state:
   Tool: mcp__postgres__list_tables

2. Describe target table:
   Tool: mcp__postgres__describe_table
   Parameters: { table_name: "users" }

3. Run test query:
   Tool: mcp__postgres__query
   Parameters: { sql: "SELECT COUNT(*) FROM users LIMIT 1" }

## Post-Implementation: Verify Migration Applied

1. Describe table again to confirm new columns:
   Tool: mcp__postgres__describe_table
   Parameters: { table_name: "users" }

2. Verify constraints:
   Tool: mcp__postgres__query
   Parameters: { sql: "SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = 'users'" }
```

### Workflow 2: PR Creation (Backend/Frontend)

```markdown
## Creating Pull Request After Implementation

1. Verify all tests pass locally
2. Commit and push changes to feature branch
3. Create PR:

Tool: mcp__github__create_pull_request
Parameters:
  title: "[backend][user-auth] Implement User entity and repository"
  body: |
    ## Summary
    - Added User entity with Email value object
    - Implemented UserRepository with Doctrine
    - Added unit and integration tests

    ## Test Plan
    - [x] Unit tests passing (15/15)
    - [x] Integration tests passing (5/5)
    - [ ] Code review

    ## Related
    - Closes #42
  base: develop
  head: feature/user-auth

4. Note the PR number for QA review
```

### Workflow 3: Blocked Notification (Any Role)

```markdown
## Notifying Team About Blocker

When status changes to BLOCKED, notify via Slack:

Tool: mcp__slack__send_message
Parameters:
  channel: "#dev-workflow"
  text: |
    :rotating_light: *BLOCKED* - Backend on feature `user-auth`

    *Reason*: Database migration fails on PostgreSQL 14
    *Error*: Column "email" cannot have UNIQUE constraint with nullable

    *Needs*:
    - DevOps review of PostgreSQL version on CI
    - OR schema redesign to make email NOT NULL first

    *Blocking*: Frontend integration (waiting for API)

    cc: @devops @planner
```

### Workflow 4: UI Verification (Frontend/QA)

```markdown
## Capturing UI Screenshots for Review

1. Navigate to the page:
   Tool: mcp__puppeteer__navigate
   Parameters: { url: "http://localhost:3000/login" }

2. Capture mobile view:
   Tool: mcp__puppeteer__screenshot
   Parameters:
     viewport: { width: 375, height: 667 }
     path: ".ai/project/screenshots/login-mobile.png"

3. Capture desktop view:
   Tool: mcp__puppeteer__screenshot
   Parameters:
     viewport: { width: 1920, height: 1080 }
     path: ".ai/project/screenshots/login-desktop.png"

4. Fill and submit form:
   Tool: mcp__puppeteer__fill
   Parameters: { selector: "#email", value: "test@example.com" }

   Tool: mcp__puppeteer__fill
   Parameters: { selector: "#password", value: "testpass123" }

   Tool: mcp__puppeteer__click
   Parameters: { selector: "button[type='submit']" }

5. Capture result:
   Tool: mcp__puppeteer__wait_for_selector
   Parameters: { selector: ".dashboard" }

   Tool: mcp__puppeteer__screenshot
   Parameters: { path: ".ai/project/screenshots/login-success.png" }
```

## Security Considerations

### 1. Role-Based Access Control (RBAC)

MCP servers enforce role-based access:

```yaml
# servers.yaml
postgres:
  allowed_roles:
    - planner
    - backend
    - qa
  denied_roles:
    - frontend  # Frontend uses API, not direct DB
```

**Rule**: Always verify your role before attempting to use an MCP tool.

### 2. Trust Levels

Each tool has a trust level that determines supervision requirements:

| Trust Level | Description | Example Tools |
|-------------|-------------|---------------|
| **high** | Read-only, safe operations | `list_tables`, `get_issue` |
| **medium** | Reversible write operations | `create_issue`, `send_message` |
| **low** | Destructive or sensitive | `merge_pull_request`, `evaluate` |

**Rule**: Low-trust operations require explicit approval or pair programming.

### 3. Data Protection

MCP servers include security constraints:

```yaml
# postgres security
security:
  read_only: true
  blocked_tables:
    - users_credentials
    - payment_tokens
```

```yaml
# puppeteer security
security:
  allowed_domains:
    - localhost
    - "${APP_DOMAIN}"
  blocked_domains:
    - "*.google.com"
```

**Rule**: Never attempt to bypass security constraints. If you need access to a blocked resource, escalate to the Planner.

### 4. Credential Management

- **Never** hardcode credentials in workflow files
- Use environment variables: `${POSTGRES_PASSWORD}`
- Credentials are injected at runtime by the MCP client

### 5. Audit Trail

All MCP tool invocations are logged:

```
[2026-02-01T10:15:30Z] [backend] mcp__postgres__query executed
  - Query: SELECT COUNT(*) FROM users
  - Rows returned: 1
  - Execution time: 45ms
```

**Rule**: Assume all MCP operations are logged and auditable.

## Error Handling

### Connection Errors

```markdown
## MCP Server Connection Failed

Error: Could not connect to postgres server
Cause: Server not running or misconfigured

Actions:
1. Verify environment variables are set
2. Check if server process is running
3. Retry with backoff (automatic, max 3 attempts)
4. If persistent, document in 50_state.md and continue without MCP
```

### Permission Denied

```markdown
## MCP Permission Denied

Error: Role 'frontend' not allowed to use 'postgres' server
Cause: RBAC restriction in servers.yaml

Actions:
1. Verify you're operating as the correct role
2. Check if another role should perform this action
3. If access is genuinely needed, request configuration change from Planner
```

### Tool Execution Timeout

```markdown
## MCP Tool Timeout

Error: Tool 'mcp__postgres__query' timed out after 120000ms
Cause: Query took too long or server unresponsive

Actions:
1. Simplify the query (add LIMIT, reduce JOINs)
2. Check database performance
3. Increase timeout if justified (requires config change)
```

## Best Practices

### 1. Prefer Native Tools When Available

```markdown
# Prefer this (native git)
git push origin feature/user-auth

# Over this (MCP)
mcp__github__... (for simple git operations)
```

### 2. Batch Related Operations

```markdown
# Good: Single MCP call
mcp__slack__send_message with complete status

# Avoid: Multiple calls for same purpose
mcp__slack__send_message "Starting work"
mcp__slack__send_message "Still working"
mcp__slack__send_message "Almost done"
```

### 3. Document MCP Usage

When using MCP tools, document in your checkpoint:

```markdown
## Backend Checkpoint: Domain Layer Complete

**MCP Tools Used**:
- mcp__postgres__describe_table (verified schema)
- mcp__github__create_pull_request (PR #45)

**Results**:
- Schema matches expected structure
- PR created and awaiting review
```

### 4. Handle Failures Gracefully

```markdown
## Graceful Degradation

If mcp__postgres__query fails:
1. Log the error
2. Continue with mock data
3. Note in 50_state.md that validation was skipped
4. Mark as needs verification before merge
```

## Integration with Workflow Commands

MCP tools integrate with workflow commands:

| Command | MCP Integration |
|---------|-----------------|
| `/workflows:plan` | Read GitHub issues for requirements |
| `/workflows:work` | Validate DB schema, capture screenshots |
| `/workflows:review` | Create PR, add review comments |
| `/workflows:sync` | Pull latest, notify on conflicts |
| `/workflows:checkpoint` | Send Slack notification on BLOCKED |

## Troubleshooting

### Check MCP Configuration

```bash
# View server configuration
cat .ai/extensions/mcp/servers.yaml

# Check environment variables
env | grep -E "(POSTGRES|GITHUB|SLACK|PUPPETEER)"
```

### Test MCP Connection

```bash
# Test postgres connection
npx -y @modelcontextprotocol/server-postgres --test

# Test github connection
npx -y @modelcontextprotocol/server-github --test
```

### View MCP Logs

```bash
# MCP debug logs (if enabled)
cat .ai/logs/mcp-debug.log
```

## Related Documentation

- [MCP Servers Configuration](/.ai/extensions/mcp/servers.yaml)
- [MCP Extension README](/.ai/extensions/mcp/README.md)
- [MCP Connector Skill](/plugins/multi-agent-workflow/skills/mcp-connector.md)
- [Trust Model](/.ai/extensions/trust/trust_model.yaml)

---

**Note**: MCP integration is optional. Workflows can function without MCP, but external tool access will be limited to CLI commands and file operations.
