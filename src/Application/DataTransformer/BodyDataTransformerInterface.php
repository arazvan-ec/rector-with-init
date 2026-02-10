<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer;

use Ec\Editorial\Domain\Model\Body\Body;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
interface BodyDataTransformerInterface
{
    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array<string, mixed>
     */
    public function execute(Body $body, array $resolveData): array;
}
