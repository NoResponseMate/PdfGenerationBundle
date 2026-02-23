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
use Sylius\PdfGenerationBundle\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfGenerationBundle\DependencyInjection\Compiler\RegisterPdfGenerationAdaptersPass;
use Sylius\PdfGenerationBundle\Renderer\HtmlToPdfRenderer;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterPdfGenerationAdaptersPassTest extends TestCase
{
    #[Test]
    public function it_does_nothing_when_deferred_parameter_is_absent(): void
    {
        $container = new ContainerBuilder();
        $rendererDefinition = new Definition(HtmlToPdfRenderer::class, [
            new ServiceLocatorArgument(['default' => new Reference('sylius_pdf_generation.adapter.default')]),
        ]);
        $container->setDefinition('sylius_pdf_generation.renderer.html', $rendererDefinition);

        $pass = new RegisterPdfGenerationAdaptersPass();
        $pass->process($container);

        /** @var ServiceLocatorArgument $locator */
        $locator = $rendererDefinition->getArgument(0);
        self::assertCount(1, $locator->getValues());
    }

    #[Test]
    public function it_resolves_deferred_context_to_tagged_service(): void
    {
        $container = new ContainerBuilder();
        $rendererDefinition = new Definition(HtmlToPdfRenderer::class, [
            new ServiceLocatorArgument([]),
        ]);
        $container->setDefinition('sylius_pdf_generation.renderer.html', $rendererDefinition);

        $container->setParameter('sylius_pdf_generation.deferred_adapter_contexts', [
            'invoice' => 'my_custom',
        ]);

        $customAdapterDefinition = new Definition(\stdClass::class);
        $customAdapterDefinition->addTag('sylius_pdf_generation.adapter', ['key' => 'my_custom']);
        $container->setDefinition('app.adapter.custom', $customAdapterDefinition);

        $pass = new RegisterPdfGenerationAdaptersPass();
        $pass->process($container);

        /** @var ServiceLocatorArgument $locator */
        $locator = $rendererDefinition->getArgument(0);
        $values = $locator->getValues();

        self::assertArrayHasKey('invoice', $values);
        self::assertSame('app.adapter.custom', (string) $values['invoice']);
    }

    #[Test]
    public function it_merges_with_existing_built_in_adapters_in_service_locator(): void
    {
        $container = new ContainerBuilder();
        $rendererDefinition = new Definition(HtmlToPdfRenderer::class, [
            new ServiceLocatorArgument([
                'default' => new Reference('sylius_pdf_generation.adapter.default'),
            ]),
        ]);
        $container->setDefinition('sylius_pdf_generation.renderer.html', $rendererDefinition);

        $container->setParameter('sylius_pdf_generation.deferred_adapter_contexts', [
            'invoice' => 'my_custom',
        ]);

        $customAdapterDefinition = new Definition(\stdClass::class);
        $customAdapterDefinition->addTag('sylius_pdf_generation.adapter', ['key' => 'my_custom']);
        $container->setDefinition('app.adapter.custom', $customAdapterDefinition);

        $pass = new RegisterPdfGenerationAdaptersPass();
        $pass->process($container);

        /** @var ServiceLocatorArgument $locator */
        $locator = $rendererDefinition->getArgument(0);
        $values = $locator->getValues();

        self::assertCount(2, $values);
        self::assertArrayHasKey('default', $values);
        self::assertArrayHasKey('invoice', $values);
        self::assertSame('sylius_pdf_generation.adapter.default', (string) $values['default']);
        self::assertSame('app.adapter.custom', (string) $values['invoice']);
    }

    #[Test]
    public function it_sets_adapter_interface_alias_when_default_context_is_deferred(): void
    {
        $container = new ContainerBuilder();
        $rendererDefinition = new Definition(HtmlToPdfRenderer::class, [
            new ServiceLocatorArgument([]),
        ]);
        $container->setDefinition('sylius_pdf_generation.renderer.html', $rendererDefinition);

        $container->setParameter('sylius_pdf_generation.deferred_adapter_contexts', [
            'default' => 'my_custom',
        ]);

        $customAdapterDefinition = new Definition(\stdClass::class);
        $customAdapterDefinition->addTag('sylius_pdf_generation.adapter', ['key' => 'my_custom']);
        $container->setDefinition('app.adapter.custom', $customAdapterDefinition);

        $pass = new RegisterPdfGenerationAdaptersPass();
        $pass->process($container);

        self::assertTrue($container->hasAlias(PdfGenerationAdapterInterface::class));
        self::assertSame('app.adapter.custom', (string) $container->getAlias(PdfGenerationAdapterInterface::class));
    }

    #[Test]
    public function it_throws_for_unknown_adapter_key(): void
    {
        $container = new ContainerBuilder();
        $rendererDefinition = new Definition(HtmlToPdfRenderer::class, [
            new ServiceLocatorArgument([]),
        ]);
        $container->setDefinition('sylius_pdf_generation.renderer.html', $rendererDefinition);

        $container->setParameter('sylius_pdf_generation.deferred_adapter_contexts', [
            'invoice' => 'nonexistent_adapter',
        ]);

        $pass = new RegisterPdfGenerationAdaptersPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The PDF generation adapter "nonexistent_adapter" used in context "invoice" is not registered.');

        $pass->process($container);
    }

    #[Test]
    public function it_throws_for_tag_missing_key_attribute(): void
    {
        $container = new ContainerBuilder();
        $rendererDefinition = new Definition(HtmlToPdfRenderer::class, [
            new ServiceLocatorArgument([]),
        ]);
        $container->setDefinition('sylius_pdf_generation.renderer.html', $rendererDefinition);

        $container->setParameter('sylius_pdf_generation.deferred_adapter_contexts', [
            'invoice' => 'my_custom',
        ]);

        $customAdapterDefinition = new Definition(\stdClass::class);
        $customAdapterDefinition->addTag('sylius_pdf_generation.adapter');
        $container->setDefinition('app.adapter.custom', $customAdapterDefinition);

        $pass = new RegisterPdfGenerationAdaptersPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The service "app.adapter.custom" tagged with "sylius_pdf_generation.adapter" must have a "key" attribute.');

        $pass->process($container);
    }

    #[Test]
    public function it_throws_for_duplicate_adapter_keys(): void
    {
        $container = new ContainerBuilder();
        $rendererDefinition = new Definition(HtmlToPdfRenderer::class, [
            new ServiceLocatorArgument([]),
        ]);
        $container->setDefinition('sylius_pdf_generation.renderer.html', $rendererDefinition);

        $container->setParameter('sylius_pdf_generation.deferred_adapter_contexts', [
            'invoice' => 'my_custom',
        ]);

        $firstDefinition = new Definition(\stdClass::class);
        $firstDefinition->addTag('sylius_pdf_generation.adapter', ['key' => 'my_custom']);
        $container->setDefinition('app.adapter.first', $firstDefinition);

        $secondDefinition = new Definition(\stdClass::class);
        $secondDefinition->addTag('sylius_pdf_generation.adapter', ['key' => 'my_custom']);
        $container->setDefinition('app.adapter.second', $secondDefinition);

        $pass = new RegisterPdfGenerationAdaptersPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The PDF generation adapter key "my_custom" is already registered');

        $pass->process($container);
    }

    #[Test]
    public function it_cleans_up_the_temporary_parameter(): void
    {
        $container = new ContainerBuilder();
        $rendererDefinition = new Definition(HtmlToPdfRenderer::class, [
            new ServiceLocatorArgument([]),
        ]);
        $container->setDefinition('sylius_pdf_generation.renderer.html', $rendererDefinition);

        $container->setParameter('sylius_pdf_generation.deferred_adapter_contexts', [
            'invoice' => 'my_custom',
        ]);

        $customAdapterDefinition = new Definition(\stdClass::class);
        $customAdapterDefinition->addTag('sylius_pdf_generation.adapter', ['key' => 'my_custom']);
        $container->setDefinition('app.adapter.custom', $customAdapterDefinition);

        $pass = new RegisterPdfGenerationAdaptersPass();
        $pass->process($container);

        self::assertFalse($container->hasParameter('sylius_pdf_generation.deferred_adapter_contexts'));
    }
}
