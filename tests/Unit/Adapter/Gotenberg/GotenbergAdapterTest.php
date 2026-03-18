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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Bridge\Gotenberg;

use Gotenberg\Gotenberg;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Adapter\Gotenberg\GotenbergAdapter;
use Sylius\PdfGenerationBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfGenerationBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistryInterface;

final class GotenbergAdapterTest extends TestCase
{
    /** @var GeneratorProviderRegistryInterface&MockObject */
    private MockObject $generatorProviderRegistry;

    /** @var OptionsProcessorInterface&MockObject */
    private MockObject $processor;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Gotenberg::class)) {
            self::markTestSkipped('gotenberg/gotenberg-php is not installed.');
        }
    }

    protected function setUp(): void
    {
        $this->generatorProviderRegistry = $this->createMock(GeneratorProviderRegistryInterface::class);
        $this->processor = $this->createMock(OptionsProcessorInterface::class);
    }

    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new GotenbergAdapter(
            $this->generatorProviderRegistry,
            $this->processor,
            'default',
        );

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_delegates_to_registry_to_get_generator(): void
    {
        $builder = Gotenberg::chromium('http://localhost:3000')->pdf();

        $this->generatorProviderRegistry
            ->expects(self::once())
            ->method('get')
            ->with('gotenberg', 'invoice')
            ->willReturn($builder);

        $adapter = new GotenbergAdapter(
            $this->generatorProviderRegistry,
            $this->processor,
            'invoice',
        );

        try {
            $adapter->generate('<html><body>Hello</body></html>');
        } catch (\Exception) {
            // Gotenberg::send() will fail without a running server, but we can verify the registry was called
        }
    }

    #[Test]
    public function it_calls_options_processor_with_generator_and_context(): void
    {
        $builder = Gotenberg::chromium('http://localhost:3000')->pdf();

        $this->generatorProviderRegistry
            ->method('get')
            ->willReturn($builder);

        $this->processor
            ->expects(self::once())
            ->method('process')
            ->with($builder, 'invoice');

        $adapter = new GotenbergAdapter(
            $this->generatorProviderRegistry,
            $this->processor,
            'invoice',
        );

        try {
            $adapter->generate('<html><body>Hello</body></html>');
        } catch (\Exception) {
            // Gotenberg::send() will fail without a running server, but we can verify the processor was called
        }
    }
}
