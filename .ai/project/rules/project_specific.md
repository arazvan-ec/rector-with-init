# Project-Specific Rules - PROJECT_X

**Project**: PROJECT_X
**Type**: Full-Stack Application (Symfony Backend + React Frontend)
**Last Updated**: 2026-01-15
**Version**: 1.0

---

## 🎯 Descripción del Proyecto

PROJECT_X es una aplicación full-stack que [describe brevemente qué hace el proyecto].

- **Backend**: Symfony 6+ (PHP 8.1+, DDD architecture)
- **Frontend1**: React 18+ (TypeScript, Administración)
- **Frontend2**: React 18+ (TypeScript, Usuario final)

---

## 🏗️ Estructura de Repositorio

```
./
├── backend/             # API Symfony
│   ├── ai/             # Contexto de AI (roles, reglas, features)
│   ├── src/            # Código backend
│   └── tests/          # Tests backend
│
├── frontend1/          # Frontend de administración
│   ├── ai/             # Estado de features
│   ├── src/            # Código React
│   └── tests/          # Tests frontend
│
├── frontend2/          # Frontend de usuario
│   ├── ai/             # Estado de features
│   ├── src/            # Código React
│   └── tests/          # Tests frontend
│
└── scripts/            # Scripts de workflow y validación
```

---

## 🔧 Stack Técnico Específico

### Backend

- **Framework**: Symfony 6.4
- **PHP**: 8.1+
- **Database**: PostgreSQL 15
- **ORM**: Doctrine ORM
- **Testing**: PHPUnit 10
- **API**: REST (JSON:API specification)
- **Auth**: JWT (LexikJWTAuthenticationBundle)

### Frontend1 (Admin)

- **Framework**: React 18
- **Language**: TypeScript 5
- **State**: Redux Toolkit + RTK Query
- **UI**: Material-UI (MUI)
- **Routing**: React Router 6
- **Testing**: Jest + React Testing Library
- **Build**: Vite

### Frontend2 (Public)

- **Framework**: React 18
- **Language**: TypeScript 5
- **State**: Context API + React Query
- **UI**: Tailwind CSS + HeadlessUI
- **Routing**: React Router 6
- **Testing**: Jest + React Testing Library
- **Build**: Vite

---

## 📋 Reglas Específicas del Backend

### API Endpoints

**Naming Convention**:
```
GET    /api/{resource}           # List
GET    /api/{resource}/{id}      # Show
POST   /api/{resource}           # Create
PUT    /api/{resource}/{id}      # Update (full)
PATCH  /api/{resource}/{id}      # Update (partial)
DELETE /api/{resource}/{id}      # Delete
```

### Response Format

**Success (200, 201)**:
```json
{
  "data": {
    "id": "uuid",
    "type": "users",
    "attributes": { ... }
  }
}
```

**Error (400, 404, 409, 500)**:
```json
{
  "error": {
    "status": 400,
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": [
      {
        "field": "email",
        "message": "Email is required"
      }
    ]
  }
}
```

### Authentication

- **JWT** en header: `Authorization: Bearer <token>`
- **Refresh Token** endpoint: `POST /api/auth/refresh`
- **Token expiration**: 1 hora (access), 7 días (refresh)

### Rate Limiting

- **Global**: 100 requests / minuto por IP
- **Auth endpoints**: 5 requests / minuto por IP
- **Response header** cuando se excede:
  ```
  HTTP/1.1 429 Too Many Requests
  Retry-After: 60
  ```

---

## 📋 Reglas Específicas del Frontend

### Frontend1 (Admin)

**Propósito**: Panel de administración para gestión interna

**Características**:
- Dashboard con métricas
- CRUD completo de recursos
- Gestión de usuarios
- Reportes y analytics

**Rutas**:
```
/admin/dashboard
/admin/users
/admin/users/:id
/admin/reports
/admin/settings
```

**Permisos**:
- Solo usuarios con role `ADMIN` o `MANAGER`
- Verificación de permisos en cada ruta

### Frontend2 (Public)

**Propósito**: Interfaz pública para usuarios finales

**Características**:
- Landing page
- Registro/Login
- Perfil de usuario
- Funcionalidades principales del producto

**Rutas**:
```
/
/login
/register
/dashboard
/profile
```

**Permisos**:
- Rutas públicas: `/`, `/login`, `/register`
- Rutas privadas: requieren autenticación

---

## 🔐 Security Rules

### Backend Security

1. **Inputs**:
   - Validar **todos** los inputs en DTOs
   - Sanitizar strings (prevent XSS)
   - Validar tipos de datos

2. **SQL Injection**:
   - Usar **siempre** Doctrine Query Builder o DQL
   - **NUNCA** concatenar SQL manualmente

3. **CORS**:
   ```yaml
   # config/packages/nelmio_cors.yaml
   nelmio_cors:
       defaults:
           origin_regex: true
           allow_origin: ['^https://example\.com$']
           allow_methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']
           allow_headers: ['Content-Type', 'Authorization']
   ```

4. **HTTPS Only** en producción

### Frontend Security

1. **XSS Prevention**:
   - **NUNCA** usar `dangerouslySetInnerHTML` sin sanitizar
   - Usar librerías como `DOMPurify` si es necesario

2. **CSRF**:
   - JWT en header (no en cookies) previene CSRF
   - Si usas cookies, agrega CSRF tokens

3. **Secrets**:
   - API keys en `.env` (no commiteadas)
   - Usar variables de entorno en build

---

## 🎨 UI/UX Guidelines

### Design System

- **Colors**: [Define paleta de colores]
- **Typography**: [Define fuentes]
- **Spacing**: Múltiplos de 4px (4, 8, 12, 16, 24, 32, etc.)

### Responsive Breakpoints

```typescript
const breakpoints = {
  mobile: '320px',
  tablet: '768px',
  desktop: '1024px',
  wide: '1440px',
};
```

### Accessibility (a11y)

- ✅ Todos los inputs tienen `<label>`
- ✅ Botones tienen texto descriptivo
- ✅ Imágenes tienen `alt` text
- ✅ Navegación por teclado funciona
- ✅ Contraste mínimo WCAG AA (4.5:1)

---

## 🧪 Testing Strategy

### Backend Tests

**Unit Tests**:
- Todos los Use Cases
- Todas las Entities del Domain
- Value Objects

**Integration Tests**:
- Repositories
- API endpoints (Controllers)

**Coverage**: Mínimo 80%

**Ejemplo**:
```bash
cd backend
./vendor/bin/phpunit
```

### Frontend Tests

**Unit Tests**:
- Componentes críticos
- Custom hooks
- Utilidades

**Integration Tests**:
- Flujos de usuario
- Formularios completos

**E2E Tests**:
- Login/Registration flow
- CRUD operations
- Casos de uso principales

**Coverage**: Mínimo 70%

**Ejemplo**:
```bash
cd frontend1
npm test                 # Unit tests
npm run test:e2e        # E2E tests
```

---

## 🚀 Deployment

### Environments

- **Local**: Desarrollo local
- **Staging**: Pre-producción (staging.example.com)
- **Production**: Producción (example.com)

### CI/CD Pipeline

1. **On Push** (cualquier branch):
   - Run linters
   - Run tests
   - Build (verificar que compila)

2. **On Merge to `develop`**:
   - Run tests
   - Build
   - Deploy to Staging

3. **On Merge to `main`**:
   - Run tests
   - Build
   - Deploy to Production
   - Tag release

---

## 📦 Dependencies

### Backend (Composer)

**Producción**:
- symfony/framework-bundle
- doctrine/orm
- lexik/jwt-authentication-bundle
- nelmio/cors-bundle

**Desarrollo**:
- phpunit/phpunit
- symfony/maker-bundle
- symfony/profiler-pack

**NO uses**:
- Dependencias obsoletas
- Paquetes sin mantenimiento (> 2 años sin actualizar)

### Frontend (NPM)

**Producción**:
- react
- react-dom
- react-router-dom
- axios o react-query
- Material-UI (frontend1) o Tailwind (frontend2)

**Desarrollo**:
- typescript
- vite
- @testing-library/react
- jest
- eslint + prettier

**NO uses**:
- Librerías pesadas innecesarias (bundle size importa)
- Paquetes sin types para TypeScript

---

## 🔄 Workflow Específico del Proyecto

### Feature Development

1. **Planner** define feature:
   - Crea `FEATURE_X.md`
   - Define contratos API
   - Crea breakdown de tareas

2. **Backend** y **Frontend** trabajan:
   - Frontend puede **mockear API** si backend no está listo
   - Ambos actualizan sus respectivos `50_state.md`

3. **Integration**:
   - Frontend reemplaza mocks con API real
   - Tests de integración

4. **QA** revisa y aprueba

5. **Deploy** a staging, luego producción

### Hotfix Process

1. Branch desde `main`: `hotfix/fix-critical-bug`
2. Fix + tests
3. Review rápido (QA)
4. Merge a `main` y `develop`
5. Deploy inmediato a producción

---

## 📊 Monitoring y Logs

### Backend Logging

- **Level**: INFO en producción, DEBUG en staging
- **Format**: JSON
- **Fields**: timestamp, level, message, context, trace_id
- **Storage**: [Define dónde: CloudWatch, ELK, etc.]

### Frontend Error Tracking

- **Tool**: Sentry (o similar)
- **Events**: Errores no capturados, llamadas API fallidas
- **User context**: User ID (si está autenticado)

---

## 🎯 Performance Targets

### Backend

- **Response time**: < 200ms (p95)
- **Database queries**: < 50ms (p95)
- **Memory usage**: < 256MB por request

### Frontend

- **Initial Load**: < 3s (3G network)
- **Time to Interactive**: < 5s
- **Bundle size**: < 500KB (gzipped)
- **Lighthouse score**: > 90

---

## 📝 Documentation

### Backend

- **API Docs**: OpenAPI/Swagger en `/api/doc`
- **PHPDoc**: Todos los métodos públicos

### Frontend

- **Storybook**: Componentes documentados (frontend1)
- **JSDoc**: Utilidades y hooks complejos

---

## ✅ Definition of Done (PROJECT_X)

Un feature está listo cuando:

- ✅ Backend implementado según DDD
- ✅ Frontend(s) implementados y responsive
- ✅ Tests escritos y pasando (backend > 80%, frontend > 70%)
- ✅ API docs actualizados (Swagger)
- ✅ QA aprobó (`APPROVED` en `50_state.md`)
- ✅ Code review hecho
- ✅ Deployed a staging
- ✅ Planner da visto bueno final
- ✅ Performance targets cumplidos

---

**Última actualización**: 2026-01-15
**Actualizado por**: Planner
**Próxima revisión**: Cuando sea necesario (cambios en stack o arquitectura)
