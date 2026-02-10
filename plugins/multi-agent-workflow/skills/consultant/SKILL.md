---
name: consultant
description: "Intelligent project analysis to build comprehensive project knowledge and recommend optimal workflows. Runs 7-layer deep analysis. Use via /workflows:discover or directly."
model: opus
context: fork
hooks:
  Stop:
    - command: "echo '[consultant] Analysis complete. Results saved to .ai/project/intelligence/'"
---

# AI Consultant Skill

Intelligent project analysis to build comprehensive project knowledge and recommend optimal workflows.

## What This Skill Does

- Detects project type, frameworks, and full tech stack
- Analyzes codebase structure, architecture patterns, and complexity
- Identifies code conventions and naming patterns
- Discovers reference implementations for templates
- Analyzes git history for development patterns
- Generates comprehensive project profile
- Recommends appropriate workflow configurations
- Creates project-specific configuration files

## When to Use

- **New project setup**: Initialize workflow for a new codebase via `/workflows:discover`
- **First time with workflow**: Get recommendations for existing project
- **After major changes**: Refresh project knowledge after refactors or new dependencies
- **Onboarding**: Understand project structure quickly
- **Switching workflows**: Determine if different workflow fits better

## Integration

This skill is the engine behind `/workflows:discover`. It can also be invoked directly:

```
/multi-agent-workflow:consult
/multi-agent-workflow:consult --interactive
/multi-agent-workflow:consult --deep
```

## Analysis Process

The consultant performs a **7-layer analysis**:

### Layer 1: Stack Detection

```markdown
## Tech Stack Detection

Scanning configuration files...

| File | Detection |
|------|-----------|
| package.json | ✅ Node.js 20, React 18, TypeScript 5.3 |
| tsconfig.json | ✅ Strict mode, ESNext target |
| prisma/schema.prisma | ✅ Prisma ORM, PostgreSQL |
| docker-compose.yml | ✅ Docker, Redis, PostgreSQL |
| .github/workflows/ | ✅ GitHub Actions CI/CD |

**Stack Summary**: Node.js + React + TypeScript + Prisma + PostgreSQL
```

### Layer 2: Architecture Pattern Detection

```markdown
## Architecture Analysis

Scanning directory structure...

| Pattern | Indicators | Confidence |
|---------|------------|------------|
| DDD | domain/, application/, infrastructure/ | 95% ✅ |
| Clean Architecture | entities/, usecases/ | 0% |
| MVC | models/, views/, controllers/ | 10% |
| Feature-based | Feature folders detected | 30% |

**Primary Pattern**: Domain-Driven Design (DDD)

### Layer Mapping
| DDD Layer | Path | Health |
|-----------|------|--------|
| Domain | src/domain/ | ✅ 15 entities, 8 value objects |
| Application | src/application/ | ✅ 12 use cases |
| Infrastructure | src/infrastructure/ | ✅ Repositories, adapters |
| Presentation | src/presentation/ | ✅ Controllers, DTOs |
```

### Layer 3: Code Convention Analysis

```markdown
## Code Conventions

### Naming Patterns (sampled from 50 files)
| Element | Pattern | Consistency |
|---------|---------|-------------|
| Files | kebab-case | 98% |
| Classes | PascalCase | 100% |
| Functions | camelCase | 97% |
| Constants | UPPER_SNAKE | 85% |
| Interfaces | No I-prefix | 100% |

### Style Configuration
| Setting | Value | Source |
|---------|-------|--------|
| Indent | 2 spaces | .editorconfig |
| Quotes | single | .prettierrc |
| Semicolons | false | .prettierrc |
| Line length | 100 | .prettierrc |
```

### Layer 4: Reference Implementation Discovery

```markdown
## Reference Implementations

Best examples found for each pattern:

| Pattern | Best Example | Quality Score |
|---------|--------------|---------------|
| Entity | src/domain/entities/User.ts | 95/100 |
| Value Object | src/domain/value-objects/Email.ts | 92/100 |
| Repository | src/infrastructure/repositories/UserRepository.ts | 88/100 |
| Use Case | src/application/use-cases/CreateUser.ts | 90/100 |
| Controller | src/presentation/controllers/UserController.ts | 85/100 |
| Component | src/components/UserProfile/index.tsx | 87/100 |
| Test | src/domain/entities/__tests__/User.test.ts | 91/100 |

These files will be used as templates for new implementations.
```

### Layer 5: Git Pattern Analysis

```markdown
## Git History Analysis

### Repository Stats
| Metric | Value |
|--------|-------|
| Commits | 1,247 |
| Contributors | 5 |
| Age | 18 months |
| Active branches | 3 |

### Development Patterns
| Pattern | Detected | Compliance |
|---------|----------|------------|
| Conventional Commits | ✅ | 94% |
| Feature branches | ✅ | 100% |
| TDD (test-first) | ⚠️ | 62% |
| Code review | ✅ | Via PRs |

### Hotspots (high change frequency)
| File | Changes | Risk Level |
|------|---------|------------|
| src/domain/entities/Order.ts | 47 | High |
| src/application/services/PaymentService.ts | 38 | Medium |
```

### Layer 6: Complexity Assessment

```markdown
## Complexity Analysis

### Metrics
| Metric | Value | Assessment |
|--------|-------|------------|
| Total files | 342 | Medium |
| Lines of code | 45,000 | Medium |
| Cyclomatic complexity avg | 4.2 | Good |
| Test coverage | 76% | Good |
| Dependencies | 127 | Medium |
| Dev dependencies | 45 | Normal |

### Complexity Score: MEDIUM (62/100)

**Factors**:
- ✅ Clear architecture (+15)
- ✅ Good test coverage (+12)
- ⚠️ Some complex services (-8)
- ⚠️ Large dependency tree (-7)
```

### Layer 7: Workflow Recommendation

```markdown
## Workflow Recommendation

Based on analysis:

| Factor | Finding | Impact |
|--------|---------|--------|
| Architecture | DDD established | Use existing patterns |
| Complexity | Medium | Standard workflow |
| Coverage | 76% | Maintain TDD |
| Team patterns | Conventional commits | Enforce in hooks |

**Recommended Workflow**: `standard` (4-phase)

**Configuration Adjustments**:
- Enable SOLID analysis (architecture present)
- Set coverage threshold: 75%
- Enforce conventional commits
- Enable parallel backend/frontend development
```

## Workflow Recommendations

### `standard` - Recommended when:
- Project has both backend and frontend
- Medium to large features
- Clear architecture exists
- Test infrastructure in place
- ~1-2 weeks of work

### `task-breakdown` - Recommended when:
- Complex, multi-week features
- Need detailed estimation
- Distributed team
- High documentation requirements
- New team members joining

### `implementation-only` - Recommended when:
- Planning already done externally
- Simple bug fixes
- Small, well-defined changes
- Hot fixes

### `research-heavy` - Recommended when:
- New technology integration
- Performance optimization
- Security hardening
- Unknown problem domain

## Generated Output Files

The consultant generates multiple files in `.ai/project/`:

### 1. Project Profile (`.ai/project/intelligence/project-profile.md`)

Comprehensive project knowledge document containing:
- Quick facts (type, stack, architecture, complexity)
- Full tech stack details
- Architecture overview with diagrams
- Code conventions
- Reference implementations
- Known patterns and anti-patterns
- Project health assessment
- Workflow recommendations
- Quick commands

### 2. Configuration (`.ai/project/config.yaml`)

```yaml
project:
  name: "my-app"
  type: "web-app"
  description: "E-commerce platform"

backend:
  framework: "express"
  language: "typescript"
  path: "src/"

frontend:
  framework: "react"
  language: "typescript"
  path: "client/"

database:
  type: "postgresql"
  orm: "prisma"

architecture:
  pattern: "ddd"
  layers:
    - name: domain
      path: src/domain
    - name: application
      path: src/application
    - name: infrastructure
      path: src/infrastructure

testing:
  framework: "jest"
  coverage_target: 80
  locations:
    unit: "**/*.test.ts"
    integration: "tests/integration/"

conventions:
  commits: conventional
  branches: "feature/*"
  file_naming: kebab-case

ai_analysis:
  last_scan: "2026-02-03T10:30:00Z"
  complexity: "medium"
  reference_files:
    entity: "src/domain/entities/User.ts"
    repository: "src/infrastructure/repositories/UserRepository.ts"
    service: "src/application/services/AuthService.ts"
```

### 3. Context Summary (`.ai/project/context.md`)

Quick reference for agents:

```markdown
# Project Context

## Quick Reference
- **Stack**: Node.js + React + TypeScript + Prisma + PostgreSQL
- **Architecture**: DDD (Domain-Driven Design)
- **Complexity**: Medium

## Reference Files
Use these as templates:
- Entity: `src/domain/entities/User.ts`
- Repository: `src/infrastructure/repositories/UserRepository.ts`
- Service: `src/application/services/AuthService.ts`
- Component: `src/components/UserProfile/index.tsx`

## Conventions
- Files: kebab-case
- Classes: PascalCase
- Tests: `*.test.ts` next to source
- Commits: conventional (feat:, fix:, etc.)

## Commands
- Dev: `npm run dev`
- Test: `npm test`
- Build: `npm run build`
```

## Interactive Mode

With `--interactive`, the consultant asks targeted questions:

1. What type of project is this? (web app, API, library, CLI, monorepo)
2. What's your primary programming language?
3. Do you follow a specific architecture pattern? (DDD, Clean, MVC, etc.)
4. What's the approximate size? (small <10k LOC, medium <50k, large <100k, very large)
5. Is there existing documentation or patterns to follow?
6. What's the team size and structure?
7. Any specific constraints or requirements?

Based on answers, generates tailored configuration and recommendations.

## Deep Mode

With `--deep`, performs additional analysis:

- Full dependency tree audit
- Security vulnerability scan
- Performance pattern detection
- Dead code identification
- Circular dependency detection
- Import/export analysis
- Test coverage gaps
- Documentation coverage

## Integration with /workflows:discover

The consultant skill is invoked by `/workflows:discover` with appropriate flags:

| Command | Consultant Invocation |
|---------|----------------------|
| `/workflows:discover --full` | `consult --deep` |
| `/workflows:discover --refresh` | `consult --incremental` |
| `/workflows:discover --report` | Read existing profile |

## Auto-Refresh

When `ai_analysis.auto_refresh: true` in config, consultant automatically runs when:
- `package.json` or equivalent changes
- New directories appear in watched paths
- 7 days since last scan
- Before `/workflows:plan` if profile is stale
