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

use Knp\Snappy\GeneratorInterface;
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

final class SyliusPdfExtensionKnpSnappyTest extends AbstractExtensionTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!interface_exists(GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }
    }

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
                'invoice' => ['adapter' => 'my_custom'],
            ],
        ]);

        self::assertTrue($this->container->hasParameter('sylius_pdf.deferred_adapter_contexts'));

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
    public function it_creates_composite_options_processor_for_default_adapter(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(
            'sylius_pdf.options_processor.composite.knp_snappy',
            CompositeOptionsProcessor::class,
        );
    }

    #[Test]
    public function it_registers_generator_provider_registry(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(
            'sylius_pdf.registry.generator_provider',
            GeneratorProviderRegistry::class,
        );
    }

    #[Test]
    public function it_aliases_generator_provider_registry_interface(): void
    {
        $this->load();

        $this->assertContainerBuilderHasAlias(
            GeneratorProviderRegistryInterface::class,
            'sylius_pdf.registry.generator_provider',
        );
    }

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfExtension()];
    }
}
