<?php

namespace App\Tests\Application\DataTransformer\Apps;

use App\Application\DataTransformer\Apps\StandfirstDataTransformer;
use App\Application\DataTransformer\BodyElementDataTransformerHandler;
use Ec\Editorial\Domain\Model\Body\GenericList;
use Ec\Editorial\Domain\Model\Standfirst;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StandfirstDataTransformerTest extends TestCase
{
    private StandfirstDataTransformer $standfirstDataTransformer;

    /** @var BodyElementDataTransformerHandler|MockObject */
    private BodyElementDataTransformerHandler $bodyElementDataTransformerHandler;

    protected function setUp(): void
    {
        $this->bodyElementDataTransformerHandler = $this->createMock(BodyElementDataTransformerHandler::class);
        $this->standfirstDataTransformer = new StandfirstDataTransformer($this->bodyElementDataTransformerHandler);
    }

    public function testWrite(): void
    {
        $standfirst = $this->createMock(Standfirst::class);
        $result = $this->standfirstDataTransformer->write($standfirst);
        $this->assertEmpty($result->read());
    }

    #[Test]
    public function standfirstShouldReturnValidData(): void
    {
        $expect = [
            'type' => 'unorderedlist',
            'items' => [
                [
                    'type' => 'listitem',
                    'content' => 'un bolillo',
                    'links' => [],
                ],
                [
                    'type' => 'listitem',
                    'content' => '#replace0#',
                    'links' => [
                        '#replace0#' => [
                            'type' => 'link',
                            'content' => 'dos bolillos',
                            'url' => 'http://www.google.com',
                            'target' => '_self',
                        ],
                    ],
                ],
            ],
        ];

        $content = $this->createMock(GenericList::class);
        $standfirst = $this->createMock(Standfirst::class);

        $standfirst->method('content')->willReturn($content);

        $this->standfirstDataTransformer->write($standfirst);

        $this->bodyElementDataTransformerHandler
            ->expects($this->once())
            ->method('execute')
            ->with($content)
            ->willReturn($expect);

        $result = $this->standfirstDataTransformer->read();

        $this->assertEquals($expect, $result);
    }
}
