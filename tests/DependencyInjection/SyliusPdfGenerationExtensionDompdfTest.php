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

use Dompdf\Dompdf;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sylius\PdfGenerationBundle\Core\Processor\CompositeOptionsProcessor;
use Sylius\PdfGenerationBundle\DependencyInjection\SyliusPdfGenerationExtension;

final class SyliusPdfGenerationExtensionDompdfTest extends AbstractExtensionTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Dompdf::class)) {
            self::markTestSkipped('dompdf/dompdf is not installed.');
        }
    }

    #[Test]
    public function it_registers_dompdf_adapter_as_default(): void
    {
        $this->load([
            'default' => ['adapter' => 'dompdf'],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.default',
            'sylius_pdf_generation.adapter.dompdf',
        );
    }

    #[Test]
    public function it_registers_dompdf_context_adapter_services(): void
    {
        $this->load([
            'default' => ['adapter' => 'dompdf'],
            'contexts' => [
                'invoice' => ['adapter' => 'dompdf'],
                'coupon' => ['adapter' => 'dompdf'],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.invoice',
            'sylius_pdf_generation.adapter.dompdf',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.coupon',
            'sylius_pdf_generation.adapter.dompdf',
        );
    }

    #[Test]
    public function it_creates_composite_options_processor(): void
    {
        $this->load([
            'default' => ['adapter' => 'dompdf'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.options_processor.composite.dompdf',
            CompositeOptionsProcessor::class,
        );
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
            'sylius_pdf_generation.adapter.default',
            'sylius_pdf_generation.adapter.dompdf',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.invoice',
            'sylius_pdf_generation.adapter.dompdf',
        );
    }

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfGenerationExtension()];
    }
}
