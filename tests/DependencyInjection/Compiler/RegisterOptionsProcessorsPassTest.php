<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\PdfGenerationBundle\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Core\Processor\CompositeOptionsProcessor;
use Sylius\PdfGenerationBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfGenerationBundle\DependencyInjection\Compiler\RegisterOptionsProcessorsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterOptionsProcessorsPassTest extends TestCase
{
    #[Test]
    public function it_does_nothing_when_no_tagged_services_exist(): void
    {
        $container = new ContainerBuilder();
        $pass = new RegisterOptionsProcessorsPass();

        $pass->process($container);

        self::assertFalse($container->hasDefinition('sylius_pdf_generation.options_processor.composite.knp_snappy'));
    }

    #[Test]
    public function it_registers_composite_processor_for_adapter(): void
    {
        $container = new ContainerBuilder();

        $processor = new Definition(OptionsProcessorInterface::class);
        $processor->addTag('sylius_pdf_generation.options_processor', ['adapter' => 'knp_snappy']);
        $container->setDefinition('app.processor', $processor);

        $pass = new RegisterOptionsProcessorsPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('sylius_pdf_generation.options_processor.composite.knp_snappy'));

        $compositeDefinition = $container->getDefinition('sylius_pdf_generation.options_processor.composite.knp_snappy');
        self::assertSame(CompositeOptionsProcessor::class, $compositeDefinition->getClass());
    }

    #[Test]
    public function it_groups_processors_by_context_defaulting_to_default(): void
    {
        $container = new ContainerBuilder();

        $processor = new Definition(OptionsProcessorInterface::class);
        $processor->addTag('sylius_pdf_generation.options_processor', ['adapter' => 'knp_snappy']);
        $container->setDefinition('app.processor', $processor);

        $pass = new RegisterOptionsProcessorsPass();
        $pass->process($container);

        /** @var array<string, list<Reference>> $contextReferences */
        $contextReferences = $container->getDefinition('sylius_pdf_generation.options_processor.composite.knp_snappy')->getArgument(0);

        self::assertArrayHasKey('default', $contextReferences);
        self::assertCount(1, $contextReferences['default']);
        self::assertEquals(new Reference('app.processor'), $contextReferences['default'][0]);
    }

    #[Test]
    public function it_groups_processors_by_explicit_context(): void
    {
        $container = new ContainerBuilder();

        $processor = new Definition(OptionsProcessorInterface::class);
        $processor->addTag('sylius_pdf_generation.options_processor', ['adapter' => 'knp_snappy', 'context' => 'invoice']);
        $container->setDefinition('app.processor', $processor);

        $pass = new RegisterOptionsProcessorsPass();
        $pass->process($container);

        /** @var array<string, list<Reference>> $contextReferences */
        $contextReferences = $container->getDefinition('sylius_pdf_generation.options_processor.composite.knp_snappy')->getArgument(0);

        self::assertArrayHasKey('invoice', $contextReferences);
        self::assertArrayNotHasKey('default', $contextReferences);
    }

    #[Test]
    public function it_sorts_processors_by_priority_descending(): void
    {
        $container = new ContainerBuilder();

        $low = new Definition(OptionsProcessorInterface::class);
        $low->addTag('sylius_pdf_generation.options_processor', ['adapter' => 'knp_snappy', 'priority' => 0]);
        $container->setDefinition('app.low_priority', $low);

        $high = new Definition(OptionsProcessorInterface::class);
        $high->addTag('sylius_pdf_generation.options_processor', ['adapter' => 'knp_snappy', 'priority' => 10]);
        $container->setDefinition('app.high_priority', $high);

        $pass = new RegisterOptionsProcessorsPass();
        $pass->process($container);

        /** @var array<string, list<Reference>> $contextReferences */
        $contextReferences = $container->getDefinition('sylius_pdf_generation.options_processor.composite.knp_snappy')->getArgument(0);

        self::assertCount(2, $contextReferences['default']);
        self::assertEquals(new Reference('app.high_priority'), $contextReferences['default'][0]);
        self::assertEquals(new Reference('app.low_priority'), $contextReferences['default'][1]);
    }

    #[Test]
    public function it_creates_separate_composites_per_adapter(): void
    {
        $container = new ContainerBuilder();

        $snappyProcessor = new Definition(OptionsProcessorInterface::class);
        $snappyProcessor->addTag('sylius_pdf_generation.options_processor', ['adapter' => 'knp_snappy']);
        $container->setDefinition('app.snappy_processor', $snappyProcessor);

        $dompdfProcessor = new Definition(OptionsProcessorInterface::class);
        $dompdfProcessor->addTag('sylius_pdf_generation.options_processor', ['adapter' => 'dompdf']);
        $container->setDefinition('app.dompdf_processor', $dompdfProcessor);

        $pass = new RegisterOptionsProcessorsPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('sylius_pdf_generation.options_processor.composite.knp_snappy'));
        self::assertTrue($container->hasDefinition('sylius_pdf_generation.options_processor.composite.dompdf'));
    }

    #[Test]
    public function it_throws_when_adapter_attribute_is_missing(): void
    {
        $container = new ContainerBuilder();

        $processor = new Definition(OptionsProcessorInterface::class);
        $processor->addTag('sylius_pdf_generation.options_processor', []);
        $container->setDefinition('app.processor', $processor);

        $pass = new RegisterOptionsProcessorsPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The service "app.processor" tagged with "sylius_pdf_generation.options_processor" must have an "adapter" attribute.');

        $pass->process($container);
    }
}
