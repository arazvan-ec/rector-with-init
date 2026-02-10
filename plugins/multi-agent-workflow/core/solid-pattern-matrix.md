# SOLID Pattern Matrix

> "No son las arquitecturas las que son buenas o malas, son los patrones correctos aplicados a las violaciones correctas"

This document provides the **definitive mapping** between SOLID violations and the design patterns that fix them. Use this matrix to make architecture decisions that guarantee SOLID compliance.

## Quick Reference: Violation → Pattern

| Violación SOLID | Síntoma | Patrón Correctivo | Alternativa |
|-----------------|---------|-------------------|-------------|
| **SRP** God Class | Clase >200 líneas, >7 métodos | **Strategy** + Extract Class | Facade + Services |
| **SRP** Mixed Concerns | Lógica de negocio + I/O juntos | **Repository** + Service Layer | Ports & Adapters |
| **OCP** Switch/If-else por tipo | `if (type === 'A')` chains | **Strategy** | Factory Method |
| **OCP** Modificar para extender | Añadir feature = cambiar clase | **Decorator** | Template Method |
| **LSP** Subtipo incompatible | Override que cambia contrato | **Composition over Inheritance** | Adapter |
| **LSP** Excepciones inesperadas | Subtipo lanza excepciones nuevas | **Null Object** | Optional/Maybe |
| **ISP** Interface gorda | Interface >5 métodos | **Interface Segregation** | Role Interfaces |
| **ISP** Métodos no usados | `throw NotImplemented` | **Adapter** | Facade |
| **DIP** Dependencia concreta | `new ConcreteClass()` directo | **Dependency Injection** | Abstract Factory |
| **DIP** Alto→Bajo nivel | Domain importa Infrastructure | **Ports & Adapters** | Gateway Pattern |

---

## Detailed Pattern Matrix

### S - Single Responsibility Principle

> "Una clase debe tener una, y solo una, razón para cambiar"

#### Violation: God Class

**Symptoms:**
- Clase con >200 líneas
- >7 métodos públicos
- >10 dependencias en constructor
- Múltiples `#region` o secciones comentadas
- Nombre genérico: `Manager`, `Handler`, `Processor`, `Service`

**Corrective Patterns:**

| Pattern | When to Use | SOLID Score | Example |
|---------|-------------|-------------|---------|
| **Strategy** | Múltiples algoritmos/comportamientos | S:5 O:5 L:5 I:5 D:5 | `PaymentStrategy` → `CreditCardPayment`, `PayPalPayment` |
| **Extract Class** | Responsabilidades claramente separables | S:5 O:3 L:4 I:4 D:3 | `UserService` → `UserValidator`, `UserNotifier` |
| **Facade** | Simplificar interfaz compleja | S:4 O:4 L:4 I:5 D:4 | `OrderFacade` coordina `Inventory`, `Payment`, `Shipping` |
| **Mediator** | Reducir comunicación N-to-N | S:5 O:4 L:4 I:4 D:5 | `ChatMediator` entre `User` objects |

**Decision Tree:**
```
¿Tiene múltiples algoritmos intercambiables?
  → SÍ: Strategy
  → NO: ¿Las responsabilidades son independientes?
    → SÍ: Extract Class
    → NO: ¿Coordina múltiples subsistemas?
      → SÍ: Facade
      → NO: ¿Hay comunicación N-to-N entre objetos?
        → SÍ: Mediator
        → NO: Extract Class con composición
```

#### Violation: Mixed Concerns

**Symptoms:**
- Lógica de negocio mezclada con acceso a datos
- HTTP handling en domain logic
- Logging/caching inline en métodos de negocio

**Corrective Patterns:**

| Pattern | When to Use | SOLID Score | Example |
|---------|-------------|-------------|---------|
| **Repository** | Separar persistencia de dominio | S:5 O:4 L:5 I:4 D:5 | `UserRepository` abstrae DB access |
| **Service Layer** | Coordinar casos de uso | S:5 O:4 L:4 I:4 D:4 | `OrderService` usa `Repository` + `PaymentGateway` |
| **Decorator** | Cross-cutting concerns (logging, cache) | S:5 O:5 L:5 I:5 D:5 | `CachedRepository` wraps `Repository` |

---

### O - Open/Closed Principle

> "Abierto para extensión, cerrado para modificación"

#### Violation: Type Switching

**Symptoms:**
- `switch(type)` o `if-else` chains por tipo
- Añadir nuevo tipo requiere modificar código existente
- Métodos con múltiples `instanceof` checks

**Corrective Patterns:**

| Pattern | When to Use | SOLID Score | Example |
|---------|-------------|-------------|---------|
| **Strategy** | Algoritmos intercambiables en runtime | S:5 O:5 L:5 I:5 D:5 | `DiscountStrategy` → `PercentDiscount`, `FixedDiscount` |
| **Factory Method** | Creación de objetos por tipo | S:4 O:5 L:5 I:4 D:5 | `NotificationFactory.create(type)` |
| **Chain of Responsibility** | Procesar por múltiples handlers | S:5 O:5 L:5 I:5 D:4 | `ValidationChain` para validadores |
| **Visitor** | Operaciones sobre jerarquía de objetos | S:4 O:5 L:4 I:3 D:4 | `ReportVisitor` genera diferentes reportes |

**Decision Tree:**
```
¿El comportamiento varía en runtime?
  → SÍ: Strategy
  → NO: ¿Es sobre creación de objetos?
    → SÍ: Factory Method / Abstract Factory
    → NO: ¿Múltiples procesadores en secuencia?
      → SÍ: Chain of Responsibility
      → NO: ¿Operaciones sobre jerarquía de tipos?
        → SÍ: Visitor
        → NO: Strategy (default)
```

#### Violation: Modification for Extension

**Symptoms:**
- Añadir feature = modificar clase existente
- Métodos que crecen con cada nuevo requisito
- Flags booleanos para habilitar comportamientos

**Corrective Patterns:**

| Pattern | When to Use | SOLID Score | Example |
|---------|-------------|-------------|---------|
| **Decorator** | Añadir comportamiento sin modificar | S:5 O:5 L:5 I:5 D:5 | `LoggingService` wraps `Service` |
| **Template Method** | Algoritmo fijo con pasos variables | S:4 O:5 L:4 I:4 D:4 | `DataImporter` con `parse()` abstracto |
| **Plugin/Extension** | Funcionalidad pluggable | S:5 O:5 L:5 I:5 D:5 | `PluginManager` carga extensiones |

---

### L - Liskov Substitution Principle

> "Los subtipos deben ser sustituibles por sus tipos base"

#### Violation: Contract Breaking Subtypes

**Symptoms:**
- Override que cambia comportamiento esperado
- Subtipo que lanza excepciones no esperadas
- Precondiciones más estrictas en subtipo
- Postcondiciones más débiles en subtipo

**Corrective Patterns:**

| Pattern | When to Use | SOLID Score | Example |
|---------|-------------|-------------|---------|
| **Composition over Inheritance** | Evitar jerarquías problemáticas | S:5 O:5 L:5 I:5 D:5 | `Car` HAS-A `Engine` vs IS-A `Vehicle` |
| **Adapter** | Adaptar interfaz incompatible | S:5 O:4 L:5 I:5 D:5 | `LegacyPaymentAdapter` implements `PaymentInterface` |
| **Null Object** | Evitar null checks y excepciones | S:4 O:4 L:5 I:4 D:4 | `NullLogger` implements `Logger` (no-op) |
| **Interface Extraction** | Definir contrato explícito | S:4 O:5 L:5 I:5 D:5 | Extract `Readable` from `File` class |

**Decision Tree:**
```
¿El subtipo viola el contrato del padre?
  → SÍ: ¿Es por incompatibilidad fundamental?
    → SÍ: Composition over Inheritance
    → NO: ¿Es por sistema legacy?
      → SÍ: Adapter
      → NO: Interface Extraction
  → NO: ¿El problema es con null/ausencia?
    → SÍ: Null Object / Optional
    → NO: Revisar precondiciones/postcondiciones
```

---

### I - Interface Segregation Principle

> "Los clientes no deben depender de interfaces que no usan"

#### Violation: Fat Interface

**Symptoms:**
- Interface con >5 métodos
- Implementaciones con métodos vacíos o `throw NotImplemented`
- Clientes que solo usan 2-3 métodos de una interface grande

**Corrective Patterns:**

| Pattern | When to Use | SOLID Score | Example |
|---------|-------------|-------------|---------|
| **Role Interfaces** | Dividir por responsabilidad | S:5 O:5 L:5 I:5 D:5 | `Readable`, `Writable`, `Seekable` |
| **Adapter** | Implementar solo lo necesario | S:5 O:4 L:5 I:5 D:5 | `ReadOnlyFileAdapter` only implements `Readable` |
| **Facade** | Simplificar interface compleja | S:4 O:4 L:4 I:5 D:4 | `SimplePrinter` wraps complex `Printer` |

**Segregation Guidelines:**

```
Interface original → Interfaces segregadas

IRepository<T>           →  IReader<T>
  - getById()                 - getById()
  - getAll()                  - getAll()
  - save()                    - findBy()
  - delete()               IWriter<T>
  - findBy()                  - save()
  - count()                   - delete()
                           ICounter<T>
                              - count()
```

---

### D - Dependency Inversion Principle

> "Depender de abstracciones, no de concreciones"

#### Violation: Concrete Dependencies

**Symptoms:**
- `new ConcreteClass()` dentro de métodos
- Import de clases de Infrastructure en Domain
- Hardcoded paths, URLs, connection strings

**Corrective Patterns:**

| Pattern | When to Use | SOLID Score | Example |
|---------|-------------|-------------|---------|
| **Dependency Injection** | Inyectar dependencias | S:5 O:5 L:5 I:5 D:5 | Constructor injection via interface |
| **Abstract Factory** | Crear familias de objetos | S:4 O:5 L:5 I:4 D:5 | `UIFactory` → `WindowsFactory`, `MacFactory` |
| **Service Locator** | Resolver dependencias en runtime | S:3 O:4 L:4 I:3 D:4 | `Container.resolve<IService>()` |
| **Ports & Adapters** | Aislar dominio de infraestructura | S:5 O:5 L:5 I:5 D:5 | Domain Port + Infrastructure Adapter |

**Architecture Pattern: Ports & Adapters (Hexagonal)**

```
┌─────────────────────────────────────────────────────────┐
│                    APPLICATION CORE                      │
│  ┌─────────────────────────────────────────────────┐   │
│  │                   DOMAIN                         │   │
│  │  - Entities                                      │   │
│  │  - Value Objects                                 │   │
│  │  - Domain Services                               │   │
│  │  - Domain Events                                 │   │
│  └─────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────┐   │
│  │              PORTS (Interfaces)                  │   │
│  │  - RepositoryInterface                           │   │
│  │  - GatewayInterface                              │   │
│  │  - NotifierInterface                             │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                          │
         ┌────────────────┼────────────────┐
         ▼                ▼                ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│  ADAPTER    │  │  ADAPTER    │  │  ADAPTER    │
│  (HTTP)     │  │  (Database) │  │  (Queue)    │
│             │  │             │  │             │
│ Controller  │  │ Repository  │  │ Publisher   │
│ Middleware  │  │ ORM Config  │  │ Consumer    │
└─────────────┘  └─────────────┘  └─────────────┘
```

---

## Pattern Selection Algorithm

When facing a SOLID violation, follow this algorithm:

```
1. IDENTIFY the specific SOLID violation(s)
   └─ Use solid-analyzer skill

2. CLASSIFY the violation type
   └─ Use this matrix's "Symptoms" section

3. SELECT candidate patterns
   └─ Use this matrix's "Corrective Patterns" tables

4. EVALUATE patterns against context
   └─ Consider: team expertise, codebase style, performance needs

5. VALIDATE the solution
   └─ Verify ALL 5 SOLID principles are satisfied
   └─ Run solid-analyzer on proposed solution

6. DOCUMENT the decision
   └─ Record in architecture_decisions.md
```

## SOLID Compliance Score per Pattern

Use this table to select patterns that maximize SOLID compliance:

| Pattern | S | O | L | I | D | Total | Best For |
|---------|---|---|---|---|---|-------|----------|
| **Strategy** | 5 | 5 | 5 | 5 | 5 | **25** | Algoritmos intercambiables |
| **Decorator** | 5 | 5 | 5 | 5 | 5 | **25** | Añadir comportamiento |
| **Ports & Adapters** | 5 | 5 | 5 | 5 | 5 | **25** | Arquitectura completa |
| **Repository** | 5 | 4 | 5 | 4 | 5 | **23** | Acceso a datos |
| **Factory Method** | 4 | 5 | 5 | 4 | 5 | **23** | Creación de objetos |
| **Chain of Responsibility** | 5 | 5 | 5 | 5 | 4 | **24** | Procesamiento secuencial |
| **Adapter** | 5 | 4 | 5 | 5 | 5 | **24** | Integración |
| **Facade** | 4 | 4 | 4 | 5 | 4 | **21** | Simplificación |
| **Template Method** | 4 | 5 | 4 | 4 | 4 | **21** | Algoritmos con pasos |
| **Mediator** | 5 | 4 | 4 | 4 | 5 | **22** | Comunicación N-N |
| **Null Object** | 4 | 4 | 5 | 4 | 4 | **21** | Evitar nulls |
| **Visitor** | 4 | 5 | 4 | 3 | 4 | **20** | Operaciones en jerarquías |
| **Singleton** | 2 | 2 | 3 | 3 | 2 | **12** | ⚠️ Evitar si posible |

## Anti-Patterns to Avoid

| Anti-Pattern | SOLID Violations | Better Alternative |
|--------------|------------------|-------------------|
| **God Class** | S, O, I | Extract Class + Strategy |
| **Service Locator** | D, I | Dependency Injection |
| **Singleton** | S, O, D | DI with scoped lifetime |
| **Anemic Domain** | S | Rich Domain Model |
| **Circular Dependencies** | D, S | Mediator, Events |
| **Primitive Obsession** | S, O | Value Objects |
| **Feature Envy** | S | Move Method |
| **Shotgun Surgery** | S, O | Extract Class |

## Integration with Workflow

### Automatic Pattern Suggestion

When `/workflows:solid-refactor` detects a violation:

```yaml
violation_detected:
  type: "SRP - God Class"
  file: "src/services/OrderService.php"
  metrics:
    lines: 542
    methods: 23
    dependencies: 15

suggested_patterns:
  primary:
    pattern: "Strategy"
    reason: "Multiple payment processing algorithms detected"
    confidence: 0.92
  secondary:
    pattern: "Extract Class"
    reason: "Clear separation between order and notification logic"
    confidence: 0.85

proposed_architecture:
  - OrderService (orchestrator, ~50 lines)
  - PaymentStrategy (interface)
    - CreditCardPayment
    - PayPalPayment
    - BankTransferPayment
  - OrderNotificationService (~80 lines)
  - OrderValidationService (~60 lines)
```

### Documentation Template

When a pattern is applied, document:

```markdown
## Architecture Decision: {ID}

**Date**: {date}
**Violation**: {SOLID principle} - {specific violation}
**Location**: {file:line}

### Pattern Applied: {Pattern Name}

**Why this pattern?**
- SOLID compliance score: {X}/25
- Team familiarity: {High/Medium/Low}
- Fits existing codebase patterns: {Yes/No}

**Alternatives considered:**
1. {Pattern A}: Rejected because {reason}
2. {Pattern B}: Rejected because {reason}

**Implementation:**
```
{code structure}
```

**Verification:**
- [ ] SRP: Single reason to change
- [ ] OCP: Extensible without modification
- [ ] LSP: Subtypes substitutable
- [ ] ISP: No unused interface methods
- [ ] DIP: Depends on abstractions
```

---

## Related Documents

- `architecture-quality-criteria.md` - Métricas SOLID verificables
- `agents/design/solid-architecture-generator.md` - Agente que genera arquitecturas
- `skills/solid-analyzer.md` - Análisis automático de SOLID
- `commands/workflows/solid-refactor.md` - Comando de refactorización
