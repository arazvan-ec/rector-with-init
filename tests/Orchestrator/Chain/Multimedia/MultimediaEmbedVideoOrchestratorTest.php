<?php

namespace App\Tests\Orchestrator\Chain\Multimedia;

use App\Orchestrator\Chain\Multimedia\MultimediaEmbedVideoOrchestrator;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
#[CoversClass(MultimediaEmbedVideoOrchestrator::class)]
class MultimediaEmbedVideoOrchestratorTest extends TestCase
{
    private MultimediaEmbedVideoOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->orchestrator = new MultimediaEmbedVideoOrchestrator();
    }

    #[Test]
    public function canOrchestrateReturnsEmbedVideoType(): void
    {
        $result = $this->orchestrator->canOrchestrate();

        self::assertSame('embed_video', $result);
    }

    #[Test]
    public function executeReturnsArrayWithMultimediaInOpeningKey(): void
    {
        $multimediaId = $this->createMock(MultimediaId::class);
        $multimediaId->method('id')->willReturn('123');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia->method('id')->willReturn($multimediaId);

        /** @var array{123: array<string, mixed>}  $result */
        $result = $this->orchestrator->execute($multimedia);

        self::assertArrayHasKey('opening', $result['123']);
        self::assertSame($multimedia, $result['123']['opening']);
    }

    #[Test]
    public function executeReturnsCorrectStructure(): void
    {
        $multimediaId = $this->createMock(MultimediaId::class);
        $multimediaId->method('id')->willReturn('456');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia->method('id')->willReturn($multimediaId);

        $result = $this->orchestrator->execute($multimedia);

        $expected = [
            '456' => [
                'opening' => $multimedia,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function executeWorksWithDifferentMultimediaIds(): void
    {
        $multimediaId1 = $this->createMock(MultimediaId::class);
        $multimediaId1->method('id')->willReturn('abc-123');

        $multimedia1 = $this->createMock(Multimedia::class);
        $multimedia1->method('id')->willReturn($multimediaId1);

        $result1 = $this->orchestrator->execute($multimedia1);

        $multimediaId2 = $this->createMock(MultimediaId::class);
        $multimediaId2->method('id')->willReturn('xyz-789');

        $multimedia2 = $this->createMock(Multimedia::class);
        $multimedia2->method('id')->willReturn($multimediaId2);

        $result2 = $this->orchestrator->execute($multimedia2);

        static::assertArrayHasKey('abc-123', $result1);
        static::assertArrayHasKey('xyz-789', $result2);
        static::assertSame($multimedia1, $result1['abc-123']['opening']);
        static::assertSame($multimedia2, $result2['xyz-789']['opening']);
    }
}
