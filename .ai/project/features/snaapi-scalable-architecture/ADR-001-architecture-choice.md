# ADR-001: Architecture Choice for SNAAPI Refactoring

**Status**: ACCEPTED
**Date**: 2026-02-03
**Deciders**: Developer + AI Assistant
**Consulted**: Architecture Criteria Interview

---

## Context

SNAAPI es un API Gateway que agrega contenido de 7 microservicios para apps móviles. Necesita refactorización para mejorar escalabilidad, performance y facilidad de implementación.

### Drivers principales (del interview):
1. **Evolvability**: "El software debe evolucionar, no quedarse atado"
2. **Performance**: Prioridad #1
3. **Simplicity**: Prioridad #2
4. **100% Test Coverage**: Non-negotiable

### Constraints:
- Equipo senior (5/5 en todos los patrones)
- PHPUnit 100%, PHPStan L9, Infection 100%
- Backward compatibility 100%

---

## Decision

### Selected Architecture: **Pipeline + DTO Factory**

```
┌─────────────────────────────────────────────────────────────┐
│                      ARCHITECTURE                            │
├─────────────────────────────────────────────────────────────┤
│  Enrichment:     Pipeline (EnricherInterface)               │
│  Transformation: DTO Factory (explicit factories)           │
│  External APIs:  Gateway Pattern (interfaces)               │
│  Cross-cutting:  Decorator (Cache + Circuit Breaker)        │
└─────────────────────────────────────────────────────────────┘
```

---

## Alternatives Considered

### For Enrichment

| Option | Score | Rejected Because |
|--------|-------|------------------|
| **Pipeline** | **206** | ✅ SELECTED |
| Specification | 198 | Naming DDD no añade valor |
| Aggregator | ~150 | Viola Open/Closed |
| Event-Driven | ~140 | Orden implícito, difícil debug |

### For Transformation

| Option | Score | Rejected Because |
|--------|-------|------------------|
| **DTO Factory** | **190** | ✅ SELECTED |
| Normalizers | 185 | Reflection, menos type-safe |
| JMS Serializer | ~120 | Contamina dominio |
| AutoMapper | ~130 | Config centralizada |

---

## Consequences

### Positive
- ✅ **Type safety**: Full IDE autocompletion, refactoring seguro
- ✅ **Performance**: Sin reflection overhead
- ✅ **Testability**: Código explícito = 100% coverage fácil
- ✅ **PHPStan L9**: Tipos completamente inferibles
- ✅ **Evolvability**: Open/Closed compliant

### Negative
- ⚠️ **Más ficheros**: Añadir campo = 3 files (vs 2 con Normalizers)
- ⚠️ **Más verbose**: DTOs + Factories explícitos
- ⚠️ **Más boilerplate**: Cada tipo necesita DTO + Factory

### Neutral
- Pipeline y DTO Factory son patrones conocidos
- Equipo senior puede manejar la complejidad

---

## Implementation

### Directory Structure

```
src/
├── Application/
│   ├── Pipeline/
│   │   ├── EditorialContext.php
│   │   ├── EnricherInterface.php
│   │   ├── EnrichmentPipeline.php
│   │   └── Enricher/
│   │       ├── EditorialEnricher.php
│   │       ├── MultimediaEnricher.php
│   │       └── ...
│   │
│   ├── DTO/
│   │   └── Response/
│   │       ├── EditorialResponse.php
│   │       ├── BodyResponse.php
│   │       └── ...
│   │
│   └── Factory/
│       └── Response/
│           ├── EditorialResponseFactory.php
│           ├── BodyResponseFactory.php
│           └── ...
│
├── Domain/
│   └── Port/
│       └── Gateway/
│           ├── EditorialGatewayInterface.php
│           └── ...
│
└── Infrastructure/
    ├── Gateway/
    │   └── Http/
    │       └── EditorialHttpGateway.php
    └── Controller/
        └── V1/
            └── EditorialController.php
```

### Example: Add `commentsCount`

```php
// 1. NEW: src/Application/Pipeline/Enricher/CommentsEnricher.php
final readonly class CommentsEnricher implements EnricherInterface
{
    public function __construct(private CommentsGatewayInterface $gateway) {}

    public function priority(): int { return 50; }
    public function supports(EditorialContext $ctx): bool { return true; }

    public function enrich(EditorialContext $ctx): void
    {
        $ctx->set('commentsCount', $this->gateway->count($ctx->editorialId()));
    }
}

// 2. MODIFY: src/Application/DTO/Response/EditorialResponse.php
final readonly class EditorialResponse implements \JsonSerializable
{
    public function __construct(
        // ... existing properties
        public int $commentsCount,  // ← ADD
    ) {}
}

// 3. MODIFY: src/Application/Factory/Response/EditorialResponseFactory.php
return new EditorialResponse(
    // ... existing
    commentsCount: $ctx->commentsCount(),  // ← ADD
);
```

---

## Metrics

| Metric | Target | How Architecture Helps |
|--------|--------|------------------------|
| Test Coverage | 100% | Explicit code = easy to test |
| PHPStan | Level 9 | Full type inference |
| Infection MSI | 100% | No magic = mutations detectable |
| Add new field | ≤3 files | Pipeline + DTO pattern |

---

## Review Triggers

Revisit this decision if:
- [ ] Performance requirements change dramatically
- [ ] Team composition changes (juniors added)
- [ ] Need for GraphQL (different serialization needs)
- [ ] Real-time requirements added (event-driven might be better)

---

## References

- Criteria Definition: `12_architecture_criteria.md`
- Criteria Evaluation: `12a_criteria_evaluation.md`
- Implementation Comparison: `07_implementation_comparison.md`
- Validation Analysis: `06_architecture_validation.md`
