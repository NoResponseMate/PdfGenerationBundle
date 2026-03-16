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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyAdapter;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;

final class KnpSnappyAdapterTest extends TestCase
{
    /** @var \Knp\Snappy\AbstractGenerator&MockObject */
    private MockObject $snappy;

    /** @var GeneratorProviderRegistryInterface&MockObject */
    private MockObject $generatorProviderRegistry;

    /** @var OptionsProcessorInterface&MockObject */
    private MockObject $processor;

    public static function setUpBeforeClass(): void
    {
        if (!interface_exists(\Knp\Snappy\GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }
    }

    protected function setUp(): void
    {
        $this->snappy = $this->createMock(\Knp\Snappy\AbstractGenerator::class);
        $this->generatorProviderRegistry = $this->createMock(GeneratorProviderRegistryInterface::class);
        $this->generatorProviderRegistry->method('get')->willReturn($this->snappy);
        $this->processor = $this->createMock(OptionsProcessorInterface::class);
    }

    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new KnpSnappyAdapter($this->generatorProviderRegistry, $this->processor, 'default');

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_delegates_to_processor_and_generates_pdf(): void
    {
        $this->processor
            ->expects(self::once())
            ->method('process')
            ->with($this->snappy, 'default');

        $this->snappy
            ->expects(self::once())
            ->method('getOutputFromHtml')
            ->with('<html>Hello</html>')
            ->willReturn('PDF FILE');

        $adapter = new KnpSnappyAdapter($this->generatorProviderRegistry, $this->processor, 'default');

        self::assertSame('PDF FILE', $adapter->generate('<html>Hello</html>'));
    }

    #[Test]
    public function it_passes_context_to_registry_and_processor(): void
    {
        $this->generatorProviderRegistry
            ->expects(self::once())
            ->method('get')
            ->with('knp_snappy', 'invoice')
            ->willReturn($this->snappy);

        $this->processor
            ->expects(self::once())
            ->method('process')
            ->with($this->snappy, 'invoice');

        $this->snappy->method('getOutputFromHtml')->willReturn('PDF');

        $adapter = new KnpSnappyAdapter(
            $this->generatorProviderRegistry,
            $this->processor,
            'invoice',
        );

        $adapter->generate('<html>Hello</html>');
    }
}
