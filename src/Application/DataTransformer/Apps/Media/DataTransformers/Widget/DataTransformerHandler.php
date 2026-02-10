<?php

/** @copyright */

namespace App\Application\DataTransformer\Apps\Media\DataTransformers\Widget;

use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details\WidgetTypeDataTransformer;
use Ec\Widget\Domain\Model\Widget;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
interface DataTransformerHandler
{
    public function addDataTransformer(WidgetTypeDataTransformer $dataTransformer): DataTransformerHandler;

    /**
     * @param Widget $widget
     *
     * @return array<string, mixed>
     */
    public function execute(Widget $widget): array;
}
