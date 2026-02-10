# Feature: SNAAPI Scalable Architecture Refactoring

## Overview

Refactorizar el proyecto SNAAPI para implementar una arquitectura más escalable basada en Hexagonal Architecture + CQRS (Query side), manteniendo compatibilidad con la API actual y mejorando la mantenibilidad, testabilidad y extensibilidad del código.

## Goals

1. **Separación clara de capas**: Domain → Application → Infrastructure
2. **Gateway Pattern**: Abstraer microservicios detrás de interfaces
3. **CQRS Query Side**: Query handlers para cada caso de uso
4. **Async Aggregation**: Llamadas paralelas con Circuit Breaker
5. **Transformer Chain mejorado**: Extensible y testeable
6. **100% backward compatible**: Misma API REST, mismas respuestas

## Current State Analysis

### What Works Well (Keep)
- Chain of Responsibility para orquestación ✓
- Strategy Pattern para transformadores ✓
- Compiler Passes para registro dinámico ✓
- Async processing con Guzzle Promises ✓
- Thin controllers ✓
- Strict typing (PHPStan Level 9) ✓

### What Needs Improvement
| Current | Problem | Proposed |
|---------|---------|----------|
| `Orchestrator/` mezclado | Orquestación + Agregación en uno | Separar `Query/` + `Aggregator/` |
| Clients directos en orchestrators | Acoplamiento a HTTP | Gateway interfaces (Ports) |
| DTOs en `Controller/Schemas/` | Infraestructura conoce estructura | Mover a `Application/DTO/` |
| Sin Circuit Breaker | Sin resiliencia ante fallos | Implementar Circuit Breaker |
| Sin caching de respuestas | Llamadas repetidas | Decorator de caché en Gateways |
| Transformers acoplados | Difícil de testear | Interfaces más pequeñas |

## Success Criteria

- [ ] PHPStan Level 9 pasa sin errores
- [ ] PHPUnit tests pasan (unit + integration)
- [ ] Mutation testing MSI >= 79%
- [ ] Misma respuesta JSON para todos los endpoints existentes
- [ ] Tiempo de respuesta <= actual (no regresión performance)
- [ ] Cobertura de tests >= 80%
- [ ] Documentación actualizada

## Scope

### In Scope
- Refactorización de `src/` siguiendo nueva arquitectura
- Creación de Gateway interfaces
- Implementación de Query Handlers
- Migración de Transformers a nueva estructura
- Tests unitarios para nuevas clases
- Circuit Breaker básico

### Out of Scope
- Nuevos endpoints
- Cambios en respuestas JSON
- Migración de versiones de PHP/Symfony
- Cambios en infraestructura (Docker, CI/CD)

## Timeline

| Phase | Description | Effort |
|-------|-------------|--------|
| 1 | Crear estructura de directorios y interfaces | Low |
| 2 | Implementar Gateway adapters | Medium |
| 3 | Crear Query Handlers | Medium |
| 4 | Migrar Transformers | Medium |
| 5 | Implementar Circuit Breaker | Low |
| 6 | Testing y validación | Medium |

## Risks

| Risk | Mitigation |
|------|------------|
| Breaking changes en API | Contract tests antes y después |
| Performance regression | Benchmark comparativo |
| External packages dependency | Adapter pattern para aislar |
