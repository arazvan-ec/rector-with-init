<?php

/**
 * @copyright
 */

namespace App\DependencyInjection\Compiler;

use App\Orchestrator\OrchestratorChainHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class EditorialOrchestratorCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $orchestrators = $container->findTaggedServiceIds('app.orchestrators');
        $orchestratorsHandler = $container->findDefinition(OrchestratorChainHandler::class);

        foreach ($orchestrators as $idService => $parameters) {
            $definition = $container->getDefinition($idService);
            $orchestratorsHandler->addMethodCall('addOrchestrator', [$definition]);
        }
    }
}
