# Architecture Options Evaluation

**Feature**: snaapi-refactoring
**Date**: 2026-02-03
**Criteria Source**: 12_architecture_criteria.md

---

## Options Evaluated

### Enrichment Layer (Data Fetching)

| Option | Description | Pattern |
|--------|-------------|---------|
| **A** | Pipeline | Enrichers secuenciales con priority() |
| **B** | Specification Pattern | DDD-style con isSatisfiedBy() |

### Transformation Layer (JSON Building)

| Option | Description | Pattern |
|--------|-------------|---------|
| **X** | Symfony Normalizers | Auto-discovery, framework-native |
| **Y** | DTO Factory | Type-safe, explicit factories |

---

## Enrichment Evaluation: Pipeline vs Specification

| Criterion | Weight | A: Pipeline | B: Specification |
|-----------|--------|-------------|------------------|
| **C1: Evolvability** | 5 (CRITICAL) | 5 (25) | 5 (25) |
| **C2: Extensibility** | 5 (CRITICAL) | 5 (25) | 5 (25) |
| **C3: Test Coverage** | 5 (CRITICAL) | 5 (25) | 5 (25) |
| **C4: Performance** | 4 | 4 (16) | 4 (16) |
| **C5: Simplicity** | 4 | 5 (20) | 4 (16) |
| **C6: Flexibility** | 4 | 5 (20) | 5 (20) |
| **C7: Maintainability** | 4 | 5 (20) | 4 (16) |
| **C8: PHPStan L9** | 4 | 5 (20) | 5 (20) |
| **C9: Backward Compat** | 4 | 5 (20) | 5 (20) |
| **C10: Min File Changes** | 3 | 5 (15) | 5 (15) |
| **TOTAL** | | **206** | **198** |

### Enrichment Winner: **Pipeline (206)**

**Why Pipeline wins**:
- Simplicity: Naming más intuitivo (`supports()` vs `isSatisfiedBy()`)
- Maintainability: Concepto más familiar (no requiere conocer DDD)
- Functionally identical, pero Pipeline es más directo

**Why Specification loses**:
- No añade valor real vs Pipeline
- Naming DDD puede confundir en contexto no-DDD
- "isSatisfiedBy" es semánticamente para validación, no enrichment

---

## Transformation Evaluation: Normalizers vs DTO Factory

| Criterion | Weight | X: Normalizers | Y: DTO Factory |
|-----------|--------|----------------|----------------|
| **C1: Evolvability** | 5 (CRITICAL) | 5 (25) | 4 (20) |
| **C2: Extensibility** | 5 (CRITICAL) | 5 (25) | 4 (20) |
| **C3: Test Coverage** | 5 (CRITICAL) | 4 (20) | 5 (25) |
| **C4: Performance** | 4 | 3 (12) | 5 (20) |
| **C5: Simplicity** | 4 | 4 (16) | 5 (20) |
| **C6: Flexibility** | 4 | 5 (20) | 4 (16) |
| **C7: Maintainability** | 4 | 4 (16) | 5 (20) |
| **C8: PHPStan L9** | 4 | 4 (16) | 5 (20) |
| **C9: Backward Compat** | 4 | 5 (20) | 5 (20) |
| **C10: Min File Changes** | 3 | 5 (15) | 3 (9) |
| **TOTAL** | | **185** | **190** |

### Transformation Winner: **DTO Factory (190)**

**Why DTO Factory wins (re-evaluated with your criteria)**:
- **Test Coverage**: Más fácil de testear al 100% (explícito)
- **Performance**: Sin reflection, más rápido
- **Simplicity**: Código explícito, sin "magia"
- **PHPStan L9**: Full type safety, mejor inferencia
- **Maintainability**: IDE support completo, refactoring seguro

**Why Normalizers loses**:
- Test Coverage: Auto-discovery hace más difícil garantizar 100%
- Performance: Reflection overhead
- PHPStan: Types menos explícitos

---

## Combined Evaluation

| Combination | Enrichment | Transformation | Total Score |
|-------------|------------|----------------|-------------|
| **Pipeline + DTO Factory** | 206 | 190 | **396** |
| Pipeline + Normalizers | 206 | 185 | 391 |
| Specification + DTO Factory | 198 | 190 | 388 |
| Specification + Normalizers | 198 | 185 | 383 |

### WINNER: **Pipeline + DTO Factory (396)**

---

## Detailed Justification

### Why Pipeline + DTO Factory?

#### For Your Priority #1: Performance
- DTO Factory: Sin reflection, compilación directa
- Pipeline: Ejecución lineal predecible
- **Score: 5/5**

#### For Your Priority #2: Simplicity
- Pipeline: `supports()`, `priority()`, `enrich()` - 3 métodos simples
- DTO Factory: Código explícito, sin auto-discovery
- **Score: 5/5**

#### For Your Priority #3: Flexibility
- Pipeline: Añadir enricher = 1 fichero
- DTO Factory: Añadir campo = modificar 2 ficheros (DTO + Factory)
- **Score: 4/5** (trade-off aceptable por type safety)

#### For Your Critical Criteria

| Critical Criterion | Score | Why |
|--------------------|-------|-----|
| Evolvability | 5 | Ambos patrones son Open/Closed compliant |
| Extensibility | 5 | Pipeline: 1 file. Factory: 2 files pero type-safe |
| Test Coverage 100% | 5 | Explícito = más fácil de cubrir al 100% |

---

## Trade-offs Accepted

| What We Gain | What We Give Up |
|--------------|-----------------|
| Type safety completo | Añadir campo = 2 files vs 1 |
| Performance (sin reflection) | Más verbose |
| IDE autocompletion | Menos "magic" |
| 100% test coverage easier | Más boilerplate |

**Aceptable porque**: El equipo es senior y prefiere explicitness sobre convención.

---

## Final Recommendation

```
╔════════════════════════════════════════════════════════════╗
║  ARCHITECTURE SELECTED: Pipeline + DTO Factory              ║
║                                                             ║
║  Enrichment:     Pipeline (EnricherInterface)               ║
║  Transformation: DTO Factory (EditorialResponseFactory)     ║
║  External APIs:  Gateway Pattern (Port/Adapter)             ║
║  Cross-cutting:  Decorator (Cache + Circuit Breaker)        ║
╚════════════════════════════════════════════════════════════╝
```

### Key Files for New Data Field

```
Añadir "commentsCount" al JSON:

1. CommentsEnricher.php (fetch data)         → NEW
2. EditorialResponse.php (add property)      → MODIFY
3. EditorialResponseFactory.php (add line)   → MODIFY

Total: 1 new + 2 modified = 3 files
Trade-off: +1 file vs Normalizers, but full type safety
```

---

## Validation Against Your Answers

| Your Answer | Architecture Support |
|-------------|---------------------|
| "API escalable" | ✅ Pipeline scales linearly |
| "Buen performance" | ✅ DTO Factory sin reflection |
| "Facilidad de implementación" | ✅ Pipeline: 1 file per enricher |
| "Debe evolucionar fácilmente" | ✅ Both patterns are Open/Closed |
| "Equipo senior" | ✅ Can handle explicit patterns |
| "100% coverage" | ✅ DTO Factory easier to test |
| "PHPStan Level 9" | ✅ Full type safety |
