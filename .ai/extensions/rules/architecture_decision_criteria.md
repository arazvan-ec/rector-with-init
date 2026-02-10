# Architecture Decision Criteria

## Overview

Este documento define los criterios objetivos para evaluar y seleccionar arquitecturas. La arquitectura correcta no es la "más popular" sino la que mejor cumple los criterios del proyecto específico.

## Framework de Evaluación

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        ARCHITECTURE DECISION FRAMEWORK                       │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│   1. CONTEXT ANALYSIS                                                        │
│      ├── Functional Requirements (qué hace)                                 │
│      ├── Quality Attributes (cómo lo hace)                                  │
│      ├── Constraints (limitaciones)                                         │
│      └── Team Context (equipo)                                              │
│                                                                              │
│   2. CRITERIA DEFINITION                                                     │
│      ├── Weight each criterion (1-5)                                        │
│      └── Define measurable metrics                                          │
│                                                                              │
│   3. OPTIONS EVALUATION                                                      │
│      ├── Score each option (1-5)                                            │
│      └── Calculate weighted score                                           │
│                                                                              │
│   4. DECISION                                                                │
│      ├── Select highest score                                               │
│      ├── Document trade-offs                                                │
│      └── Define migration path                                              │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 1. Context Analysis Categories

### 1.1 Functional Requirements
- **Core Operations**: ¿Qué operaciones principales realiza?
- **Data Flow**: ¿Cómo fluyen los datos?
- **Integration Points**: ¿Con qué sistemas se integra?
- **Domain Complexity**: ¿Cuánta lógica de negocio hay?

### 1.2 Quality Attributes (NFRs)
| Attribute | Question |
|-----------|----------|
| **Extensibility** | ¿Con qué frecuencia se añaden nuevas features? |
| **Modifiability** | ¿Qué tan fácil debe ser cambiar código existente? |
| **Testability** | ¿Qué nivel de testing se requiere? |
| **Performance** | ¿Cuáles son los requisitos de latencia/throughput? |
| **Scalability** | ¿Cómo debe escalar el sistema? |
| **Maintainability** | ¿Quién mantendrá el código y por cuánto tiempo? |
| **Reliability** | ¿Qué tolerancia a fallos se necesita? |

### 1.3 Constraints
| Type | Examples |
|------|----------|
| **Technical** | Framework, lenguaje, infraestructura existente |
| **Team** | Experiencia, tamaño, distribución |
| **Time** | Deadline, time-to-market |
| **Budget** | Recursos disponibles |
| **Compliance** | Regulaciones, estándares |

### 1.4 Change Drivers
- ¿Qué cambios son más probables?
- ¿Con qué frecuencia?
- ¿Quién los implementará?

---

## 2. Criteria Weighting Guide

### Weight Scale
| Weight | Meaning | When to use |
|--------|---------|-------------|
| 5 | Critical | El sistema falla sin esto |
| 4 | Very Important | Impacto significativo en éxito |
| 3 | Important | Deseable, mejora la calidad |
| 2 | Nice to have | Beneficioso pero no esencial |
| 1 | Low priority | Consideración menor |

### Score Scale
| Score | Meaning |
|-------|---------|
| 5 | Excelente soporte nativo |
| 4 | Buen soporte con poco esfuerzo |
| 3 | Soporte adecuado |
| 2 | Soporte limitado, requiere workarounds |
| 1 | Difícil o imposible |

---

## 3. Common Criteria by Project Type

### API Gateway / BFF
| Criterion | Typical Weight |
|-----------|---------------|
| Extensibility (añadir nuevos datos) | 5 |
| Integration ease (nuevos microservicios) | 5 |
| Response composition | 4 |
| Error handling / resilience | 4 |
| Caching strategy | 4 |
| Testing in isolation | 3 |
| Performance | 3 |

### CRUD Application
| Criterion | Typical Weight |
|-----------|---------------|
| Data validation | 5 |
| Transaction management | 5 |
| Domain logic encapsulation | 4 |
| Query flexibility | 3 |
| Audit trail | 3 |

### Event-Driven System
| Criterion | Typical Weight |
|-----------|---------------|
| Event handling | 5 |
| Eventual consistency | 5 |
| Idempotency | 4 |
| Replay capability | 4 |
| Monitoring / tracing | 4 |

---

## 4. Architecture Options Comparison

### Common Patterns for API Aggregation

| Pattern | Best For | Avoid When |
|---------|----------|------------|
| **Pipeline** | Frequent additions, independent enrichments | Complex dependencies between steps |
| **Aggregator** | Fixed set of sources, complex coordination | Frequent new sources |
| **Mediator** | Complex workflows, conditional logic | Simple pass-through |
| **Chain of Responsibility** | Dynamic routing by type | All requests same flow |
| **Saga** | Distributed transactions | Read-only operations |

### Common Patterns for Data Transformation

| Pattern | Best For | Avoid When |
|---------|----------|------------|
| **Normalizer (Symfony)** | Many types, framework-native | Custom serialization needs |
| **Visitor** | Operations across type hierarchy | Types change frequently |
| **Strategy** | Swappable algorithms | Single implementation |
| **Mapper** | Simple field mapping | Complex transformations |
| **Builder** | Complex object construction | Simple objects |

---

## 5. Decision Matrix Template

```markdown
## Project: [NAME]
## Decision: [What architecture pattern to use for X]
## Date: [DATE]

### Context
[Brief description of the problem and constraints]

### Criteria

| Criterion | Weight (1-5) | Justification |
|-----------|--------------|---------------|
| C1 | W1 | Why this weight |
| C2 | W2 | Why this weight |
| ... | ... | ... |

### Options Evaluated

| Option | Description |
|--------|-------------|
| A | [Brief description] |
| B | [Brief description] |
| C | [Brief description] |

### Evaluation

| Criterion | Weight | Option A | Option B | Option C |
|-----------|--------|----------|----------|----------|
| C1 | W1 | Score | Score | Score |
| C2 | W2 | Score | Score | Score |
| ... | ... | ... | ... | ... |
| **Weighted Total** | - | **X.X** | **X.X** | **X.X** |

### Decision
[Selected option and reasoning]

### Trade-offs Accepted
- [What we give up by choosing this]

### Migration Path
- [How to implement incrementally]
```

---

## 6. Red Flags: Signs of Wrong Architecture Choice

| Red Flag | Indicates |
|----------|-----------|
| Adding feature requires 5+ file changes | Poor extensibility |
| Can't test component in isolation | High coupling |
| Same logic duplicated across modules | Poor abstraction |
| Changing one thing breaks unrelated things | Hidden dependencies |
| New team members struggle to understand flow | High complexity |
| Performance issues in simple operations | Over-engineering |
| "It depends on everything" | God class / poor separation |
