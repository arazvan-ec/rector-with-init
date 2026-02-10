<?php

/**
 * @copyright
 */

namespace App\Tests\Orchestrator;

use App\Orchestrator\Chain\EditorialOrchestrator;
use App\Orchestrator\Exceptions\DuplicateChainInOrchestratorHandlerException;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use App\Orchestrator\OrchestratorChainHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(OrchestratorChainHandler::class)]
class OrchestratorChainHandlerTest extends TestCase
{
    /** @var EditorialOrchestrator|MockObject */
    private EditorialOrchestrator $orchestratorChainMock;
    private OrchestratorChainHandler $orchestratorChainHandler;

    protected function setUp(): void
    {
        $this->orchestratorChainMock = $this->createMock(EditorialOrchestrator::class);
        $this->orchestratorChainMock
            ->method('canOrchestrate')
            ->willReturn('fake-ochestrator');

        $this->orchestratorChainHandler = new OrchestratorChainHandler();
    }

    #[Test]
    public function handlerShouldReturnString(): void
    {
        $alias = 'fake';
        $handlerReturn = [
            'param' => $alias,
        ];
        $type = 'fake-ochestrator';

        $requestMock = $this->createMock(Request::class);

        $this->orchestratorChainMock->expects(static::once())
            ->method('execute')
            ->with($requestMock)
            ->willReturn($handlerReturn);

        $this->orchestratorChainHandler->addOrchestrator($this->orchestratorChainMock);

        $return = $this->orchestratorChainHandler->handler($type, $requestMock);

        static::assertSame($handlerReturn, $return);
    }

    #[Test]
    public function handlerShouldReturnOrchestratorTypeNotExistException(): void
    {
        $type = 'fake77';

        $requestMock = $this->createMock(Request::class);

        $this->orchestratorChainHandler->addOrchestrator($this->orchestratorChainMock);

        $this->expectException(OrchestratorTypeNotExistException::class);
        $this->expectExceptionMessage('Orchestrator fake77 not exist');

        $this->orchestratorChainHandler->handler($type, $requestMock);
    }

    #[Test]
    public function addOrchestratorShouldReturnThis(): void
    {
        $return = $this->orchestratorChainHandler->addOrchestrator($this->orchestratorChainMock);

        static::assertSame($this->orchestratorChainHandler, $return);
    }

    #[Test]
    public function addOrchestratorShouldReturnException(): void
    {
        $orchestratorDuplicate = $this->createMock(EditorialOrchestrator::class);
        $orchestratorDuplicate
            ->method('canOrchestrate')
            ->willReturn('fake-ochestrator');

        $this->expectException(DuplicateChainInOrchestratorHandlerException::class);

        $this->orchestratorChainHandler->addOrchestrator($orchestratorDuplicate);

        $this->orchestratorChainHandler->addOrchestrator($orchestratorDuplicate);
    }
}
