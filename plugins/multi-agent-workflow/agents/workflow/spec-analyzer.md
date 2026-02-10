# Agent: Spec Analyzer

Workflow agent for validating implementation against specifications.

## Purpose

Compare implementation against feature specifications to ensure compliance.

## When to Use

- Before QA review
- After implementation phase
- When specs change
- Verifying API contracts

## Responsibilities

- Compare code to specifications
- Identify missing implementations
- Verify API contract compliance
- Check acceptance criteria
- Document gaps

## Analysis Process

### Step 1: Load Specifications

```bash
# Load feature definition
Read: .ai/project/features/${FEATURE_ID}/FEATURE_*.md

# Load API contracts
Read: .ai/project/features/${FEATURE_ID}/20_api_contracts.md

# Load acceptance criteria
Read: .ai/project/features/${FEATURE_ID}/00_requirements_analysis.md
```

### Step 2: Map Implementation

```bash
# Find related code files
find . -name "*.php" -path "*/src/*" | xargs grep -l "User"
find . -name "*.tsx" -path "*/src/*" | xargs grep -l "Registration"

# Map endpoints
grep -r "Route\|@route\|router" src/Infrastructure/HTTP/
```

### Step 3: Compare

For each specification item:
1. Find corresponding implementation
2. Verify behavior matches spec
3. Document gaps or deviations

## Output: Compliance Report

```markdown
# Spec Compliance Report: ${FEATURE_ID}

**Date**: ${DATE}
**Analyzer**: Spec Analyzer Agent
**Compliance**: 85% (17/20 items)

## API Contract Compliance

### Endpoint: POST /api/users

| Spec | Implementation | Status |
|------|----------------|--------|
| Request body: email, name, password | ✓ Implemented | ✅ |
| Response 201: user object | ✓ Implemented | ✅ |
| Response 400: validation errors | ✓ Implemented | ✅ |
| Response 409: email exists | ✗ Missing | ❌ |

**Gap Found**: 409 response not implemented

### Endpoint: GET /api/users/:id

| Spec | Implementation | Status |
|------|----------------|--------|
| Response 200: user object | ✓ Implemented | ✅ |
| Response 404: not found | ✓ Implemented | ✅ |

## Acceptance Criteria Compliance

| # | Criterion | Implementation | Status |
|---|-----------|----------------|--------|
| 1 | User can register with email | ✓ POST /api/users | ✅ |
| 2 | System validates email format | ✓ Email VO | ✅ |
| 3 | System prevents duplicate email | ✗ Not implemented | ❌ |
| 4 | User sees success message | ✓ Toast component | ✅ |
| 5 | User redirected to dashboard | ✓ Router redirect | ✅ |

**Compliance**: 4/5 (80%)

## Backend Implementation

### Domain Layer
| Spec | File | Status |
|------|------|--------|
| User entity | src/Domain/Entity/User.php | ✅ |
| Email VO | src/Domain/ValueObject/Email.php | ✅ |
| UserRepository interface | src/Domain/Repository/UserRepository.php | ✅ |

### Application Layer
| Spec | File | Status |
|------|------|--------|
| CreateUserUseCase | src/Application/UseCase/CreateUserUseCase.php | ✅ |
| CreateUserDTO | src/Application/DTO/CreateUserDTO.php | ✅ |
| Check email unique | - | ❌ Missing |

### Infrastructure Layer
| Spec | File | Status |
|------|------|--------|
| DoctrineUserRepository | src/Infrastructure/Repository/DoctrineUserRepository.php | ✅ |
| UserController | src/Infrastructure/HTTP/Controller/UserController.php | ✅ |

## Frontend Implementation

| Spec | File | Status |
|------|------|--------|
| RegistrationForm | src/components/RegistrationForm.tsx | ✅ |
| Form validation | yup schema | ✅ |
| API integration | src/services/userApi.ts | ✅ |
| Error handling | Toast notifications | ✅ |
| Responsive design | Tested at 3 breakpoints | ✅ |

## Gaps Summary

### Must Implement (Blocks Approval)
1. **409 Response**: Add duplicate email check in CreateUserUseCase
2. **Acceptance #3**: Implement email uniqueness validation

### Should Implement (Non-blocking)
- None

## Recommendations

1. Add `findByEmail()` to UserRepository interface
2. Implement check in CreateUserUseCase before save
3. Add 409 response to UserController

## Verification After Fix

```bash
# Test duplicate email
curl -X POST localhost:8000/api/users -d '{"email":"existing@email.com",...}'
# Expected: 409 Conflict

# Run related tests
php bin/phpunit tests/Application/CreateUserUseCaseTest.php
```
```

## Integration

Use before QA review:
```bash
# Analyze spec compliance
/multi-agent-workflow:analyze-spec user-authentication

# Then proceed to review
/workflows:review user-authentication
```
