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
use Sylius\PdfBundle\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\DependencyInjection\SyliusPdfExtension;
use Sylius\PdfBundle\Manager\FilesystemPdfFileManager;
use Sylius\PdfBundle\Manager\PdfFileManagerInterface;
use Sylius\PdfBundle\Renderer\HtmlToPdfRenderer;
use Sylius\PdfBundle\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfBundle\Renderer\TwigToPdfRenderer;
use Sylius\PdfBundle\Renderer\TwigToPdfRendererInterface;

final class SyliusPdfExtensionTest extends AbstractExtensionTestCase
{
    #[Test]
    public function it_registers_default_adapter_service(): void
    {
        $this->load();

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.default',
            'sylius_pdf.adapter.knp_snappy',
        );
    }

    #[Test]
    public function it_registers_default_factory_for_knp_snappy(): void
    {
        $this->load();

        $this->assertContainerBuilderHasAlias(
            'sylius_pdf.factory.default',
            'sylius_pdf.factory.knp_snappy',
        );
    }

    #[Test]
    public function it_registers_dompdf_factory_when_configured(): void
    {
        $this->load([
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'options' => ['defaultPaperSize' => 'a4']],
            ],
        ]);

        $this->assertContainerBuilderHasAlias(
            'sylius_pdf.factory.invoice',
            'sylius_pdf.factory.dompdf',
        );
    }

    #[Test]
    public function it_aliases_adapter_interface_to_default_adapter(): void
    {
        $this->load();

        $this->assertContainerBuilderHasAlias(
            PdfGenerationAdapterInterface::class,
            'sylius_pdf.adapter.default',
        );
    }

    #[Test]
    public function it_registers_html_renderer_service(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(
            'sylius_pdf.renderer.html',
            HtmlToPdfRenderer::class,
        );
    }

    #[Test]
    public function it_aliases_renderer_interface_to_html_renderer(): void
    {
        $this->load();

        $this->assertContainerBuilderHasAlias(
            HtmlToPdfRendererInterface::class,
            'sylius_pdf.renderer.html',
        );
    }

    #[Test]
    public function it_registers_custom_context_adapter_services(): void
    {
        $this->load([
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'options' => ['defaultPaperSize' => 'a4']],
                'coupon' => ['adapter' => 'dompdf', 'options' => ['isRemoteEnabled' => true]],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.invoice',
            'sylius_pdf.adapter.dompdf',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.coupon',
            'sylius_pdf.adapter.dompdf',
        );
    }

    #[Test]
    public function it_registers_dompdf_adapter_as_default_when_configured(): void
    {
        $this->load([
            'default' => ['adapter' => 'dompdf', 'options' => ['defaultPaperSize' => 'a4']],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.default',
            'sylius_pdf.adapter.dompdf',
        );
    }

    #[Test]
    public function it_loads_default_pdf_files_directory_parameter(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.pdf_files_directory',
            '%kernel.project_dir%/private/pdf',
        );
    }

    #[Test]
    public function it_loads_custom_pdf_files_directory_parameter(): void
    {
        $this->load(['pdf_files_directory' => '/custom/path']);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.pdf_files_directory',
            '/custom/path',
        );
    }

    #[Test]
    public function it_registers_filesystem_pdf_file_manager_service(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(
            'sylius_pdf.manager.filesystem',
            FilesystemPdfFileManager::class,
        );
    }

    #[Test]
    public function it_aliases_manager_interface_to_filesystem_manager(): void
    {
        $this->load();

        $this->assertContainerBuilderHasAlias(
            PdfFileManagerInterface::class,
            'sylius_pdf.manager',
        );
    }

    #[Test]
    public function it_registers_twig_to_pdf_renderer_service(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(
            'sylius_pdf.renderer.twig',
            TwigToPdfRenderer::class,
        );
    }

    #[Test]
    public function it_aliases_twig_renderer_interface_to_twig_renderer(): void
    {
        $this->load();

        $this->assertContainerBuilderHasAlias(
            TwigToPdfRendererInterface::class,
            'sylius_pdf.renderer.twig',
        );
    }

    #[Test]
    public function it_defers_unknown_adapter_to_compiler_pass(): void
    {
        $this->load([
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom', 'options' => []],
            ],
        ]);

        self::assertTrue($this->container->hasParameter('sylius_pdf.deferred_adapter_contexts'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('sylius_pdf.deferred_adapter_contexts');
        self::assertSame(['invoice' => 'my_custom'], $deferred);
    }

    #[Test]
    public function it_does_not_set_adapter_alias_when_default_is_custom(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom', 'options' => []],
        ]);

        self::assertFalse($this->container->hasAlias(PdfGenerationAdapterInterface::class));
    }

    #[Test]
    public function it_mixes_built_in_and_custom_adapters_correctly(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy', 'options' => []],
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom', 'options' => []],
                'coupon' => ['adapter' => 'dompdf', 'options' => []],
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
        $deferred = $this->container->getParameter('sylius_pdf.deferred_adapter_contexts');
        self::assertSame(['invoice' => 'my_custom'], $deferred);
    }

    #[Test]
    public function it_sets_context_pdf_files_directories_parameter(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.context_pdf_files_directories',
            ['default' => '%kernel.project_dir%/private/pdf'],
        );
    }

    #[Test]
    public function it_passes_context_directories_to_manager_definition(): void
    {
        $this->load([
            'pdf_files_directory' => '/root/pdf',
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'pdf_files_directory' => '/custom/invoices'],
                'coupon' => ['adapter' => 'dompdf'],
            ],
        ]);

        $managerDefinition = $this->container->getDefinition('sylius_pdf.manager.filesystem');
        self::assertSame([
            'default' => '/root/pdf',
            'invoice' => '/custom/invoices',
            'coupon' => '/root/pdf',
        ], $managerDefinition->getArgument(0));
    }

    #[Test]
    public function it_inherits_root_pdf_files_directory_for_context_without_override(): void
    {
        $this->load([
            'pdf_files_directory' => '/root/pdf',
            'contexts' => [
                'coupon' => ['adapter' => 'dompdf'],
            ],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.context_pdf_files_directories',
            ['default' => '/root/pdf', 'coupon' => '/root/pdf'],
        );
    }

    #[Test]
    public function it_uses_context_pdf_files_directory_override(): void
    {
        $this->load([
            'pdf_files_directory' => '/root/pdf',
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'pdf_files_directory' => '/custom/invoices'],
            ],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.context_pdf_files_directories',
            ['default' => '/root/pdf', 'invoice' => '/custom/invoices'],
        );
    }

    #[Test]
    public function it_uses_default_block_pdf_files_directory_override(): void
    {
        $this->load([
            'pdf_files_directory' => '/root/pdf',
            'default' => ['pdf_files_directory' => '/custom/default'],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.context_pdf_files_directories',
            ['default' => '/custom/default'],
        );
    }

    #[Test]
    public function it_does_not_set_deferred_parameter_when_all_adapters_are_built_in(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy', 'options' => []],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'options' => []],
            ],
        ]);

        self::assertFalse($this->container->hasParameter('sylius_pdf.deferred_adapter_contexts'));
    }

    #[Test]
    public function it_stores_context_options_as_parameters(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy', 'options' => ['allowed_files' => ['logo.png']]],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'options' => ['defaultPaperSize' => 'a4']],
            ],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.adapter.default.options',
            ['allowed_files' => ['logo.png']],
        );

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.adapter.invoice.options',
            ['defaultPaperSize' => 'a4'],
        );
    }

    #[Test]
    public function it_stores_context_options_for_custom_adapters(): void
    {
        $this->load([
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom', 'options' => ['custom_key' => 'custom_value']],
            ],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf.adapter.invoice.options',
            ['custom_key' => 'custom_value'],
        );
    }

    #[Test]
    public function it_defers_custom_factory_to_compiler_pass(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy', 'factory' => 'my_custom_factory', 'options' => []],
        ]);

        self::assertTrue($this->container->hasParameter('sylius_pdf.deferred_factory_contexts'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('sylius_pdf.deferred_factory_contexts');
        self::assertSame(['default' => 'my_custom_factory'], $deferred);
    }

    #[Test]
    public function it_does_not_set_deferred_factory_parameter_when_all_factories_are_built_in(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy', 'options' => []],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'options' => []],
            ],
        ]);

        self::assertFalse($this->container->hasParameter('sylius_pdf.deferred_factory_contexts'));
    }

    #[Test]
    public function it_defers_custom_factory_for_context(): void
    {
        $this->load([
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'factory' => 'my_custom_factory', 'options' => []],
            ],
        ]);

        self::assertTrue($this->container->hasParameter('sylius_pdf.deferred_factory_contexts'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('sylius_pdf.deferred_factory_contexts');
        self::assertSame(['invoice' => 'my_custom_factory'], $deferred);
    }

    #[Test]
    public function it_registers_built_in_factory_when_factory_matches_adapter(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy', 'factory' => 'knp_snappy', 'options' => []],
        ]);

        $this->assertContainerBuilderHasAlias(
            'sylius_pdf.factory.default',
            'sylius_pdf.factory.knp_snappy',
        );

        self::assertFalse($this->container->hasParameter('sylius_pdf.deferred_factory_contexts'));
    }

    #[Test]
    public function it_loads_adapter_services_file_only_once_for_multiple_contexts(): void
    {
        $this->load([
            'default' => ['adapter' => 'dompdf', 'options' => []],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf', 'options' => ['defaultPaperSize' => 'a4']],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.default',
            'sylius_pdf.adapter.dompdf',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.invoice',
            'sylius_pdf.adapter.dompdf',
        );

        $this->assertContainerBuilderHasAlias(
            'sylius_pdf.factory.default',
            'sylius_pdf.factory.dompdf',
        );

        $this->assertContainerBuilderHasAlias(
            'sylius_pdf.factory.invoice',
            'sylius_pdf.factory.dompdf',
        );
    }

    #[Test]
    public function it_does_not_load_unused_adapter_services(): void
    {
        $this->load([
            'default' => ['adapter' => 'dompdf', 'options' => []],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf.factory.knp_snappy'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf.adapter.knp_snappy'));
    }

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfExtension()];
    }
}
