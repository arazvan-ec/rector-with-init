<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer;

use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class BodyElementDataTransformerHandler
{
    /** @var BodyElementDataTransformer[] */
    private array $dataTransformers;

    public function __construct()
    {
        $this->dataTransformers = [];
    }

    public function addDataTransformer(BodyElementDataTransformer $dataTransformer): BodyElementDataTransformerHandler
    {
        $this->dataTransformers[$dataTransformer->canTransform()] = $dataTransformer;

        return $this;
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array<string, mixed>
     *
     * @throws BodyDataTransformerNotFoundException
     */
    public function execute(BodyElement $bodyElement, array $resolveData = []): array
    {
        if (empty($this->dataTransformers[\get_class($bodyElement)])) {
            $message = \sprintf('BodyElement data transformer type %s not found', $bodyElement->type());
            throw new BodyDataTransformerNotFoundException($message);
        }

        $transformer = $this->dataTransformers[\get_class($bodyElement)];
        $transformer->write($bodyElement, $resolveData);

        return $transformer->read();
    }
}
