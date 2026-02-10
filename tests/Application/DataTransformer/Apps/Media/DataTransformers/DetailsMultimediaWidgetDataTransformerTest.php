<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Media\DataTransformers;

use App\Application\DataTransformer\Apps\Media\DataTransformers\DetailsMultimediaWidgetDataTransformer;
use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\DataTransformerHandler;
use App\Tests\Application\DataTransformer\Apps\Media\DataTransformers\DataProvider\DetailsMultimediaWidgetDataProvider;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaWidget;
use Ec\Widget\Domain\Model\EveryWidget;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailsMultimediaWidgetDataTransformerTest extends TestCase
{
    private DetailsMultimediaWidgetDataTransformer $transformer;

    /** @var MockObject|DataTransformerHandler */
    private DataTransformerHandler|MockObject $widgetDataTransformerHandler;

    protected function setUp(): void
    {
        $this->widgetDataTransformerHandler = $this->createMock(DataTransformerHandler::class);
        $this->transformer = new DetailsMultimediaWidgetDataTransformer($this->widgetDataTransformerHandler);
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenMultimediaIdIsEmpty(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('');

        $result = $this->transformer->write([], $opening)->read();

        static::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenMultimediaIdNotFoundInArray(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('nonexistent-id');

        $multimedia = $this->createMock(MultimediaWidget::class);
        $widget = $this->createMock(EveryWidget::class);

        /** @var array<string, array{opening: MultimediaWidget, resource: EveryWidget}> $arrayMultimedia */
        $arrayMultimedia = [
            'id1' => [
                'opening' => $multimedia,
                'resource' => $widget,
            ],
        ];

        $result = $this->transformer->write($arrayMultimedia, $opening)->read();

        static::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnMultimediaWidgetClass(): void
    {
        static::assertSame(MultimediaWidget::class, $this->transformer->canTransform());
    }

    /**
     * @param array<string, mixed> $specificWidgetData
     * @param array<string, mixed> $expectedResult
     */
    #[Test]
    #[DataProviderExternal(DetailsMultimediaWidgetDataProvider::class, 'getData')]
    public function shouldTransformMultimediaWidgetCorrectly(
        string $multimediaId,
        string $caption,
        array $specificWidgetData,
        array $expectedResult,
    ): void {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn($multimediaId);

        $multimedia = $this->createMock(MultimediaWidget::class);
        $multimedia->method('caption')->willReturn($caption);

        $widget = $this->createMock(\Ec\Widget\Domain\Model\Widget::class);

        /** @var array<string, array{opening: MultimediaWidget, resource: \Ec\Widget\Domain\Model\Widget}> $arrayMultimedia */
        $arrayMultimedia = [
            $multimediaId => [
                'opening' => $multimedia,
                'resource' => $widget,
            ],
        ];

        $this->widgetDataTransformerHandler
            ->expects($this->once())
            ->method('execute')
            ->with($widget)
            ->willReturn($specificWidgetData);

        $result = $this->transformer->write($arrayMultimedia, $opening)->read();

        static::assertSame($expectedResult['type'], $result['type']);
        static::assertSame($expectedResult['caption'], $result['caption']);

        foreach ($specificWidgetData as $key => $value) {
            static::assertArrayHasKey($key, $result);
            static::assertSame($value, $result[$key]);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function provideWidgetData(): array
    {
        return DetailsMultimediaWidgetDataProvider::getData();
    }
}
