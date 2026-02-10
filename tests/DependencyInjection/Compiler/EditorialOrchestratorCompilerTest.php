<?php

/**
 * @copyright
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\EditorialOrchestratorCompiler;
use App\Orchestrator\OrchestratorChainHandler;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(EditorialOrchestratorCompiler::class)]
class EditorialOrchestratorCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function process(): void
    {
        $orchestratorDefinition = new Definition();
        $orchestratorDefinition->addTag('app.orchestrators');
        $this->setDefinition('orchestrator_service', $orchestratorDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            OrchestratorChainHandler::class,
            'addOrchestrator',
            [
                $orchestratorDefinition,
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $orchestratorChainDefinition = new Definition(OrchestratorChainHandler::class);
        $container->setDefinition(OrchestratorChainHandler::class, $orchestratorChainDefinition);

        $container->addCompilerPass(new EditorialOrchestratorCompiler());
    }
}
