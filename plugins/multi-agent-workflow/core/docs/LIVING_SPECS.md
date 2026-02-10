# Living Specs System

> Especificaciones vivas que se extraen del código y se actualizan automáticamente.

**Version**: 1.0.0
**Status**: Active

---

## Concepto

**Living Specs** es un sistema que transforma las especificaciones de un proyecto de documentos estáticos a documentos vivos que:

1. **Se extraen automáticamente** del código existente
2. **Se actualizan automáticamente** cuando se completan features
3. **Informan la planificación** de nuevas features
4. **Detectan drift** entre código y documentación

---

## Arquitectura

### Estructura de Directorios

```
.ai/project/specs/
├── entities/                      # Especificaciones de entidades
│   ├── user.yaml                  # Entidad User
│   ├── order.yaml                 # Entidad Order
│   └── _index.yaml                # Índice de entidades
│
├── api-contracts/                 # Contratos de API
│   ├── users-api.yaml             # Endpoints /api/users
│   ├── orders-api.yaml            # Endpoints /api/orders
│   └── _index.yaml                # Índice de endpoints
│
├── business-rules/                # Reglas de negocio
│   ├── validation-rules.yaml      # Reglas de validación
│   ├── authorization-rules.yaml   # Reglas de autorización
│   └── _index.yaml                # Índice de reglas
│
├── architectural-constraints/     # Restricciones arquitectónicas
│   ├── layer-dependencies.yaml    # Dependencias entre capas
│   ├── naming-conventions.yaml    # Convenciones de nombres
│   └── _index.yaml                # Índice de restricciones
│
└── spec-manifest.yaml             # Manifiesto maestro
```

### Formato de Specs

Todas las specs usan YAML con:
- Metadatos (source_file, last_extracted, confidence)
- Contenido estructurado según el tipo
- Referencias cruzadas a otras specs
- Historial de cambios

---

## Flujos

### 1. Extracción Inicial

```
/workflows:discover --full
         │
         ├── Detectar stack y arquitectura (existente)
         │
         ├── NUEVO: Extraer Entity Specs
         │   ├── Parsear archivos de entidades/modelos
         │   ├── Extraer campos, tipos, constraints
         │   ├── Extraer relaciones
         │   └── Generar specs/entities/*.yaml
         │
         ├── NUEVO: Extraer API Contracts
         │   ├── Parsear controllers/routes
         │   ├── Extraer endpoints, métodos, parámetros
         │   ├── Extraer responses
         │   └── Generar specs/api-contracts/*.yaml
         │
         ├── NUEVO: Extraer Business Rules
         │   ├── Parsear validators, services
         │   ├── Extraer reglas de validación
         │   ├── Extraer reglas de autorización
         │   └── Generar specs/business-rules/*.yaml
         │
         ├── NUEVO: Extraer Architectural Constraints
         │   ├── Analizar imports y dependencias
         │   ├── Documentar boundaries de capas
         │   └── Generar specs/architectural-constraints/*.yaml
         │
         └── Generar spec-manifest.yaml
```

### 2. Planificación con Specs

```
/workflows:plan nueva-feature
         │
         ├── NUEVO: Cargar Project Specs
         │   └── Leer todos los *.yaml de specs/
         │
         ├── Phase 1: UNDERSTAND
         │   └── Mostrar specs relevantes existentes
         │
         ├── Phase 2: SPECS + Integration Analysis
         │   ├── Identificar qué EXTIENDE
         │   ├── Identificar qué MODIFICA
         │   ├── Identificar qué CREA nuevo
         │   └── Detectar conflictos
         │
         └── Phase 3: SOLUTIONS + Architectural Impact
             ├── Mostrar capas afectadas
             ├── Mostrar módulos tocados
             └── Estimar scope de cambios
```

### 3. Auto-Actualización

```
/workflows:compound feature-name
         │
         ├── Pasos existentes (analyze, extract, etc.)
         │
         ├── NUEVO: Spec Diff Analysis
         │   ├── Comparar 12_specs.md vs project specs
         │   ├── Identificar nuevas entities/endpoints/rules
         │   └── Generar diff report
         │
         └── NUEVO: Update Project Specs
             ├── Merge nuevas specs
             ├── Actualizar specs modificadas
             └── Actualizar spec-manifest.yaml
```

### 4. Detección de Drift

```
/workflows:specs drift
         │
         ├── Comparar código actual vs specs documentadas
         │
         ├── Detectar:
         │   ├── Campos nuevos en código no en specs
         │   ├── Endpoints nuevos no documentados
         │   ├── Reglas modificadas
         │   └── Constraints violados
         │
         └── Generar reporte de drift
```

---

## Comandos

### `/workflows:discover`

Ahora incluye extracción de specs:

```bash
# Extracción completa (incluye specs)
/workflows:discover --full

# Solo extraer specs, sin regenerar profile
/workflows:discover --specs-only

# Refresh incremental de specs
/workflows:discover --refresh
```

### `/workflows:plan`

Ahora carga y usa specs:

```bash
# Planning con análisis de integración (default)
/workflows:plan mi-feature

# Mostrar impacto detallado
/workflows:plan mi-feature --show-impact

# Sin análisis de specs (legacy mode)
/workflows:plan mi-feature --no-specs
```

### `/workflows:compound`

Ahora actualiza specs:

```bash
# Compound con auto-update de specs (default)
/workflows:compound mi-feature

# Solo actualizar specs
/workflows:compound mi-feature --specs-only

# Sin actualizar specs
/workflows:compound mi-feature --no-specs
```

### `/workflows:specs`

Comando dedicado para gestión de specs:

```bash
# Ver resumen de todas las specs
/workflows:specs

# Ver specs por tipo
/workflows:specs entities
/workflows:specs api
/workflows:specs rules
/workflows:specs constraints

# Refresh manual
/workflows:specs refresh

# Detectar drift
/workflows:specs drift

# Validar código contra specs
/workflows:specs validate
```

---

## Integración con Agentes

### spec-extractor

Agente responsable de la extracción de specs:
- Invocado por `/workflows:discover`
- Parsea código fuente
- Genera specs YAML
- Calcula confidence scores

### spec-merger

Skill responsable del merge de specs:
- Invocado por `/workflows:compound`
- Compara feature specs vs project specs
- Resuelve conflictos
- Actualiza manifest

### spec-analyzer (mejorado)

Agente existente, ahora también:
- Compara implementación vs project specs
- Detecta drift
- Valida compliance

---

## Schemas

Las specs siguen JSON schemas definidos:

| Schema | Propósito |
|--------|-----------|
| `entity_spec.json` | Especificaciones de entidades |
| `business_rule_spec.json` | Reglas de negocio |
| `architectural_constraint_spec.json` | Restricciones arquitectónicas |
| `spec_manifest.json` | Índice maestro |

Ver: `/plugins/multi-agent-workflow/core/schemas/`

---

## Beneficios

### Para Planning

- Planificación informada por estado real del proyecto
- Detección temprana de conflictos
- Estimaciones más precisas de scope

### Para Consistencia

- Nuevas features integran correctamente
- Patrones existentes se reutilizan
- Convenciones se mantienen

### Para Documentación

- Specs siempre actualizadas
- Single source of truth
- Onboarding más fácil

### Para Calidad

- Drift detection previene inconsistencias
- Validation asegura compliance
- Review más efectivo

---

## Configuración

En `.ai/project/config.yaml`:

```yaml
specs:
  enabled: true
  auto_extract: true
  auto_update: true
  drift_check_on_plan: true

  extraction:
    entities: true
    api_contracts: true
    business_rules: true
    architectural_constraints: true

  confidence_threshold: 70  # % mínimo para incluir spec

  paths:
    entities: "src/domain/entities"
    controllers: "src/presentation/controllers"
    validators: "src/domain/validators"
```

---

## Troubleshooting

### "Specs no se extraen correctamente"

1. Verificar que las rutas en config.yaml sean correctas
2. Ejecutar `/workflows:discover --full` para re-extraer
3. Revisar confidence scores en spec-manifest.yaml

### "Drift detectado pero es intencional"

1. Ejecutar `/workflows:specs refresh` para sincronizar
2. O editar manualmente el spec YAML

### "Planning no muestra specs existentes"

1. Verificar que exista `.ai/project/specs/`
2. Ejecutar `/workflows:discover --specs-only`
3. Verificar `specs.enabled: true` en config

---

## Roadmap

### v1.0 (Actual)
- ✅ Extracción básica de entities, API, rules
- ✅ Auto-update en compound
- ✅ Integration analysis en plan

### v1.1 (Próximo)
- [ ] UI para edición manual de specs
- [ ] Webhooks para CI/CD
- [ ] Export a OpenAPI/Swagger

### v2.0 (Futuro)
- [ ] Spec generation desde natural language
- [ ] Automated test generation desde specs
- [ ] Cross-project spec sharing
