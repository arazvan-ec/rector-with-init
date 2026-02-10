<?php

/** @copyright */

namespace App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details;

use Ec\Widget\Domain\Model\HtmlWidget;
use Ec\Widget\Domain\Model\Widget;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class HtmlWidgetDataTransformer implements WidgetTypeDataTransformer
{
    private Widget $widget;

    public function write(Widget $widget): self
    {
        $this->widget = $widget;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        if (!$this->widget instanceof HtmlWidget) {
            return [];
        }

        return [
            'url' => $this->widget->url() ?: null,
            'aspectRatio' => $this->calculateAspectRatio($this->widget->params()),
        ];
    }

    public function canTransform(): string
    {
        return 'html';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function calculateAspectRatio(array $params): ?float
    {
        if (empty($params['aspect-ratio']) || !\is_string($params['aspect-ratio'])) {
            return null;
        }

        $aspectRatioValue = $params['aspect-ratio'];

        if (!str_contains($aspectRatioValue, '/')) {
            return null;
        }

        $parts = explode('/', $aspectRatioValue);

        if (2 !== \count($parts)) {
            return null;
        }

        $numerator = trim($parts[0]);
        $denominator = trim($parts[1]);

        if (!is_numeric($numerator) || !is_numeric($denominator)) {
            return null;
        }

        $denominatorFloat = (float) $denominator;

        if (0.0 === $denominatorFloat) {
            return null;
        }

        return round((float) $numerator / $denominatorFloat, 1);
    }
}
