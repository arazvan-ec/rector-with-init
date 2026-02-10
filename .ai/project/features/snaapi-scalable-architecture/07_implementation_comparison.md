# Implementation Comparison - Ejemplos Prácticos

## Caso de Uso: Añadir `commentsCount` al JSON

Este es el test de validación de la arquitectura. Veamos cómo se implementa en cada alternativa.

---

## ENRICHMENT: Pipeline vs Specification Pattern

### Opción A: Pipeline (Score 143)

```php
// ============================================
// ESTRUCTURA DE DIRECTORIOS
// ============================================
src/
└── Application/
    └── Pipeline/
        ├── EditorialContext.php
        ├── EnricherInterface.php
        ├── EnrichmentPipeline.php
        └── Enricher/
            ├── EditorialEnricher.php
            ├── MultimediaEnricher.php
            ├── SectionEnricher.php
            ├── TagsEnricher.php
            ├── JournalistsEnricher.php
            └── CommentsEnricher.php      // ← NUEVO (1 fichero)

// ============================================
// INTERFAZ
// ============================================
// src/Application/Pipeline/EnricherInterface.php
interface EnricherInterface
{
    public function priority(): int;
    public function supports(EditorialContext $context): bool;
    public function enrich(EditorialContext $context): void;
}

// ============================================
// CONTEXT (DTO mutable)
// ============================================
// src/Application/Pipeline/EditorialContext.php
final class EditorialContext
{
    private array $data = [];

    public function __construct(
        private readonly string $editorialId,
    ) {}

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    // Getters tipados para IDE
    public function editorial(): ?Editorial { return $this->get('editorial'); }
    public function commentsCount(): int { return $this->get('commentsCount', 0); }
}

// ============================================
// PIPELINE
// ============================================
// src/Application/Pipeline/EnrichmentPipeline.php
final class EnrichmentPipeline
{
    public function __construct(
        #[TaggedIterator('app.enricher')]
        private iterable $enrichers,
        private LoggerInterface $logger,
    ) {}

    public function process(EditorialContext $context): EditorialContext
    {
        $sorted = $this->sortByPriority($this->enrichers);

        foreach ($sorted as $enricher) {
            $name = get_class($enricher);

            if (!$enricher->supports($context)) {
                $this->logger->debug("Skipped: {$name}");
                continue;
            }

            try {
                $enricher->enrich($context);
                $this->logger->info("Enriched: {$name}");
            } catch (Throwable $e) {
                $this->logger->warning("Failed: {$name}", ['error' => $e->getMessage()]);
                // Continúa - graceful degradation
            }
        }

        return $context;
    }
}

// ============================================
// ENRICHER EXISTENTE
// ============================================
// src/Application/Pipeline/Enricher/EditorialEnricher.php
final readonly class EditorialEnricher implements EnricherInterface
{
    public function __construct(
        private EditorialGatewayInterface $gateway,
    ) {}

    public function priority(): int { return 100; }

    public function supports(EditorialContext $context): bool
    {
        return true;
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $this->gateway->findById($context->editorialId());
        $context->set('editorial', $editorial);
    }
}

// ============================================
// AÑADIR commentsCount - SOLO ESTE FICHERO
// ============================================
// src/Application/Pipeline/Enricher/CommentsEnricher.php
final readonly class CommentsEnricher implements EnricherInterface
{
    public function __construct(
        private CommentsGatewayInterface $gateway,
    ) {}

    public function priority(): int { return 50; }

    public function supports(EditorialContext $context): bool
    {
        return $context->editorial() !== null;
    }

    public function enrich(EditorialContext $context): void
    {
        $count = $this->gateway->countByEditorialId($context->editorialId());
        $context->set('commentsCount', $count);
    }
}

// ============================================
// USO
// ============================================
$context = new EditorialContext('12345');
$enrichedContext = $pipeline->process($context);

echo $enrichedContext->commentsCount(); // 42
```

**Para añadir `commentsCount`:**
- Ficheros nuevos: 1 (`CommentsEnricher.php`)
- Ficheros modificados: 0
- Configuración: 0 (autoconfigure detecta el tag)

---

### Opción B: Specification Pattern (Score 140)

```php
// ============================================
// ESTRUCTURA DE DIRECTORIOS
// ============================================
src/
└── Application/
    └── Enrichment/
        ├── EditorialContext.php
        ├── EnrichmentSpecificationInterface.php
        ├── CompositeEnricher.php
        └── Specification/
            ├── EditorialSpecification.php
            ├── MultimediaSpecification.php
            ├── SectionSpecification.php
            ├── TagsSpecification.php
            ├── JournalistsSpecification.php
            └── CommentsSpecification.php  // ← NUEVO (1 fichero)

// ============================================
// INTERFAZ
// ============================================
// src/Application/Enrichment/EnrichmentSpecificationInterface.php
interface EnrichmentSpecificationInterface
{
    /**
     * ¿Esta especificación aplica para este contexto?
     */
    public function isSatisfiedBy(EditorialContext $context): bool;

    /**
     * Enriquece el contexto
     */
    public function enrich(EditorialContext $context): void;

    /**
     * Orden de ejecución
     */
    public function order(): int;
}

// ============================================
// COMPOSITE ENRICHER
// ============================================
// src/Application/Enrichment/CompositeEnricher.php
final class CompositeEnricher
{
    /** @var EnrichmentSpecificationInterface[] */
    private array $specifications;

    public function __construct(
        #[TaggedIterator('app.enrichment_spec')]
        iterable $specifications,
        private LoggerInterface $logger,
    ) {
        $this->specifications = $this->sortByOrder($specifications);
    }

    public function enrich(EditorialContext $context): EditorialContext
    {
        foreach ($this->specifications as $spec) {
            $name = get_class($spec);

            if (!$spec->isSatisfiedBy($context)) {
                $this->logger->debug("Specification not satisfied: {$name}");
                continue;
            }

            try {
                $spec->enrich($context);
                $this->logger->info("Specification applied: {$name}");
            } catch (Throwable $e) {
                $this->logger->warning("Specification failed: {$name}");
            }
        }

        return $context;
    }
}

// ============================================
// SPECIFICATION EXISTENTE
// ============================================
// src/Application/Enrichment/Specification/EditorialSpecification.php
final readonly class EditorialSpecification implements EnrichmentSpecificationInterface
{
    public function __construct(
        private EditorialGatewayInterface $gateway,
    ) {}

    public function order(): int { return 100; }

    public function isSatisfiedBy(EditorialContext $context): bool
    {
        return true;
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $this->gateway->findById($context->editorialId());
        $context->set('editorial', $editorial);
    }
}

// ============================================
// AÑADIR commentsCount - SOLO ESTE FICHERO
// ============================================
// src/Application/Enrichment/Specification/CommentsSpecification.php
final readonly class CommentsSpecification implements EnrichmentSpecificationInterface
{
    public function __construct(
        private CommentsGatewayInterface $gateway,
    ) {}

    public function order(): int { return 50; }

    public function isSatisfiedBy(EditorialContext $context): bool
    {
        return $context->editorial() !== null;
    }

    public function enrich(EditorialContext $context): void
    {
        $count = $this->gateway->countByEditorialId($context->editorialId());
        $context->set('commentsCount', $count);
    }
}
```

**Para añadir `commentsCount`:**
- Ficheros nuevos: 1 (`CommentsSpecification.php`)
- Ficheros modificados: 0
- Configuración: 0

---

### Comparación Pipeline vs Specification

| Aspecto | Pipeline | Specification |
|---------|----------|---------------|
| **Ficheros para nuevo dato** | 1 | 1 |
| **Naming** | `Enricher` | `Specification` |
| **Método condition** | `supports()` | `isSatisfiedBy()` |
| **Método order** | `priority()` | `order()` |
| **Concepto DDD** | No | Sí (Specification Pattern) |
| **Familiaridad equipo** | Alta (común) | Media (DDD) |
| **Semántica** | "Enriquecer datos" | "Satisfacer condición" |

**Diferencia real**: Solo naming y semántica. Funcionalmente idénticos.

---

## TRANSFORMATION: Normalizers vs DTO Factory

### Opción X: Symfony Normalizers (Score 138)

```php
// ============================================
// ESTRUCTURA DE DIRECTORIOS
// ============================================
src/
└── Infrastructure/
    └── Serializer/
        └── Normalizer/
            ├── EditorialNormalizer.php
            ├── BodyNormalizer.php
            ├── MultimediaNormalizer.php
            ├── SectionNormalizer.php
            ├── TagNormalizer.php
            ├── SignatureNormalizer.php
            └── BodyElement/
                ├── ParagraphNormalizer.php
                ├── SubHeadNormalizer.php
                └── ... (18 normalizers)

// ============================================
// NORMALIZER PRINCIPAL
// ============================================
// src/Infrastructure/Serializer/Normalizer/EditorialNormalizer.php
final class EditorialNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof EditorialContext;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        /** @var EditorialContext $object */
        $editorial = $object->editorial();

        return [
            'id' => $editorial->id(),
            'url' => $editorial->url(),
            'titles' => $this->normalizer->normalize($editorial->titles(), $format, $context),
            'lead' => $editorial->lead(),
            'publicationDate' => $editorial->publicationDate()->format('c'),
            'body' => $this->normalizer->normalize($editorial->body(), $format, $context),
            'multimedia' => $this->normalizer->normalize($object->multimedia(), $format, $context),
            'signatures' => $this->normalizer->normalize($object->journalists(), $format, $context),
            'section' => $this->normalizer->normalize($object->section(), $format, $context),
            'tags' => $this->normalizer->normalize($object->tags(), $format, $context),
            'commentsCount' => $object->commentsCount(),  // ← AÑADIR AQUÍ
        ];
    }
}

// ============================================
// BODY NORMALIZER (delega a elementos)
// ============================================
// src/Infrastructure/Serializer/Normalizer/BodyNormalizer.php
final class BodyNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof Body;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        /** @var Body $object */
        return [
            'type' => $object->type(),
            'elements' => array_map(
                fn($el) => $this->normalizer->normalize($el, $format, $context),
                $object->elements()
            ),
        ];
    }
}

// ============================================
// ELEMENT NORMALIZER (auto-descubierto)
// ============================================
// src/Infrastructure/Serializer/Normalizer/BodyElement/ParagraphNormalizer.php
final class ParagraphNormalizer implements NormalizerInterface
{
    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof Paragraph;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        /** @var Paragraph $object */
        return [
            'type' => 'paragraph',
            'content' => $object->content(),
        ];
    }
}

// ============================================
// USO
// ============================================
$json = $serializer->normalize($enrichedContext, 'json');
// El serializer automáticamente encuentra y usa los normalizers correctos
```

**Para añadir `commentsCount` al JSON:**
- Si viene del enricher: 1 línea en `EditorialNormalizer`
- Si es nuevo campo de Editorial: 0 cambios (ya está)

**Ventaja**: Auto-discovery, recursivo, framework-native
**Desventaja**: "Magia", no sabes qué normalizer se usa sin debugging

---

### Opción Y: DTO Factory (Score 123)

```php
// ============================================
// ESTRUCTURA DE DIRECTORIOS
// ============================================
src/
└── Application/
    ├── DTO/
    │   └── Response/
    │       ├── EditorialResponse.php
    │       ├── BodyResponse.php
    │       ├── BodyElementResponse.php
    │       ├── MultimediaResponse.php
    │       ├── SectionResponse.php
    │       ├── TagResponse.php
    │       └── SignatureResponse.php
    └── Factory/
        └── Response/
            ├── EditorialResponseFactory.php
            ├── BodyResponseFactory.php
            ├── BodyElementResponseFactory.php
            ├── MultimediaResponseFactory.php
            ├── SectionResponseFactory.php
            ├── TagResponseFactory.php
            └── SignatureResponseFactory.php

// ============================================
// DTO (inmutable, tipado)
// ============================================
// src/Application/DTO/Response/EditorialResponse.php
final readonly class EditorialResponse implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $url,
        public TitlesResponse $titles,
        public string $lead,
        public string $publicationDate,
        public BodyResponse $body,
        public ?MultimediaResponse $multimedia,
        public array $signatures,
        public ?SectionResponse $section,
        public array $tags,
        public int $commentsCount,  // ← Tipado, IDE sabe que existe
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'titles' => $this->titles,
            'lead' => $this->lead,
            'publicationDate' => $this->publicationDate,
            'body' => $this->body,
            'multimedia' => $this->multimedia,
            'signatures' => $this->signatures,
            'section' => $this->section,
            'tags' => $this->tags,
            'commentsCount' => $this->commentsCount,
        ];
    }
}

// ============================================
// FACTORY PRINCIPAL
// ============================================
// src/Application/Factory/Response/EditorialResponseFactory.php
final readonly class EditorialResponseFactory
{
    public function __construct(
        private BodyResponseFactory $bodyFactory,
        private MultimediaResponseFactory $multimediaFactory,
        private SectionResponseFactory $sectionFactory,
        private TagResponseFactory $tagFactory,
        private SignatureResponseFactory $signatureFactory,
    ) {}

    public function create(EditorialContext $context): EditorialResponse
    {
        $editorial = $context->editorial();

        return new EditorialResponse(
            id: $editorial->id(),
            url: $editorial->url(),
            titles: new TitlesResponse(
                preTitle: $editorial->titles()->preTitle(),
                title: $editorial->titles()->title(),
                urlTitle: $editorial->titles()->urlTitle(),
            ),
            lead: $editorial->lead(),
            publicationDate: $editorial->publicationDate()->format('c'),
            body: $this->bodyFactory->create($editorial->body()),
            multimedia: $context->multimedia()
                ? $this->multimediaFactory->create($context->multimedia())
                : null,
            signatures: array_map(
                fn($j) => $this->signatureFactory->create($j),
                $context->journalists()
            ),
            section: $context->section()
                ? $this->sectionFactory->create($context->section())
                : null,
            tags: array_map(
                fn($t) => $this->tagFactory->create($t),
                $context->tags()
            ),
            commentsCount: $context->commentsCount(),  // ← Explícito
        );
    }
}

// ============================================
// BODY FACTORY
// ============================================
// src/Application/Factory/Response/BodyResponseFactory.php
final readonly class BodyResponseFactory
{
    public function __construct(
        private BodyElementResponseFactory $elementFactory,
    ) {}

    public function create(Body $body): BodyResponse
    {
        return new BodyResponse(
            type: $body->type(),
            elements: array_map(
                fn($el) => $this->elementFactory->create($el),
                $body->elements()
            ),
        );
    }
}

// ============================================
// ELEMENT FACTORY (con match)
// ============================================
// src/Application/Factory/Response/BodyElementResponseFactory.php
final class BodyElementResponseFactory
{
    public function create(BodyElement $element): BodyElementResponse
    {
        return match (true) {
            $element instanceof Paragraph => new BodyElementResponse(
                type: 'paragraph',
                content: $element->content(),
            ),
            $element instanceof SubHead => new BodyElementResponse(
                type: 'subhead',
                content: $element->content(),
                level: $element->level(),
            ),
            $element instanceof BodyTagPicture => new BodyElementResponse(
                type: 'picture',
                content: $element->content(),
                imageUrl: $element->imageUrl(),
                caption: $element->caption(),
            ),
            // ... más tipos
            default => throw new UnsupportedElementException($element),
        };
    }
}

// ============================================
// USO
// ============================================
$response = $editorialResponseFactory->create($enrichedContext);
$json = json_encode($response);
```

**Para añadir `commentsCount` al JSON:**
- Modificar `EditorialResponse` DTO: añadir propiedad
- Modificar `EditorialResponseFactory`: añadir línea en constructor
- Total: 2 ficheros modificados

**Ventaja**: Type safety completo, IDE autocompletion, explícito
**Desventaja**: Más verbose, más ficheros, cambios requieren modificar DTO + Factory

---

## Comparación Directa

### Añadir nuevo campo `commentsCount`

| Paso | Normalizers | DTO Factory |
|------|-------------|-------------|
| 1 | Añadir línea en EditorialNormalizer | Añadir propiedad en EditorialResponse |
| 2 | - | Añadir línea en EditorialResponseFactory |
| **Ficheros** | **1** | **2** |
| **Líneas** | **1** | **2** |

### Añadir nuevo tipo de BodyElement `Quote`

| Paso | Normalizers | DTO Factory |
|------|-------------|-------------|
| 1 | Crear QuoteNormalizer.php | Añadir case en BodyElementResponseFactory |
| **Ficheros** | **1 nuevo** | **1 modificado** |

### Debugging "¿Por qué el campo X no aparece?"

| Acción | Normalizers | DTO Factory |
|--------|-------------|-------------|
| Encontrar código | Buscar `supportsNormalization` | Ir directo a Factory |
| Certeza | Baja (puede haber otro normalizer) | Alta (un solo lugar) |
| IDE support | Limitado | Completo |

---

## Mi Recomendación Final

### Si priorizas **mínimos cambios y convención**:
```
Pipeline + Normalizers
```
- Añadir dato = 1 fichero (enricher) + 1 línea (normalizer)
- Framework-native, el equipo Symfony lo conoce
- Trade-off: menos explícito, debugging requiere conocer el framework

### Si priorizas **type safety y explicitness**:
```
Pipeline + DTO Factory
```
- Añadir dato = 1 fichero (enricher) + 2 ficheros (DTO + Factory)
- IDE autocompletion completo
- Trade-off: más verbose, más ficheros

### Si priorizas **semántica DDD**:
```
Specification Pattern + DTO Factory
```
- Naming alineado con DDD (isSatisfiedBy, Specification)
- Full type safety
- Trade-off: más conceptos, más ficheros

---

## Pregunta para decidir

¿Qué prefieres?

1. **Menos ficheros, más convención** → Pipeline + Normalizers
2. **Type safety, más explícito** → Pipeline + DTO Factory
3. **Semántica DDD, formal** → Specification + DTO Factory
