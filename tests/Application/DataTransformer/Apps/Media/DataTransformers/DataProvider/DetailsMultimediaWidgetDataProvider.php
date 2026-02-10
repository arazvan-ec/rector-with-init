<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Media\DataTransformers\DataProvider;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailsMultimediaWidgetDataProvider
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getData(): array
    {
        return [
            'html-widget-complete' => [
                'multimediaId' => 'widget-123',
                'caption' => 'Lotería del Niño 2026',
                'specificWidgetData' => [
                    'url' => 'https://www.elconfidencial.dev/lottery-service/591a7a1e',
                    'aspectRatio' => 1.3,
                    'name' => 'Lotería del Niño 2026',
                    'description' => 'Lotería del Niño 2026',
                    'body' => '<p><iframe class="lotteryWidgetWrapper"></iframe></p>',
                    'visible' => true,
                    'home' => false,
                    'cache' => 0,
                ],
                'expectedResult' => [
                    'type' => 'widget',
                    'caption' => 'Lotería del Niño 2026',
                ],
            ],
            'html-widget-simple' => [
                'multimediaId' => 'widget-456',
                'caption' => 'Widget Simple',
                'specificWidgetData' => [
                    'url' => 'https://www.elconfidencial.dev/simple-widget/456',
                    'aspectRatio' => null,
                    'name' => 'Widget Simple',
                    'description' => 'Un widget sin aspect ratio',
                    'body' => '<div>Simple content</div>',
                ],
                'expectedResult' => [
                    'type' => 'widget',
                    'caption' => 'Widget Simple',
                ],
            ],
            'widget-with-empty-caption' => [
                'multimediaId' => 'widget-789',
                'caption' => '',
                'specificWidgetData' => [
                    'url' => 'https://www.elconfidencial.dev/widget/789',
                    'aspectRatio' => 1.8,
                    'name' => 'Widget sin caption',
                    'description' => 'Widget test',
                    'body' => '<div>Test</div>',
                ],
                'expectedResult' => [
                    'type' => 'widget',
                    'caption' => '',
                ],
            ],
        ];
    }
}
