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

namespace Tests\Sylius\PdfBundle\Unit\Bridge\KnpSnappy;

use Knp\Snappy\GeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyAdapter;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Sylius\PdfBundle\Core\Registry\OptionsProcessorRegistryInterface;

final class KnpSnappyAdapterTest extends TestCase
{
    private GeneratorInterface&MockObject $snappy;

    private GeneratorProviderRegistryInterface&MockObject $generatorProviderRegistry;

    private MockObject&OptionsProcessorRegistryInterface $processorRegistry;

    protected function setUp(): void
    {
        $this->snappy = $this->createMock(GeneratorInterface::class);
        $this->generatorProviderRegistry = $this->createMock(GeneratorProviderRegistryInterface::class);
        $this->generatorProviderRegistry->method('get')->willReturn($this->snappy);
        $this->processorRegistry = $this->createMock(OptionsProcessorRegistryInterface::class);
    }

    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new KnpSnappyAdapter($this->generatorProviderRegistry, $this->processorRegistry, 'default');

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_delegates_to_registries_and_generates_pdf(): void
    {
        $this->processorRegistry
            ->expects(self::once())
            ->method('process')
            ->with($this->snappy, 'knp_snappy', 'default');

        $this->snappy
            ->expects(self::once())
            ->method('getOutputFromHtml')
            ->with('<html>Hello</html>')
            ->willReturn('PDF FILE');

        $adapter = new KnpSnappyAdapter($this->generatorProviderRegistry, $this->processorRegistry, 'default');

        self::assertSame('PDF FILE', $adapter->generate('<html>Hello</html>'));
    }

    #[Test]
    public function it_passes_context_to_registries(): void
    {
        $this->generatorProviderRegistry
            ->expects(self::once())
            ->method('get')
            ->with('knp_snappy', 'invoice')
            ->willReturn($this->snappy);

        $this->processorRegistry
            ->expects(self::once())
            ->method('process')
            ->with($this->snappy, 'knp_snappy', 'invoice');

        $this->snappy->method('getOutputFromHtml')->willReturn('PDF');

        $adapter = new KnpSnappyAdapter(
            $this->generatorProviderRegistry,
            $this->processorRegistry,
            'invoice',
        );

        $adapter->generate('<html>Hello</html>');
    }
}
