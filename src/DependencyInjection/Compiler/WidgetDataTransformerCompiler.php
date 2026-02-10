<?php

/**
 * @copyright
 */

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\DetailWidgetDataTransformerHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class WidgetDataTransformerCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definedServiceTags = $container->findTaggedServiceIds('ec.widget.dataTransformer');
        $dataTransformersHandler = $container->findDefinition(DetailWidgetDataTransformerHandler::class);

        foreach ($definedServiceTags as $idService => $parameters) {
            $definition = $container->getDefinition($idService);
            $dataTransformersHandler->addMethodCall('addDataTransformer', [$definition]);
        }
    }
}
