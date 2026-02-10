<?php

/**
 * @copyright
 */

namespace App\Tests\Controller\V1;

use App\Controller\V1\EditorialController;
use App\Orchestrator\OrchestratorChain;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(EditorialController::class)]
class EditorialControllerTest extends TestCase
{
    /** @var OrchestratorChain|MockObject */
    private OrchestratorChain $orchestratorChain;

    private EditorialController $controller;

    protected function setUp(): void
    {
        $this->orchestratorChain = $this->createMock(OrchestratorChain::class);
        $this->controller = new EditorialController($this->orchestratorChain);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->orchestratorChain, $this->controller);
    }

    #[Test]
    public function getEditorialByIdMustReturnEditorial(): void
    {
        $orchestratorResponse = ['editorial' => ['id' => '1234']];
        $id = '1234';

        $this->orchestratorChain
            ->expects($this->once())
            ->method('handler')
            ->with('editorial', $this->isInstanceOf(Request::class))
            ->willReturn($orchestratorResponse);

        /** @var Request|MockObject $request */
        $request = $this->createMock(Request::class);
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->method('set')->with('id', $id);

        $response = $this->controller->getEditorialById($request, $id);
        $content = $response->getContent();
        $this->assertIsString($content, 'Expected content to be a string.');

        $decodedResponse = json_decode($content, true);
        $this->assertSame($orchestratorResponse, $decodedResponse);
    }
}
