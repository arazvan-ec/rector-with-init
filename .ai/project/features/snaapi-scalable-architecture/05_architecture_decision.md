# Architecture Decision Record - SNAAPI

## Project Context

**SNAAPI** es un API Gateway que:
- Agrega contenido de 7 microservicios
- Transforma datos para apps móviles
- Es READ-ONLY (no persiste datos)
- Tiene ~18 tipos de body elements
- Añade nuevos campos/microservicios con frecuencia

---

## 1. Context Analysis

### 1.1 Functional Requirements
| Requirement | Description |
|-------------|-------------|
| **Core Operation** | Agregar datos de múltiples microservicios en una respuesta |
| **Data Flow** | Request → Fetch parallel → Compose → Transform → Response |
| **Integration Points** | 7 microservicios HTTP (Editorial, Multimedia, Section, Tag, Journalist, Membership, Widget) |
| **Domain Complexity** | BAJA - No hay lógica de negocio, solo agregación y transformación |

### 1.2 Quality Attributes (NFRs)

| Attribute | Requirement | Justification |
|-----------|-------------|---------------|
| **Extensibility** | ALTA | Se añaden nuevos campos y microservicios frecuentemente |
| **Modifiability** | ALTA | Cambios en estructura de respuesta son comunes |
| **Testability** | ALTA | PHPStan L9 + Mutation 79% = tests son críticos |
| **Performance** | MEDIA | 2s timeout, async fetching ya implementado |
| **Reliability** | ALTA | Fallos de microservicios no deben tumbar la API |
| **Maintainability** | ALTA | Código debe ser entendible por nuevos desarrolladores |

### 1.3 Constraints
| Type | Constraint |
|------|------------|
| **Technical** | Symfony 6.4, PHP 8.1+, packages externos ec/* |
| **Team** | Desarrolladores conocen Symfony |
| **Time** | Refactoring incremental, no big-bang |
| **Existing Code** | Mantener backward compatibility 100% |

### 1.4 Change Drivers (What changes most often?)

| Change Type | Frequency | Impact |
|-------------|-----------|--------|
| Añadir nuevo campo al JSON | ALTA (semanal) | 1-5 ficheros |
| Añadir nuevo microservicio | MEDIA (mensual) | 3-5 ficheros |
| Añadir nuevo tipo body element | MEDIA (mensual) | 1-2 ficheros |
| Cambiar estructura de respuesta | BAJA (trimestral) | Varios ficheros |
| Cambiar lógica de transformación | BAJA | 1 fichero |

**Conclusión**: El driver principal es **añadir nuevos datos con mínimo impacto**.

---

## 2. Criteria Definition for SNAAPI

| Criterion | Weight | Justification |
|-----------|--------|---------------|
| **Extensibility** (añadir nuevos datos) | **5** | Driver principal de cambios |
| **Minimal file changes** | **5** | Requisito explícito del usuario |
| **Clear responsibilities** | **5** | Requisito explícito del usuario |
| **Testability** (isolation) | **4** | Quality gates estrictos |
| **Resilience** (fault tolerance) | **4** | Microservicios pueden fallar |
| **Symfony alignment** | **3** | Aprovechar framework nativo |
| **Learning curve** | **3** | Equipo debe entenderlo |
| **Performance** | **2** | Ya optimizado con async |

---

## 3. Options Evaluated

### For Data Enrichment (fetching from microservices)

| Option | Description |
|--------|-------------|
| **A: Aggregator** | Clase central que coordina todas las llamadas |
| **B: Pipeline** | Cadena de enrichers independientes |
| **C: Mediator** | Objeto que orquesta comunicación entre componentes |

### For Data Transformation (building response)

| Option | Description |
|--------|-------------|
| **X: Custom Transformers** (actual) | Chain of Responsibility con transformers propios |
| **Y: Symfony Normalizers** | Usar sistema de serialización nativo |
| **Z: Visitor Pattern** | Visitor que recorre estructura y transforma |

---

## 4. Evaluation Matrix

### 4.1 Enrichment Pattern

| Criterion | Weight | Aggregator | Pipeline | Mediator |
|-----------|--------|------------|----------|----------|
| Extensibility | 5 | 2 | **5** | 3 |
| Minimal file changes | 5 | 2 | **5** | 3 |
| Clear responsibilities | 5 | 3 | **5** | 4 |
| Testability | 4 | 3 | **5** | 4 |
| Resilience | 4 | 3 | **4** | 4 |
| Symfony alignment | 3 | 3 | **4** | 3 |
| Learning curve | 3 | **4** | 4 | 3 |
| Performance | 2 | 4 | 3 | 3 |
| **WEIGHTED TOTAL** | | **86** | **143** | **109** |

**Winner: Pipeline** (143 vs 109 vs 86)

#### Scoring Justification

**Pipeline scores 5 in Extensibility because:**
- Añadir nuevo enricher = 1 fichero nuevo
- No modifica código existente
- Open/Closed principle

**Aggregator scores 2 in Extensibility because:**
- Añadir nuevo dato = modificar Aggregator + crear Gateway + modificar DTO
- Viola Open/Closed

### 4.2 Transformation Pattern

| Criterion | Weight | Custom Trans. | Sf Normalizers | Visitor |
|-----------|--------|---------------|----------------|---------|
| Extensibility | 5 | 3 | **5** | 2 |
| Minimal file changes | 5 | 3 | **5** | 2 |
| Clear responsibilities | 5 | 4 | **5** | 3 |
| Testability | 4 | 4 | **5** | 3 |
| Resilience | 4 | 3 | 3 | 3 |
| Symfony alignment | 3 | 2 | **5** | 1 |
| Learning curve | 3 | 3 | **4** | 2 |
| Performance | 2 | 4 | 3 | 4 |
| **WEIGHTED TOTAL** | | **100** | **138** | **75** |

**Winner: Symfony Normalizers** (138 vs 100 vs 75)

#### Scoring Justification

**Symfony Normalizers scores 5 in Extensibility because:**
- Autoconfigure detecta nuevos normalizers automáticamente
- No hay que registrar manualmente
- Composición nativa (normalizer puede llamar a otros)

**Visitor scores 2 in Extensibility because:**
- Añadir nuevo tipo = modificar visitor existente
- Viola Open/Closed

---

## 5. Decision

### Selected Architecture

| Layer | Pattern | Reason |
|-------|---------|--------|
| **Enrichment** | Pipeline | Score 143/155 - máxima extensibilidad |
| **Transformation** | Symfony Normalizers | Score 138/155 - framework-native + extensible |
| **External Services** | Gateway (Port/Adapter) | Abstrae HTTP, permite mocking |
| **Cross-cutting** | Decorator | Cache + Circuit Breaker sin modificar gateways |

### Architecture Diagram

```
Request
   │
   ▼
┌─────────────┐
│ Controller  │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ENRICHMENT PIPELINE                                  │
│                                                                              │
│   EditorialContext (mutable DTO que viaja por el pipeline)                  │
│                                                                              │
│   ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐              │
│   │ Editorial │→ │Multimedia │→ │  Section  │→ │   Tags    │→ ...         │
│   │ Enricher  │  │ Enricher  │  │ Enricher  │  │ Enricher  │              │
│   └─────┬─────┘  └─────┬─────┘  └─────┬─────┘  └─────┬─────┘              │
│         │              │              │              │                      │
│         ▼              ▼              ▼              ▼                      │
│   ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐              │
│   │ Editorial │  │Multimedia │  │  Section  │  │   Tag     │              │
│   │  Gateway  │  │  Gateway  │  │  Gateway  │  │  Gateway  │              │
│   │(interface)│  │(interface)│  │(interface)│  │(interface)│              │
│   └───────────┘  └───────────┘  └───────────┘  └───────────┘              │
│                                                                              │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   │
                                   ▼ EditorialContext (enriched)
┌─────────────────────────────────────────────────────────────────────────────┐
│                      SYMFONY SERIALIZER                                      │
│                                                                              │
│   $serializer->normalize($context->toEditorial(), 'json')                   │
│                                                                              │
│   ┌──────────────┐                                                          │
│   │EditorialNorm.│ → llama a otros normalizers automáticamente              │
│   └──────┬───────┘                                                          │
│          │                                                                   │
│   ┌──────┴──────┬──────────────┬──────────────┬──────────────┐             │
│   ▼             ▼              ▼              ▼              ▼             │
│ BodyNorm.  MultimediaN.  SectionNorm.  TagsNorm.  SignaturesN.            │
│   │                                                                         │
│   ├─→ ParagraphNormalizer                                                   │
│   ├─→ SubHeadNormalizer                                                     │
│   ├─→ PictureNormalizer                                                     │
│   └─→ ... (auto-discovered)                                                 │
│                                                                              │
└──────────────────────────────────┬──────────────────────────────────────────┘
                                   │
                                   ▼
                            JSON Response
```

---

## 6. Trade-offs Accepted

| What We Gain | What We Give Up |
|--------------|-----------------|
| 1 fichero para nuevo campo | Menos control sobre orden de normalización |
| Extensibilidad máxima | Debugging puede ser más difícil (magic) |
| Framework-native | Dependencia del Serializer de Symfony |
| Auto-discovery | Configuración implícita vs explícita |

---

## 7. Validation Checklist

Para verificar que la arquitectura elegida es correcta:

- [ ] Añadir nuevo campo del mismo microservicio = **1 fichero** (modificar enricher)
- [ ] Añadir nuevo microservicio = **2 ficheros** (gateway + enricher)
- [ ] Añadir nuevo tipo body element = **1 fichero** (normalizer)
- [ ] Testear enricher en aislamiento = **Sí** (mock gateway)
- [ ] Testear normalizer en aislamiento = **Sí** (input → output)
- [ ] Microservicio falla = **Pipeline continúa** (graceful degradation)
- [ ] Nuevo desarrollador entiende el flujo = **Sí** (lineal, predecible)

---

## 8. Migration Path

### Phase 1: Infrastructure (no breaking changes)
1. Crear `EnricherInterface` y `EditorialContext`
2. Crear Gateway interfaces
3. Coexistir con código actual

### Phase 2: Enrichers
1. Migrar cada orquestador a enricher
2. Un enricher por microservicio
3. Tests de comparación old vs new

### Phase 3: Normalizers
1. Crear normalizers para cada tipo
2. Reemplazar DataTransformers actuales
3. Validar JSON output idéntico

### Phase 4: Cleanup
1. Eliminar código deprecated
2. Actualizar documentación

---

## 9. Future Considerations

Si en el futuro:

| Escenario | Adaptación |
|-----------|------------|
| Se añade lógica de negocio compleja | Considerar Domain Services antes del pipeline |
| Se necesitan writes | Añadir Command handlers (CQRS completo) |
| Performance se vuelve crítica | Añadir caching en Gateways (Decorator) |
| Se necesitan diferentes formatos (XML, etc) | Serializer ya lo soporta nativamente |
