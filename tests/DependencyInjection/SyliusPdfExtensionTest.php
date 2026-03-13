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

namespace Tests\Sylius\PdfBundle\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Manager\FilesystemPdfFileManager;
use Sylius\PdfBundle\Core\Manager\PdfFileManagerInterface;
use Sylius\PdfBundle\Core\Processor\CompositeOptionsProcessor;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistry;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRenderer;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfBundle\Core\Renderer\TwigToPdfRenderer;
use Sylius\PdfBundle\Core\Renderer\TwigToPdfRendererInterface;
use Sylius\PdfBundle\DependencyInjection\SyliusPdfExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class SyliusPdfExtensionTest extends AbstractExtensionTestCase
{
    #[Test]
    public function it_registers_html_renderer_service(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf.renderer.html',
            HtmlToPdfRenderer::class,
        );
    }

    #[Test]
    public function it_aliases_renderer_interface_to_html_renderer(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasAlias(
            HtmlToPdfRendererInterface::class,
            'sylius_pdf.renderer.html',
        );
    }

    #[Test]
    public function it_loads_default_pdf_files_directory_parameter(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.pdf_files_directory',
            '%kernel.project_dir%/private/pdf',
        );
    }

    #[Test]
    public function it_loads_custom_pdf_files_directory_parameter(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
            'pdf_files_directory' => '/custom/path',
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.pdf_files_directory',
            '/custom/path',
        );
    }

    #[Test]
    public function it_registers_filesystem_pdf_file_manager_service(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf.manager.filesystem',
            FilesystemPdfFileManager::class,
        );
    }

    #[Test]
    public function it_aliases_manager_interface_to_filesystem_manager(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasAlias(
            PdfFileManagerInterface::class,
            'sylius_pdf.manager',
        );
    }

    #[Test]
    public function it_sets_context_pdf_files_directories_parameter(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.context_pdf_files_directories',
            ['default' => '%kernel.project_dir%/private/pdf'],
        );
    }

    #[Test]
    public function it_uses_default_block_pdf_files_directory_override(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom', 'pdf_files_directory' => '/custom/default'],
            'pdf_files_directory' => '/root/pdf',
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.context_pdf_files_directories',
            ['default' => '/custom/default'],
        );
    }

    #[Test]
    public function it_aliases_adapter_interface_to_default_adapter(): void
    {
        if (!interface_exists(\Knp\Snappy\GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }

        $this->load();

        $this->assertContainerBuilderHasAlias(
            PdfGenerationAdapterInterface::class,
            'sylius_pdf.adapter.default',
        );
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

        self::assertTrue($this->container->hasParameter('.sylius_pdf.deferred_adapter_contexts'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('.sylius_pdf.deferred_adapter_contexts');
        self::assertSame(['default' => 'my_custom', 'invoice' => 'my_custom'], $deferred);
    }

    #[Test]
    public function it_defers_only_custom_context_adapter_when_default_is_built_in(): void
    {
        if (!interface_exists(\Knp\Snappy\GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }

        $this->load([
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom'],
            ],
        ]);

        self::assertTrue($this->container->hasParameter('.sylius_pdf.deferred_adapter_contexts'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('.sylius_pdf.deferred_adapter_contexts');
        self::assertSame(['invoice' => 'my_custom'], $deferred);
    }

    #[Test]
    public function it_does_not_register_knp_snappy_services_when_using_only_custom_adapter(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf.adapter.knp_snappy'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf.generator_provider.knp_snappy'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf.options_processor.knp_snappy'));
    }

    #[Test]
    public function it_does_not_register_dompdf_services_when_using_only_custom_adapter(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf.adapter.dompdf'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf.generator_provider.dompdf'));
    }

    #[Test]
    public function it_throws_when_knp_snappy_is_configured_but_not_installed(): void
    {
        if (interface_exists(\Knp\Snappy\GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is installed.');
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "knp_snappy" adapter is configured for the "default" context, but its required dependency');

        $this->load();
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
            'sylius_pdf.adapter.default',
            'sylius_pdf.adapter.knp_snappy',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.coupon',
            'sylius_pdf.adapter.dompdf',
        );

        self::assertFalse($this->container->hasDefinition('sylius_pdf.adapter.invoice'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('.sylius_pdf.deferred_adapter_contexts');
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

        self::assertFalse($this->container->hasParameter('.sylius_pdf.deferred_adapter_contexts'));
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
            'sylius_pdf.options_processor.composite.knp_snappy',
            CompositeOptionsProcessor::class,
        );

        $this->assertContainerBuilderHasService(
            'sylius_pdf.options_processor.composite.dompdf',
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
            'sylius_pdf.registry.generator_provider',
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
            'sylius_pdf.registry.generator_provider',
        );
    }

    #[Test]
    public function it_passes_context_directories_to_manager_definition(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
            'pdf_files_directory' => '/root/pdf',
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom'],
            ],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.context_pdf_files_directories',
            ['default' => '/root/pdf', 'invoice' => '/root/pdf'],
        );
    }

    #[Test]
    public function it_inherits_root_pdf_files_directory_for_context_without_override(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
            'pdf_files_directory' => '/root/pdf',
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom'],
            ],
        ]);

        /** @var array<string, string> $directories */
        $directories = $this->container->getParameter('sylius_pdf.context_pdf_files_directories');
        self::assertSame('/root/pdf', $directories['invoice']);
    }

    #[Test]
    public function it_uses_context_pdf_files_directory_override(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
            'pdf_files_directory' => '/root/pdf',
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom', 'pdf_files_directory' => '/custom/invoices'],
            ],
        ]);

        /** @var array<string, string> $directories */
        $directories = $this->container->getParameter('sylius_pdf.context_pdf_files_directories');
        self::assertSame('/custom/invoices', $directories['invoice']);
    }

    #[Test]
    public function it_does_not_load_unused_adapter_services(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf.adapter.knp_snappy'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf.adapter.dompdf'));
    }

    #[Test]
    public function it_registers_twig_to_pdf_renderer_service_when_twig_is_available(): void
    {
        $this->container->registerExtension(new class() extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'twig';
            }
        });
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf.renderer.twig',
            TwigToPdfRenderer::class,
        );
    }

    #[Test]
    public function it_aliases_twig_renderer_interface_to_twig_renderer_when_twig_is_available(): void
    {
        $this->container->registerExtension(new class() extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'twig';
            }
        });
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        $this->assertContainerBuilderHasAlias(
            TwigToPdfRendererInterface::class,
            'sylius_pdf.renderer.twig',
        );
    }

    #[Test]
    public function it_does_not_register_twig_renderer_when_twig_is_not_available(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf.renderer.twig'));
        self::assertFalse($this->container->hasAlias(TwigToPdfRendererInterface::class));
    }

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfExtension()];
    }
}
