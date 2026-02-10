<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\BodyElementDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyElement;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
abstract class ElementTypeDataTransformer implements BodyElementDataTransformer
{
    protected BodyElement $bodyElement;

    /** @var array<string, mixed> */
    private array $resolveData;

    /**
     * @param array<string, mixed> $resolveData
     */
    public function write(BodyElement $bodyElement, array $resolveData = []): BodyElementDataTransformer
    {
        $this->bodyElement = $bodyElement;
        $this->resolveData = $resolveData;

        return $this;
    }

    public function read(): array
    {
        $elementArray = [];
        $elementArray['type'] = $this->bodyElement->type();

        return $elementArray;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveData(): array
    {
        return $this->resolveData;
    }
}
