# Pairing Patterns with Claude Code Agents

**Version**: 1.0
**Last Updated**: 2026-01-15
**Based on**: [How to Pair With an Agent](https://ampcode.com/how-to-pair-with-an-agent)

---

## 🎯 Core Principle: Vision & Direction

> **The difference between effective and ineffective agent use is vision and direction.**

Don't passively react to Claude's outputs. **Lead with clear specifications and feedback loops.**

---

## 🪑 The "Sitting Next to Me" Test

Imagine Claude is a **highly capable colleague** sitting next to you who:
- ✅ Works 10x faster than you
- ✅ Has access to all your tools
- ❌ Needs clear direction (not vague requests)
- ❌ Can't read your mind

### Bad (Vague)
```
"Why isn't the back button working?"
```

Claude doesn't know:
- Which back button?
- Which page?
- What should it do?
- How to verify the fix?

### Good (Specific & Directive)
```
"The back button on the settings page doesn't navigate to the previous page.

Tasks:
1. Reproduce it locally (open /settings, click back button)
2. Find the bug (check onClick handler, routing config)
3. Fix it (follow pattern in src/pages/ProfilePage.tsx)
4. Verify: Click through user flow: Home → Settings → Back button → should go to Home
5. Run e2e tests: npm run test:e2e -- settings
"
```

Claude now knows:
- Exactly what's broken
- Where to look
- What pattern to follow
- How to verify the fix
- What tests to run

---

## ⚡ The Speed Trap

**Critical failure point**: Claude produces output faster than you can verify it.

### Symptom
You ask for a feature that should take an afternoon. Claude generates code across 10 files in 2 minutes. You say "looks good" without verifying. Result: **broken code everywhere**.

### Why It Happens
- Claude lacks context you have in your head
- You can't review 10 files in 2 minutes
- No feedback loop to catch errors early

### Solution: Engineer Feedback Loops

**ALWAYS include verification steps in your prompts:**

```
"Implement user registration feature.

Reference: Follow the pattern in src/features/auth/LoginForm.tsx

Steps:
1. Create RegistrationForm component
2. Add form validation (use react-hook-form like in LoginForm)
3. Integrate with POST /api/users endpoint
4. Add success/error toast notifications
5. VERIFY: Fill form with test data, submit, check network tab shows 201 response
6. VERIFY: Check user appears in database (use Prisma Studio or SQL query)
7. Run tests: npm test -- RegistrationForm
8. STOP and show me the results before proceeding
"
```

Key additions:
- ✅ Reference existing pattern
- ✅ Explicit verification steps
- ✅ Tools to use for verification
- ✅ Tests to run
- ✅ **STOP checkpoint** before proceeding

---

## 🔄 Feedback Loop Patterns

### Pattern 1: Reference Existing Code

**Bad:**
```
"Add dark mode support"
```

**Good:**
```
"Add dark mode support to the dashboard.

Reference implementation: src/themes/ThemeProvider.tsx already handles light/dark toggle.
Follow that pattern.

Verify:
1. Use Chrome DevTools
2. Toggle dark mode
3. Check all dashboard components render correctly
4. Toggle back to light mode
5. Verify no console errors
"
```

### Pattern 2: Specify Success Criteria

**Bad:**
```
"Make the API faster"
```

**Good:**
```
"Optimize the GET /api/users endpoint. Current response time: 2.3s. Target: < 500ms.

Investigate:
1. Check database query (likely N+1 problem)
2. Add indexes if needed
3. Consider caching with Redis

Verify:
1. Use curl with -w '%{time_total}' to measure time
2. Run load test: ab -n 100 -c 10 http://localhost:3000/api/users
3. Response time should be < 500ms for 95th percentile
4. Show me the before/after metrics
"
```

### Pattern 3: Build Incremental Checkpoints

**Bad:**
```
"Build the entire checkout flow"
```

**Good:**
```
"Build the checkout flow. Let's do this incrementally with verification at each step.

Step 1: Cart summary component
- Show items, quantities, prices
- Calculate total
- STOP: Show me the component, I'll verify the calculations

Step 2: Shipping form
- Name, address, city, zip
- Validation (use yup schema like in src/forms/ProfileForm.tsx)
- STOP: I'll test the validation

Step 3: Payment integration
- Stripe Elements integration
- Test mode
- STOP: I'll verify with test card 4242 4242 4242 4242

[Continue after each checkpoint passes]
"
```

---

## 🎯 Best Practices

### 1. Provide Context & Constraints

**Mention potential pitfalls:**
```
"Implement file upload.

Note: We have a 5MB file size limit (check in src/config/upload.ts).
Watch out for: CORS issues (we use pre-signed S3 URLs).
Security: Validate file types on backend (see src/utils/validateFileType.ts).
"
```

### 2. Build Verification Into Requests

**Don't say:**
```
"Add user search"
```

**Say:**
```
"Add user search with autocomplete.

Implementation:
- Debounce input (300ms, use lodash.debounce)
- Query GET /api/users?q={query}
- Max 10 results
- Follow pattern in src/components/SearchBar.tsx

Testing requirements:
1. Type 'joh' → should show users with 'joh' in name
2. Type fast (without debounce delay) → should NOT spam API
3. Empty query → clear results
4. API error → show error toast
5. Run: npm test -- UserSearch.test.tsx
"
```

### 3. Specify Tools for Validation

**Examples:**
```
"Use Chrome DevTools Network tab to verify API calls"
"Use Prisma Studio to check database records"
"Use curl to test the endpoint manually"
"Use React DevTools to inspect component state"
"Check server logs for errors: docker logs app-server"
```

### 4. Use Pattern Matching

**Reference specific files:**
```
"Follow the pattern in src/api/messages.ts"
"Use the same error handling as src/utils/apiClient.ts"
"Structure like src/features/auth/LoginPage.tsx"
"Copy the test structure from tests/unit/UserService.test.ts"
```

### 5. Create Feedback Loops

**Always end with verification:**
```
"When done:
1. Run unit tests: npm test
2. Run e2e tests: npm run test:e2e
3. Manual test: [specific steps]
4. Show me test results before moving to next task
"
```

---

## 🚫 Anti-Patterns (What NOT to Do)

### ❌ Anti-Pattern 1: Vague Requests

**Bad:**
```
"Fix the bug"
"Make it better"
"Optimize performance"
"Add validation"
```

**Why it fails**: Claude doesn't know what bug, what "better" means, which performance metric, or what to validate.

### ❌ Anti-Pattern 2: No Verification

**Bad:**
```
"Implement user authentication.

[Claude generates 15 files]

You: "Looks good!" (without testing)
```

**Result**: Broken code in production.

### ❌ Anti-Pattern 3: Trusting Without Testing

**Bad:**
```
You: "Did you test it?"
Claude: "Yes, I verified the implementation."
You: "Great, let's deploy."
```

**Why it fails**: **Trust isn't a feeling, it's a passing test suite.**

Claude can't actually run code. You must verify.

### ❌ Anti-Pattern 4: No Context

**Bad:**
```
"Add a new API endpoint"
```

**Missing**:
- What does the endpoint do?
- What's the route?
- Request/response format?
- Where's our API code?
- What pattern to follow?

### ❌ Anti-Pattern 5: All-or-Nothing Tasks

**Bad:**
```
"Build the entire user management system"

[Claude generates massive PR with 50 files]
```

**Better**: Break into incremental checkpoints (see Pattern 3 above).

---

## ✅ Effective Prompt Template

Use this template for effective prompts:

```markdown
## Task
[Clear, specific description of what to do]

## Context
- Current state: [what exists now]
- Goal: [what should exist after]
- Constraints: [limitations, requirements]
- Pitfalls: [known issues to avoid]

## Reference
Follow pattern in: [specific file path]
Use same approach as: [another feature]

## Implementation Steps
1. [Specific step with detail]
2. [Another step]
3. [etc.]

## Verification Steps
After implementation:
1. [Manual test step 1]
2. [Manual test step 2]
3. Run tests: [command]
4. Check: [what to verify]
5. STOP and show me results

## Success Criteria
- [ ] [Criterion 1]
- [ ] [Criterion 2]
- [ ] All tests passing
- [ ] No console errors
- [ ] Meets [performance/quality] target
```

---

## 🎯 Examples: Before & After

### Example 1: Adding a Feature

**❌ Before (Ineffective):**
```
"Add comments to blog posts"
```

**✅ After (Effective):**
```
"Add comments feature to blog posts.

Context:
- Blog posts are in src/features/blog/Post.tsx
- We use Prisma for database (see prisma/schema.prisma)
- Users must be authenticated to comment

Reference:
- Follow the pattern of Likes feature in src/features/blog/LikeButton.tsx
- Use the same API client setup from src/api/client.ts

Implementation:
1. Update Prisma schema:
   - Add Comment model (fields: id, content, authorId, postId, createdAt)
   - Add relation to Post and User models

2. Create API endpoint:
   - POST /api/posts/:postId/comments
   - GET /api/posts/:postId/comments
   - Follow REST conventions in src/api/posts.ts

3. Create CommentForm component:
   - Textarea for content
   - Submit button
   - Show loading state
   - Handle errors (toast notification)

4. Create CommentList component:
   - Show author name, avatar, timestamp
   - Pagination (10 per page)

Verification:
1. Migrate database: npx prisma migrate dev
2. Create a comment via Postman/curl: POST localhost:3000/api/posts/1/comments
3. Verify in Prisma Studio: comment appears in database
4. Test UI: log in, write comment, submit, see it appear
5. Run tests: npm test -- Comment
6. Check for console errors

Success Criteria:
- [ ] Authenticated users can post comments
- [ ] Comments display under posts
- [ ] Comment count updates
- [ ] Tests passing
- [ ] No N+1 query issues (check with Prisma query logging)
```

### Example 2: Fixing a Bug

**❌ Before (Ineffective):**
```
"The login isn't working"
```

**✅ After (Effective):**
```
"Login form submits but user stays on login page instead of redirecting to dashboard.

Context:
- Login form: src/features/auth/LoginForm.tsx
- Auth API: src/api/auth.ts
- Expected behavior: On successful login, redirect to /dashboard

Reproduce:
1. Open /login
2. Enter email: test@example.com, password: password123
3. Click "Login"
4. Observe: Form submits (loading spinner shows), then stops
5. Expected: Should redirect to /dashboard
6. Actual: Stays on /login page

Investigation:
1. Check browser console for errors
2. Check Network tab: Does POST /api/auth/login return 200?
3. Check response: Is token in response body?
4. Check: Is token saved to localStorage?
5. Check: Is router.push('/dashboard') being called?

Debug:
- Add console.log in onSubmit handler
- Check if onSuccess callback is called
- Verify redirect logic

Reference:
- Registration flow works correctly (see src/features/auth/RegisterForm.tsx)
- Copy the redirect pattern from there

Fix & Verify:
1. Fix the redirect issue
2. Test: Log in with test credentials → should redirect to /dashboard
3. Test: Hard refresh /dashboard → should stay logged in (token persists)
4. Test: Log out → should redirect to /login
5. Run: npm test -- LoginForm.test.tsx
```

---

## 🔄 Self-Review Pattern (The 80% Problem)

**Origin**: Addy Osmani, "The 80% Problem in Agentic Coding" (January 2026)

AI models now generate ~80% of code, creating **comprehension debt** - the gap between code you can review and code you could write.

### The Problem

```
❌ DANGEROUS CYCLE:

AI generates 1000 lines → You review in 5 minutes → "Looks good" → Ship

Result: Code you "approved" but don't truly understand
```

### Self-Review Protocol

Before marking code COMPLETED, require the agent to critique its own work with "fresh context":

```markdown
## Self-Review Checklist

### Step 1: Reset Context
"Forget you wrote this. Review it as a skeptical senior developer."

### Step 2: Code Critique
- [ ] Would I write this the same way manually?
- [ ] Are there abstractions I don't fully understand?
- [ ] Did I copy patterns without understanding why?
- [ ] Are there "magic" values or logic I can't justify?
- [ ] What would a skeptical reviewer ask about this?

### Step 3: Assumption Validation
- [ ] What assumptions did I make?
- [ ] Did I validate these or just proceed?
- [ ] What could go wrong that I haven't considered?

### Step 4: Simplification Check
- [ ] Is this the simplest solution?
- [ ] Did I over-engineer? (YAGNI violations)
- [ ] Could this be 50% shorter while doing the same thing?

### Step 5: Output
List at least:
- 3 potential improvements
- 2 questions a reviewer would ask
- 1 thing that could be simpler
```

### Practical Application

**Bad:**
```
You: "Implement authentication"
Claude: [generates 500 lines]
You: "Looks good, commit it"
```

**Good:**
```
You: "Implement authentication"
Claude: [generates 500 lines]
You: "Now review your own code as a skeptical senior developer.
      What would you change? What assumptions did you make?
      What questions would a reviewer ask?"
Claude: [provides critique]
You: "Good points. Fix issues 1 and 3, then explain why
      you chose JWT over sessions"
Claude: [fixes and explains]
You: "Now I understand. Commit it."
```

### Comprehension Checkpoint Template

Use after every major feature or every 3 TDD iterations:

```markdown
## Comprehension Checkpoint

**Feature**: [name]
**Date**: [date]

### Quick Knowledge Test (Answer without looking at code)

1. What does this code do? (one sentence)
   Answer: ___

2. How does data flow through it?
   Answer: ___

3. What happens when [edge case]?
   Answer: ___

4. How would you add [hypothetical feature]?
   Answer: ___

### Score
- [ ] 5/5 - Could rewrite from scratch
- [ ] 4/5 - Could modify confidently
- [ ] 3/5 - Could maintain with docs
- [ ] 2/5 - Need help to modify
- [ ] 1/5 - Only know it "works"

**Score < 3 = STOP** - Cannot proceed until comprehension improves
```

### Integration with Workflow

Add to your prompts:

```
"After implementation:
1. Run tests: [command]
2. Self-review: Critique your code as a skeptical reviewer
3. List 3 improvements and 2 questions
4. Comprehension check: Can you explain this without looking?
5. STOP and show me results"
```

> **Reference**: `.ai/workflow/docs/COMPREHENSION_DEBT.md` for full methodology

---

## 🔬 Trust = Passing Test Suite

> **"Trust isn't a feeling, it's a passing test suite."**

### Don't Trust Claude's Word

**❌ Bad:**
```
You: "Did you test it?"
Claude: "Yes, I ran the tests and everything passes."
You: "Great!"
```

Claude **cannot** actually run tests. It's generating text.

### Verify Everything

**✅ Good:**
```
You: "Run the tests"
Claude: [shows you the command]
You: [Actually run the command yourself]
Terminal: ✓ 47 tests passing
You: "Confirmed, tests pass. Continue."
```

### Build Environmental Verification

Instead of trusting Claude, **design the environment** to verify automatically:

1. **Pre-commit hooks** - Run linters, formatters, tests
2. **CI/CD pipelines** - Automated testing on push
3. **Type checking** - TypeScript catches errors at compile time
4. **E2E tests** - Verify full user flows
5. **Code review** - Human verification of logic

---

## 📊 Checklist: Effective Prompts

Before sending a prompt to Claude, verify:

- [ ] **Specific**: Clear, concrete description of what to do
- [ ] **Context**: Relevant background, constraints, pitfalls
- [ ] **Reference**: Point to existing code patterns to follow
- [ ] **Steps**: Break down into concrete implementation steps
- [ ] **Verification**: Explicit steps to verify the result
- [ ] **Tools**: Specify which tools to use for validation
- [ ] **Tests**: Include test commands to run
- [ ] **Checkpoint**: STOP point before continuing
- [ ] **Success criteria**: Clear definition of "done"

---

## 🚀 Applying to Our Workflow System

### In Roles (backend.md, frontend.md, etc.)

Update prompts to Claude to include:
- References to existing code patterns
- Explicit verification steps
- Testing requirements
- Checkpoints

### In Workflows (YAML)

Add to each stage:
- `verification_steps`: Required checks before marking COMPLETED
- `reference_files`: Specific files to follow as patterns
- `testing_requirements`: Tests that must pass
- `checkpoint`: When to stop and wait for human verification

### In Practice

When using `tilix_start.sh`, each role gets a prompt that:
- ✅ References specific files
- ✅ Lists verification steps
- ✅ Requires tests to pass
- ✅ Has checkpoints for review

---

## 💡 Key Takeaways

1. **Be directive, not passive** - Give clear instructions
2. **Include verification in every prompt** - Don't trust without testing
3. **Reference existing patterns** - Show Claude what "good" looks like
4. **Break into checkpoints** - Verify incrementally, not all at once
5. **Specify tools** - Tell Claude exactly how to verify
6. **Trust = Passing tests** - The environment validates, not feelings
7. **Avoid the speed trap** - Don't let Claude generate faster than you can verify

---

**Remember**: Claude is like a 10x colleague who needs clear direction. Your job is to provide vision, specify patterns, and verify results. Success depends on **how you lead**, not just on Claude's capabilities.

---

**Next**: See updated role files for examples of these patterns in practice.
