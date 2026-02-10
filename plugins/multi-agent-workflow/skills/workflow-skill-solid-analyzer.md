---
name: workflow-skill-solid-analyzer
description: "Analyzes code for SOLID principle violations with automated detection and severity scoring. MANDATORY for ALL tasks - use before any implementation. <example>Context: Need to check SOLID compliance.\\nuser: \"Check if our code follows SOLID\"\\nassistant: \"I'll use workflow-skill-solid-analyzer to detect any SOLID violations\"</example>"
model: inherit
context: fork
hooks:
  Stop:
    - command: "echo '[solid-analyzer] SOLID analysis complete. Report saved to .ai/project/analysis/'"
---

# SOLID Analyzer Skill

Automated analysis tool for detecting SOLID principle violations in codebases. Provides violation detection, severity scoring, and pattern recommendations.

## Philosophy

> "No puedes arreglar lo que no puedes medir"

This skill provides **objective, measurable** SOLID compliance analysis, not subjective opinions. Every violation is:
- Quantifiable (with metrics)
- Locatable (with file:line)
- Actionable (with recommended pattern)

## Invocation

```bash
# Analyze specific path
/workflow-skill:solid-analyzer --path=src/services

# Analyze with severity threshold
/workflow-skill:solid-analyzer --path=src --min-severity=high

# Analyze and output to file
/workflow-skill:solid-analyzer --path=src --output=.ai/solid-report.md

# Quick summary only
/workflow-skill:solid-analyzer --path=src --summary

# Focus on specific principle
/workflow-skill:solid-analyzer --path=src --principle=SRP

# Full analysis with pattern recommendations
/workflow-skill:solid-analyzer --path=src --recommend-patterns
```

## Analysis Process

### Step 1: Metric Collection

Collect quantitative metrics for each file/class:

```yaml
metrics_collected:
  per_class:
    - lines_of_code
    - public_method_count
    - constructor_dependency_count
    - interface_method_count
    - abstract_vs_concrete_dependencies
    - cyclomatic_complexity
    - import_statements

  per_file:
    - class_count
    - interface_count
    - layer_violations (domain importing infrastructure)
```

### Step 2: Violation Detection

#### S - Single Responsibility Violations

**Detection Rules:**

| Rule ID | Violation | Detection Method | Threshold |
|---------|-----------|-----------------|-----------|
| SRP-001 | God Class | Lines of code | >200 lines |
| SRP-002 | Too Many Methods | Public method count | >7 methods |
| SRP-003 | Too Many Dependencies | Constructor params | >7 dependencies |
| SRP-004 | Mixed Concerns | Domain + Infrastructure imports | Any mix |
| SRP-005 | Multiple Responsibilities | Class name contains "And" or multiple nouns | Name analysis |

**Search Patterns:**

```bash
# SRP-001: God Classes (>200 lines)
# Count lines per class file
find {path} -name "*.php" -exec wc -l {} + | sort -rn

# SRP-002: Too many public methods
Grep: pattern="public function" output_mode=count

# SRP-003: Too many constructor dependencies
Grep: pattern="__construct\([^)]{100,}\)" output_mode=content

# SRP-004: Mixed concerns
# Domain importing Infrastructure
Grep: pattern="use App\\Infrastructure" path=src/Domain

# SRP-005: Bad naming
Grep: pattern="class \w+(And|Manager|Handler|Processor|Service)\w*"
```

#### O - Open/Closed Violations

**Detection Rules:**

| Rule ID | Violation | Detection Method | Threshold |
|---------|-----------|-----------------|-----------|
| OCP-001 | Type Switching | switch/if-else by type | Any occurrence |
| OCP-002 | Instanceof Chains | Multiple instanceof checks | >2 in method |
| OCP-003 | Hardcoded Types | String type comparisons | Any occurrence |
| OCP-004 | Non-extensible | Final class without interface | Any occurrence |

**Search Patterns:**

```bash
# OCP-001: Switch by type
Grep: pattern="switch\s*\(\s*\$\w+->getType\(\)" output_mode=content
Grep: pattern="switch\s*\(\s*\$type\s*\)" output_mode=content

# OCP-002: Instanceof chains
Grep: pattern="instanceof" output_mode=content -C 5

# OCP-003: String type comparisons
Grep: pattern="=== ['\"][\w]+['\"]" output_mode=content
Grep: pattern="getType\(\)\s*===\s*['\"]" output_mode=content

# OCP-004: Final without interface
Grep: pattern="final class" output_mode=files_with_matches
# Then check if they implement interfaces
```

#### L - Liskov Substitution Violations

**Detection Rules:**

| Rule ID | Violation | Detection Method | Threshold |
|---------|-----------|-----------------|-----------|
| LSP-001 | Exception in Override | throw in overridden method | New exception types |
| LSP-002 | Empty Implementation | Empty method body or return null | Any occurrence |
| LSP-003 | Type Narrowing | More restrictive param types | Type analysis |
| LSP-004 | Contract Change | Different return type | Type analysis |

**Search Patterns:**

```bash
# LSP-001: Throwing in override (need context)
Grep: pattern="throw new \w+Exception" output_mode=content

# LSP-002: Empty implementations
Grep: pattern="function \w+\([^)]*\)\s*:\s*\w+\s*\{\s*\}" output_mode=content
Grep: pattern="return null;" output_mode=content

# LSP-003/004: Requires static analysis tool
# Recommend PHPStan level 9 or TypeScript strict mode
```

#### I - Interface Segregation Violations

**Detection Rules:**

| Rule ID | Violation | Detection Method | Threshold |
|---------|-----------|-----------------|-----------|
| ISP-001 | Fat Interface | Interface method count | >5 methods |
| ISP-002 | Unused Methods | NotImplementedException | Any occurrence |
| ISP-003 | Partial Implementation | Empty methods in impl | Any occurrence |
| ISP-004 | God Interface | Interface > 100 lines | >100 lines |

**Search Patterns:**

```bash
# ISP-001: Fat interfaces
# Count methods per interface
Grep: pattern="interface \w+" output_mode=files_with_matches
# Then count "public function" per file

# ISP-002: NotImplementedException
Grep: pattern="NotImplemented|throw new \w*NotSupported" output_mode=content

# ISP-003: Empty implementations
Grep: pattern="public function \w+\([^)]*\)\s*\{\s*\/\/" output_mode=content

# ISP-004: Large interfaces
# Check interface file sizes
```

#### D - Dependency Inversion Violations

**Detection Rules:**

| Rule ID | Violation | Detection Method | Threshold |
|---------|-----------|-----------------|-----------|
| DIP-001 | Concrete Dependency | new ConcreteClass() | Any in domain |
| DIP-002 | Layer Violation | Domain→Infrastructure import | Any occurrence |
| DIP-003 | Missing Interface | Class without interface | Service/Repository |
| DIP-004 | Static Calls | Static method calls | Any in domain |

**Search Patterns:**

```bash
# DIP-001: Direct instantiation
Grep: pattern="new [A-Z]\w+(Client|Service|Repository|Gateway)" output_mode=content

# DIP-002: Layer violations
Grep: pattern="use App\\Infrastructure" path=src/Domain output_mode=content
Grep: pattern="use App\\Infrastructure" path=src/Application output_mode=content

# DIP-003: Missing interfaces
# Find classes not implementing interfaces
Grep: pattern="class \w+ \{" output_mode=files_with_matches
# vs
Grep: pattern="class \w+ implements" output_mode=files_with_matches

# DIP-004: Static calls
Grep: pattern="::\w+\(" output_mode=content
```

### Step 3: Severity Scoring

Each violation is scored:

| Severity | Score | Criteria |
|----------|-------|----------|
| **Critical** | 10 | Breaks architecture, hard to test, blocks features |
| **High** | 7 | Significant maintainability issue |
| **Medium** | 4 | Code smell, technical debt |
| **Low** | 2 | Minor issue, style preference |

**Severity Matrix:**

| Violation | Default Severity | Upgrade Condition |
|-----------|------------------|-------------------|
| SRP-001 God Class | High | Critical if >500 lines |
| SRP-002 Too Many Methods | Medium | High if >15 methods |
| SRP-003 Too Many Deps | High | Critical if >12 deps |
| OCP-001 Type Switching | High | Critical if >5 cases |
| OCP-002 Instanceof Chain | Medium | High if >4 checks |
| LSP-001 Exception Override | High | - |
| LSP-002 Empty Implementation | Medium | High if in interface |
| ISP-001 Fat Interface | Medium | High if >10 methods |
| ISP-002 NotImplemented | High | Critical if production code |
| DIP-001 Concrete Dependency | High | Critical if in domain |
| DIP-002 Layer Violation | Critical | - |

### Step 4: Pattern Recommendation

For each violation, recommend corrective pattern from `solid-pattern-matrix.md`:

```yaml
violation:
  id: SRP-001
  type: "God Class"
  location: "src/services/OrderService.php"
  severity: Critical
  metrics:
    lines: 542
    methods: 23
    dependencies: 15

recommendation:
  primary_pattern: "Strategy"
  confidence: 0.92
  rationale: "Multiple payment algorithms detected in switch statements"

  secondary_pattern: "Extract Class"
  confidence: 0.85
  rationale: "Clear separation possible between order and notification logic"

  implementation_hint: |
    1. Extract PaymentStrategy interface
    2. Create concrete strategies per payment type
    3. Extract NotificationService
    4. OrderService becomes thin orchestrator (~50 lines)
```

## Output Format

### Full Report

```markdown
# SOLID Analysis Report

**Analyzed**: {path}
**Date**: {timestamp}
**Files Scanned**: {count}
**Classes Analyzed**: {count}

## Executive Summary

| Principle | Violations | Critical | High | Score |
|-----------|------------|----------|------|-------|
| S - Single Responsibility | 5 | 1 | 3 | 65/100 |
| O - Open/Closed | 3 | 0 | 2 | 78/100 |
| L - Liskov Substitution | 1 | 0 | 1 | 90/100 |
| I - Interface Segregation | 2 | 0 | 1 | 85/100 |
| D - Dependency Inversion | 4 | 2 | 1 | 60/100 |

**Overall SOLID Score**: 75.6/100
**Grade**: C (Needs Improvement)

### Score Interpretation

| Score | Grade | Meaning |
|-------|-------|---------|
| 90-100 | A | Excellent SOLID compliance |
| 80-89 | B | Good, minor issues |
| 70-79 | C | Needs improvement |
| 60-69 | D | Significant violations |
| <60 | F | Major refactoring needed |

## Detailed Violations

### Critical Violations (Fix Immediately)

#### V1: DIP-002 - Layer Violation
**Location**: `src/Domain/Service/OrderService.php:15`
**Severity**: Critical (10)

```php
// Line 15
use App\Infrastructure\Client\PaymentGatewayClient; // VIOLATION
```

**Impact**: Domain layer coupled to infrastructure, cannot test in isolation
**Pattern**: Ports & Adapters
**Fix**:
```php
// Create port in Domain
interface PaymentGatewayInterface {
    public function process(Payment $payment): PaymentResult;
}

// Implement adapter in Infrastructure
class PaymentGatewayClient implements PaymentGatewayInterface { ... }
```

#### V2: SRP-001 - God Class
**Location**: `src/Service/OrderService.php`
**Severity**: Critical (10)

**Metrics**:
- Lines: 542 (threshold: 200)
- Methods: 23 (threshold: 7)
- Dependencies: 15 (threshold: 7)

**Detected Responsibilities**:
1. Order creation
2. Order validation
3. Payment processing
4. Inventory checking
5. Notification sending
6. Shipping calculation

**Pattern**: Strategy + Extract Class
**Fix**: See migration plan in recommendations

### High Priority Violations

#### V3: OCP-001 - Type Switching
**Location**: `src/Service/PaymentProcessor.php:45-89`
**Severity**: High (7)

```php
// Lines 45-89
switch ($payment->getType()) {
    case 'credit_card':
        return $this->processCreditCard($payment);
    case 'paypal':
        return $this->processPayPal($payment);
    case 'bank_transfer':
        return $this->processBankTransfer($payment);
    // Adding new type requires modifying this file
}
```

**Pattern**: Strategy
**Fix**:
```php
interface PaymentStrategyInterface {
    public function supports(string $type): bool;
    public function process(Payment $payment): PaymentResult;
}

class PaymentProcessor {
    public function __construct(
        private iterable $strategies // injected strategies
    ) {}

    public function process(Payment $payment): PaymentResult {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($payment->getType())) {
                return $strategy->process($payment);
            }
        }
        throw new UnsupportedPaymentTypeException();
    }
}
```

[... more violations ...]

## Recommendations Summary

### Priority 1: Critical (This Sprint)

| Violation | File | Pattern | Effort |
|-----------|------|---------|--------|
| DIP-002 | OrderService.php | Ports & Adapters | 2h |
| SRP-001 | OrderService.php | Strategy + Extract | 8h |

### Priority 2: High (Next Sprint)

| Violation | File | Pattern | Effort |
|-----------|------|---------|--------|
| OCP-001 | PaymentProcessor.php | Strategy | 4h |
| DIP-001 | UserRepository.php | Repository Interface | 2h |

### Priority 3: Medium (Backlog)

[... remaining violations ...]

## Architecture Health Indicators

### Dependency Direction
```
Domain ← Application ← Infrastructure ← Presentation
   ✓         ✓              ✓              ✓

Legend: ✓ = correct direction, ✗ = violation
```

### Layer Violations Map
```
Domain:
  - OrderService.php → Infrastructure (VIOLATION)
  - UserEntity.php → OK

Application:
  - CreateOrderHandler.php → OK
  - PaymentProcessor.php → OK

Infrastructure:
  - (no domain imports expected)
```

## Appendix: Raw Metrics

### Class Size Distribution

| Size (lines) | Count | Percentage |
|--------------|-------|------------|
| 0-50 | 45 | 60% |
| 51-100 | 20 | 27% |
| 101-200 | 7 | 9% |
| 201-500 | 2 | 3% |
| >500 | 1 | 1% |

### Dependency Count Distribution

| Dependencies | Count | Percentage |
|--------------|-------|------------|
| 0-3 | 52 | 69% |
| 4-7 | 18 | 24% |
| 8-12 | 4 | 5% |
| >12 | 1 | 1% |
```

### Summary Output (--summary)

```markdown
# SOLID Quick Summary: {path}

**Score**: 75.6/100 (Grade C)
**Critical Issues**: 2
**High Issues**: 5
**Top Priority**: Fix DIP-002 in OrderService.php

| Principle | Score | Status |
|-----------|-------|--------|
| S | 65 | Needs work |
| O | 78 | Acceptable |
| L | 90 | Good |
| I | 85 | Good |
| D | 60 | Needs work |

Run with `--recommend-patterns` for fix suggestions.
```

## Integration with Workflow

### Automatic Triggers

The analyzer runs automatically when:
- `/workflows:plan` starts (baseline analysis)
- `/workflows:solid-refactor` is invoked
- Architecture criteria evaluation includes SOLID
- PR review for files in `src/Domain` or `src/Application`

### CI/CD Integration

```yaml
# Example GitHub Action
solid-check:
  runs-on: ubuntu-latest
  steps:
    - name: SOLID Analysis
      run: |
        /workflow-skill:solid-analyzer --path=src --min-severity=high --output=solid-report.md

    - name: Check Score
      run: |
        score=$(grep "Overall SOLID Score" solid-report.md | grep -oP '\d+\.\d+')
        if (( $(echo "$score < 70" | bc -l) )); then
          echo "SOLID score below threshold: $score"
          exit 1
        fi
```

### Output Files

Generates in `.ai/project/analysis/`:
```
├── solid-report-{timestamp}.md    # Full report
├── solid-summary-{timestamp}.md   # Quick summary
└── solid-violations.json          # Machine-readable
```

## Configuration

### .solid-analyzer.yaml

```yaml
# Thresholds (override defaults)
thresholds:
  srp:
    max_lines: 200
    max_methods: 7
    max_dependencies: 7
  isp:
    max_interface_methods: 5

# Paths to exclude
exclude:
  - vendor/
  - tests/
  - migrations/

# Severity overrides
severity_overrides:
  - rule: SRP-001
    in_path: "src/Legacy/*"
    severity: medium  # downgrade for legacy code

# Pattern preferences
pattern_preferences:
  - violation: OCP-001
    prefer: Strategy
    avoid: Visitor  # team not familiar
```

## Related

- `core/solid-pattern-matrix.md` - Pattern selection guide
- `agents/design/solid-architecture-generator.md` - Architecture generation
- `commands/workflows/solid-refactor.md` - Refactoring workflow
- `architecture-quality-criteria.md` - Quality metrics
