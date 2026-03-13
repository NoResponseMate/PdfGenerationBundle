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
use Sylius\PdfBundle\DependencyInjection\SyliusPdfExtension;

final class SyliusPdfExtensionTest extends AbstractExtensionTestCase
{
    #[Test]
    public function it_defers_unknown_adapter_to_compiler_pass(): void
    {
        $this->load([
            'default' => ['adapter' => 'my_custom'],
            'contexts' => [
                'invoice' => ['adapter' => 'my_custom'],
            ],
        ]);

        self::assertTrue($this->container->hasParameter('sylius_pdf.deferred_adapter_contexts'));

        /** @var array<string, string> $deferred */
        $deferred = $this->container->getParameter('sylius_pdf.deferred_adapter_contexts');
        self::assertSame(['default' => 'my_custom', 'invoice' => 'my_custom'], $deferred);
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
        self::assertFalse($this->container->hasDefinition('sylius_pdf.options_processor.dompdf'));
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

    protected function getContainerExtensions(): array
    {
        return [new SyliusPdfExtension()];
    }
}
