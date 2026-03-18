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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Bridge\Dompdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Adapter\Dompdf\DompdfAdapter;
use Sylius\PdfGenerationBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfGenerationBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistryInterface;

final class DompdfAdapterTest extends TestCase
{
    /** @var GeneratorProviderRegistryInterface&MockObject */
    private MockObject $generatorProviderRegistry;

    /** @var OptionsProcessorInterface&MockObject */
    private MockObject $processor;

    /** @var \Dompdf\Dompdf&MockObject */
    private MockObject $dompdf;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            self::markTestSkipped('dompdf/dompdf is not installed.');
        }
    }

    protected function setUp(): void
    {
        $this->dompdf = $this->createMock(\Dompdf\Dompdf::class);
        $this->generatorProviderRegistry = $this->createMock(GeneratorProviderRegistryInterface::class);
        $this->processor = $this->createMock(OptionsProcessorInterface::class);
    }

    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new DompdfAdapter(
            $this->generatorProviderRegistry,
            $this->processor,
            'default',
        );

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_delegates_to_registry_to_get_generator(): void
    {
        $this->generatorProviderRegistry
            ->expects(self::once())
            ->method('get')
            ->with('dompdf', 'invoice')
            ->willReturn($this->dompdf);

        $this->dompdf->expects(self::once())->method('loadHtml')->with('<html><body>Hello</body></html>');
        $this->dompdf->expects(self::once())->method('render');
        $this->dompdf->expects(self::once())->method('output')->willReturn('PDF CONTENT');

        $adapter = new DompdfAdapter(
            $this->generatorProviderRegistry,
            $this->processor,
            'invoice',
        );

        $result = $adapter->generate('<html><body>Hello</body></html>');

        self::assertSame('PDF CONTENT', $result);
    }

    #[Test]
    public function it_calls_options_processor_with_generator_and_context(): void
    {
        $this->generatorProviderRegistry
            ->method('get')
            ->willReturn($this->dompdf);

        $this->dompdf->method('output')->willReturn('PDF CONTENT');

        $this->processor
            ->expects(self::once())
            ->method('process')
            ->with($this->dompdf, 'invoice');

        $adapter = new DompdfAdapter(
            $this->generatorProviderRegistry,
            $this->processor,
            'invoice',
        );

        $adapter->generate('<html><body>Hello</body></html>');
    }
}
