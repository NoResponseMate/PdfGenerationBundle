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

namespace Tests\Sylius\PdfBundle\Unit\Adapter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Adapter\DompdfAdapter;
use Sylius\PdfBundle\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Factory\DompdfGeneratorFactory;
use Sylius\PdfBundle\Factory\GeneratorFactoryInterface;

final class DompdfAdapterTest extends TestCase
{
    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new DompdfAdapter(new DompdfGeneratorFactory(), [], 'default');

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_generates_pdf_from_html(): void
    {
        $adapter = new DompdfAdapter(new DompdfGeneratorFactory(), [], 'default');

        $result = $adapter->generate('<html><body>Hello</body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }

    #[Test]
    public function it_generates_pdf_with_custom_options(): void
    {
        $adapter = new DompdfAdapter(new DompdfGeneratorFactory(), ['defaultPaperSize' => 'a4'], 'default');

        $result = $adapter->generate('<html><body>Hello</body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }

    #[Test]
    public function it_delegates_to_factory_to_create_generator(): void
    {
        $factory = $this->createMock(GeneratorFactoryInterface::class);
        $factory
            ->expects(self::once())
            ->method('createGenerator')
            ->with(['defaultPaperSize' => 'a4'], 'invoice')
            ->willReturn(new \Dompdf\Dompdf(new \Dompdf\Options(['defaultPaperSize' => 'a4'])));

        $adapter = new DompdfAdapter($factory, ['defaultPaperSize' => 'a4'], 'invoice');

        $result = $adapter->generate('<html><body>Hello</body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }
}
