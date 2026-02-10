# Git Workflow para Claude Code Multi-Instancia

**Versión**: 1.0
**Última actualización**: 2026-01-15

---

## 🎯 Propósito

Este documento define el **Git workflow obligatorio** para sincronizar múltiples instancias de Claude Code trabajando en paralelo. Asegura que:

- ✅ Todas las instancias vean los mismos cambios
- ✅ No haya conflictos de estado
- ✅ Cada cambio sea auditable
- ✅ Se pueda revertir si algo falla

---

## 📜 Principio Fundamental

> **"Si no está en Git, no existe"**

Todo cambio relevante (código, `50_state.md`, documentación) **DEBE** ir a un commit y push inmediatamente. No dejes cambios solo en disco local.

---

## 🌳 Branching Strategy

### Estructura de Branches

```
main (o master)
│
├── develop                    # Desarrollo activo
│   │
│   ├── feature/user-auth     # Feature branches
│   ├── feature/payment
│   └── feature/dashboard
│
├── hotfix/fix-critical-bug   # Hotfixes urgentes
└── release/v1.0.0            # Releases
```

### Branch por Feature (Recomendado)

**Formato**: `feature/[feature-id]` o `claude/[feature-id]-[session-id]`

**Ejemplos**:
- `feature/user-authentication`
- `feature/payment-gateway`
- `claude/user-auth-EmUfG` (para sesiones de Claude Code on web)

**Reglas**:
1. **Un feature = un branch**
2. **Todos los roles trabajan en el mismo branch** del feature
3. Cuando el feature esté completo y QA apruebe, merge a `develop`
4. Nunca hacer push directo a `main`

### Workflow Típico

```bash
# 1. Crear branch de feature
git checkout develop
git pull origin develop
git checkout -b feature/user-auth

# 2. Todos los roles trabajan en este branch
# (Planner, Backend, Frontend, QA todos en feature/user-auth)

# 3. Cuando QA aprueba, merge a develop
git checkout develop
git merge feature/user-auth
git push origin develop

# 4. Eventualmente, develop se mergea a main para release
```

---

## 🔄 Git Workflow por Rol

### Antes de Empezar Cualquier Tarea

**SIEMPRE** sincroniza primero:

```bash
./.ai/scripts/git_sync.sh [feature-id]
```

O manualmente:
```bash
git pull origin feature/[feature-id]
```

Esto asegura que tienes los últimos cambios de otros roles.

---

### Planner

#### 1. Sincronizar

```bash
./.ai/scripts/git_sync.sh user-auth
```

#### 2. Trabajar

- Crear `FEATURE_X.md`
- Crear `30_tasks.md`
- Actualizar `50_state.md` (planner section)

#### 3. Commit y Push (Inmediatamente al terminar)

```bash
./.ai/scripts/git_commit_push.sh planner user-auth "Define feature and create task breakdown"
```

**Commit message format**: `[planner][user-auth] Define feature and create task breakdown`

#### Frecuencia

**Al terminar el planning completo** (una vez)

---

### Backend

#### 1. Sincronizar

```bash
./.ai/scripts/git_sync.sh user-auth
# Esto trae los cambios del Planner (FEATURE_X.md, 30_tasks.md)
```

#### 2. Trabajar

- Implementar backend según DDD
- Escribir tests

#### 3. Commit y Push (**FRECUENTEMENTE**)

Después de **cada milestone**:

```bash
# Ejemplo 1: Entity creada
./.ai/scripts/git_commit_push.sh backend user-auth "Implement User entity and Email value object"

# Ejemplo 2: Repository implementado
./.ai/scripts/git_commit_push.sh backend user-auth "Implement UserRepository with Doctrine"

# Ejemplo 3: Use Case listo
./.ai/scripts/git_commit_push.sh backend user-auth "Implement RegisterUserUseCase with tests"

# Ejemplo 4: Controller y endpoint
./.ai/scripts/git_commit_push.sh backend user-auth "Add POST /api/users endpoint"

# Final: Todo completo
./.ai/scripts/git_commit_push.sh backend user-auth "Complete backend implementation - all tests passing"
```

#### Frecuencia

**Cada 30-60 minutos** o después de cada componente significativo completado.

**¿Por qué frecuente?**
- Frontend puede empezar a integrar endpoints parciales
- Si algo falla, es fácil revertir commits pequeños
- QA puede revisar progresivamente
- Evita perder trabajo si la sesión se cierra

---

### Frontend

#### 1. Sincronizar

```bash
./.ai/scripts/git_sync.sh user-auth
# Esto trae cambios del Planner y Backend
```

#### 2. Verificar si Backend está listo

```bash
cat .ai/projects/PROJECT_X/features/user-auth/50_state.md | grep -A 5 "## 💻 Backend"
```

Si Backend status es `COMPLETED`, puedes integrar API real.
Si no, mockea la API y continúa.

#### 3. Trabajar

- Implementar UI
- Mockear API si necesario
- Escribir tests

#### 4. Commit y Push (**FRECUENTEMENTE**)

```bash
# Ejemplo 1: Mocks de API
./.ai/scripts/git_commit_push.sh frontend user-auth "Add API mocks for user endpoints"

# Ejemplo 2: Componente
./.ai/scripts/git_commit_push.sh frontend user-auth "Implement LoginForm component"

# Ejemplo 3: Otro componente
./.ai/scripts/git_commit_push.sh frontend user-auth "Implement UserList component with pagination"

# Ejemplo 4: Integración con API real (si backend ya está listo)
./.ai/scripts/git_commit_push.sh frontend user-auth "Replace mocks with real API integration"

# Final: Todo completo
./.ai/scripts/git_commit_push.sh frontend user-auth "Complete frontend implementation - all tests passing"
```

#### Frecuencia

**Cada 30-60 minutos** o después de cada componente.

---

### QA

#### 1. Sincronizar

```bash
./.ai/scripts/git_sync.sh user-auth
# Esto trae TODO el código de Backend y Frontend
```

#### 2. Verificar que Backend y Frontend están `COMPLETED`

```bash
cat .ai/projects/PROJECT_X/features/user-auth/50_state.md
```

Si alguno no está `COMPLETED`, espera a que terminen.

#### 3. Trabajar

- Revisar código
- Ejecutar tests
- Validar criterios de aceptación
- Crear `qa_report_user-auth.md`

#### 4. Commit y Push (Una vez al terminar)

**Si APRUEBA**:
```bash
./.ai/scripts/git_commit_push.sh qa user-auth "QA review: APPROVED - feature ready for production"
```

**Si RECHAZA**:
```bash
./.ai/scripts/git_commit_push.sh qa user-auth "QA review: REJECTED - API validation missing, UI not responsive"
```

#### Frecuencia

**Una vez después de revisar todo** (a menos que haya múltiples rondas de review si Backend/Frontend arreglan issues).

---

## 📊 Tabla Resumen: Frecuencia de Commits

| Rol | Frecuencia de Commit/Push | Momento |
|-----|---------------------------|---------|
| **Planner** | 1 vez | Al terminar planning |
| **Backend** | Cada 30-60 min | Después de cada componente (entity, use case, endpoint) |
| **Frontend** | Cada 30-60 min | Después de cada componente (form, list, integration) |
| **QA** | 1 vez (o 2-3 si hay rondas) | Al terminar review |

---

## 🛠️ Scripts Disponibles

### `git_sync.sh` - Sincronizar con remoto

```bash
./.ai/scripts/git_sync.sh [feature-id]
```

**Lo que hace**:
- Hace `git fetch`
- Stash de cambios locales (si existen)
- `git pull` del branch actual
- Aplica el stash de vuelta
- Muestra el estado del feature

**Cuándo usarlo**: **SIEMPRE** antes de empezar a trabajar.

---

### `git_commit_push.sh` - Commit y push con validación

```bash
./.ai/scripts/git_commit_push.sh [role] [feature-id] [message]
```

**Ejemplo**:
```bash
./.ai/scripts/git_commit_push.sh backend user-auth "Implement User entity"
```

**Lo que hace**:
1. Valida el workflow (ejecuta `validate_workflow.py`)
2. Muestra qué archivos se van a commitear
3. Pide confirmación
4. Hace `git add -A`
5. Commit con formato: `[role][feature-id] message`
6. Push con retry logic (hasta 4 intentos si falla)

**Cuándo usarlo**: Después de completar cada milestone o tarea significativa.

---

### `install_git_hooks.sh` - Instalar hooks de validación

```bash
./.ai/scripts/install_git_hooks.sh
```

**Lo que hace**:
- Copia `hooks/pre-commit` a `.git/hooks/pre-commit`
- Hace el hook ejecutable

**Efecto**: Antes de **cada** commit, se valida automáticamente:
- ✅ YAML syntax
- ✅ Formato de `50_state.md`
- ✅ No hay secrets (passwords, API keys)
- ✅ No hay archivos `.env`

---

## 🔐 Pre-commit Hook

El pre-commit hook **valida antes de permitir el commit**. Si falla, el commit es rechazado.

### Instalar

```bash
./.ai/scripts/install_git_hooks.sh
```

### Qué valida

1. **YAML syntax** - Workflows deben ser YAML válidos
2. **Formato de `50_state.md`** - Debe tener secciones: Planner, Backend, Frontend, QA
3. **Secrets** - No se permiten passwords, API keys, tokens
4. **Archivos `.env`** - NUNCA se deben commitear
5. **Workflow completo** - Ejecuta `validate_workflow.py`

### Bypass (NO recomendado)

```bash
git commit --no-verify
```

**Solo usa esto si**:
- Estás commiteando documentación
- Sabes que la validación es un falso positivo

**NO uses `--no-verify` para**:
- Saltarte validaciones legítimas
- Commitear secrets
- Commitear código roto

---

## 🚨 Manejo de Conflictos

### Conflicto en `50_state.md`

Si dos roles actualizan `50_state.md` simultáneamente:

```bash
# Al hacer git pull
Auto-merging .ai/projects/PROJECT_X/features/user-auth/50_state.md
CONFLICT (content): Merge conflict in 50_state.md
```

**Solución**:

1. Abrir `50_state.md`
2. Ver el conflicto:
   ```markdown
   <<<<<<< HEAD
   **Status**: COMPLETED (Backend)
   =======
   **Status**: COMPLETED (Frontend)
   >>>>>>> origin/feature/user-auth
   ```
3. Resolver manualmente (ambos son válidos, mantén ambos)
4. `git add 50_state.md`
5. `git commit` (sin mensaje, usa el default de merge)
6. `git push`

**Prevención**:
- Cada rol tiene su **propia sección** en `50_state.md`
- Conflictos deberían ser raros
- Si ocurren, generalmente es fácil resolverlos

---

## 📋 Checklist: Workflow Correcto

### Antes de Empezar

- [ ] Branch de feature existe o lo creo: `git checkout -b feature/[feature-id]`
- [ ] Sincronizo: `./.ai/scripts/git_sync.sh [feature-id]`
- [ ] Leo mi rol: `.ai/roles/[my-role].md`
- [ ] Leo reglas: `global_rules.md`, `ddd_rules.md`, `project_specific.md`
- [ ] Leo workflow: `workflows/default.yaml`

### Durante el Trabajo

- [ ] Leo `FEATURE_X.md` (lo que debo implementar)
- [ ] Leo `30_tasks.md` (mis tareas específicas)
- [ ] Leo `50_state.md` (estado de otros roles, dependencias)
- [ ] Implemento código/docs
- [ ] Actualizo `50_state.md` (mi sección)
- [ ] **Commit y push frecuentemente** (cada 30-60 min)

### Al Terminar

- [ ] Todos los tests pasan
- [ ] `50_state.md` (mi sección) status = `COMPLETED` (o `APPROVED`/`REJECTED` si soy QA)
- [ ] Commit y push final
- [ ] Notifico a otros roles (vía mensaje o simplemente ellos hacen `git_sync.sh`)

---

## 🎯 Beneficios de Este Workflow

| Beneficio | Explicación |
|-----------|-------------|
| **Estado compartido confiable** | Todas las instancias ven lo mismo via Git |
| **Auditoría completa** | Cada commit es un snapshot del progreso |
| **Recuperación fácil** | Si algo falla, `git revert` al commit anterior |
| **Sincronización automática** | `git_sync.sh` trae cambios de otros roles |
| **Prevención de conflictos** | Estado granular por rol reduce conflictos |
| **Trabajo en paralelo** | Backend y Frontend pueden trabajar simultáneamente sin pisarse |
| **Validación automática** | Pre-commit hook previene commits rotos |

---

## 📖 Ejemplos Completos

### Ejemplo 1: Planner Define Feature

```bash
# Planner en Tab 1 de Tilix

cd /path/to/project
git checkout -b feature/user-auth

# Claude Code:
# "I am the PLANNER for feature user-auth..."

# (Claude crea FEATURE_X.md, 30_tasks.md, actualiza 50_state.md)

# Cuando Claude termina:
./.ai/scripts/git_commit_push.sh planner user-auth "Define user authentication feature and breakdown tasks"

# Output:
# ✓ Workflow validation passed
# ✓ Staged all changes
# ✓ Committed: [planner][user-auth] Define user authentication feature
# ✓ Pushed to origin/feature/user-auth
```

### Ejemplo 2: Backend Implementa (con commits frecuentes)

```bash
# Backend en Tab 2 de Tilix

cd /path/to/project
./.ai/scripts/git_sync.sh user-auth  # Trae cambios del Planner

# Claude Code:
# "I am the BACKEND ENGINEER for feature user-auth..."

# (Claude implementa User entity)
./.ai/scripts/git_commit_push.sh backend user-auth "Implement User entity with Email value object"

# (Claude implementa Repository)
./.ai/scripts/git_commit_push.sh backend user-auth "Implement UserRepository with Doctrine"

# (Claude implementa Use Case)
./.ai/scripts/git_commit_push.sh backend user-auth "Implement RegisterUserUseCase with validation"

# (Claude implementa Controller)
./.ai/scripts/git_commit_push.sh backend user-auth "Add POST /api/users endpoint with tests"

# (Claude termina)
./.ai/scripts/git_commit_push.sh backend user-auth "Complete backend implementation"
```

### Ejemplo 3: Frontend Integra (primero mocks, luego API real)

```bash
# Frontend en Tab 3 de Tilix

cd /path/to/project
./.ai/scripts/git_sync.sh user-auth  # Trae cambios del Planner

# Verificar si Backend está listo
cat .ai/projects/PROJECT_X/features/user-auth/50_state.md | grep "Backend"

# Backend status: IN_PROGRESS (no listo aún)

# Claude Code mockea API y continúa
./.ai/scripts/git_commit_push.sh frontend user-auth "Add API mocks for user endpoints"

# Implementa UI
./.ai/scripts/git_commit_push.sh frontend user-auth "Implement RegistrationForm component"

# Más tarde, Backend termina. Frontend sincroniza:
./.ai/scripts/git_sync.sh user-auth

# Verifica Backend status: COMPLETED

# Reemplaza mocks con API real
./.ai/scripts/git_commit_push.sh frontend user-auth "Replace mocks with real API integration"

# Termina
./.ai/scripts/git_commit_push.sh frontend user-auth "Complete frontend implementation"
```

### Ejemplo 4: QA Revisa

```bash
# QA en Tab 4 de Tilix

cd /path/to/project
./.ai/scripts/git_sync.sh user-auth  # Trae TODO el código

# Verificar que Backend y Frontend están COMPLETED
cat .ai/projects/PROJECT_X/features/user-auth/50_state.md

# Backend: COMPLETED ✓
# Frontend: COMPLETED ✓

# Claude Code revisa todo, ejecuta tests, valida criterios

# Si TODO está bien:
./.ai/scripts/git_commit_push.sh qa user-auth "QA review: APPROVED - all criteria met, tests passing"

# Si hay problemas:
./.ai/scripts/git_commit_push.sh qa user-auth "QA review: REJECTED - missing email validation, UI not responsive on mobile"
```

---

## ✅ Resumen: Reglas de Oro

1. **Sincroniza SIEMPRE antes de empezar**: `./.ai/scripts/git_sync.sh [feature-id]`
2. **Commit y push FRECUENTEMENTE**: Cada 30-60 min o después de cada milestone
3. **Usa los scripts**: `git_commit_push.sh` valida automáticamente antes de commitear
4. **No saltees el pre-commit hook**: Es tu red de seguridad
5. **Un feature = un branch**: Todos los roles en el mismo branch
6. **Lee `50_state.md` frecuentemente**: Para saber el estado de otros roles
7. **Si te bloqueas, pushea inmediatamente**: Con status `BLOCKED` en tu sección

---

**¿Tienes dudas sobre el Git workflow?** Consulta esta guía o los scripts en `./.ai/scripts/`.

**Happy coding with synchronized Claude instances! 🚀**
