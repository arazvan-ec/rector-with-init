---
name: workflows:onboarding
description: "Interactive onboarding experience for new users. Guides through setup, verification, and first workflow."
argument_hint: [--verify | --skip-tutorial]
---

# /workflows:onboarding - Experiencia de Primera Vez

**Version**: 1.0.0
**Category**: Setup
**Priority**: Recommended for new users

---

## Purpose

Este comando proporciona una experiencia guiada e interactiva para nuevos usuarios del plugin Multi-Agent Workflow. Verifica prerequisitos, explica conceptos clave, y guía al usuario a través de su primer workflow.

## Invocation

```bash
# Onboarding completo (recomendado)
/workflows:onboarding

# Solo verificar prerequisitos
/workflows:onboarding --verify

# Saltar tutorial interactivo
/workflows:onboarding --skip-tutorial
```

## Execution Protocol

### Step 1: Welcome Message

Display this welcome banner:

```
╔══════════════════════════════════════════════════════════════════╗
║                                                                  ║
║   🎯 Multi-Agent Workflow v2.5.0                                 ║
║   ─────────────────────────────────────                          ║
║   Compound Engineering + Context Engineering                     ║
║                                                                  ║
║   21 agentes especializados                                      ║
║   25 comandos de workflow                                        ║
║   12 skills reutilizables                                        ║
║                                                                  ║
╚══════════════════════════════════════════════════════════════════╝

Hola! Soy tu asistente de onboarding.

Te guiaré paso a paso para que puedas empezar a usar el workflow
de desarrollo multi-agente en menos de 5 minutos.
```

### Step 2: Prerequisites Verification

Run these checks and report status:

```markdown
## Verificación de Prerequisitos

Verificando tu entorno de desarrollo...

| Componente      | Requerido | Estado |
|-----------------|-----------|--------|
| Claude Code CLI | ✓         | [checking...] |
| Git             | ✓         | [checking...] |
| Git config      | ✓         | [checking...] |
| Proyecto Git    | ✓         | [checking...] |
```

Execute verification commands:

```bash
# Check Claude CLI (we're already in it)
echo "✅ Claude Code CLI: Activo"

# Check Git
git --version 2>/dev/null && echo "✅ Git: Instalado" || echo "❌ Git: No encontrado"

# Check Git config
git config user.name && git config user.email && echo "✅ Git config: Configurado" || echo "⚠️ Git config: Configurar user.name y user.email"

# Check if in git repo
git rev-parse --git-dir 2>/dev/null && echo "✅ Repositorio Git: Detectado" || echo "⚠️ No estás en un repositorio Git"
```

If any check fails, provide remediation steps:

```markdown
### Solución de Problemas

**Git no instalado**:
- macOS: `brew install git`
- Ubuntu: `sudo apt install git`
- Windows: Descargar de https://git-scm.com

**Git no configurado**:
```bash
git config --global user.name "Tu Nombre"
git config --global user.email "tu@email.com"
```

**No es un repositorio Git**:
```bash
git init
```
```

### Step 2.5: What's New in v2.5.0

```markdown
## Novedades en v2.5.0

### Scoped Rules (reglas con alcance)
Las reglas ahora se cargan solo cuando son relevantes:
- `testing-rules.md` → al editar archivos de test
- `security-rules.md` → al tocar código de auth/seguridad/pagos
- `git-rules.md` → durante operaciones git

### CLAUDE.md Slim (~130 líneas)
El contexto always-loaded se redujo un 70%. Los detalles se cargan bajo demanda
desde docs de referencia (ROUTING_REFERENCE.md, SESSION_CONTINUITY.md, etc.)

### Routing Reference
Templates de preguntas y decision matrix ahora en `core/docs/ROUTING_REFERENCE.md`,
cargados solo cuando se necesitan para routing complejo.

### Urgency Calibration
Lenguaje claro y directo sin fatiga de urgencia (eliminados MANDATORY/CRITICAL en mayúsculas).

> Para detalles técnicos: Ver `core/docs/CONTEXT_ENGINEERING.md`
```

### Step 3: Explain Core Concepts (30 seconds)

```markdown
## Conceptos Esenciales (30 segundos)

Antes de empezar, hay **3 conceptos clave** que debes conocer:

### 1. Regla 80/20
```
┌────────────────────────────────────────────────────┐
│  80% Planificación  │  20% Ejecución               │
│  ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓   │  ▓▓▓▓                        │
└────────────────────────────────────────────────────┘
```
El 80% del tiempo va en planificar bien. El código se escribe casi solo.

### 2. Flujo de 4 Fases
```
PLAN → WORK → REVIEW → COMPOUND
 80%    15%     4%        1%
```
Cada feature pasa por estas 4 fases, siempre en orden.

### 3. Auto-corrección (Ralph Wiggum Loop)
Si algo falla, el sistema intenta corregirlo automáticamente hasta 10 veces.
Tú solo intervienes si se bloquea.

> Para más detalles: [GLOSSARY.md](../../GLOSSARY.md)
```

### Step 4: Show Available Commands (Quick Reference)

```markdown
## Comandos Principales

Solo necesitas recordar **5 comandos** para empezar:

| Comando            | Qué hace                     | Cuándo usarlo        |
|--------------------|------------------------------|----------------------|
| `/workflows:plan`  | Planifica una feature        | Siempre primero      |
| `/workflows:work`  | Implementa el código         | Después de planificar|
| `/workflows:review`| Revisa calidad automática    | Antes de merge       |
| `/workflows:status`| Ver estado actual            | Cuando quieras       |
| `/workflows:help`  | Ayuda y navegación           | Si te pierdes        |

### Comando Bonus

| Comando            | Qué hace                     |
|--------------------|------------------------------|
| `/workflows:route` | Te guía al workflow correcto |

> Si no sabes qué comando usar, `/workflows:route` te ayuda a decidir.
```

### Step 5: Interactive First Workflow (Optional)

Ask the user:

```markdown
## Tu Primera Feature

¿Quieres crear tu primera feature ahora?

Opciones:
1. **[Sí, guíame]** - Te acompañaré paso a paso con una feature de ejemplo
2. **[Sí, tengo una idea]** - Planifica algo que necesites realmente
3. **[No, más tarde]** - Puedes volver a correr `/workflows:onboarding` cuando quieras

¿Qué prefieres? (1/2/3)
```

#### Option 1: Guided Example

```markdown
### Feature de Ejemplo: "Hello World API"

Vamos a crear un endpoint simple para probar el workflow.

**Paso 1**: Planificar

Ejecutaré el comando de planificación:
```bash
/workflows:plan hello-api
```

Esto creará la estructura en `.ai/project/features/hello-api/`

[El planner automáticamente generará:
- Análisis de requisitos
- Arquitectura simple
- Contrato de API
- Tareas para implementación]

¿Listo para ver el resultado? (continuar/cancelar)
```

#### Option 2: User's Own Idea

```markdown
### Tu Feature

Describe en una oración qué quieres construir.

**Ejemplo**: "Un sistema de login con email y contraseña"

Tu descripción: _____

[Esperar input del usuario, luego ejecutar:]
/workflows:route "[user input]"
```

#### Option 3: Skip for Now

```markdown
### Entendido!

Cuando estés listo para empezar, solo escribe:

```bash
/workflows:plan nombre-de-tu-feature
```

O si no estás seguro de qué workflow usar:

```bash
/workflows:route "descripción de lo que necesitas"
```
```

### Step 6: Show Resources

```markdown
## Recursos de Aprendizaje

Tu camino de aprendizaje recomendado:

```
    AHORA              DESPUÉS            CUANDO NECESITES
      │                   │                     │
      ▼                   ▼                     ▼
┌──────────┐      ┌──────────────┐      ┌──────────────┐
│QUICKSTART│ ───▶ │   TUTORIAL   │ ───▶ │    README    │
│  5 min   │      │   30-45 min  │      │ Referencia   │
└──────────┘      └──────────────┘      └──────────────┘
```

### Archivos Clave

| Archivo | Descripción | Comando rápido |
|---------|-------------|----------------|
| [QUICKSTART.md](../../QUICKSTART.md) | Inicio rápido | Ya lo cubrimos |
| [TUTORIAL.md](../../TUTORIAL.md) | Ejemplo completo | Recomendado siguiente |
| [GLOSSARY.md](../../GLOSSARY.md) | Definiciones | Si no entiendes algo |
| [INDEX.md](../../INDEX.md) | Mapa navegación | Si te pierdes |

### Ayuda Rápida

```bash
# Ver ayuda en cualquier momento
/workflows:help

# Ver estado de tu feature
/workflows:status mi-feature

# Si te trabas
/workflows:sync
```
```

### Step 7: Completion Summary

```markdown
## Onboarding Completado!

### Resumen

✅ Prerequisitos verificados
✅ Conceptos clave explicados
✅ Comandos principales mostrados
✅ Recursos de aprendizaje compartidos

### Próximos Pasos Recomendados

1. **Ahora**: Prueba `/workflows:plan test-feature` para ver el sistema en acción
2. **Hoy**: Lee [TUTORIAL.md](../../TUTORIAL.md) para un ejemplo completo
3. **Esta semana**: Usa el workflow en una feature real de tu proyecto

### Recuerda

```
La regla de oro: Planifica primero, codifica después.
Si te trabas: /workflows:help o /workflows:route
```

---

¡Buena suerte!
```

## Flags

| Flag | Description |
|------|-------------|
| `--verify` | Only run prerequisite verification, skip tutorial |
| `--skip-tutorial` | Skip the interactive tutorial section |

## Integration Notes

This command should be suggested:
1. After first plugin installation
2. When user seems confused about where to start
3. When `/workflows:help` is invoked by a new user

## Related Commands

- `/workflows:help` - Quick reference and navigation
- `/workflows:route` - Route to appropriate workflow
- `/workflows:status` - Check current state
