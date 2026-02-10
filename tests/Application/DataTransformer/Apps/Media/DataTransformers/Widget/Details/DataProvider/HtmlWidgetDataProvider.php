<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Media\DataTransformers\Widget\Details\DataProvider;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class HtmlWidgetDataProvider
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getData(): array
    {
        return [
            'complete-widget-with-aspect-ratio-4-3' => [
                'name' => 'Lotería del Niño 2026',
                'description' => 'Widget de lotería',
                'body' => '<p><iframe class="lotteryWidgetWrapper" frameborder="0" height="650"></iframe></p>',
                'url' => 'https://www.elconfidencial.dev/lottery-service/591a7a1e-20a7-439d-aa36-45a9d84e4c4a',
                'visible' => true,
                'home' => false,
                'cache' => 0,
                'params' => [
                    'overflow' => true,
                    'height' => 500,
                    'width' => '100%',
                    'aspect-ratio' => '4/3',
                    'class' => 'lotteryWidgetWrapper',
                ],
                'expectedResult' => [
                    'url' => 'https://www.elconfidencial.dev/lottery-service/591a7a1e-20a7-439d-aa36-45a9d84e4c4a',
                    'aspectRatio' => 1.3,
                    'name' => 'Lotería del Niño 2026',
                    'description' => 'Widget de lotería',
                    'body' => '<p><iframe class="lotteryWidgetWrapper" frameborder="0" height="650"></iframe></p>',
                    'visible' => true,
                    'home' => false,
                    'cache' => 0,
                ],
            ],
            'widget-with-aspect-ratio-16-9' => [
                'name' => 'Widget Video',
                'description' => 'Widget para video',
                'body' => '<div>Video player</div>',
                'url' => 'https://www.elconfidencial.dev/video-widget/123',
                'visible' => true,
                'home' => true,
                'cache' => 60,
                'params' => [
                    'overflow' => false,
                    'height' => 1080,
                    'width' => 1920,
                    'aspect-ratio' => '16/9',
                ],
                'expectedResult' => [
                    'url' => 'https://www.elconfidencial.dev/video-widget/123',
                    'aspectRatio' => 1.8,
                    'name' => 'Widget Video',
                    'description' => 'Widget para video',
                    'body' => '<div>Video player</div>',
                    'visible' => true,
                    'home' => true,
                    'cache' => 60,
                ],
            ],
            'widget-without-aspect-ratio' => [
                'name' => 'Widget Simple',
                'description' => 'Sin aspect ratio',
                'body' => '<div>Simple widget</div>',
                'url' => 'https://www.elconfidencial.dev/simple-widget/456',
                'visible' => false,
                'home' => false,
                'cache' => 0,
                'params' => [
                    'overflow' => true,
                    'height' => 300,
                    'width' => '100%',
                ],
                'expectedResult' => [
                    'url' => 'https://www.elconfidencial.dev/simple-widget/456',
                    'aspectRatio' => null,
                    'name' => 'Widget Simple',
                    'description' => 'Sin aspect ratio',
                    'body' => '<div>Simple widget</div>',
                    'visible' => false,
                    'home' => false,
                    'cache' => 0,
                ],
            ],
            'widget-with-square-aspect-ratio-1-1' => [
                'name' => 'Widget Cuadrado',
                'description' => 'Widget cuadrado',
                'body' => '<div>Square widget</div>',
                'url' => 'https://www.elconfidencial.dev/square-widget/789',
                'visible' => true,
                'home' => false,
                'cache' => 30,
                'params' => [
                    'aspect-ratio' => '1/1',
                    'class' => 'square',
                ],
                'expectedResult' => [
                    'url' => 'https://www.elconfidencial.dev/square-widget/789',
                    'aspectRatio' => 1.0,
                    'name' => 'Widget Cuadrado',
                    'description' => 'Widget cuadrado',
                    'body' => '<div>Square widget</div>',
                    'visible' => true,
                    'home' => false,
                    'cache' => 30,
                ],
            ],
            'widget-with-empty-url' => [
                'name' => 'Widget con URL vacía',
                'description' => 'Widget sin URL válida',
                'body' => '<div>Empty URL widget</div>',
                'url' => '',
                'visible' => true,
                'home' => false,
                'cache' => 0,
                'params' => [
                    'aspect-ratio' => '16/9',
                ],
                'expectedResult' => [
                    'url' => null,
                    'aspectRatio' => 1.8,
                    'name' => 'Widget con URL vacía',
                    'description' => 'Widget sin URL válida',
                    'body' => '<div>Empty URL widget</div>',
                    'visible' => true,
                    'home' => false,
                    'cache' => 0,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getAspectRatioData(): array
    {
        return [
            'aspect-ratio-4-3' => [
                'params' => ['aspect-ratio' => '4/3'],
                'expectedAspectRatio' => 1.3,
            ],
            'aspect-ratio-16-9' => [
                'params' => ['aspect-ratio' => '16/9'],
                'expectedAspectRatio' => 1.8,
            ],
            'aspect-ratio-1-1' => [
                'params' => ['aspect-ratio' => '1/1'],
                'expectedAspectRatio' => 1.0,
            ],
            'aspect-ratio-21-9' => [
                'params' => ['aspect-ratio' => '21/9'],
                'expectedAspectRatio' => 2.3,
            ],
            'aspect-ratio-9-16' => [
                'params' => ['aspect-ratio' => '9/16'],
                'expectedAspectRatio' => 0.6,
            ],
            'no-aspect-ratio' => [
                'params' => [],
                'expectedAspectRatio' => null,
            ],
            'empty-aspect-ratio' => [
                'params' => ['aspect-ratio' => ''],
                'expectedAspectRatio' => null,
            ],
            'invalid-aspect-ratio-no-slash' => [
                'params' => ['aspect-ratio' => '43'],
                'expectedAspectRatio' => null,
            ],
            'invalid-aspect-ratio-not-numeric' => [
                'params' => ['aspect-ratio' => 'a/b'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-division-by-zero' => [
                'params' => ['aspect-ratio' => '4/0'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-with-spaces' => [
                'params' => ['aspect-ratio' => ' 16 / 9 '],
                'expectedAspectRatio' => 1.8,
            ],
            'aspect-ratio-not-string' => [
                'params' => ['aspect-ratio' => 123],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-numerator-with-leading-space' => [
                'params' => ['aspect-ratio' => ' 4/3'],
                'expectedAspectRatio' => 1.3,
            ],
            'aspect-ratio-denominator-with-trailing-space' => [
                'params' => ['aspect-ratio' => '4/3 '],
                'expectedAspectRatio' => 1.3,
            ],
            'aspect-ratio-both-with-spaces' => [
                'params' => ['aspect-ratio' => ' 4 / 3 '],
                'expectedAspectRatio' => 1.3,
            ],
            'aspect-ratio-first-part-not-numeric' => [
                'params' => ['aspect-ratio' => 'abc/3'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-second-part-not-numeric' => [
                'params' => ['aspect-ratio' => '4/xyz'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-only-one-part' => [
                'params' => ['aspect-ratio' => '4'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-three-parts' => [
                'params' => ['aspect-ratio' => '4/3/2'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-with-multiple-slashes' => [
                'params' => ['aspect-ratio' => '4//3'],
                'expectedAspectRatio' => null,
            ],
            'aspect-ratio-different-numerator-denominator' => [
                'params' => ['aspect-ratio' => '8/4'],
                'expectedAspectRatio' => 2.0,
            ],
            'aspect-ratio-asymmetric-values' => [
                'params' => ['aspect-ratio' => '3/9'],
                'expectedAspectRatio' => 0.3,
            ],
            'aspect-ratio-numerator-internal-space' => [
                'params' => ['aspect-ratio' => ' 16 /9'],
                'expectedAspectRatio' => 1.8,
            ],
            'aspect-ratio-denominator-internal-space' => [
                'params' => ['aspect-ratio' => '16/ 9 '],
                'expectedAspectRatio' => 1.8,
            ],
            'aspect-ratio-large-values-need-cast' => [
                'params' => ['aspect-ratio' => '10/3'],
                'expectedAspectRatio' => 3.3,
            ],
            'aspect-ratio-decimal-strings' => [
                'params' => ['aspect-ratio' => '5.5/2.5'],
                'expectedAspectRatio' => 2.2,
            ],
            'aspect-ratio-very-different-values' => [
                'params' => ['aspect-ratio' => '20/5'],
                'expectedAspectRatio' => 4.0,
            ],
            'aspect-ratio-with-leading-space-numerator' => [
                'params' => ['aspect-ratio' => ' 7/2'],
                'expectedAspectRatio' => 3.5,
            ],
            'aspect-ratio-with-trailing-space-denominator' => [
                'params' => ['aspect-ratio' => '7/2 '],
                'expectedAspectRatio' => 3.5,
            ],
            'aspect-ratio-requires-float-precision' => [
                'params' => ['aspect-ratio' => '10/3'],
                'expectedAspectRatio' => 3.3,
            ],
        ];
    }
}
