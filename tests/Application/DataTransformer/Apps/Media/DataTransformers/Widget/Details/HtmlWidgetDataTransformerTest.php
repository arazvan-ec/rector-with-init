<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details;

use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details\HtmlWidgetDataTransformer;
use App\Tests\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details\DataProvider\HtmlWidgetDataProvider;
use Ec\Widget\Domain\Model\HtmlWidget;
use Ec\Widget\Domain\Model\Widget;
use Ec\Widget\Domain\Model\WidgetId;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class HtmlWidgetDataTransformerTest extends TestCase
{
    private HtmlWidgetDataTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new HtmlWidgetDataTransformer();
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenWidgetIsNotHtmlWidget(): void
    {
        /** @var Widget|MockObject $widget */
        $widget = $this->createMock(Widget::class);

        $result = $this->transformer->write($widget)->read();

        static::assertSame([], $result);
    }

    #[Test]
    public function shouldReturnHtmlTypeFromCanTransform(): void
    {
        static::assertSame('html', $this->transformer->canTransform());
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $expectedResult
     */
    #[Test]
    #[DataProviderExternal(HtmlWidgetDataProvider::class, 'getData')]
    public function shouldTransformHtmlWidgetCorrectly(
        string $name,
        string $description,
        string $body,
        string $url,
        bool $visible,
        bool $home,
        int $cache,
        array $params,
        array $expectedResult,
    ): void {
        $widgetId = $this->createMock(WidgetId::class);
        $widgetId->method('__toString')->willReturn('123');

        $htmlWidget = $this->createMock(HtmlWidget::class);
        $htmlWidget->method('id')->willReturn($widgetId);
        $htmlWidget->method('name')->willReturn($name);
        $htmlWidget->method('description')->willReturn($description);
        $htmlWidget->method('body')->willReturn($body);
        $htmlWidget->method('url')->willReturn($url);
        $htmlWidget->method('isVisible')->willReturn($visible);
        $htmlWidget->method('home')->willReturn($home);
        $htmlWidget->method('cache')->willReturn($cache);
        $htmlWidget->method('params')->willReturn($params);

        $result = $this->transformer->write($htmlWidget)->read();

        static::assertSame($expectedResult['url'], $result['url']);
        static::assertSame($expectedResult['aspectRatio'], $result['aspectRatio']);
    }

    /**
     * @param array<string, mixed> $params
     */
    #[Test]
    #[DataProviderExternal(HtmlWidgetDataProvider::class, 'getAspectRatioData')]
    public function shouldCalculateAspectRatioCorrectly(
        array $params,
        ?float $expectedAspectRatio,
    ): void {
        $widgetId = $this->createMock(WidgetId::class);

        $htmlWidget = $this->createMock(HtmlWidget::class);
        $htmlWidget->method('id')->willReturn($widgetId);
        $htmlWidget->method('name')->willReturn('Test');
        $htmlWidget->method('description')->willReturn('Test');
        $htmlWidget->method('body')->willReturn('Test');
        $htmlWidget->method('url')->willReturn('http://test.com');
        $htmlWidget->method('isVisible')->willReturn(true);
        $htmlWidget->method('home')->willReturn(false);
        $htmlWidget->method('cache')->willReturn(0);
        $htmlWidget->method('params')->willReturn($params);

        $result = $this->transformer->write($htmlWidget)->read();

        static::assertSame($expectedAspectRatio, $result['aspectRatio']);
    }
}
