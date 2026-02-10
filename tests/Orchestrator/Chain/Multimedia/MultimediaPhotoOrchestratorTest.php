<?php

/**
 * @copyright
 */

namespace App\Tests\Orchestrator\Chain\Multimedia;

use App\Orchestrator\Chain\Multimedia\MultimediaPhotoOrchestrator;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaId;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Multimedia\ResourceId;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
#[CoversClass(MultimediaPhotoOrchestrator::class)]
class MultimediaPhotoOrchestratorTest extends TestCase
{
    /** @var QueryMultimediaClient|MockObject */
    private QueryMultimediaClient $queryMultimediaClient;
    /** @var LoggerInterface|MockObject */
    private LoggerInterface $logger;
    private MultimediaPhotoOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->queryMultimediaClient = $this->createMock(QueryMultimediaClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->orchestrator = new MultimediaPhotoOrchestrator($this->queryMultimediaClient, $this->logger);
    }

    #[Test]
    public function canOrchestrateReturnsPhoto(): void
    {
        self::assertSame('photo', $this->orchestrator->canOrchestrate());
    }

    #[Test]
    public function executeReturnsArrayWithMultimediaAndResource(): void
    {
        $multimediaId = $this->createMock(MultimediaId::class);
        $multimediaId->method('id')->willReturn('123');

        $multimedia = $this->createMock(MultimediaPhoto::class);
        $multimedia->method('id')->willReturn($multimediaId);

        $resourceId = $this->createMock(ResourceId::class);
        $resourceId->method('id')->willReturn('456');

        $multimedia->method('resourceId')->willReturn($resourceId);

        $photo = $this->createMock(Photo::class);
        $this->queryMultimediaClient
            ->expects(self::once())
            ->method('findPhotoById')
            ->with('456')
            ->willReturn($photo);

        /** @var array{123: array<string, mixed>}  $result */
        $result = $this->orchestrator->execute($multimedia);

        self::assertArrayHasKey('opening', $result['123']);
        self::assertArrayHasKey('resource', $result['123']);
        self::assertSame($multimedia, $result['123']['opening']);
        self::assertSame($photo, $result['123']['resource']);
    }

    #[Test]
    public function executeLogsErrorAndReturnsEmptyArrayWhenExceptionIsThrown(): void
    {
        $resourceId = '456';
        $exceptionMessage = 'Connection timeout';

        $multimediaId = $this->createMock(MultimediaId::class);
        $multimediaId->method('id')->willReturn('123');

        $resourceIdMock = $this->createMock(ResourceId::class);
        $resourceIdMock->method('id')->willReturn($resourceId);

        $multimedia = $this->createMock(MultimediaPhoto::class);
        $multimedia->method('id')->willReturn($multimediaId);
        $multimedia->method('resourceId')->willReturn($resourceIdMock);

        $this->queryMultimediaClient
            ->expects(self::once())
            ->method('findPhotoById')
            ->with($resourceId)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(\sprintf(
                'Failed to retrieve photo with ID %s: %s',
                $resourceId,
                $exceptionMessage
            ));

        $result = $this->orchestrator->execute($multimedia);

        self::assertSame([], $result);
    }
}
