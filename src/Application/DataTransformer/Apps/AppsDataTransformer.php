<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
interface AppsDataTransformer
{
    /**
     * @param Tag[] $tags
     */
    public function write(Editorial $editorial, Section $section, array $tags): AppsDataTransformer;

    /**
     * @return array<string, mixed>
     */
    public function read(): array;
}
