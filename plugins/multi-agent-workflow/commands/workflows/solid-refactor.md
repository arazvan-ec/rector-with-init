---
name: solid-refactor
description: "Complete SOLID-compliant refactoring workflow. Analyzes code, detects violations, selects patterns, generates architecture, and guides implementation. <example>Context: Legacy code needs SOLID refactoring.\\nuser: \"Refactor the payment module to be SOLID compliant\"\\nassistant: \"/workflows:solid-refactor --path=src/Payment\"</example>"
model: inherit
---

# /workflows:solid-refactor

> **Tier 3 (Automatic)**: SOLID refactoring is triggered automatically by `/workflows:review` when the score is below 18/25. Only invoke this command manually for standalone SOLID refactoring of existing code.

Complete workflow for refactoring code to strict SOLID compliance. This command orchestrates analysis, pattern selection, architecture generation, and implementation guidance.

## Synopsis

```bash
/workflows:solid-refactor --path=<target-path> [options]
```

## Options

| Option | Description | Default |
|--------|-------------|---------|
| `--path` | Target directory or file to refactor | Required |
| `--min-score` | Minimum SOLID score to achieve | 22/25 |
| `--dry-run` | Analyze and propose without changes | false |
| `--interactive` | Confirm each refactoring step | true |
| `--output` | Output directory for artifacts | `.ai/refactor/` |
| `--focus` | Focus on specific principle (S/O/L/I/D) | all |
| `--preserve-api` | Don't change public interfaces | false |

## Workflow Phases

### Phase 1: Analysis

```
┌─────────────────────────────────────────────────────────────┐
│                    PHASE 1: ANALYSIS                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Invoke /workflow-skill:solid-analyzer --path={target}            │
│     └─ Collect metrics for all classes                      │
│     └─ Detect violations with severity                      │
│     └─ Calculate baseline SOLID score                       │
│                                                              │
│  2. Generate violation report                                │
│     └─ Critical violations (must fix)                       │
│     └─ High violations (should fix)                         │
│     └─ Medium/Low violations (nice to fix)                  │
│                                                              │
│  3. Map violations to patterns                               │
│     └─ Read core/solid-pattern-matrix.md                    │
│     └─ Select best pattern per violation                    │
│     └─ Calculate expected post-refactor score               │
│                                                              │
│  Output: .ai/refactor/{timestamp}/01_analysis.md            │
└─────────────────────────────────────────────────────────────┘
```

### Phase 2: Architecture Design

```
┌─────────────────────────────────────────────────────────────┐
│                 PHASE 2: ARCHITECTURE DESIGN                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Invoke solid-architecture-generator agent               │
│     └─ Input: Violation list + Pattern recommendations      │
│     └─ Generate target architecture                         │
│     └─ Define new class structure                           │
│     └─ Define interfaces                                    │
│                                                              │
│  2. Validate proposed architecture                           │
│     └─ Verify SOLID score >= min-score                      │
│     └─ Check all critical violations addressed              │
│     └─ Ensure no new violations introduced                  │
│                                                              │
│  3. Generate class diagram                                   │
│     └─ Before vs After comparison                           │
│     └─ Dependency direction verification                    │
│                                                              │
│  Output: .ai/refactor/{timestamp}/02_architecture.md        │
└─────────────────────────────────────────────────────────────┘
```

### Phase 3: Migration Planning

```
┌─────────────────────────────────────────────────────────────┐
│                  PHASE 3: MIGRATION PLANNING                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Generate step-by-step migration plan                     │
│     └─ Identify safe refactoring order                      │
│     └─ Define intermediate states                           │
│     └─ Plan for backward compatibility (if needed)          │
│                                                              │
│  2. Estimate impact                                          │
│     └─ Files to create                                      │
│     └─ Files to modify                                      │
│     └─ Files to delete                                      │
│     └─ Tests to update                                      │
│                                                              │
│  3. Define checkpoints                                       │
│     └─ After each major refactoring step                    │
│     └─ Test verification at each checkpoint                 │
│                                                              │
│  Output: .ai/refactor/{timestamp}/03_migration_plan.md      │
└─────────────────────────────────────────────────────────────┘
```

### Phase 4: Implementation

```
┌─────────────────────────────────────────────────────────────┐
│                   PHASE 4: IMPLEMENTATION                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  For each step in migration plan:                            │
│                                                              │
│  1. Create new files (interfaces, classes)                   │
│     └─ Follow architecture specification                    │
│     └─ Apply selected patterns correctly                    │
│                                                              │
│  2. Migrate logic                                            │
│     └─ Extract from old classes                             │
│     └─ Place in new classes                                 │
│     └─ Maintain behavior (no functional changes)            │
│                                                              │
│  3. Update dependencies                                      │
│     └─ Wire new classes in DI container                     │
│     └─ Update imports                                       │
│                                                              │
│  4. Verify checkpoint                                        │
│     └─ Run tests                                            │
│     └─ Run /workflow-skill:solid-analyzer on changed files           │
│     └─ Confirm no regressions                               │
│                                                              │
│  5. If --interactive: confirm before proceeding              │
│                                                              │
│  Output: Refactored code + checkpoint logs                   │
└─────────────────────────────────────────────────────────────┘
```

### Phase 5: Verification

```
┌─────────────────────────────────────────────────────────────┐
│                    PHASE 5: VERIFICATION                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Final SOLID analysis                                     │
│     └─ /workflow-skill:solid-analyzer --path={target}                │
│     └─ Compare with baseline                                │
│     └─ Verify score >= min-score                            │
│                                                              │
│  2. Run full test suite                                      │
│     └─ All existing tests must pass                         │
│     └─ Coverage must not decrease                           │
│                                                              │
│  3. Architecture validation                                  │
│     └─ No layer violations                                  │
│     └─ All dependencies are interfaces                      │
│     └─ No God Classes remaining                             │
│                                                              │
│  4. Generate final report                                    │
│     └─ Before/After comparison                              │
│     └─ Patterns applied                                     │
│     └─ Recommendations for future                           │
│                                                              │
│  Output: .ai/refactor/{timestamp}/04_final_report.md        │
└─────────────────────────────────────────────────────────────┘
```

## Output Artifacts

```
.ai/refactor/{timestamp}/
├── 01_analysis.md           # Initial SOLID analysis
├── 02_architecture.md       # Target architecture design
├── 03_migration_plan.md     # Step-by-step migration
├── 04_final_report.md       # Verification and summary
├── checkpoints/
│   ├── checkpoint_1.md      # After step 1
│   ├── checkpoint_2.md      # After step 2
│   └── ...
└── diagrams/
    ├── before.md            # Class diagram before
    └── after.md             # Class diagram after
```

## Example Usage

### Basic Refactoring

```bash
# Refactor entire src/Service directory
/workflows:solid-refactor --path=src/Service

# Output:
# Analyzing src/Service...
# Found 12 SOLID violations (3 critical, 5 high, 4 medium)
# Baseline SOLID Score: 14/25 (Grade: C)
#
# Proposed refactoring:
# - Split OrderService (God Class) → 4 focused services
# - Apply Strategy pattern for payment processing
# - Extract interfaces for all repositories
#
# Expected SOLID Score: 24/25 (Grade: A)
#
# Proceed with refactoring? (y/n)
```

### Dry Run

```bash
# Analyze without making changes
/workflows:solid-refactor --path=src/Payment --dry-run

# Output:
# [DRY RUN MODE - No changes will be made]
#
# Analysis complete. Proposed changes saved to:
# .ai/refactor/2026-02-03_14-30/02_architecture.md
#
# Review the proposed architecture before running without --dry-run
```

### Focus on Specific Principle

```bash
# Only fix Dependency Inversion violations
/workflows:solid-refactor --path=src/Domain --focus=D

# Output:
# Focusing on Dependency Inversion (D) principle only
#
# Found 5 DIP violations:
# - OrderService.php:15 - imports Infrastructure
# - UserRepository.php:8 - concrete dependency
# ...
```

### High Score Target

```bash
# Require perfect SOLID score
/workflows:solid-refactor --path=src/Core --min-score=25

# Will not complete until achieving 25/25 score
```

## Interactive Mode

When `--interactive` is enabled (default), the workflow prompts at key decisions:

```
╔════════════════════════════════════════════════════════════╗
║           SOLID REFACTOR: Step 3 of 8                       ║
╚════════════════════════════════════════════════════════════╝

Current: Applying Strategy pattern to PaymentProcessor

This will:
  CREATE: src/Payment/Strategy/PaymentStrategyInterface.php
  CREATE: src/Payment/Strategy/CreditCardStrategy.php
  CREATE: src/Payment/Strategy/PayPalStrategy.php
  MODIFY: src/Payment/PaymentProcessor.php (remove switch, inject strategies)
  MODIFY: config/services.yaml (register strategies)

Tests affected: 3 files
Estimated impact: 5 files

[P]roceed  [S]kip  [M]odify  [A]bort  [D]etails
>
```

## Integration with Other Commands

### After Planning

```bash
# Plan feature first, then ensure SOLID compliance
/workflows:plan my-feature
/workflows:solid-refactor --path=src/MyFeature
```

### Before Review

```bash
# Ensure SOLID before code review
/workflows:solid-refactor --path=src/Changed --dry-run
/workflows:review
```

### In CI/CD

```yaml
# GitHub Action example
solid-check:
  steps:
    - name: SOLID Analysis
      run: /workflows:solid-refactor --path=src --dry-run --min-score=18

    - name: Fail if below threshold
      if: failure()
      run: echo "SOLID score below threshold. Run /workflows:solid-refactor locally."
```

## Pattern Application Examples

### Example 1: God Class → Strategy + Extract

**Before:**
```php
class OrderService {
    // 400+ lines
    // 15 methods
    // Handles: creation, validation, payment, notification, shipping
}
```

**After:**
```php
// 5 focused classes
class CreateOrderHandler { /* ~50 lines */ }
class OrderValidator { /* ~60 lines */ }
class PaymentStrategyInterface { /* 2 methods */ }
class OrderNotifier { /* ~40 lines */ }
class ShippingCalculator { /* ~50 lines */ }
```

### Example 2: Switch → Strategy

**Before:**
```php
public function process(Payment $payment): Result {
    switch ($payment->getType()) {
        case 'credit_card': return $this->processCreditCard($payment);
        case 'paypal': return $this->processPayPal($payment);
        // Adding new type = modify this file
    }
}
```

**After:**
```php
public function process(Payment $payment): Result {
    foreach ($this->strategies as $strategy) {
        if ($strategy->supports($payment)) {
            return $strategy->process($payment);
        }
    }
    // Adding new type = add new Strategy class (OCP compliant)
}
```

### Example 3: Concrete → Interface (DIP)

**Before:**
```php
class OrderService {
    public function __construct(
        private MySQLOrderRepository $repository, // Concrete!
        private StripePaymentClient $payment,     // Concrete!
    ) {}
}
```

**After:**
```php
class OrderService {
    public function __construct(
        private OrderRepositoryInterface $repository, // Interface
        private PaymentGatewayInterface $payment,     // Interface
    ) {}
}
```

## Troubleshooting

### "Cannot achieve minimum score"

If the refactoring cannot achieve the minimum score:

1. Check if violations require architectural redesign (not just refactoring)
2. Consider lowering `--min-score` temporarily
3. Review `.ai/refactor/*/02_architecture.md` for alternative approaches

### "Tests failing after refactoring"

1. Check if tests were testing implementation details (not behavior)
2. Review checkpoint logs for when tests started failing
3. Consider using `--preserve-api` if public interfaces must remain stable

### "Too many changes"

1. Use `--focus=X` to address one principle at a time
2. Start with Critical violations only
3. Use `--dry-run` to preview scope before committing

## Related

- `skills/solid-analyzer.md` - SOLID analysis tool
- `agents/design/solid-architecture-generator.md` - Architecture generation
- `core/solid-pattern-matrix.md` - Violation → Pattern mapping
- `skills/criteria-generator.md` - Architecture criteria
- `/workflows:plan` - Feature planning workflow
- `/workflows:review` - Code review workflow
