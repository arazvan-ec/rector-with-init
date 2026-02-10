<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer;

use Ec\Editorial\Domain\Model\Body\BodyElement;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
interface BodyElementDataTransformer
{
    /**
     * @param array<string, mixed> $resolveData
     */
    public function write(BodyElement $bodyElement, array $resolveData = []): BodyElementDataTransformer;

    /**
     * @return array<string, mixed>
     */
    public function read(): array;

    public function canTransform(): string;
}
