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
use Sylius\PdfBundle\Core\Processor\CompositeOptionsProcessor;
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
    public function it_registers_knp_snappy_adapter_as_default(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.default',
            'sylius_pdf.adapter.knp_snappy',
        );
    }

    #[Test]
    public function it_does_not_register_default_adapter_when_not_configured(): void
    {
        $this->load();

        self::assertFalse($this->container->hasDefinition('sylius_pdf.adapter.default'));
    }

    #[Test]
    public function it_registers_knp_snappy_context_adapter_services(): void
    {
        $this->load([
            'contexts' => [
                'invoice' => ['adapter' => 'knp_snappy'],
                'coupon' => ['adapter' => 'knp_snappy'],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.invoice',
            'sylius_pdf.adapter.knp_snappy',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.coupon',
            'sylius_pdf.adapter.knp_snappy',
        );
    }

    #[Test]
    public function it_creates_composite_options_processor(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
        ]);

        $this->assertContainerBuilderHasService(
            'sylius_pdf.options_processor.composite.knp_snappy',
            CompositeOptionsProcessor::class,
        );
    }

    #[Test]
    public function it_loads_adapter_services_file_only_once_for_multiple_contexts(): void
    {
        $this->load([
            'default' => ['adapter' => 'knp_snappy'],
            'contexts' => [
                'invoice' => ['adapter' => 'knp_snappy'],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.default',
            'sylius_pdf.adapter.knp_snappy',
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'sylius_pdf.adapter.invoice',
            'sylius_pdf.adapter.knp_snappy',
        );
    }

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfExtension()];
    }
}
