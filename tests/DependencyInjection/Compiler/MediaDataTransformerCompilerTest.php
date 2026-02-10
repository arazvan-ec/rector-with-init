<?php

/**
 * @copyright
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\Application\DataTransformer\Apps\Media\MediaDataTransformerHandler;
use App\DependencyInjection\Compiler\MediaDataTransformerCompiler;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(MediaDataTransformerCompiler::class)]
class MediaDataTransformerCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function process(): void
    {
        $mediaDataTransformerHandlerDefinition = new Definition();
        $this->setDefinition(MediaDataTransformerHandler::class, $mediaDataTransformerHandlerDefinition);

        $dataTransformerDefinition = new Definition();
        $dataTransformerDefinition->addTag('app.media_data_transformer');
        $this->setDefinition('data_transformer_service', $dataTransformerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            MediaDataTransformerHandler::class,
            'addDataTransformer',
            [
                $dataTransformerDefinition,
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $orchestratorChainDefinition = new Definition(MediaDataTransformerHandler::class);
        $container->setDefinition(MediaDataTransformerHandler::class, $orchestratorChainDefinition);

        $container->addCompilerPass(new MediaDataTransformerCompiler());
    }
}
