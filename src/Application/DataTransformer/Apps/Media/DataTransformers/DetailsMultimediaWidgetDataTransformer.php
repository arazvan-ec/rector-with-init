<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Media\DataTransformers;

use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\DataTransformerHandler;
use App\Application\DataTransformer\Apps\Media\MediaDataTransformer;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaWidget;
use Ec\Widget\Domain\Model\EveryWidget;
use Ec\Widget\Domain\Model\Widget;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailsMultimediaWidgetDataTransformer implements MediaDataTransformer
{
    /**
     * @var array<string, array{opening: MultimediaWidget, resource: EveryWidget}>
     */
    private array $arrayMultimedia = [];
    private Opening $openingMultimedia;

    public function __construct(
        private readonly DataTransformerHandler $widgetDataTransformerHandler,
    ) {
    }

    /**
     * @param array<string, array{opening: MultimediaWidget, resource: EveryWidget}> $arrayMultimedia
     */
    public function write(array $arrayMultimedia, Opening $openingMultimedia): self
    {
        $this->arrayMultimedia = $arrayMultimedia;
        $this->openingMultimedia = $openingMultimedia;

        return $this;
    }

    /**
     * @return array<string, mixed>|array{}
     */
    public function read(): array
    {
        $multimediaId = $this->openingMultimedia->multimediaId();

        if (!$multimediaId || empty($this->arrayMultimedia[$multimediaId])) {
            return [];
        }

        /** @var MultimediaWidget $multimedia */
        $multimedia = $this->arrayMultimedia[$multimediaId]['opening'];
        /** @var Widget $resource */
        $resource = $this->arrayMultimedia[$multimediaId]['resource'];

        $specificWidgetTypeData = $this->widgetDataTransformerHandler->execute($resource);

        return $this->buildBaseResponse($multimedia, $specificWidgetTypeData);
    }

    public function canTransform(): string
    {
        return MultimediaWidget::class;
    }

    /**
     * @param MultimediaWidget     $multimedia
     * @param array<string, mixed> $specificWidgetTypeData
     *
     * @return array<string, mixed>
     */
    private function buildBaseResponse(
        MultimediaWidget $multimedia,
        array $specificWidgetTypeData,
    ): array {
        return [
            'type' => MultimediaWidget::TYPE,
            'caption' => $multimedia->caption(),
            ...$specificWidgetTypeData,
        ];
    }
}
