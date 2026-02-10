<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\DependencyInjection\Compiler;

use App\Application\DataTransformer\Apps\Media\MediaDataTransformerHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class MediaDataTransformerCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $translators = $container->findTaggedServiceIds('app.media_data_transformer');
        $translatorsHandler = $container->findDefinition(MediaDataTransformerHandler::class);

        foreach ($translators as $idService => $parameters) {
            $definition = $container->getDefinition($idService);
            $translatorsHandler->addMethodCall('addDataTransformer', [$definition]);
        }
    }
}
