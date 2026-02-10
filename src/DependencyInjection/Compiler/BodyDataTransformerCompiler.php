<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\DependencyInjection\Compiler;

use App\Application\DataTransformer\BodyElementDataTransformerHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Antonio Jose Cerezo Aranda <acerezo@elconfidencial.com>
 */
class BodyDataTransformerCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $translators = $container->findTaggedServiceIds('app.data_transformer');
        $translatorsHandler = $container->findDefinition(BodyElementDataTransformerHandler::class);

        foreach ($translators as $idService => $parameters) {
            $definition = $container->getDefinition($idService);
            $translatorsHandler->addMethodCall('addDataTransformer', [$definition]);
        }
    }
}
