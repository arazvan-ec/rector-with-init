---
name: workflows:discover
description: "Deep project analysis to build comprehensive knowledge. Scans codebase, detects patterns, and creates project profile."
argument_hint: [--full | --refresh | --report]
---

# /workflows:discover - Descubrimiento Profundo del Proyecto

**Version**: 1.0.0
**Category**: Setup & Analysis
**Priority**: Run once after installation, then periodically

---

## Purpose

Analiza el proyecto en profundidad para construir un conocimiento completo que permita al plugin trabajar de manera más efectiva. Genera un perfil del proyecto con toda la información relevante.

## When to Use

| Situación | Recomendación |
|-----------|---------------|
| Primera instalación del plugin | `/workflows:discover --full` |
| Después de cambios mayores (nueva librería, refactor) | `/workflows:discover --refresh` |
| Ver resumen del conocimiento actual | `/workflows:discover --report` |
| Antes de planificar feature compleja | Automático en `/workflows:plan` |
| Update specs after domain model changes | `/workflows:discover --specs-only` |
| Deep analysis of business rules | `/workflows:discover --specs-only --specs-depth=deep` |
| Generate API documentation baseline | `/workflows:discover --specs-only` then review `api-contracts/` |

## Invocation

```bash
# Análisis completo (primera vez)
/workflows:discover --full

# Refrescar conocimiento existente
/workflows:discover --refresh

# Solo mostrar reporte del conocimiento actual
/workflows:discover --report

# Por defecto: análisis inteligente (detecta qué necesita actualizar)
/workflows:discover

# Spec extraction examples
/workflows:discover --specs-only           # Only extract specs, skip profile
/workflows:discover --no-extract-specs     # Full discovery without spec extraction
/workflows:discover --specs-depth=deep     # Deep spec extraction (includes invariants, lifecycle)
/workflows:discover --full --specs-only    # Force re-extract all specs from scratch
```

## Execution Protocol

### Step 1: Create Project Intelligence Directory

```bash
mkdir -p .ai/project/intelligence
mkdir -p .ai/project/specs/entities
mkdir -p .ai/project/specs/api-contracts
mkdir -p .ai/project/specs/business-rules
mkdir -p .ai/project/specs/architectural-constraints
```

### Step 2: Detect Project Type and Stack

Scan for configuration files and detect:

```markdown
## Detección de Stack

Escaneando archivos de configuración...

### Backend Detection
| Archivo | Framework/Lenguaje |
|---------|-------------------|
| `package.json` → Node.js, dependencias |
| `composer.json` → PHP, Symfony/Laravel |
| `requirements.txt` / `pyproject.toml` → Python |
| `go.mod` → Go |
| `Cargo.toml` → Rust |
| `pom.xml` / `build.gradle` → Java |
| `.csproj` → .NET |

### Frontend Detection
| Archivo | Framework |
|---------|-----------|
| `package.json` → React/Vue/Angular/Svelte |
| `tsconfig.json` → TypeScript |
| `vite.config.*` → Vite |
| `next.config.*` → Next.js |
| `nuxt.config.*` → Nuxt |

### Infrastructure Detection
| Archivo | Tecnología |
|---------|------------|
| `docker-compose.yml` → Docker |
| `Dockerfile` → Containerization |
| `.github/workflows/` → GitHub Actions |
| `terraform/` → Infrastructure as Code |
| `k8s/` / `kubernetes/` → Kubernetes |
```

### Step 3: Analyze Project Structure

```markdown
## Análisis de Estructura

### Directory Map
```
[Generar árbol de directorios hasta 3 niveles]
```

### Architecture Pattern Detection

| Patrón | Indicadores | Detectado |
|--------|-------------|-----------|
| **DDD (Domain-Driven Design)** | `domain/`, `application/`, `infrastructure/` | ✓/✗ |
| **Clean Architecture** | `entities/`, `usecases/`, `adapters/` | ✓/✗ |
| **MVC** | `models/`, `views/`, `controllers/` | ✓/✗ |
| **Hexagonal** | `ports/`, `adapters/` | ✓/✗ |
| **Atomic Design** | `atoms/`, `molecules/`, `organisms/` | ✓/✗ |
| **Feature-based** | Feature folders with all concerns | ✓/✗ |

### Layer Analysis (if DDD/Clean detected)
- Domain Layer: [path and health]
- Application Layer: [path and health]
- Infrastructure Layer: [path and health]
- Presentation Layer: [path and health]
```

### Step 4: Entity Specification Extraction

> **Agent**: `spec-extractor` | **Mode**: Entity Analysis

Extract domain entities and their specifications from the codebase:

```markdown
## Entity Specification Extraction

Scanning for domain entities and models...

### Detected Entities
| Entity | Location | Type | Attributes | Relations |
|--------|----------|------|------------|-----------|
| [EntityName] | `src/domain/entities/` | Aggregate/Entity/VO | X attrs | X relations |

### Entity Specification Format
For each entity, generate `.ai/project/specs/entities/[entity-name].yaml`:

```yaml
entity:
  name: "[EntityName]"
  type: "[aggregate|entity|value-object]"
  description: "[Purpose and business meaning]"

  attributes:
    - name: "[attr_name]"
      type: "[type]"
      required: true|false
      validation: "[rules]"
      description: "[meaning]"

  relations:
    - target: "[RelatedEntity]"
      type: "[one-to-one|one-to-many|many-to-many]"
      cardinality: "[0..1|1|0..*|1..*]"
      description: "[relationship meaning]"

  invariants:
    - "[Business rule that must always hold]"

  lifecycle:
    states: ["created", "active", "archived"]
    transitions:
      - from: "created"
        to: "active"
        trigger: "[event/action]"

  source_files:
    - "[path/to/entity.ts]"

  extracted_at: "[timestamp]"
```

### Extraction Depth
- **Surface**: Class/interface names and basic types
- **Standard**: Attributes, relations, basic validations
- **Deep**: Invariants, lifecycle, business rules embedded in code
```

### Step 5: API Contract Extraction

> **Agent**: `spec-extractor` | **Mode**: API Analysis

Extract API contracts and endpoint specifications:

```markdown
## API Contract Extraction

Scanning for API definitions...

### Sources Analyzed
| Source | Type | Endpoints Found |
|--------|------|-----------------|
| `routes/` | Express/Fastify routes | X |
| `controllers/` | Controller methods | X |
| `openapi.yaml` | OpenAPI spec | X |
| `*.graphql` | GraphQL schemas | X |

### Contract Specification Format
For each API group, generate `.ai/project/specs/api-contracts/[api-name].yaml`:

```yaml
api_contract:
  name: "[API Group Name]"
  version: "[detected version]"
  base_path: "/api/v1/[resource]"
  description: "[Purpose]"

  endpoints:
    - method: "GET|POST|PUT|PATCH|DELETE"
      path: "/[path]"
      description: "[what it does]"

      parameters:
        - name: "[param]"
          in: "path|query|header|body"
          type: "[type]"
          required: true|false
          validation: "[rules]"

      request:
        content_type: "application/json"
        schema:
          $ref: "#/schemas/[RequestDTO]"

      responses:
        - status: 200
          description: "Success"
          schema:
            $ref: "#/schemas/[ResponseDTO]"
        - status: 400
          description: "Validation error"
        - status: 401
          description: "Unauthorized"

      authentication: "[jwt|api-key|none]"
      rate_limit: "[if detected]"

  schemas:
    [SchemaName]:
      type: object
      properties:
        [field]: { type: "[type]" }

  source_files:
    - "[path/to/controller.ts]"
    - "[path/to/routes.ts]"

  extracted_at: "[timestamp]"
```

### Detection Methods
- Route decorator parsing (@Get, @Post, etc.)
- OpenAPI/Swagger file parsing
- GraphQL schema introspection
- Express/Fastify route analysis
- Controller method signature analysis
```

### Step 6: Business Rule Extraction

> **Agent**: `spec-extractor` | **Mode**: Business Rule Analysis

Extract business rules and domain logic:

```markdown
## Business Rule Extraction

Analyzing domain logic for business rules...

### Rule Sources
| Source | Location | Rules Found |
|--------|----------|-------------|
| Domain Services | `src/domain/services/` | X |
| Entity Methods | `src/domain/entities/` | X |
| Validators | `src/domain/validators/` | X |
| Specifications | `src/domain/specifications/` | X |
| Policy Classes | `src/domain/policies/` | X |

### Business Rule Specification Format
Generate `.ai/project/specs/business-rules/[domain-area].yaml`:

```yaml
business_rules:
  domain_area: "[Area Name]"
  description: "[What this area covers]"

  rules:
    - id: "BR-[AREA]-001"
      name: "[Human-readable name]"
      description: "[What the rule enforces]"

      type: "[validation|calculation|authorization|workflow|constraint]"

      condition:
        when: "[trigger condition]"
        given: "[preconditions]"
        then: "[expected outcome]"

      implementation:
        location: "[file path]"
        method: "[method name]"
        pattern: "[specification|policy|validator|guard]"

      exceptions:
        - "[when rule doesn't apply]"

      related_entities:
        - "[Entity1]"
        - "[Entity2]"

      test_coverage:
        unit_tests: "[path to tests]"
        scenarios_tested: X

      priority: "[critical|high|medium|low]"

  cross_cutting_rules:
    - id: "BR-CROSS-001"
      name: "[Rule spanning multiple areas]"
      affected_areas: ["Area1", "Area2"]
      description: "[rule description]"

  source_files:
    - "[path/to/service.ts]"

  extracted_at: "[timestamp]"
```

### Rule Detection Heuristics
- Guard clauses and validation logic
- if/throw patterns with business messages
- Specification pattern implementations
- Policy class decisions
- Domain event triggers
```

### Step 7: Architectural Constraint Extraction

> **Agent**: `spec-extractor` | **Mode**: Constraint Analysis

Extract architectural constraints and design decisions:

```markdown
## Architectural Constraint Extraction

Analyzing codebase for architectural constraints...

### Constraint Categories
| Category | Source | Constraints Found |
|----------|--------|-------------------|
| Layer Dependencies | Import analysis | X |
| Module Boundaries | Package structure | X |
| Design Patterns | Code patterns | X |
| Security Constraints | Auth/Authz code | X |
| Performance Constraints | Caching/optimization | X |

### Constraint Specification Format
Generate `.ai/project/specs/architectural-constraints/[category].yaml`:

```yaml
architectural_constraints:
  category: "[Category Name]"
  description: "[What these constraints govern]"

  layer_constraints:
    - name: "Domain Layer Purity"
      description: "Domain layer has no external dependencies"
      rule: "src/domain/** cannot import from src/infrastructure/**"
      enforcement: "[eslint-rule|architect-review|ci-check]"
      violations_found: X

    - name: "Dependency Direction"
      description: "Dependencies point inward"
      layers:
        - { name: "presentation", can_depend_on: ["application"] }
        - { name: "application", can_depend_on: ["domain"] }
        - { name: "domain", can_depend_on: [] }

  module_constraints:
    - name: "[Module Boundary]"
      description: "[What the boundary enforces]"
      modules:
        - name: "[ModuleName]"
          public_api: "[exposed interface]"
          internal: "[hidden implementation]"
          dependencies: ["[allowed modules]"]

  pattern_constraints:
    - name: "Repository Pattern"
      description: "All data access through repositories"
      applies_to: "src/domain/**"
      pattern: "Repository"
      anti_patterns: ["Direct DB calls in domain"]

    - name: "[Pattern Name]"
      applies_to: "[scope]"
      required: true|false

  security_constraints:
    - name: "[Security Constraint]"
      description: "[What it protects]"
      enforcement: "[how enforced]"
      scope: "[affected areas]"

  performance_constraints:
    - name: "[Performance Constraint]"
      description: "[What it optimizes]"
      threshold: "[metric]"
      implementation: "[how achieved]"

  technology_constraints:
    - name: "[Tech Constraint]"
      description: "[Why this constraint exists]"
      allowed: ["[tech1]", "[tech2]"]
      forbidden: ["[tech3]"]
      reason: "[rationale]"

  source_evidence:
    - "[path/to/architectural/decision]"

  extracted_at: "[timestamp]"
```

### Detection Methods
- Import/dependency graph analysis
- Package.json/tsconfig path mappings
- ESLint/architecture rules
- ADR (Architecture Decision Records) parsing
- Code pattern recognition
```

### Step 8: Generate Spec Manifest

Create `.ai/project/specs/spec-manifest.yaml` to index all extracted specs:

```yaml
# Auto-generated spec manifest
# Updated by /workflows:discover on [DATE]

manifest:
  version: "1.0"
  generated_at: "[timestamp]"
  project: "[project-name]"

  statistics:
    entities: X
    api_contracts: X
    business_rules: X
    architectural_constraints: X
    total_specs: X

  entities:
    - name: "[Entity1]"
      file: "entities/entity1.yaml"
      type: "aggregate"
      last_updated: "[timestamp]"

  api_contracts:
    - name: "[API1]"
      file: "api-contracts/api1.yaml"
      endpoints: X
      last_updated: "[timestamp]"

  business_rules:
    - domain_area: "[Area1]"
      file: "business-rules/area1.yaml"
      rules_count: X
      critical_rules: X
      last_updated: "[timestamp]"

  architectural_constraints:
    - category: "[Category1]"
      file: "architectural-constraints/category1.yaml"
      constraints_count: X
      last_updated: "[timestamp]"

  extraction_config:
    extract_specs: true
    depth: "standard"
    include_tests: false

  next_recommended_update: "[timestamp + 7 days]"
```

### Step 9: Scan Code Patterns (Skip if --specs-only)

```markdown
## Patrones de Código Detectados

### Naming Conventions
| Tipo | Patrón Detectado | Ejemplo |
|------|------------------|---------|
| Archivos | kebab-case / PascalCase / camelCase | `user-service.ts` |
| Clases | PascalCase | `UserService` |
| Funciones | camelCase | `getUserById` |
| Variables | camelCase | `userId` |
| Constantes | UPPER_SNAKE | `MAX_RETRIES` |
| Interfaces | I-prefix / no-prefix | `IUserRepository` |

### Code Style
- Indentation: [tabs/spaces, size]
- Quotes: [single/double]
- Semicolons: [yes/no]
- Line length: [detected max]
- Import style: [absolute/relative]

### Testing Patterns
| Tipo | Framework | Ubicación | Cobertura Estimada |
|------|-----------|-----------|-------------------|
| Unit | Jest/PHPUnit/pytest | `__tests__/`, `tests/` | X% |
| Integration | Supertest/etc | `tests/integration/` | X% |
| E2E | Playwright/Cypress | `e2e/`, `tests/e2e/` | X% |
```

### Step 10: Analyze Dependencies (Skip if --specs-only)

```markdown
## Análisis de Dependencias

### Core Dependencies
| Paquete | Versión | Propósito |
|---------|---------|-----------|
| [framework] | vX.X | Core framework |
| [orm] | vX.X | Database |
| [auth] | vX.X | Authentication |

### Dev Dependencies Relevantes
| Paquete | Propósito |
|---------|-----------|
| [test-framework] | Testing |
| [linter] | Code quality |
| [formatter] | Formatting |

### Potential Security Concerns
[Ejecutar audit si disponible]

### Dependency Graph Highlights
- Total dependencies: X
- Direct: X
- Transitive: X
- Outdated: X
```

### Step 11: Git History Analysis (Skip if --specs-only)

```markdown
## Análisis de Historia Git

### Repository Stats
| Métrica | Valor |
|---------|-------|
| Total commits | X |
| Contributors | X |
| First commit | YYYY-MM-DD |
| Last commit | YYYY-MM-DD |
| Active branches | X |

### Commit Patterns
| Patrón | Uso |
|--------|-----|
| Conventional Commits | ✓/✗ |
| Prefix style | feat:/fix:/etc |
| Typical commit size | X files, X lines |

### Hotspots (most changed files)
| Archivo | Cambios | Riesgo |
|---------|---------|--------|
| [file1] | X commits | Alto/Medio/Bajo |
| [file2] | X commits | Alto/Medio/Bajo |

### Recent Activity
[Últimos 10 commits relevantes]
```

### Step 12: Detect Existing Documentation (Skip if --specs-only)

```markdown
## Documentación Existente

| Archivo | Tipo | Estado |
|---------|------|--------|
| `README.md` | General | ✓/✗ |
| `CONTRIBUTING.md` | Contribution | ✓/✗ |
| `docs/` | Documentation folder | ✓/✗ |
| `API.md` | API docs | ✓/✗ |
| `CHANGELOG.md` | Change history | ✓/✗ |
| `.ai/` | AI context | ✓/✗ |

### Code Documentation
- JSDoc/TSDoc/PHPDoc coverage: X%
- README in key directories: X/Y
```

### Step 13: Generate Project Profile (Skip if --specs-only)

Create `.ai/project/intelligence/project-profile.md`:

```markdown
# Project Profile: [Project Name]

> Auto-generated by /workflows:discover on [DATE]
> Last updated: [TIMESTAMP]

---

## Quick Facts

| Attribute | Value |
|-----------|-------|
| **Type** | [Web App / API / Library / CLI / Monorepo] |
| **Primary Language** | [TypeScript / PHP / Python / etc] |
| **Framework** | [React + Node / Symfony / Django / etc] |
| **Architecture** | [DDD / MVC / Clean / Monolith / Microservices] |
| **Database** | [PostgreSQL / MySQL / MongoDB / etc] |
| **Complexity** | [Low / Medium / High / Very High] |
| **Test Coverage** | [X%] |
| **Team Size** | [Solo / Small / Medium / Large] (inferred from git) |

---

## Tech Stack

### Backend
- **Runtime**: [Node.js 20 / PHP 8.3 / Python 3.12]
- **Framework**: [Express / NestJS / Symfony / FastAPI]
- **ORM**: [Prisma / TypeORM / Doctrine / SQLAlchemy]
- **Auth**: [JWT / Session / OAuth]

### Frontend
- **Framework**: [React 18 / Vue 3 / Angular 17]
- **State**: [Redux / Zustand / Pinia]
- **Styling**: [Tailwind / CSS Modules / Styled Components]
- **Build**: [Vite / Webpack / Turbopack]

### Infrastructure
- **Container**: [Docker / Podman]
- **CI/CD**: [GitHub Actions / GitLab CI / Jenkins]
- **Cloud**: [AWS / GCP / Azure / Vercel]
- **Monitoring**: [DataDog / Sentry / etc]

---

## Architecture Overview

### Structure
```
[Simplified directory tree with annotations]
```

### Patterns in Use
- [Pattern 1]: Used in [location], purpose: [why]
- [Pattern 2]: Used in [location], purpose: [why]

### Key Abstractions
| Abstraction | Location | Purpose |
|-------------|----------|---------|
| Repository | `src/infrastructure/repositories/` | Data access |
| Service | `src/application/services/` | Business logic |
| Controller | `src/presentation/controllers/` | HTTP handling |

---

## Code Conventions

### Naming
```typescript
// Files: kebab-case
user-service.ts

// Classes: PascalCase
class UserService {}

// Functions: camelCase
function getUserById() {}

// Interfaces: PascalCase (no I-prefix)
interface UserRepository {}
```

### Style Rules
- Indent: 2 spaces
- Quotes: single
- Semicolons: no
- Max line: 100 chars
- Imports: absolute paths from `src/`

### Testing Convention
- Unit tests: `*.test.ts` next to source
- Integration: `tests/integration/`
- E2E: `e2e/`
- Coverage target: 80%

---

## Reference Implementations

When implementing new features, use these as templates:

### Entity Example
→ `src/domain/entities/User.ts`

### Repository Example
→ `src/infrastructure/repositories/UserRepository.ts`

### Service Example
→ `src/application/services/AuthService.ts`

### Controller Example
→ `src/presentation/controllers/UserController.ts`

### Component Example
→ `src/components/UserProfile/UserProfile.tsx`

### Test Example
→ `src/domain/entities/User.test.ts`

---

## Known Patterns & Anti-Patterns

### Patterns to Follow
1. **[Pattern Name]**: [Description and when to use]
2. **[Pattern Name]**: [Description and when to use]

### Anti-Patterns to Avoid
1. **[Anti-Pattern]**: [Why and what to do instead]
2. **[Anti-Pattern]**: [Why and what to do instead]

---

## Project Health

### Strengths
- ✅ [Strength 1]
- ✅ [Strength 2]

### Areas for Improvement
- ⚠️ [Area 1]: [Recommendation]
- ⚠️ [Area 2]: [Recommendation]

### Technical Debt
| Item | Severity | Location |
|------|----------|----------|
| [Debt item] | High/Med/Low | [File/area] |

---

## Workflow Recommendations

Based on this project's characteristics:

| Task Type | Recommended Workflow | Why |
|-----------|---------------------|-----|
| New Feature | `/workflows:plan` (full) | Complex architecture needs planning |
| Bug Fix | `/workflows:route` → work | Direct fix with review |
| Refactor | `/workflows:solid-refactor` | SOLID analysis first |
| Performance | Performance Review Agent | Specialized analysis |

### Trust Level by Area
| Area | Trust | Approach |
|------|-------|----------|
| Domain layer | High | Established patterns |
| New modules | Medium | Verify with reviewer |
| External integrations | Low | Extra review needed |

---

## Quick Commands for This Project

```bash
# Run tests
[detected test command]

# Start dev server
[detected dev command]

# Build
[detected build command]

# Lint
[detected lint command]

# Type check
[detected type check command]
```

---

*Profile regenerated automatically. Manual edits will be preserved in sections marked `<!-- CUSTOM -->`*
```

### Step 14: Generate Config if Missing (Skip if --specs-only)

If `.ai/project/config.yaml` doesn't exist, create it:

```yaml
# Auto-generated by /workflows:discover
# Manual edits are preserved on refresh

project:
  name: "[detected]"
  type: "[web-app|api|library|cli|monorepo]"
  description: "[from README or package.json]"

backend:
  framework: "[detected]"
  language: "[detected]"
  path: "[detected]"

frontend:
  framework: "[detected]"
  language: "[detected]"
  path: "[detected]"

database:
  type: "[detected]"
  orm: "[detected]"

architecture:
  pattern: "[ddd|mvc|clean|hexagonal|feature-based]"
  layers:
    - name: domain
      path: src/domain
    - name: application
      path: src/application
    - name: infrastructure
      path: src/infrastructure

testing:
  framework: "[detected]"
  coverage_target: 80
  locations:
    unit: "**/*.test.ts"
    integration: "tests/integration/"
    e2e: "e2e/"

conventions:
  commits: conventional
  branches: "[detected pattern]"
  file_naming: kebab-case

ai_analysis:
  last_scan: "[timestamp]"
  complexity: "[low|medium|high|very-high]"
  detected_patterns: []
  reference_files: {}

workflow:
  default: standard
  auto_refresh: true
  session_max_messages: 50
```

### Step 15: Display Summary

```markdown
╔══════════════════════════════════════════════════════════════════════╗
║                    Discovery Completado                              ║
╚══════════════════════════════════════════════════════════════════════╝

## Resumen del Proyecto

| Aspecto | Detectado |
|---------|-----------|
| **Nombre** | [project-name] |
| **Tipo** | [type] |
| **Stack** | [backend] + [frontend] |
| **Arquitectura** | [pattern] |
| **Complejidad** | [level] |

## Archivos Generados

✅ `.ai/project/intelligence/project-profile.md` - Perfil completo
✅ `.ai/project/config.yaml` - Configuración del proyecto
✅ `.ai/project/context.md` - Contexto para agentes

### Spec Files (if --extract-specs enabled)

✅ `.ai/project/specs/entities/*.yaml` - Entity specifications
✅ `.ai/project/specs/api-contracts/*.yaml` - API contract definitions
✅ `.ai/project/specs/business-rules/*.yaml` - Business rule documentation
✅ `.ai/project/specs/architectural-constraints/*.yaml` - Architecture constraints
✅ `.ai/project/specs/spec-manifest.yaml` - Spec index and metadata

## Conocimiento Capturado

- 📁 Estructura: [X] directorios mapeados
- 🔧 Patrones: [X] patrones detectados
- 📦 Dependencias: [X] analizadas
- 📝 Commits: [X] analizados
- 🎯 Referencias: [X] archivos template identificados

## Specs Extracted (if --extract-specs enabled)

| Spec Type | Count | Files Generated |
|-----------|-------|-----------------|
| **Entities** | [X] | `.ai/project/specs/entities/` |
| **API Contracts** | [X] | `.ai/project/specs/api-contracts/` |
| **Business Rules** | [X] | `.ai/project/specs/business-rules/` |
| **Architectural Constraints** | [X] | `.ai/project/specs/architectural-constraints/` |
| **Total Specs** | [X] | See `spec-manifest.yaml` |

> Specs extracted by `spec-extractor` agent. Run `/workflows:discover --specs-only` to update specs without full discovery.

## Próximos Pasos

1. **Revisar el perfil**: `cat .ai/project/intelligence/project-profile.md`
2. **Ajustar si necesario**: Edita `config.yaml` para correcciones
3. **Empezar a trabajar**: `/workflows:plan tu-feature`

> El plugin ahora tiene conocimiento profundo de tu proyecto.
> Este conocimiento se usa automáticamente en planning y reviews.
```

## Flags

| Flag | Description |
|------|-------------|
| `--full` | Análisis completo desde cero (sobrescribe) |
| `--refresh` | Actualiza solo lo que cambió |
| `--report` | Solo muestra el perfil actual sin re-escanear |
| `--quiet` | Menos output, solo errores |
| `--extract-specs` | Enable spec extraction (default: true) |
| `--no-extract-specs` | Disable spec extraction |
| `--specs-only` | Only extract specs, skip profile generation |
| `--specs-depth=[surface\|standard\|deep]` | Depth of spec extraction (default: standard) |

## Integration with Other Commands

- **`/workflows:plan`**: Lee el project-profile para informar planificación
- **`/workflows:work`**: Usa convenciones detectadas para código consistente
- **`/workflows:review`**: Valida contra patrones documentados
- **`/workflows:compound`**: Actualiza perfil con nuevos aprendizajes

### Spec Extraction Agent

The `spec-extractor` agent is responsible for all specification extraction:

| Mode | Purpose | Output |
|------|---------|--------|
| Entity Analysis | Extract domain entities and models | `specs/entities/*.yaml` |
| API Analysis | Extract API contracts and endpoints | `specs/api-contracts/*.yaml` |
| Business Rule Analysis | Extract business rules and domain logic | `specs/business-rules/*.yaml` |
| Constraint Analysis | Extract architectural constraints | `specs/architectural-constraints/*.yaml` |

The agent uses static analysis, pattern recognition, and AST parsing to extract specifications from the codebase without requiring runtime execution.

## Auto-Discovery Triggers

El discovery se ejecuta automáticamente cuando:
1. Se detecta que no existe `.ai/project/config.yaml`
2. Han pasado más de 7 días desde el último scan
3. Se detectan cambios significativos en `package.json` o equivalente

## Related Commands

- `/workflows:onboarding` - Para nuevos usuarios
- `/workflows:status` - Estado actual
- `/workflows:reload` - Recargar sin re-discovery
