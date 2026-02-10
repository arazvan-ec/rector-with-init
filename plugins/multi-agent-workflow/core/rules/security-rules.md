# Security & Trust Model Rules

Rules that apply when working with security-sensitive code and trust boundaries.

**Applies to**: Authentication (`auth/`, `security/`), payments (`payment/`, `billing/`), user data handling, API authorization, and encryption-related code.

---

## Trust Model (Supervision Calibration)

The amount of supervision depends on three factors:

```
FAMILIARITY → TRUST → CONTROL
```

### Control Levels

| Level | When to Apply | What It Means |
|-------|---------------|---------------|
| HIGH | New technology, critical code, first feature of a type | Review each step, frequent checkpoints, pair review |
| MEDIUM | Known technology, established patterns | Review at main checkpoints, tests required |
| LOW | Similar to previous features, high confidence | Final review, trust automated tests |

### Decision Matrix

| Situation | Familiarity | Control Level |
|-----------|-------------|---------------|
| First auth feature | Low | HIGH |
| Second auth feature (same pattern) | High | MEDIUM |
| Tenth similar CRUD | High | LOW |
| New external API integration | Low | HIGH |
| Refactor of known code | High | LOW |
| Any feature with security implications | Variable | Always HIGH |

### The Last-Mile Problem

AI helps reach 70% quickly, but the remaining 30% (edge cases, security, integration) needs careful attention.

- Initial 70% can proceed with LOW control
- Final 30% needs HIGH control
- Adjust supervision as the feature progresses

## Security Prohibitions

- Do not store secrets in code or configuration files committed to git
- Do not bypass authentication or authorization checks
- Do not log sensitive data (passwords, tokens, PII)
- Do not use user input in queries without sanitization
- Do not disable security headers or CORS without explicit approval

## Trust Enforcement via Hooks

Lifecycle hooks automatically enforce trust boundaries:

- `PreToolUse` blocks modifications to `auth/`, `security/`, `payment/` paths without pair review
- All actions in sensitive areas are logged to `.ai/logs/` for audit
- See `core/docs/LIFECYCLE_HOOKS.md` for hook configuration details
