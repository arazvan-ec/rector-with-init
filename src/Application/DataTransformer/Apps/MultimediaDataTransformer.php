<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps;

use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
interface MultimediaDataTransformer
{
    /**
     * @param array<mixed> $arrayMultimedia
     */
    public function write(array $arrayMultimedia, MultimediaEditorial $openingMultimedia): MultimediaDataTransformer;

    /**
     * @return array<string, mixed>
     */
    public function read(): array;
}
