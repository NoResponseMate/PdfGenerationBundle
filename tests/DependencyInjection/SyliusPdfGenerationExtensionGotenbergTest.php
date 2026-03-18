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

use Gotenberg\Gotenberg;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sylius\PdfGenerationBundle\Core\Processor\CompositeOptionsProcessor;
use Sylius\PdfGenerationBundle\DependencyInjection\SyliusPdfGenerationExtension;

final class SyliusPdfGenerationExtensionGotenbergTest extends AbstractExtensionTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Gotenberg::class)) {
            self::markTestSkipped('gotenberg/gotenberg-php is not installed.');
        }
    }

    #[Test]
    public function it_registers_gotenberg_adapter_as_default(): void
    {
        $this->load([
            'default' => ['adapter' => 'gotenberg'],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.default',
            'sylius_pdf_generation.adapter.gotenberg',
        );
    }

    #[Test]
    public function it_registers_gotenberg_context_adapter_services(): void
    {
        $this->load([
            'default' => ['adapter' => 'gotenberg'],
            'contexts' => [
                'invoice' => ['adapter' => 'gotenberg'],
                'coupon' => ['adapter' => 'gotenberg'],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.invoice',
            'sylius_pdf_generation.adapter.gotenberg',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.coupon',
            'sylius_pdf_generation.adapter.gotenberg',
        );
    }

    #[Test]
    public function it_creates_composite_options_processor(): void
    {
        $this->load([
            'default' => ['adapter' => 'gotenberg'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf_generation.options_processor.composite.gotenberg',
            CompositeOptionsProcessor::class,
        );
    }

    #[Test]
    public function it_loads_adapter_services_file_only_once_for_multiple_contexts(): void
    {
        $this->load([
            'default' => ['adapter' => 'gotenberg'],
            'contexts' => [
                'invoice' => ['adapter' => 'gotenberg'],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.default',
            'sylius_pdf_generation.adapter.gotenberg',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf_generation.adapter.invoice',
            'sylius_pdf_generation.adapter.gotenberg',
        );
    }

    #[Test]
    public function it_sets_gotenberg_base_url_parameter(): void
    {
        $this->load([
            'gotenberg' => ['base_url' => 'http://gotenberg:3000'],
            'default' => ['adapter' => 'gotenberg'],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf_generation.gotenberg.base_url',
            'http://gotenberg:3000',
        );
    }

    #[Test]
    public function it_uses_default_gotenberg_base_url(): void
    {
        $this->load([
            'default' => ['adapter' => 'gotenberg'],
        ]);

        $this->assertContainerBuilderHasParameter(
            'sylius_pdf_generation.gotenberg.base_url',
            'http://localhost:3000',
        );
    }

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfGenerationExtension()];
    }
}
