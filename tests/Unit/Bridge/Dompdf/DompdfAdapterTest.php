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

namespace Tests\Sylius\PdfBundle\Unit\Bridge\Dompdf;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\Dompdf\DompdfAdapter;
use Sylius\PdfBundle\Bridge\Dompdf\DompdfGeneratorProvider;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistry;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class DompdfAdapterTest extends TestCase
{
    private function createRegistryWithDompdfProvider(): GeneratorProviderRegistryInterface
    {
        $provider = new DompdfGeneratorProvider();

        return new GeneratorProviderRegistry(new ServiceLocator([
            'dompdf' => static fn () => $provider,
        ]));
    }

    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new DompdfAdapter(
            $this->createRegistryWithDompdfProvider(),
            $this->createMock(OptionsProcessorInterface::class),
            'default',
        );

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_generates_pdf_from_html(): void
    {
        $adapter = new DompdfAdapter(
            $this->createRegistryWithDompdfProvider(),
            $this->createMock(OptionsProcessorInterface::class),
            'default',
        );

        $result = $adapter->generate('<html><body>Hello</body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }

    #[Test]
    public function it_delegates_to_registry_to_get_generator(): void
    {
        $generatorProviderRegistry = $this->createMock(GeneratorProviderRegistryInterface::class);
        $generatorProviderRegistry
            ->expects(self::once())
            ->method('get')
            ->with('dompdf', 'invoice')
            ->willReturn(new \Dompdf\Dompdf());

        $adapter = new DompdfAdapter(
            $generatorProviderRegistry,
            $this->createMock(OptionsProcessorInterface::class),
            'invoice',
        );

        $result = $adapter->generate('<html><body>Hello</body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }

    #[Test]
    public function it_calls_options_processor_with_generator_and_context(): void
    {
        $processor = $this->createMock(OptionsProcessorInterface::class);
        $processor
            ->expects(self::once())
            ->method('process')
            ->with(self::isInstanceOf(\Dompdf\Dompdf::class), 'invoice');

        $adapter = new DompdfAdapter(
            $this->createRegistryWithDompdfProvider(),
            $processor,
            'invoice',
        );

        $adapter->generate('<html><body>Hello</body></html>');
    }
}
