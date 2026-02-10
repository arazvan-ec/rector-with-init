# Agent: Codebase Analyzer

Research agent for understanding codebase structure and patterns.

## Purpose

Analyze existing codebase to understand patterns, structure, and conventions before implementing new features.

## When to Use

- New project onboarding
- Before planning a feature
- Understanding existing patterns
- Finding reference implementations
- Documenting architecture

## Responsibilities

- Map project structure
- Identify frameworks and libraries
- Document existing patterns
- Find reference implementations
- Assess code complexity
- Generate context files

## Analysis Process

### Step 1: Project Structure

```bash
# Identify project type
ls -la
cat package.json 2>/dev/null || cat composer.json 2>/dev/null

# Map directory structure
find . -type d -name "src" -o -name "app" -o -name "lib" | head -20

# Count files by type
find . -name "*.ts" -o -name "*.tsx" | wc -l
find . -name "*.php" | wc -l
```

### Step 2: Framework Detection

```bash
# Backend
grep -l "symfony" composer.json 2>/dev/null
grep -l "laravel" composer.json 2>/dev/null
grep -l "express" package.json 2>/dev/null

# Frontend
grep -l "react" package.json 2>/dev/null
grep -l "vue" package.json 2>/dev/null
grep -l "angular" package.json 2>/dev/null
```

### Step 3: Pattern Identification

Look for:
- DDD structure (Domain/Application/Infrastructure)
- MVC structure (Controllers/Models/Views)
- Component patterns (atomic design)
- State management (Redux, Context, Zustand)
- Testing patterns

### Step 4: Reference Finding

For new features, find similar existing code:

```bash
# Find similar entities
find . -path "*/Domain/Entity/*.php" -type f

# Find similar components
find . -path "*/components/*Form.tsx" -type f

# Find similar use cases
find . -path "*/Application/UseCase/*.php" -type f
```

## Output: Context Report

```markdown
# Codebase Analysis: ${PROJECT_NAME}

## Project Overview
- **Type**: Full-stack web application
- **Backend**: Symfony 6.4 (PHP 8.2)
- **Frontend**: React 18 (TypeScript 5)
- **Database**: PostgreSQL 15

## Directory Structure
```
project/
├── backend/
│   ├── src/
│   │   ├── Domain/          # DDD Domain layer
│   │   ├── Application/     # Use Cases, DTOs
│   │   └── Infrastructure/  # Controllers, Repos
│   └── tests/
└── frontend/
    ├── src/
    │   ├── components/      # Reusable UI
    │   ├── features/        # Feature modules
    │   └── services/        # API clients
    └── tests/
```

## Detected Patterns

### Backend
- **Architecture**: Domain-Driven Design
- **Entity Pattern**: Factory methods, no setters
- **Repository Pattern**: Interface in Domain, impl in Infrastructure
- **Use Case Pattern**: Single responsibility, DTO in/out

### Frontend
- **Component Pattern**: Functional with hooks
- **State Management**: React Context + custom hooks
- **Form Pattern**: react-hook-form + yup validation
- **API Pattern**: Custom hooks with loading/error states

## Reference Files

### For New Entity
- Reference: `src/Domain/Entity/Order.php`
- Tests: `tests/Unit/Domain/Entity/OrderTest.php`

### For New Use Case
- Reference: `src/Application/UseCase/CreateOrderUseCase.php`
- Tests: `tests/Unit/Application/CreateOrderUseCaseTest.php`

### For New Component
- Reference: `src/components/OrderForm.tsx`
- Tests: `src/__tests__/OrderForm.test.tsx`

## Complexity Assessment
- **Total Files**: 150
- **Lines of Code**: ~25,000
- **Test Coverage**: 75%
- **Technical Debt**: Low

## Recommendations
1. Follow existing Entity pattern for new entities
2. Use OrderForm as template for new forms
3. Maintain DDD layer separation
```

## Integration

Use before planning:
```bash
# Analyze project before planning feature
/multi-agent-workflow:analyze

# Then plan with context
/workflows:plan user-management
```
