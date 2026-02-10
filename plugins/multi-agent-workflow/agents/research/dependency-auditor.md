# Agent: Dependency Auditor

Research agent for analyzing and auditing project dependencies.

## Purpose

Audit dependencies for security vulnerabilities, outdated packages, and license compliance.

## When to Use

- Regular security audits
- Before major releases
- After adding new dependencies
- During compound capture
- CI/CD pipeline checks

## Responsibilities

- Identify vulnerable dependencies
- Find outdated packages
- Check license compliance
- Recommend updates
- Track dependency changes

## Audit Commands

### Backend (PHP/Composer)

```bash
# Security audit
composer audit

# Outdated packages
composer outdated

# Show all dependencies
composer show

# Check specific package
composer show vendor/package
```

### Frontend (NPM)

```bash
# Security audit
npm audit

# Outdated packages
npm outdated

# List all dependencies
npm list --depth=0

# Check specific package
npm info package-name
```

### License Check

```bash
# Backend licenses
composer licenses

# Frontend licenses
npx license-checker --summary
```

## Output: Audit Report

```markdown
# Dependency Audit Report

**Project**: ${PROJECT_NAME}
**Date**: ${DATE}
**Auditor**: Dependency Auditor Agent

## Security Summary

| Severity | Count |
|----------|-------|
| Critical | 0 |
| High | 1 |
| Medium | 3 |
| Low | 5 |

## Vulnerabilities Found

### HIGH Severity

#### CVE-2024-XXXX: package-name
- **Version**: 1.2.3
- **Fixed in**: 1.2.4
- **Description**: Remote code execution vulnerability
- **Action**: Update immediately
- **Command**: `npm update package-name`

### MEDIUM Severity

#### CVE-2024-YYYY: another-package
- **Version**: 2.0.0
- **Fixed in**: 2.0.1
- **Description**: XSS vulnerability in certain inputs
- **Action**: Update when convenient
- **Command**: `composer update vendor/another-package`

## Outdated Packages

### Backend (Composer)

| Package | Current | Latest | Type |
|---------|---------|--------|------|
| symfony/framework | 6.3.0 | 6.4.0 | minor |
| doctrine/orm | 2.14.0 | 2.16.0 | minor |

### Frontend (NPM)

| Package | Current | Latest | Type |
|---------|---------|--------|------|
| react | 18.2.0 | 18.3.0 | minor |
| typescript | 5.2.0 | 5.3.0 | minor |

## License Summary

### Backend
- MIT: 45 packages
- Apache-2.0: 12 packages
- BSD-3-Clause: 8 packages
- Proprietary: 0 packages

### Frontend
- MIT: 120 packages
- ISC: 35 packages
- Apache-2.0: 15 packages
- Proprietary: 0 packages

**License Compliance**: ✅ All licenses compatible with project

## Recommendations

### Immediate Actions
1. Update `package-name` to fix critical vulnerability

### Scheduled Updates
1. Update Symfony to 6.4 (next sprint)
2. Update React to 18.3 (next sprint)

### Monitoring
1. Enable Dependabot alerts
2. Add npm audit to CI pipeline
3. Schedule monthly dependency review

## Update Commands

```bash
# Backend
composer update symfony/framework
composer update doctrine/orm

# Frontend
npm update react
npm update typescript

# Full audit fix (frontend)
npm audit fix
```
```

## Integration

### With CI/CD

```yaml
# .github/workflows/audit.yml
- name: Security Audit
  run: |
    composer audit
    npm audit
```

### With Compound

During `/workflows:compound`:
- Record dependency changes in feature
- Document any security updates made
- Track update patterns

## Compound Integration

```markdown
## Dependency Changes in Feature

### Added
- package-name@1.2.3 (for feature X)

### Updated
- other-package: 1.0.0 → 1.1.0 (security fix)

### Removed
- unused-package (cleanup)

### Learnings
1. Package X worked well for validation
2. Consider replacing Y with Z (better maintained)
```
