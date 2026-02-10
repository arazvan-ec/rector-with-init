<?php

/** @copyright */

namespace App\Application\DataTransformer\Apps\Media\DataTransformers\Widget;

use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details\WidgetTypeDataTransformer;
use Ec\Widget\Domain\Model\Widget;
use Ec\Widget\Exceptions\WidgetDataTransformerAlreadyExistsException;
use Ec\Widget\Exceptions\WidgetDataTransformerNotFoundException;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailWidgetDataTransformerHandler implements DataTransformerHandler
{
    /** @var array<string, WidgetTypeDataTransformer> */
    private array $dataTransformers = [];

    /**
     * @throws WidgetDataTransformerAlreadyExistsException
     */
    public function addDataTransformer(WidgetTypeDataTransformer $dataTransformer): DataTransformerHandler
    {
        $widgetType = $dataTransformer->canTransform();

        if (isset($this->dataTransformers[$widgetType])) {
            throw new WidgetDataTransformerAlreadyExistsException(\sprintf('Data transformer for widget type %s already exists', $widgetType));
        }

        $this->dataTransformers[$widgetType] = $dataTransformer;

        return $this;
    }

    /**
     * @param Widget $widget
     *
     * @return array<string, mixed>
     *
     * @throws WidgetDataTransformerNotFoundException
     */
    public function execute(Widget $widget): array
    {
        $widgetType = $widget->type();

        if (!$widgetType || empty($this->dataTransformers[$widgetType])) {
            throw new WidgetDataTransformerNotFoundException(\sprintf('No data transformer found for widget type %s', $widgetType ?: 'unknown'));
        }

        $transformer = $this->dataTransformers[$widgetType];
        $transformer->write($widget);

        return $transformer->read();
    }
}
