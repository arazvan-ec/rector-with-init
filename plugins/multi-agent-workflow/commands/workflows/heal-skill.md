---
name: workflows:heal-skill
description: "Fix incorrect SKILL.md files when a skill has wrong instructions or outdated references"
argument_hint: "[optional: specific issue to fix]"
allowed-tools: [Read, Edit, Bash(ls:*), Bash(git:*)]
---

# Heal Skill

> **Tier 4 (Developer-Only)**: This command is for plugin development, not for feature work. Use it to fix broken skill definitions.

Update a skill's SKILL.md and related files based on corrections discovered during execution.

## Purpose

When you discover that a skill has incorrect instructions, outdated API references, or missing information, use this command to:
1. Detect which skill is affected
2. Reflect on what went wrong
3. Propose specific fixes
4. Get user approval
5. Apply changes with optional commit

## Usage

```bash
/workflows:heal-skill                    # Detect from context
/workflows:heal-skill "API endpoint wrong"  # With specific issue
```

## Workflow

### Step 1: Detect Skill

Identify the skill from conversation context:
- Look for skill invocation messages
- Check which SKILL.md was recently referenced
- Examine current task context

```bash
# Find available skills
ls -1 ./skills/*/SKILL.md | head -10
ls -1 ~/.claude/skills/*/SKILL.md | head -10
```

Set: `SKILL_NAME=[skill-name]` and `SKILL_DIR=[skill-path]`

If unclear, ask the user: "Which skill needs healing?"

### Step 2: Reflection and Analysis

If `$ARGUMENTS` provided, focus on that issue. Otherwise analyze broader context.

**Determine:**
- **What was wrong**: Quote specific sections from SKILL.md that are incorrect
- **Discovery method**: Error messages, trial and error, documentation lookup
- **Root cause**: Outdated API, incorrect parameters, missing context
- **Scope of impact**: Single section or multiple files?
- **Proposed fix**: Which files, which sections, before/after

### Step 3: Scan Affected Files

```bash
ls -la $SKILL_DIR/
ls -la $SKILL_DIR/references/ 2>/dev/null
ls -la $SKILL_DIR/scripts/ 2>/dev/null
```

### Step 4: Present Proposed Changes

Present changes in this format:

```markdown
**Skill being healed:** [skill-name]
**Issue discovered:** [1-2 sentence summary]
**Root cause:** [brief explanation]

**Files to be modified:**
- [ ] SKILL.md
- [ ] references/[file].md
- [ ] scripts/[file].py

**Proposed changes:**

### Change 1: SKILL.md - [Section name]
**Location:** Line [X] in SKILL.md

**Current (incorrect):**
```
[exact text from current file]
```

**Corrected:**
```
[new text]
```

**Reason:** [why this fixes the issue]

[repeat for each change]

**Impact assessment:**
- Affects: [authentication/API endpoints/parameters/examples/etc.]

**Verification:**
These changes will prevent: [specific error that prompted this]
```

### Step 5: Request Approval

```
Should I apply these changes?

1. Yes, apply and commit all changes
2. Apply but don't commit (let me review first)
3. Revise the changes (I'll provide feedback)
4. Cancel (don't make changes)

Choose (1-4):
```

**Wait for user response. Do not proceed without approval.**

### Step 6: Apply Changes

Only after approval (option 1 or 2):

1. Use Edit tool for each correction across all files
2. Read back modified sections to verify
3. If option 1, commit with structured message:
   ```
   fix(skill): Heal [skill-name] - [brief description]

   - [Change 1 summary]
   - [Change 2 summary]

   Discovered during: [feature/task that revealed the issue]
   ```
4. Confirm completion with file list

## Success Criteria

- [ ] Skill correctly detected from context
- [ ] All incorrect sections identified with before/after
- [ ] User approved changes before application
- [ ] All edits applied across SKILL.md and related files
- [ ] Changes verified by reading back
- [ ] Commit created if user chose option 1
- [ ] Completion confirmed with file list

## Verification

Before completing:
- Read back each modified section to confirm changes
- Ensure cross-file consistency (SKILL.md examples match references/)
- Verify git commit created if option 1 selected
- Check no unintended files modified

## Common Healing Scenarios

### 1. Outdated API Reference
```
**Before:** endpoint: /api/v1/users
**After:** endpoint: /api/v2/users
**Reason:** API v1 deprecated, v2 required
```

### 2. Incorrect Parameter Names
```
**Before:** --token="xxx"
**After:** --api-key="xxx"
**Reason:** CLI renamed parameter in v3.0
```

### 3. Missing Step
```
**Before:**
1. Run command
2. Check output

**After:**
1. Set environment variable
2. Run command
3. Check output
**Reason:** Auth required, was previously implicit
```

### 4. Wrong Default Value
```
**Before:** timeout: 30 (default)
**After:** timeout: 120 (default)
**Reason:** Default changed in library update
```

## Integration with Compound Engineering

When a skill is healed:
1. Consider creating `docs/solutions/` entry if issue was non-obvious
2. Check if other skills might have similar issues
3. Update any templates that reference the skill
4. Add to compound_log.md if significant fix

## Auto-Detection Triggers

This command may be auto-suggested when:
- Skill execution fails with error
- User says "that didn't work" after skill use
- API returns unexpected error codes
- Skill output doesn't match expected format
