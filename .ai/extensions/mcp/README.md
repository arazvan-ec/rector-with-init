# MCP Server Extensions

This directory contains Model Context Protocol (MCP) server configurations for the Multi-Agent Workflow plugin.

## Overview

MCP servers provide external tool access to AI agents, enabling them to interact with databases, APIs, browsers, and notification systems during workflow execution.

## Directory Structure

```
.ai/extensions/mcp/
├── servers.yaml    # MCP server configurations
└── README.md       # This documentation
```

## Configured Servers

| Server | Purpose | Primary Roles |
|--------|---------|---------------|
| **postgres** | Database access for migrations validation | backend, qa |
| **github** | PR creation, issue management | all roles |
| **slack** | Notifications for BLOCKED status | all roles |
| **puppeteer** | UI verification automation | frontend, qa |

## Quick Start

### 1. Set Environment Variables

Create a `.env` file or export these variables:

```bash
# PostgreSQL
export POSTGRES_HOST=localhost
export POSTGRES_PORT=5432
export POSTGRES_USER=app_user
export POSTGRES_PASSWORD=your_password
export POSTGRES_DATABASE=app_db

# GitHub
export GITHUB_TOKEN=ghp_xxxxxxxxxxxx
export GITHUB_OWNER=your-org
export GITHUB_REPO=your-repo

# Slack
export SLACK_BOT_TOKEN=xoxb-xxxxxxxxxxxx
export SLACK_CHANNEL=#dev-workflow

# Puppeteer
export APP_DOMAIN=localhost:3000
```

### 2. Verify Server Configuration

```bash
# Check if MCP servers are properly configured
cat .ai/extensions/mcp/servers.yaml
```

### 3. Use in Workflows

Agents can invoke MCP tools using the naming convention:

```
mcp__<server>__<tool>
```

Example:
```
mcp__postgres__list_tables
mcp__github__create_pull_request
mcp__slack__send_message
mcp__puppeteer__screenshot
```

## Server Details

### PostgreSQL Server

**Purpose**: Database access for migrations validation, schema inspection, and read queries.

**Environment Variables**:
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `POSTGRES_HOST` | No | localhost | Database host |
| `POSTGRES_PORT` | No | 5432 | Database port |
| `POSTGRES_USER` | Yes | - | Database user |
| `POSTGRES_PASSWORD` | Yes | - | Database password |
| `POSTGRES_DATABASE` | Yes | - | Database name |

**Available Tools**:
- `query` - Execute SELECT queries (read-only)
- `describe_table` - Get table schema information
- `list_tables` - List all tables in database

**Usage Examples**:

```markdown
## Backend validating migration
Use mcp__postgres__describe_table to verify the users table schema:
- Table: users
- Expected columns: id, email, created_at, updated_at

## QA checking data integrity
Use mcp__postgres__query to verify test data:
- Query: SELECT COUNT(*) FROM users WHERE email LIKE '%@test.com'
```

**Security Notes**:
- Read-only by default
- Blocked tables: `users_credentials`, `payment_tokens`, `api_keys`
- Results limited to 1000 rows

---

### GitHub Server

**Purpose**: PR creation, issue reading, code review integration.

**Environment Variables**:
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `GITHUB_TOKEN` | Yes | - | GitHub personal access token |
| `GITHUB_OWNER` | Yes | - | Repository owner/organization |
| `GITHUB_REPO` | Yes | - | Repository name |

**Available Tools**:
- `create_pull_request` - Create a new PR
- `create_issue` - Create a new issue
- `get_issue` - Get issue details
- `list_issues` - List repository issues
- `add_comment` - Comment on issue/PR
- `get_pull_request` - Get PR details
- `list_pull_requests` - List PRs
- `merge_pull_request` - Merge a PR (planner only)

**Usage Examples**:

```markdown
## Backend creating PR after implementation
Use mcp__github__create_pull_request:
- Title: [backend][user-auth] Implement User entity and repository
- Base: develop
- Head: feature/user-auth
- Body: ## Summary\n- Added User entity\n- Added UserRepository

## QA creating issue for bug
Use mcp__github__create_issue:
- Title: [bug] Email validation fails for plus addresses
- Labels: bug, backend
- Body: ## Steps to reproduce...
```

**Security Notes**:
- Protected branches: `main`, `master`, `develop`
- Merge requires review approval
- Only planner role can merge PRs

---

### Slack Server

**Purpose**: Notifications when roles are BLOCKED or workflow events occur.

**Environment Variables**:
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `SLACK_BOT_TOKEN` | Yes | - | Slack bot OAuth token |
| `SLACK_CHANNEL` | No | #dev-workflow | Default notification channel |

**Available Tools**:
- `send_message` - Send a message to a channel
- `send_thread_reply` - Reply to a thread
- `list_channels` - List available channels
- `get_channel_history` - Get recent messages

**Usage Examples**:

```markdown
## Backend notifying about blocker
Use mcp__slack__send_message:
- Channel: #dev-workflow
- Message: :rotating_light: *BLOCKED* - Backend on feature `user-auth`
  *Reason*: Database migration fails on CI
  *Needs*: DevOps review of PostgreSQL version

## QA announcing approval
Use mcp__slack__send_message:
- Channel: #dev-workflow
- Message: :tada: *QA APPROVED* - Feature `user-auth` ready for merge!
```

**Notification Templates**:
The server includes pre-defined templates for common events:
- `blocked` - Role is blocked and needs help
- `completed` - Role finished their work
- `qa_rejected` - QA found issues
- `qa_approved` - Feature passed QA

**Security Notes**:
- Allowed channels: `#dev-workflow`, `#dev-alerts`, `#engineering`
- Rate limit: 10 messages per minute

---

### Puppeteer Server

**Purpose**: Browser automation for UI verification and E2E testing.

**Environment Variables**:
| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `PUPPETEER_HEADLESS` | No | true | Run browser headless |
| `PUPPETEER_TIMEOUT` | No | 30000 | Default timeout in ms |
| `APP_DOMAIN` | No | localhost | Application domain for URL filtering |

**Available Tools**:
- `navigate` - Navigate to a URL
- `screenshot` - Capture page screenshot
- `click` - Click an element
- `fill` - Fill a form field
- `evaluate` - Execute JavaScript (QA only)
- `get_content` - Get page text content
- `wait_for_selector` - Wait for element

**Usage Examples**:

```markdown
## Frontend verifying responsive design
Use mcp__puppeteer__navigate to http://localhost:3000/login
Use mcp__puppeteer__screenshot with viewport 375x667 (mobile)
Use mcp__puppeteer__screenshot with viewport 1920x1080 (desktop)

## QA running E2E test
Use mcp__puppeteer__navigate to http://localhost:3000/register
Use mcp__puppeteer__fill:
- Selector: #email
- Value: test@example.com
Use mcp__puppeteer__click:
- Selector: button[type="submit"]
Use mcp__puppeteer__wait_for_selector:
- Selector: .success-message
```

**Security Notes**:
- Only allowed domains: localhost, APP_DOMAIN
- Blocked external domains (Google, Facebook, GitHub)
- Max 50 screenshots per session
- Screenshots saved to `.ai/project/screenshots/`

## Role-Based Access

Access to MCP servers is controlled by role:

| Server | Planner | Backend | Frontend | QA |
|--------|:-------:|:-------:|:--------:|:--:|
| postgres | Read | Read/Write | - | Read |
| github | Full | PR | PR | Comment |
| slack | Full | Notify | Notify | Notify |
| puppeteer | - | - | Full | Full |

## Adding New Servers

1. Edit `servers.yaml` and add new server configuration
2. Follow the existing pattern:
   - Define command and args
   - Set environment variables
   - Configure allowed_roles
   - List available tools with trust_level
   - Add security constraints
3. Document the server in this README
4. Update `MCP_INTEGRATION.md` with usage examples

## Troubleshooting

### Server Connection Issues

```bash
# Check if MCP server package is available
npx -y @modelcontextprotocol/server-postgres --help

# Verify environment variables
env | grep POSTGRES
env | grep GITHUB
env | grep SLACK
```

### Permission Denied

If you see "Role not allowed to use this server":
1. Check your current role in `50_state.md`
2. Verify the role is in `allowed_roles` for the server
3. Check if specific tool has additional `allowed_roles` restrictions

### Tool Execution Timeout

If tools are timing out:
1. Check `settings.execution_timeout` in `servers.yaml`
2. For database queries, ensure they are optimized
3. For Puppeteer, increase `PUPPETEER_TIMEOUT`

## Related Documentation

- [MCP Integration Guide](/home/user/workflow/plugins/multi-agent-workflow/core/docs/MCP_INTEGRATION.md)
- [MCP Connector Skill](/home/user/workflow/plugins/multi-agent-workflow/skills/mcp-connector.md)
- [Trust Model](/home/user/workflow/.ai/extensions/trust/trust_model.yaml)
