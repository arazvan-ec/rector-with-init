<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Media\DataTransformers\Widget;

use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details\WidgetTypeDataTransformer;
use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\DetailWidgetDataTransformerHandler;
use Ec\Widget\Domain\Model\Widget;
use Ec\Widget\Exceptions\WidgetDataTransformerAlreadyExistsException;
use Ec\Widget\Exceptions\WidgetDataTransformerNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailWidgetDataTransformerHandlerTest extends TestCase
{
    private DetailWidgetDataTransformerHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new DetailWidgetDataTransformerHandler();
    }

    #[Test]
    public function shouldAddDataTransformerSuccessfully(): void
    {
        /** @var MockObject|WidgetTypeDataTransformer $transformer */
        $transformer = $this->createMock(WidgetTypeDataTransformer::class);
        $transformer->method('canTransform')->willReturn('html');

        $result = $this->handler->addDataTransformer($transformer);

        static::assertInstanceOf(DetailWidgetDataTransformerHandler::class, $result);
    }

    #[Test]
    public function shouldThrowExceptionWhenAddingDuplicateTransformer(): void
    {
        /** @var MockObject|WidgetTypeDataTransformer $transformer1 */
        $transformer1 = $this->createMock(WidgetTypeDataTransformer::class);
        $transformer1->method('canTransform')->willReturn('html');

        /** @var MockObject|WidgetTypeDataTransformer $transformer2 */
        $transformer2 = $this->createMock(WidgetTypeDataTransformer::class);
        $transformer2->method('canTransform')->willReturn('html');

        $this->handler->addDataTransformer($transformer1);

        $this->expectException(WidgetDataTransformerAlreadyExistsException::class);
        $this->expectExceptionMessage('Data transformer for widget type html already exists');

        $this->handler->addDataTransformer($transformer2);
    }

    #[Test]
    public function shouldExecuteCorrectTransformerBasedOnWidgetType(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget->method('type')->willReturn('html');

        $expectedResult = [
            'url' => 'https://example.com',
            'aspectRatio' => 1.33,
            'name' => 'Test Widget',
        ];

        /** @var MockObject|WidgetTypeDataTransformer $transformer */
        $transformer = $this->createMock(WidgetTypeDataTransformer::class);
        $transformer->method('canTransform')->willReturn('html');
        $transformer->expects($this->once())
            ->method('write')
            ->with($widget)
            ->willReturnSelf();
        $transformer->expects($this->once())
            ->method('read')
            ->willReturn($expectedResult);

        $this->handler->addDataTransformer($transformer);

        $result = $this->handler->execute($widget);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function shouldThrowExceptionWhenNoTransformerFoundForWidgetType(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget->method('type')->willReturn('unknown');

        $this->expectException(WidgetDataTransformerNotFoundException::class);
        $this->expectExceptionMessage('No data transformer found for widget type unknown');

        $this->handler->execute($widget);
    }

    #[Test]
    public function shouldThrowExceptionWhenWidgetTypeIsMissing(): void
    {
        $widget = $this->createMock(Widget::class);
        $widget->method('type')->willReturn('');

        $this->expectException(WidgetDataTransformerNotFoundException::class);
        $this->expectExceptionMessage('No data transformer found for widget type unknown');

        $this->handler->execute($widget);
    }

    #[Test]
    public function shouldHandleMultipleTransformers(): void
    {
        $htmlWidget = $this->createMock(Widget::class);
        $htmlWidget->method('type')->willReturn('html');

        $lotteryWidget = $this->createMock(Widget::class);
        $lotteryWidget->method('type')->willReturn('lottery');

        /** @var MockObject|WidgetTypeDataTransformer $htmlTransformer */
        $htmlTransformer = $this->createMock(WidgetTypeDataTransformer::class);
        $htmlTransformer->method('canTransform')->willReturn('html');
        $htmlTransformer->method('write')->willReturnSelf();
        $htmlTransformer->method('read')->willReturn(['type' => 'html']);

        /** @var MockObject|WidgetTypeDataTransformer $lotteryTransformer */
        $lotteryTransformer = $this->createMock(WidgetTypeDataTransformer::class);
        $lotteryTransformer->method('canTransform')->willReturn('lottery');
        $lotteryTransformer->method('write')->willReturnSelf();
        $lotteryTransformer->method('read')->willReturn(['type' => 'lottery']);

        $this->handler->addDataTransformer($htmlTransformer);
        $this->handler->addDataTransformer($lotteryTransformer);

        $htmlResult = $this->handler->execute($htmlWidget);
        $lotteryResult = $this->handler->execute($lotteryWidget);

        static::assertSame(['type' => 'html'], $htmlResult);
        static::assertSame(['type' => 'lottery'], $lotteryResult);
    }
}
