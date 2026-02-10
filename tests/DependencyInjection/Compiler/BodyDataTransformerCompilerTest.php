<?php

/**
 * @copyright
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\Application\DataTransformer\BodyElementDataTransformerHandler;
use App\DependencyInjection\Compiler\BodyDataTransformerCompiler;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(BodyDataTransformerCompiler::class)]
class BodyDataTransformerCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function process(): void
    {
        $bodyElementDataTransformerHandlerDefinition = new Definition();
        $this->setDefinition(BodyElementDataTransformerHandler::class, $bodyElementDataTransformerHandlerDefinition);

        $dataTransformerDefinition = new Definition();
        $dataTransformerDefinition->addTag('app.data_transformer');
        $this->setDefinition('data_transformer_service', $dataTransformerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            BodyElementDataTransformerHandler::class,
            'addDataTransformer',
            [
                $dataTransformerDefinition,
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $orchestratorChainDefinition = new Definition(BodyElementDataTransformerHandler::class);
        $container->setDefinition(BodyElementDataTransformerHandler::class, $orchestratorChainDefinition);

        $container->addCompilerPass(new BodyDataTransformerCompiler());
    }
}
