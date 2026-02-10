# Clarification Prompts Template Library

**Version**: 1.0.0
**Purpose**: Standard prompts for gathering context before workflow selection

---

## Usage

When confidence in classifying a user request is below 60%, use these templates to gather the necessary information before proceeding.

---

## General Routing Prompt

Use this when you can't determine the type of work:

```markdown
## Análisis de Solicitud

He recibido tu solicitud, pero necesito algunos detalles para ayudarte de la mejor manera.

**¿Qué tipo de trabajo necesitas?**

1. 🆕 **Nueva funcionalidad** - Agregar algo que no existe
2. 🐛 **Corrección de bug** - Arreglar algo que no funciona
3. 🔄 **Refactoring** - Mejorar código sin cambiar comportamiento
4. 🔍 **Investigación** - Entender cómo funciona algo
5. 📝 **Documentación** - Crear o actualizar docs
6. ✅ **Code review** - Revisar código existente
7. ⚙️ **Configuración** - Setup o configuración
8. ❓ **Otro** - Describir qué necesitas

Por favor, indica el número o describe brevemente tu necesidad.
```

---

## Feature Request Prompts

### Initial Feature Classification

```markdown
## Nueva Funcionalidad

Entiendo que quieres agregar una nueva funcionalidad. Para planificarla correctamente, necesito entender:

1. **Descripción breve**: ¿Qué hace esta funcionalidad en 2-3 oraciones?

2. **Usuarios**: ¿Quién la usará?
   - [ ] Usuarios finales
   - [ ] Administradores
   - [ ] Otros sistemas (API)
   - [ ] Interno del sistema

3. **Stack involucrado**:
   - [ ] Solo backend
   - [ ] Solo frontend
   - [ ] Fullstack (backend + frontend)
   - [ ] No estoy seguro

4. **Complejidad estimada**:
   - [ ] Simple (< 2 horas, pocos archivos)
   - [ ] Media (2-8 horas, varios componentes)
   - [ ] Compleja (> 8 horas, múltiples capas)
   - [ ] No estoy seguro

5. **¿Hay integraciones externas?** (APIs, servicios terceros, etc.)
   - [ ] Sí → ¿Cuáles?
   - [ ] No
```

### Sensitive Feature Detection

```markdown
## Verificación de Áreas Sensibles

Antes de proceder, necesito confirmar si esta funcionalidad toca áreas sensibles:

**¿Involucra alguno de estos?**

- [ ] **Autenticación** (login, registro, tokens, sesiones)
- [ ] **Autorización** (permisos, roles, acceso)
- [ ] **Pagos** (transacciones, billing, suscripciones)
- [ ] **Datos personales** (PII, emails, direcciones)
- [ ] **Infraestructura** (DB migrations, deployment, configs)

Si marcaste alguno, aplicaré controles adicionales de seguridad y revisión obligatoria.
```

---

## Bug Fix Prompts

### Initial Bug Assessment

```markdown
## Diagnóstico de Bug

Para ayudarte a corregir este problema, necesito entender mejor la situación:

1. **¿Funcionaba antes?**
   - [ ] Sí, dejó de funcionar (regresión)
   - [ ] No, nunca funcionó correctamente
   - [ ] No estoy seguro

2. **Comportamiento actual**: ¿Qué está pasando ahora?
   _[Describe lo que observas]_

3. **Comportamiento esperado**: ¿Qué debería pasar?
   _[Describe lo que esperas]_

4. **¿Es reproducible?**
   - [ ] Siempre ocurre
   - [ ] Ocurre a veces (intermitente)
   - [ ] Difícil de reproducir

5. **Entorno afectado**:
   - [ ] Local (desarrollo)
   - [ ] Staging/Testing
   - [ ] Producción
   - [ ] Todos los entornos

6. **¿Hay mensaje de error?**
   - [ ] Sí → Por favor, cópialo aquí
   - [ ] No, falla silenciosamente
```

### Bug Reproduction Steps

```markdown
## Pasos para Reproducir

Para poder diagnosticar el bug correctamente, necesito los pasos exactos:

1. **Punto de partida**: ¿En qué estado/página empiezas?
2. **Acciones**: ¿Qué pasos sigues exactamente?
3. **Datos de prueba**: ¿Qué datos usas? (sin datos sensibles)
4. **Resultado**: ¿Qué ves cuando falla?

**Formato sugerido**:
```
1. Ir a [URL/página]
2. Hacer clic en [elemento]
3. Ingresar [datos]
4. Resultado: [lo que pasa]
5. Esperado: [lo que debería pasar]
```
```

---

## Refactoring Prompts

### Refactoring Scope Assessment

```markdown
## Análisis de Refactoring

El refactoring requiere planificación cuidadosa. Necesito entender:

1. **Motivación**: ¿Por qué quieres refactorizar?
   - [ ] Mejorar performance
   - [ ] Mejorar mantenibilidad
   - [ ] Preparar para nueva funcionalidad
   - [ ] Corregir problemas de arquitectura
   - [ ] Reducir deuda técnica
   - [ ] Otro: _________

2. **Alcance**: ¿Qué archivos/módulos afecta?
   - [ ] Un solo archivo
   - [ ] Un módulo/componente (3-5 archivos)
   - [ ] Múltiples módulos (5-15 archivos)
   - [ ] Sistémico (15+ archivos)

3. **Riesgo**: ¿Qué podría romperse?
   _[Describe las áreas de riesgo]_

4. **Tests existentes**: ¿Hay cobertura de tests?
   - [ ] Sí, buena cobertura
   - [ ] Parcial, algunos tests
   - [ ] No, sin tests

5. **¿Cambia la API pública?**
   - [ ] No, solo cambios internos
   - [ ] Sí, cambia interfaces/contratos
```

---

## Investigation Prompts

### Research Request Clarification

```markdown
## Investigación

Entiendo que necesitas investigar/entender algo. Para darte la mejor respuesta:

1. **Pregunta principal**: ¿Qué necesitas saber exactamente?
   _[Formula tu pregunta lo más específicamente posible]_

2. **Contexto**: ¿Por qué necesitas esta información?
   - [ ] Entender código existente
   - [ ] Preparar un cambio
   - [ ] Debugging
   - [ ] Documentación
   - [ ] Curiosidad/aprendizaje

3. **Alcance de búsqueda**: ¿Dónde debería buscar?
   - [ ] En el código fuente
   - [ ] En el historial de git
   - [ ] En las dependencias
   - [ ] En la documentación
   - [ ] Todo lo anterior

4. **Formato de respuesta**: ¿Qué esperas recibir?
   - [ ] Explicación verbal
   - [ ] Código de ejemplo
   - [ ] Lista de archivos relevantes
   - [ ] Reporte detallado
```

---

## Code Review Prompts

### Review Scope Clarification

```markdown
## Code Review

Para hacer una revisión efectiva, necesito saber:

1. **¿Qué revisar?**
   - [ ] PR/branch específico → ¿Cuál?
   - [ ] Archivos específicos → ¿Cuáles?
   - [ ] Feature completa → ¿Cuál?
   - [ ] Todo el proyecto (general)

2. **Enfoque de la revisión**:
   - [ ] General (calidad de código)
   - [ ] Seguridad
   - [ ] Performance
   - [ ] Arquitectura/DDD
   - [ ] Todos los anteriores

3. **Preocupaciones específicas**: ¿Hay algo en particular que te preocupe?
   _[Describe tus preocupaciones]_

4. **Nivel de detalle**:
   - [ ] Rápido (problemas críticos solamente)
   - [ ] Normal (problemas + sugerencias)
   - [ ] Exhaustivo (todo, incluyendo estilo)
```

---

## Configuration/Setup Prompts

### Setup Clarification

```markdown
## Configuración/Setup

Entiendo que necesitas ayuda con configuración. Para orientarte:

1. **¿Qué necesitas configurar?**
   - [ ] Entorno de desarrollo local
   - [ ] Tests/CI
   - [ ] Deployment
   - [ ] Integración con servicio externo
   - [ ] Configuración del proyecto
   - [ ] Otro: _________

2. **Estado actual**: ¿Qué ya tienes configurado?
   _[Describe el estado actual]_

3. **Problema/Objetivo**: ¿Qué intentas lograr?
   _[Describe el objetivo]_

4. **¿Has intentado algo?**:
   - [ ] Sí → ¿Qué? ¿Qué pasó?
   - [ ] No, no sé por dónde empezar
```

---

## Confidence Assessment Template

Use internally to calculate confidence before responding:

```markdown
## Confidence Assessment (Internal)

Request: "[user request]"

| Signal | Present? | Points |
|--------|----------|--------|
| Clear work type keyword | [ ] | +20 |
| File/function reference | [ ] | +15 |
| Specific technology mentioned | [ ] | +10 |
| Clear action verb | [ ] | +15 |
| Expected behavior described | [ ] | +20 |
| Error message provided | [ ] | +20 |
| Vague language used | [ ] | -25 |
| Multiple interpretations possible | [ ] | -20 |

**Total Score**: ___/100

**Decision**:
- Score >= 60: Proceed with classification
- Score < 60: Use clarification prompts
```

---

## Response Templates

### After Gathering Information

```markdown
## Resumen y Siguiente Paso

Gracias por la información. Aquí está mi análisis:

**Tipo de trabajo**: [feature/bug/refactor/etc.]
**Complejidad**: [simple/media/compleja]
**Workflow recomendado**: [workflow name]
**Comando**: `/workflows:[command] --workflow=[workflow-name]`

**Razón**: [brief explanation]

**Pasos que seguiré**:
1. [step 1]
2. [step 2]
3. [step 3]

¿Te parece bien proceder así, o prefieres ajustar algo?
```

### When User Wants to Skip Routing

```markdown
## Nota Importante

Entiendo que quieres proceder rápido, pero el routing es importante porque:

1. **Evita trabajo desperdiciado** - Asegura que entiendo correctamente
2. **Selecciona el mejor enfoque** - Diferentes tipos de trabajo necesitan diferentes workflows
3. **Considera seguridad** - Algunas áreas requieren controles adicionales

Solo necesito 2-3 respuestas rápidas para continuar efectivamente. ¿Me ayudas con eso?
```

---

## Quick Reference

| Situation | Template to Use |
|-----------|-----------------|
| Can't determine work type | General Routing Prompt |
| Feature request, need details | Initial Feature Classification |
| Feature touches sensitive areas | Sensitive Feature Detection |
| Bug report, need more info | Initial Bug Assessment |
| Need reproduction steps | Bug Reproduction Steps |
| Refactoring request | Refactoring Scope Assessment |
| Investigation/question | Research Request Clarification |
| Code review request | Review Scope Clarification |
| Setup/config help | Setup Clarification |
| User wants to skip | When User Wants to Skip Routing |
