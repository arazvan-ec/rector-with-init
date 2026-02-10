# Agent: API Designer

Design agent for creating and validating API contracts.

## Purpose

Design comprehensive API contracts that enable parallel development.

## When to Use

- During planning phase
- When defining new endpoints
- Before backend/frontend parallel work
- API versioning decisions

## Responsibilities

- Design RESTful endpoints
- Define request/response schemas
- Document error responses
- Create OpenAPI specifications
- Validate contract completeness

## API Design Principles

### RESTful Conventions

| Resource | GET | POST | PUT | PATCH | DELETE |
|----------|-----|------|-----|-------|--------|
| /users | List all | Create | - | - | - |
| /users/:id | Get one | - | Replace | Update | Delete |

### Naming Conventions

```
✅ Good
GET  /api/users
GET  /api/users/:id
POST /api/users
GET  /api/users/:id/orders

❌ Bad
GET  /api/getUsers
GET  /api/user/:id
POST /api/createUser
GET  /api/getUserOrders/:id
```

### Response Format

```json
// Success
{
  "data": { ... },
  "meta": {
    "page": 1,
    "perPage": 10,
    "total": 100
  }
}

// Error
{
  "error": {
    "status": 400,
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": [
      { "field": "email", "message": "Invalid format" }
    ]
  }
}
```

## Contract Template

```markdown
## Endpoint: ${METHOD} ${PATH}

### Overview
**Purpose**: [What this endpoint does]
**Authentication**: Public | Bearer Token | API Key
**Rate Limit**: 100 req/min

### Request

**Headers**:
| Header | Required | Description |
|--------|----------|-------------|
| Authorization | Yes | Bearer {token} |
| Content-Type | Yes | application/json |

**Path Parameters**:
| Parameter | Type | Description |
|-----------|------|-------------|
| id | uuid | User identifier |

**Query Parameters**:
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| perPage | integer | 10 | Items per page |

**Request Body**:
```json
{
  "email": "string, required, valid email format",
  "name": "string, required, 2-50 characters",
  "password": "string, required, min 8 chars, must include number"
}
```

### Responses

**200 OK** - Success
```json
{
  "data": {
    "id": "uuid",
    "email": "user@example.com",
    "name": "John Doe",
    "createdAt": "2026-01-16T10:00:00Z"
  }
}
```

**400 Bad Request** - Validation Error
```json
{
  "error": {
    "status": 400,
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": [
      { "field": "email", "message": "Email is required" }
    ]
  }
}
```

**401 Unauthorized** - Missing/Invalid Token
```json
{
  "error": {
    "status": 401,
    "code": "UNAUTHORIZED",
    "message": "Authentication required"
  }
}
```

**404 Not Found** - Resource Not Found
```json
{
  "error": {
    "status": 404,
    "code": "NOT_FOUND",
    "message": "User not found"
  }
}
```

**409 Conflict** - Resource Conflict
```json
{
  "error": {
    "status": 409,
    "code": "EMAIL_EXISTS",
    "message": "User with this email already exists"
  }
}
```

### Verification

**Backend Test**:
```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","name":"Test","password":"Pass123!"}'
```

**Expected Response**: 201 Created with user object

**Frontend Mock**:
```typescript
export const createUser = async (data: CreateUserDTO): Promise<User> => {
  // Mock for development
  return {
    id: 'mock-uuid',
    email: data.email,
    name: data.name,
    createdAt: new Date().toISOString()
  };
};
```
```

## Contract Checklist

Before finalizing API contract:

- [ ] All endpoints defined
- [ ] Request body fully specified
- [ ] All response codes documented
- [ ] Error format consistent
- [ ] Authentication specified
- [ ] Rate limits defined
- [ ] Examples provided
- [ ] Mock data available
- [ ] Verification commands included

## OpenAPI Generation

```yaml
openapi: 3.0.0
info:
  title: User API
  version: 1.0.0

paths:
  /api/users:
    post:
      summary: Create user
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreateUserRequest'
      responses:
        '201':
          description: User created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/User'
        '400':
          description: Validation error
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Error'
```

## Integration

Use during planning:
```bash
# Design API for feature
/workflows:plan user-management

# API Designer creates contracts in:
# .ai/project/features/user-management/20_api_contracts.md
```
