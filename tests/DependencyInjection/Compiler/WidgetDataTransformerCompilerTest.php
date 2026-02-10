<?php

/**
 * @copyright
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\DetailWidgetDataTransformerHandler;
use App\DependencyInjection\Compiler\WidgetDataTransformerCompiler;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(WidgetDataTransformerCompiler::class)]
class WidgetDataTransformerCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function process(): void
    {
        $widgetDataTransformerHandlerDefinition = new Definition();
        $this->setDefinition(DetailWidgetDataTransformerHandler::class, $widgetDataTransformerHandlerDefinition);

        $dataTransformerDefinition = new Definition();
        $dataTransformerDefinition->addTag('ec.widget.dataTransformer');
        $this->setDefinition('widget_data_transformer_service', $dataTransformerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            DetailWidgetDataTransformerHandler::class,
            'addDataTransformer',
            [
                $dataTransformerDefinition,
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $handlerDefinition = new Definition(DetailWidgetDataTransformerHandler::class);
        $container->setDefinition(DetailWidgetDataTransformerHandler::class, $handlerDefinition);

        $container->addCompilerPass(new WidgetDataTransformerCompiler());
    }
}
