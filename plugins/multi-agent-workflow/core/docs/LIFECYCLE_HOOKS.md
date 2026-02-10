# Lifecycle Hooks Documentation

> SDK Lifecycle Hooks for Trust Enforcement, Audit Trails, and Context Preservation

This document describes the lifecycle hooks integrated with Claude Code's hook system to enforce workflow policies, maintain audit trails, and preserve context across sessions.

---

## Overview

Lifecycle hooks are shell scripts that Claude Code executes at specific points during agent operation. They enable:

- **Trust enforcement**: Blocking or requiring review for sensitive file modifications
- **Audit trails**: Logging all tool uses for compliance and debugging
- **State management**: Auto-updating workflow state files
- **Context preservation**: Saving critical information before compaction or session end

---

## Hook Locations

All lifecycle hooks are located in:

```
.ai/hooks/lifecycle/
  pre_tool_use.sh    # Called before each tool execution
  post_tool_use.sh   # Called after each tool execution
  stop.sh            # Called when agent session ends
  pre_compact.sh     # Called before context compaction
```

---

## Hook Registration

Hooks are registered in `.claude/settings.json` using the Claude Code SDK format:

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "Edit|Write",
        "hooks": [
          {
            "type": "command",
            "command": ".ai/hooks/lifecycle/pre_tool_use.sh",
            "description": "Enforce trust model before file edits"
          }
        ]
      }
    ],
    "PostToolUse": [
      {
        "matcher": "Edit|Write|Bash",
        "hooks": [
          {
            "type": "command",
            "command": ".ai/hooks/lifecycle/post_tool_use.sh",
            "description": "Update state files and create audit trail"
          }
        ]
      }
    ],
    "Stop": [
      {
        "matcher": "",
        "hooks": [
          {
            "type": "command",
            "command": ".ai/hooks/lifecycle/stop.sh",
            "description": "Auto-checkpoint and verify no unhandled blockers"
          }
        ]
      }
    ],
    "PreCompact": [
      {
        "matcher": "",
        "hooks": [
          {
            "type": "command",
            "command": ".ai/hooks/lifecycle/pre_compact.sh",
            "description": "Save context summary before compaction"
          }
        ]
      }
    ]
  }
}
```

### Matcher Syntax

| Pattern | Matches |
|---------|---------|
| `""` (empty) | All events |
| `Edit\|Write` | Edit OR Write tools |
| `mcp__.*` | Any MCP tool |
| `Bash` | Only Bash tool |

---

## Hook Input/Output Protocol

### Input (JSON on stdin)

Hooks receive JSON on stdin. The structure varies by hook type:

**PreToolUse / PostToolUse:**
```json
{
  "tool_name": "Edit",
  "tool_input": { "file_path": "/path/to/file", ... },
  "session_id": "abc123"
}
```

**Stop:**
```json
{
  "session_id": "abc123",
  "stop_reason": "user_request"
}
```

**PreCompact:**
```json
{
  "session_id": "abc123",
  "message_count": 50,
  "token_count": 180000
}
```

### Output (Exit codes and stdout)

| Exit Code | Meaning |
|-----------|---------|
| `0` | Allow / Success |
| `2` | Block (PreToolUse only) |

**JSON output for decisions:**
```json
{"decision": "allow"}
{"decision": "block", "reason": "Explanation"}
```

---

## Hook Descriptions

### 1. Pre Tool Use Hook (`pre_tool_use.sh`)

**Purpose**: Enforce trust model before file edits, block unauthorized modifications to sensitive paths.

**Trigger**: Before Edit or Write tools are executed.

**JSON Input** (via stdin):
```json
{
  "tool_name": "Edit",
  "tool_input": { "file_path": "/path/to/file", ... },
  "session_id": "abc123"
}
```
| `WORKING_DIR` | Current working directory |

**Behavior**:

1. **Trust Level Check**: Evaluates the target file against the trust model
2. **Low-Trust Path Blocking**: Files in `auth/`, `security/`, `payment/` directories require pair review
3. **Logging**: Records all tool uses to `.ai/logs/tool_use_YYYYMMDD.log`

**Low-Trust Paths** (require `PAIR_REVIEW_APPROVED=true`):
- `auth/`, `Auth/`
- `security/`, `Security/`
- `payment/`, `Payment/`
- `billing/`, `checkout/`
- `session/`, `credential/`, `secret/`
- `migrations/`
- `.env`, `*.pem`, `*.key`

**Example Output** (blocked):
```
=== TRUST ENFORCEMENT: LOW TRUST PATH ===
File: src/auth/LoginService.ts
Tool: Edit

This file is in a low-trust path (auth/, security/, payment/).
Modifications require pair review with a human.

BLOCKED: Set PAIR_REVIEW_APPROVED=true after human review to proceed.
```

---

### 2. Post Tool Use Hook (`post_tool_use.sh`)

**Purpose**: Auto-update state files after tool completion and create audit trails.

**Trigger**: After any tool execution completes.

**Environment Variables**:
| Variable | Description |
|----------|-------------|
| `TOOL_NAME` | Name of the tool that was invoked |
| `TOOL_INPUT` | JSON string of tool input parameters |
| `TOOL_RESULT` | Result/output of the tool execution |
| `TOOL_SUCCESS` | "true" or "false" indicating success |
| `SESSION_ID` | Current session identifier |

**Behavior**:

1. **State Updates**: Updates `50_state.md` Last Updated timestamp for active features
2. **Audit Trail**: Logs all modifications to `.ai/logs/audit_YYYYMMDD.log`
3. **Modification Tracking**: Tracks files modified in current session

**Audit Log Format**:
```
---
timestamp: 2026-02-01T10:30:00+00:00
session: abc123
tool: Edit
file: src/services/UserService.ts
status: SUCCESS
---
```

---

### 3. Stop Hook (`stop.sh`)

**Purpose**: Auto-checkpoint when agent stops, verify no BLOCKED status left unhandled.

**Trigger**: When the agent session ends (user request, token limit, error).

**Environment Variables**:
| Variable | Description |
|----------|-------------|
| `SESSION_ID` | Current session identifier |
| `STOP_REASON` | Reason for stopping |
| `WORKING_DIR` | Current working directory |
| `SESSION_DURATION` | Duration of session in seconds |

**Behavior**:

1. **Auto-Checkpoint**: Creates checkpoint file in `features/<id>/checkpoints/`
2. **Blocker Check**: Scans all feature state files for unhandled `BLOCKED` status
3. **Session Logging**: Records session end in `.ai/logs/session_YYYYMMDD.log`

**Auto-Checkpoint Location**:
```
.ai/project/features/<feature-id>/checkpoints/checkpoint_<role>_auto_<timestamp>.md
```

**Blocker Warning**:
```
=== WARNING: BLOCKED STATUS DETECTED ===
The following features have unhandled BLOCKED status:
  - user-authentication
    Blocked items:
      **Status**: BLOCKED
      Waiting for security team approval
```

---

### 4. Pre Compact Hook (`pre_compact.sh`)

**Purpose**: Save context summary before compaction, preserve critical state information.

**Trigger**: Before Claude Code compacts the context window.

**Environment Variables**:
| Variable | Description |
|----------|-------------|
| `SESSION_ID` | Current session identifier |
| `COMPACT_REASON` | Reason for compaction |
| `WORKING_DIR` | Current working directory |
| `CONTEXT_TOKENS` | Approximate tokens being compacted |

**Behavior**:

1. **Context Snapshot**: Creates detailed snapshot in `.ai/logs/context_snapshots/`
2. **State Preservation**: Captures current feature, role, task, and recent changes
3. **Resume Guide**: Provides instructions for context restoration post-compaction

**Context Snapshot Contents**:
- Active feature and role
- Current task description
- Files modified this session
- Recent git activity
- Key decisions made
- Instructions for resuming

**Snapshot Location**:
```
.ai/logs/context_snapshots/context_<session-id>_<timestamp>.md
```

---

## Configuration Examples

### Basic Configuration

Register all hooks with wildcard matcher:

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "*",
        "hooks": [".ai/hooks/lifecycle/pre_tool_use.sh"]
      }
    ]
  }
}
```

### Selective Hook Registration

Only run hooks for specific tools:

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "Edit",
        "hooks": [".ai/hooks/lifecycle/pre_tool_use.sh"]
      },
      {
        "matcher": "Write",
        "hooks": [".ai/hooks/lifecycle/pre_tool_use.sh"]
      }
    ]
  }
}
```

### Environment Variable Override

Approve pair review via environment:

```bash
PAIR_REVIEW_APPROVED=true claude code
```

---

## Log Files

All hooks write to `.ai/logs/`:

| Log File | Purpose |
|----------|---------|
| `tool_use_YYYYMMDD.log` | All tool invocations |
| `audit_YYYYMMDD.log` | Detailed audit trail |
| `session_YYYYMMDD.log` | Session start/stop events |
| `context_snapshots/` | Pre-compaction context saves |

---

## Trust Model Integration

The `pre_tool_use.sh` hook integrates with the trust evaluator at:
```
.ai/extensions/scripts/enforcement/trust_evaluator.sh
```

Trust levels:
- **HIGH**: Auto-approve (tests, docs, config)
- **MEDIUM**: Requires code review (features, APIs)
- **LOW**: Requires pair programming (auth, security, payment)

See `.ai/extensions/trust/trust_model.yaml` for full configuration.

---

## Troubleshooting

### Hook Not Executing

1. Verify hook is executable: `chmod +x .ai/hooks/lifecycle/*.sh`
2. Check settings.json syntax: `cat .claude/settings.json | jq .`
3. Verify path is correct (relative to project root)

### Trust Enforcement Too Strict

To bypass for a single session (not recommended):
```bash
PAIR_REVIEW_APPROVED=true claude code
```

To add exceptions, modify `.ai/extensions/trust/trust_model.yaml`.

### Context Not Preserved

1. Check `.ai/logs/context_snapshots/` for saved snapshots
2. Verify `pre_compact.sh` has write permissions
3. Review hook output for errors

---

## Security Considerations

- Hooks run with the same permissions as Claude Code
- Audit logs may contain sensitive file paths - secure appropriately
- PAIR_REVIEW_APPROVED should only be set after genuine human review
- Consider encrypting or restricting access to `.ai/logs/`

---

## Related Documentation

- [Trust Model Configuration](../../../extensions/trust/trust_model.yaml)
- [Workflow Decision Matrix](./WORKFLOW_DECISION_MATRIX.md)
- [Checkpoint System](../../../extensions/scripts/create_checkpoint.sh)
