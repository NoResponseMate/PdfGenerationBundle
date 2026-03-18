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

namespace Tests\Sylius\PdfGenerationBundle\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sylius\PdfGenerationBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfGenerationBundle\Core\Filesystem\Manager\PdfFileManager;
use Sylius\PdfGenerationBundle\Core\Filesystem\Manager\PdfFileManagerInterface;
use Sylius\PdfGenerationBundle\Core\Processor\CompositeOptionsProcessor;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistry;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Sylius\PdfGenerationBundle\Core\Renderer\HtmlToPdfRenderer;
use Sylius\PdfGenerationBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRenderer;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface;
use Sylius\PdfGenerationBundle\DependencyInjection\SyliusPdfGenerationExtension;
use Sylius\PdfGenerationBundle\Filesystem\Symfony\SymfonyFilesystemPdfStorage;

final class SyliusPdfGenerationExtensionTest extends AbstractExtensionTestCase
{
    #[Test]
    public function it_registers_html_renderer_service(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.renderer.html',
            HtmlToPdfRenderer::class,
        );
        $this->assertContainerBuilderHasAlias(
            HtmlToPdfRendererInterface::class,
            'sylius_pdf_generation.renderer.html',
        );
    }

    #[Test]
    public function it_registers_twig_renderer_service(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.renderer.twig',
            TwigToPdfRenderer::class,
        );
        $this->assertContainerBuilderHasAlias(
            TwigToPdfRendererInterface::class,
            'sylius_pdf_generation.renderer.twig',
        );
    }

    #[Test]
    public function it_registers_pdf_file_manager_service(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.manager',
            PdfFileManager::class,
        );
    }

    #[Test]
    public function it_aliases_manager_interface_to_manager(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasAlias(
            PdfFileManagerInterface::class,
            'sylius_pdf_generation.manager',
        );
    }

    #[Test]
    public function it_registers_default_filesystem_storage(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.storage.default',
            SymfonyFilesystemPdfStorage::class,
        );
    }

    #[Test]
    public function it_registers_per_context_storage(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
            'contexts' => [
                'invoice' => [
                    'adapter' => 'my_custom',
                    'storage' => [
                        'type' => 'filesystem',
                        'directory' => '/custom/invoices',
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.storage.invoice',
            SymfonyFilesystemPdfStorage::class,
        );
    }

    #[Test]
    public function it_inherits_default_storage_for_context_without_override(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom'],
            ],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.storage.invoice',
            SymfonyFilesystemPdfStorage::class,
        );
    }

    #[Test]
    public function it_uses_default_block_storage_override(): void
    {
        $this->load([
            'default' => [
                'adapter' => 'my_custom',
                'storage' => [
                    'type' => 'filesystem',
                    'directory' => '/custom/default',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.storage.default',
            SymfonyFilesystemPdfStorage::class,
        );
    }

    #[Test]
    public function it_aliases_adapter_interface_to_default_adapter(): void
    {
        if (!interface_exists(\Knp\Snappy\GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }

        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
        ]);

        $this->assertContainerBuilderHasAlias(
            PdfGenerationAdapterInterface::class,
            'sylius_pdf_generation.adapter.default',
        );
    }

    #[Test]
    public function it_does_not_set_adapter_alias_when_no_default_adapter_configured(): void
    {
        $this->load();

        self::assertFalse($this->container->hasAlias(PdfGenerationAdapterInterface::class));
        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.adapter.default'));
    }

    #[Test]
    public function it_does_not_set_adapter_alias_when_default_is_custom(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        self::assertFalse($this->container->hasAlias(PdfGenerationAdapterInterface::class));
    }

    #[Test]
    public function it_defers_unknown_adapter_to_compiler_pass(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom'],
            ],
        ]);

        self::assertTrue($this->container->hasParameter('.sylius_pdf_generation.deferred_adapter_contexts'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('.sylius_pdf_generation.deferred_adapter_contexts');
        self::assertSame(['default' => 'my_custom', 'invoice' => 'my_custom'], $deferred);
    }

    #[Test]
    public function it_defers_only_custom_context_adapter_when_default_is_built_in(): void
    {
        if (!interface_exists(\Knp\Snappy\GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }

        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom'],
            ],
        ]);

        self::assertTrue($this->container->hasParameter('.sylius_pdf_generation.deferred_adapter_contexts'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('.sylius_pdf_generation.deferred_adapter_contexts');
        self::assertSame(['invoice' => 'my_custom'], $deferred);
    }

    #[Test]
    public function it_does_not_register_knp_snappy_services_when_using_only_custom_adapter(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.adapter.knp_snappy'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.generator_provider.knp_snappy'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.options_processor.knp_snappy'));
    }

    #[Test]
    public function it_does_not_register_dompdf_services_when_using_only_custom_adapter(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.adapter.dompdf'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.generator_provider.dompdf'));
    }

    #[Test]
    public function it_throws_when_knp_snappy_is_configured_but_not_installed(): void
    {
        if (interface_exists(\Knp\Snappy\GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is installed.');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "knp_snappy" adapter is configured for the "default" context, but its required dependency');

        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
        ]);
    }

    #[Test]
    public function it_throws_when_dompdf_is_configured_but_not_installed(): void
    {
        if (class_exists(\Dompdf\Dompdf::class)) {
            self::markTestSkipped('dompdf/dompdf is installed.');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "dompdf" adapter is configured for the "default" context, but its required dependency');

        $this->load([
            'default' => ['adapter' => 'dompdf'],
        ]);
    }

    #[Test]
    public function it_mixes_built_in_and_custom_adapters_correctly(): void
    {
        if (!interface_exists(\Knp\Snappy\GeneratorInterface::class) || !class_exists(\Dompdf\Dompdf::class)) {
            self::markTestSkipped('Both knplabs/knp-snappy-bundle and dompdf/dompdf are required.');
        }

        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom'],
                'coupon' => ['adapter' => 'dompdf'],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.default',
            'sylius_pdf_generation.adapter.knp_snappy',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.coupon',
            'sylius_pdf_generation.adapter.dompdf',
        );

        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.adapter.invoice'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('.sylius_pdf_generation.deferred_adapter_contexts');
        self::assertSame(['invoice' => 'my_custom'], $deferred);
    }

    #[Test]
    public function it_does_not_set_deferred_parameter_when_all_adapters_are_built_in(): void
    {
        if (!interface_exists(\Knp\Snappy\GeneratorInterface::class) || !class_exists(\Dompdf\Dompdf::class)) {
            self::markTestSkipped('Both knplabs/knp-snappy-bundle and dompdf/dompdf are required.');
        }

        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
            ],
        ]);

        self::assertFalse($this->container->hasParameter('.sylius_pdf_generation.deferred_adapter_contexts'));
    }

    #[Test]
    public function it_creates_composite_options_processor_for_each_adapter_type(): void
    {
        if (!interface_exists(\Knp\Snappy\GeneratorInterface::class) || !class_exists(\Dompdf\Dompdf::class)) {
            self::markTestSkipped('Both knplabs/knp-snappy-bundle and dompdf/dompdf are required.');
        }

        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
            ],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.options_processor.composite.knp_snappy',
            CompositeOptionsProcessor::class,
        );

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.options_processor.composite.dompdf',
            CompositeOptionsProcessor::class,
        );
    }

    #[Test]
    public function it_registers_generator_provider_registry(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.registry.generator_provider',
            GeneratorProviderRegistry::class,
        );
    }

    #[Test]
    public function it_aliases_generator_provider_registry_interface(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasAlias(
            GeneratorProviderRegistryInterface::class,
            'sylius_pdf_generation.registry.generator_provider',
        );
    }

    #[Test]
    public function it_does_not_load_unused_adapter_services(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.adapter.knp_snappy'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf_generation.adapter.dompdf'));
    }

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfGenerationExtension()];
    }
}
