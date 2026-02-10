<?php

/**
 * @copyright
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\WidgetLegacyCreatorHandlerCompiler;
use Ec\Widget\Application\Service\WidgetLegacyCreatorHandler;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
#[CoversClass(WidgetLegacyCreatorHandlerCompiler::class)]
final class WidgetLegacyCreatorCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function process(): void
    {
        $creatorDefinition = new Definition();
        $creatorDefinition->addTag('ec.widget.legacyCreator');
        $this->setDefinition('creator', $creatorDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            WidgetLegacyCreatorHandler::class,
            'addCreator',
            [$creatorDefinition]
        );
    }

    #[Test]
    public function processWithMultipleTaggedServices(): void
    {
        $definition1 = new Definition();
        $definition1->addTag('ec.widget.legacyCreator');
        $this->setDefinition('creator1', $definition1);

        $definition2 = new Definition();
        $definition2->addTag('ec.widget.legacyCreator');
        $this->setDefinition('creator2', $definition2);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            WidgetLegacyCreatorHandler::class,
            'addCreator',
            [$definition1]
        );
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            WidgetLegacyCreatorHandler::class,
            'addCreator',
            [$definition2]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $handlerDefinition = new Definition(WidgetLegacyCreatorHandler::class);
        $container->setDefinition(WidgetLegacyCreatorHandler::class, $handlerDefinition);

        $container->addCompilerPass(new WidgetLegacyCreatorHandlerCompiler());
    }
}
