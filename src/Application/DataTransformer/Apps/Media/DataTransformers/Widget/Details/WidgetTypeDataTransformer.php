<?php

/** @copyright */

namespace App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details;

use Ec\Widget\Domain\Model\Widget;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
interface WidgetTypeDataTransformer
{
    /**
     * @param Widget $widget
     */
    public function write(Widget $widget): self;

    /**
     * @return array<string, mixed>
     */
    public function read(): array;

    public function canTransform(): string;
}
