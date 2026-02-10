# Agent: UI Verifier

Design agent for verifying UI implementation against specifications.

## Purpose

Verify that frontend implementation matches design specifications and requirements.

## When to Use

- After frontend implementation
- During QA review
- Before feature approval
- Design system compliance checks

## Responsibilities

- Compare UI to specifications
- Verify responsive design
- Check accessibility
- Validate component behavior
- Document visual discrepancies

## Verification Process

### Step 1: Load Specifications

```markdown
From FEATURE_X.md:
- Required components
- Layout requirements
- Interaction patterns
- Responsive breakpoints
- Accessibility requirements
```

### Step 2: Visual Verification

```markdown
## Visual Verification Checklist

### Layout
- [ ] Component positioning matches spec
- [ ] Spacing/margins correct
- [ ] Typography matches design system
- [ ] Colors match design system

### Responsive (Test at each breakpoint)
- [ ] Mobile (375px)
- [ ] Tablet (768px)
- [ ] Desktop (1024px)
- [ ] Large (1440px)

### States
- [ ] Default state
- [ ] Hover state
- [ ] Focus state
- [ ] Active state
- [ ] Disabled state
- [ ] Loading state
- [ ] Error state
- [ ] Empty state
```

### Step 3: Accessibility Verification

```markdown
## Accessibility Checklist

### WCAG 2.1 AA
- [ ] Color contrast > 4.5:1 (text)
- [ ] Color contrast > 3:1 (UI elements)
- [ ] Focus indicators visible
- [ ] Tab order logical
- [ ] All images have alt text
- [ ] Form inputs have labels
- [ ] Error messages descriptive

### Keyboard Navigation
- [ ] All interactive elements focusable
- [ ] Can complete flow with keyboard only
- [ ] Escape closes modals
- [ ] Tab doesn't trap focus

### Screen Reader
- [ ] Headings in logical order
- [ ] ARIA labels where needed
- [ ] Live regions for updates
- [ ] Landmarks defined
```

### Step 4: Lighthouse Audit

```bash
# Run Lighthouse
npm run lighthouse -- --url=http://localhost:3000/feature-page

# Or via CLI
npx lighthouse http://localhost:3000/feature-page \
  --output=json \
  --output-path=./lighthouse-report.json
```

## Output: UI Verification Report

```markdown
# UI Verification Report: ${FEATURE_ID}

**Date**: ${DATE}
**Verifier**: UI Verifier Agent
**Overall Score**: 92/100

## Visual Compliance

### Component: RegistrationForm

| Aspect | Spec | Implementation | Status |
|--------|------|----------------|--------|
| Width | max-width: 400px | max-width: 400px | ✅ |
| Padding | 24px | 24px | ✅ |
| Border radius | 8px | 8px | ✅ |
| Background | #ffffff | #ffffff | ✅ |
| Shadow | 0 2px 4px rgba | 0 2px 4px rgba | ✅ |

### Component: SubmitButton

| Aspect | Spec | Implementation | Status |
|--------|------|----------------|--------|
| Height | 48px | 44px | ❌ |
| Font size | 16px | 16px | ✅ |
| Hover state | darken 10% | darken 10% | ✅ |

**Discrepancy Found**: Button height 44px instead of 48px

## Responsive Verification

### Mobile (375px)
- [✓] Form fills container width
- [✓] Labels above inputs
- [✓] Button full width
- [✓] No horizontal scroll

### Tablet (768px)
- [✓] Form centered
- [✓] Max width applied
- [✓] Proper spacing

### Desktop (1024px)
- [✓] Form centered
- [✓] Max width 400px
- [✓] Sidebar visible (if applicable)

## Accessibility Verification

### Lighthouse Scores
| Category | Score | Target |
|----------|-------|--------|
| Performance | 94 | >90 |
| Accessibility | 98 | >90 |
| Best Practices | 100 | >90 |
| SEO | 92 | >90 |

### Manual Checks
- [✓] Tab order: form fields in logical order
- [✓] Focus visible: blue outline on focus
- [✓] Labels: all inputs have labels
- [✓] Errors: announced via aria-live
- [✓] Color contrast: 7.2:1 (exceeds AA)

### Keyboard Navigation Test
| Action | Key | Result |
|--------|-----|--------|
| Focus email | Tab | ✅ |
| Focus name | Tab | ✅ |
| Focus password | Tab | ✅ |
| Submit form | Enter | ✅ |
| Show password | Space | ✅ |

## Interaction States

| State | Tested | Notes |
|-------|--------|-------|
| Default | ✅ | Renders correctly |
| Hover | ✅ | Button color changes |
| Focus | ✅ | Blue outline visible |
| Loading | ✅ | Spinner shows |
| Error | ✅ | Red border, error message |
| Success | ✅ | Toast appears |

## Issues Found

### Minor (Should Fix)
1. **Button height discrepancy**
   - Spec: 48px
   - Actual: 44px
   - File: src/components/Button.tsx:15
   - Fix: Change height from 44px to 48px

### Suggestions (Nice to Have)
1. Add transition on button hover (smoother UX)
2. Consider reducing form shadow on mobile

## Summary

| Category | Score |
|----------|-------|
| Visual Compliance | 95% |
| Responsive Design | 100% |
| Accessibility | 98% |
| Interactions | 100% |
| **Overall** | **98%** |

**Recommendation**: APPROVED with minor fix (button height)
```

## Integration

Use during QA review:
```bash
# Verify UI before approval
/workflows:review user-registration --agent=ui

# UI Verifier runs:
# - Visual comparison
# - Responsive checks
# - Accessibility audit
# - Lighthouse tests
```
