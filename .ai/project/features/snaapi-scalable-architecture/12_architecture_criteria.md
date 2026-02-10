# Architecture Criteria: snaapi-refactoring

**Status**: VALIDATED
**Generated**: 2026-02-03
**Consultation Date**: 2026-02-03

---

## Context Summary

**Feature Purpose**: Hacer una API escalable, con buen performance y facilidad de implementación

**Lifespan**: N/A - La arquitectura debe permitir evolución continua, no atarse a un tiempo fijo

**Criticality**: CORE - Es la API que usan las apps móviles (impacto directo en usuarios)

### Constraints

**Technical (Hard)**:
- Symfony 6.4
- PHP 8.1+
- Packages externos `ec/*-domain` (Editorial, Multimedia, Section, Tag, Journalist, Membership, Widget)
- HTTP async con Guzzle Promises
- Sin base de datos local (read-only gateway)

**Quality Gates (Non-negotiable)**:
- PHPUnit: 100% coverage
- PHPStan: Level 9 (máximo)
- Infection: 100% MSI
- PHP-CS-Fixer: 0 errores
- Todos los lints al máximo

**Business**:
- Backward compatibility 100% (misma respuesta JSON)
- Zero downtime en migración

### Team Context

- **Composition**: Equipo de desarrolladores seniors
- **Pattern Expertise**: 5/5 en todos los patrones (Pipeline, Normalizers, DTO Factory, Specification)
- **Implication**: No hay restricción por conocimiento del equipo

---

## Criteria Matrix

| ID | Criterion | Category | Weight | Rationale |
|----|-----------|----------|--------|-----------|
| **C1** | **Evolvability** | Strategic | **CRITICAL** | "El software debe evolucionar, no quedarse atado" - Usuario |
| **C2** | **Extensibility** (añadir nuevos datos) | Technical | **CRITICAL** | Driver principal: facilidad de implementación |
| **C3** | **Test Coverage 100%** | Quality | **CRITICAL** | Non-negotiable: PHPUnit + Infection al 100% |
| **C4** | **Performance** | Technical | **HIGH** | Prioridad #1 del usuario |
| **C5** | **Simplicity** (código entendible) | Technical | **HIGH** | Prioridad #2 del usuario |
| **C6** | **Flexibility** (cambios futuros) | Technical | **HIGH** | Prioridad #3 del usuario |
| **C7** | **Maintainability** | Technical | **HIGH** | API crítica, largo plazo |
| **C8** | **PHPStan Level 9** | Quality | **HIGH** | Non-negotiable constraint |
| **C9** | **Backward Compatibility** | Business | **HIGH** | Mismo JSON output |
| **C10** | **Minimal File Changes** | Process | **MEDIUM** | Facilidad de implementación |

### Weight Definitions
- **CRITICAL (5)**: Must score 4+ or option is DISQUALIFIED
- **HIGH (4)**: Strong preference, heavily weighted
- **MEDIUM (3)**: Considered but not decisive

### Critical Criteria (Must Pass)
Cualquier opción que score < 4 en estos criterios queda **descalificada**:
- C1: Evolvability
- C2: Extensibility
- C3: Test Coverage 100%

---

## Key Insight from Interview

> **"¿La vida esperada debería importar? Un software debe evolucionar, no quedarse atado. La arquitectura debe ayudar a evolucionar fácilmente."**

Este insight define el criterio más importante: **EVOLVABILITY**.

Una arquitectura que "funciona hoy" pero dificulta cambios futuros es **inaceptable**.

---

## Consultation Summary

### Priority Order (from Q7)
1. **Performance** - La API debe ser rápida
2. **Simplicity** - El código debe ser entendible
3. **Flexibility** - Debe adaptarse a cambios

### Team Factor
- Equipo senior experto (5/5 en todos los patrones)
- **Implicación**: Podemos elegir la mejor arquitectura técnica sin preocuparnos por learning curve
- **Ventaja**: Podemos usar patrones avanzados si son la mejor opción

### Quality Non-Negotiables
- 100% test coverage
- 100% mutation score (Infection)
- PHPStan Level 9
- Zero lint errors

---

## Scoring Guide for Evaluation

| Score | Meaning | Example |
|-------|---------|---------|
| 5 | Excelente - soporte nativo | Añadir campo = 1 fichero, 0 config |
| 4 | Bueno - poco esfuerzo | Añadir campo = 1-2 ficheros |
| 3 | Aceptable | Añadir campo = 2-3 ficheros |
| 2 | Pobre - requiere workarounds | Añadir campo = 4+ ficheros |
| 1 | Inaceptable | Añadir campo = refactor mayor |

---

## When to Revisit These Criteria

Reconsiderar estos criterios si:
- [ ] Cambian los quality gates del proyecto
- [ ] El equipo cambia significativamente (juniors)
- [ ] Se añaden restricciones de tiempo (deadline tight)
- [ ] La API deja de ser crítica
- [ ] Se permite persistencia local

---

## Next Steps

1. ✅ Criteria defined
2. → Evaluate architecture options against criteria
3. → Select winning architecture
4. → Document ADR (Architecture Decision Record)
5. → Update tasks based on selected architecture
