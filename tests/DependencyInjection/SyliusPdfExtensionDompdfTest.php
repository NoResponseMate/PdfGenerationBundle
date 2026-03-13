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

use Dompdf\Dompdf;
use Knp\Snappy\GeneratorInterface;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sylius\PdfBundle\Core\Processor\CompositeOptionsProcessor;
use Sylius\PdfBundle\DependencyInjection\SyliusPdfExtension;

final class SyliusPdfExtensionDompdfTest extends AbstractExtensionTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Dompdf::class)) {
            self::markTestSkipped('dompdf/dompdf is not installed.');
        }

        if (!interface_exists(GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }
    }

    #[Test]
    public function it_registers_custom_context_adapter_services(): void
    {
        $this->load([
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
                'coupon' => ['adapter' => 'dompdf'],
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
            'default' => ['adapter' => 'dompdf'],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.default',
            'sylius_pdf.adapter.dompdf',
        );
    }

    #[Test]
    public function it_mixes_built_in_and_custom_adapters_correctly(): void
    {
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
    public function it_does_not_set_deferred_parameter_when_all_adapters_are_built_in(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
            ],
        ]);

        self::assertFalse($this->container->hasParameter('.sylius_pdf.deferred_adapter_contexts'));
    }

    #[Test]
    public function it_loads_adapter_services_file_only_once_for_multiple_contexts(): void
    {
        $this->load([
            'default' => ['adapter' => 'dompdf'],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
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
    }

    #[Test]
    public function it_does_not_load_unused_adapter_services(): void
    {
        $this->load([
            'default' => ['adapter' => 'dompdf'],
        ]);

        self::assertFalse($this->container->hasDefinition('sylius_pdf.generator_provider.knp_snappy'));
        self::assertFalse($this->container->hasDefinition('sylius_pdf.adapter.knp_snappy'));
    }

    #[Test]
    public function it_creates_composite_options_processor_for_each_adapter_type(): void
    {
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

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfExtension()];
    }
}
