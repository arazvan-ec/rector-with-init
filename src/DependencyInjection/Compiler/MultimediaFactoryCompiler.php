<?php

/**
 * @copyright
 */

namespace App\DependencyInjection\Compiler;

use Ec\Multimedia\Application\Factory\Multimedia;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class MultimediaFactoryCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definedServiceTags = $container->findTaggedServiceIds('ec.multimedia.factory');
        $definedHandler = $container->findDefinition(Multimedia::class);

        foreach ($definedServiceTags as $idService => $parameters) {
            $definition = $container->getDefinition($idService);
            $definedHandler->addMethodCall('addFactory', [$definition]);
        }
    }
}
