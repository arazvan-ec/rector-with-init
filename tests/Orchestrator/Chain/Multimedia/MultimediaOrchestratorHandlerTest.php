<?php

namespace App\Tests\Orchestrator\Chain\Multimedia;

use App\Orchestrator\Chain\Multimedia\MultimediaEmbedVideoOrchestrator;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorInterface;
use App\Orchestrator\Chain\Multimedia\MultimediaPhotoOrchestrator;
use App\Orchestrator\Exceptions\DuplicateChainInOrchestratorHandlerException;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultimediaOrchestratorHandler::class)]
class MultimediaOrchestratorHandlerTest extends TestCase
{
    private MultimediaOrchestratorHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new MultimediaOrchestratorHandler();
    }

    #[Test]
    public function handlerExecutesCorrectOrchestratorAndReturnsResult(): void
    {
        $multimedia = $this->createMock(MultimediaPhoto::class);
        $expectedResult = ['data' => 'processed'];

        $orchestrator = $this->createMock(MultimediaPhotoOrchestrator::class);
        $this->handler->addOrchestrator($orchestrator);

        $orchestrator
            ->method('canOrchestrate')
            ->willReturn('photo');

        $orchestrator->expects(self::once())
            ->method('execute')
            ->with($multimedia)
            ->willReturn($expectedResult);

        $result = $this->handler->handler($multimedia);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function handlerSelectsFirstMatchingOrchestrator(): void
    {
        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia->method('type')
            ->willReturn('embed_video');

        $orchestrator1 = $this->createMock(MultimediaPhotoOrchestrator::class);
        $orchestrator1
            ->method('canOrchestrate')
            ->willReturn('photo');
        $orchestrator1->expects(self::never())
            ->method('execute');

        $orchestrator2 = $this->createMock(MultimediaEmbedVideoOrchestrator::class);
        $orchestrator2
            ->method('canOrchestrate')
            ->willReturn('embed_video');
        $orchestrator2->expects(self::once())
            ->method('execute')
            ->with($multimedia)
            ->willReturn(['result' => 'from_orchestrator2']);

        $this->handler->addOrchestrator($orchestrator1);
        $this->handler->addOrchestrator($orchestrator2);

        $result = $this->handler->handler($multimedia);

        static::assertEquals(['result' => 'from_orchestrator2'], $result);
    }

    #[Test]
    public function addOrchestratorReturnsHandlerInstance(): void
    {
        $orchestrator = $this->createMock(MultimediaOrchestratorInterface::class);

        $result = $this->handler->addOrchestrator($orchestrator);

        static::assertSame($this->handler, $result);
    }

    #[Test]
    public function addOrchestratorThrowsExceptionWhenDuplicateTypeIsAdded(): void
    {
        $orchestrator1 = $this->createMock(MultimediaOrchestratorInterface::class);
        $orchestrator1->method('canOrchestrate')
            ->willReturn('photo');

        $orchestrator2 = $this->createMock(MultimediaOrchestratorInterface::class);
        $orchestrator2->method('canOrchestrate')
            ->willReturn('photo');

        $this->handler->addOrchestrator($orchestrator1);

        $this->expectException(DuplicateChainInOrchestratorHandlerException::class);

        $this->handler->addOrchestrator($orchestrator2);
    }

    #[Test]
    public function handlerThrowsExceptionWithNoOrchestratorsAdded(): void
    {
        $multimedia = $this->createMock(Multimedia::class);

        $this->expectException(OrchestratorTypeNotExistException::class);

        $this->handler->handler($multimedia);
    }

    #[Test]
    public function handlerThrowsExceptionWhenMultimediaTypeHasNoOrchestrator(): void
    {
        $multimedia = $this->createMock(Multimedia::class);
        $multimedia->method('type')->willReturn('unsupported_type');

        $orchestrator = $this->createMock(MultimediaOrchestratorInterface::class);
        $orchestrator->method('canOrchestrate')->willReturn('photo');

        $this->handler->addOrchestrator($orchestrator);

        $this->expectException(OrchestratorTypeNotExistException::class);
        $this->handler->handler($multimedia);
    }
}
