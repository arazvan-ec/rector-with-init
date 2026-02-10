<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Media;

use Ec\Editorial\Domain\Model\Opening;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
interface MediaDataTransformer
{
    /**
     * @param array<string, mixed> $arrayMultimedia
     */
    public function write(array $arrayMultimedia, Opening $openingMultimedia): MediaDataTransformer;

    /**
     * @return array<string, mixed>
     */
    public function read(): array;

    public function canTransform(): string;
}
