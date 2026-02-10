<?php

/**
 * @copyright
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\MultimediaOrchestratorCompiler;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(MultimediaOrchestratorCompiler::class)]
class MultimediaOrchestratorCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function process(): void
    {
        $orchestratorDefinition = new Definition();
        $orchestratorDefinition->addTag('app.multimedia.orchestrators');
        $this->setDefinition('orchestrator_service', $orchestratorDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            MultimediaOrchestratorHandler::class,
            'addOrchestrator',
            [
                $orchestratorDefinition,
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $orchestratorChainDefinition = new Definition(MultimediaOrchestratorHandler::class);
        $container->setDefinition(MultimediaOrchestratorHandler::class, $orchestratorChainDefinition);

        $container->addCompilerPass(new MultimediaOrchestratorCompiler());
    }
}
