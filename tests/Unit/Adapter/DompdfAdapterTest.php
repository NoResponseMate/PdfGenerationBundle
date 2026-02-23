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

final class DompdfAdapterTest extends TestCase
{
    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new DompdfAdapter();

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_generates_pdf_from_html(): void
    {
        $adapter = new DompdfAdapter();

        $result = $adapter->generate('<html><body>Hello</body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }

    #[Test]
    public function it_generates_pdf_with_custom_options(): void
    {
        $adapter = new DompdfAdapter(['defaultPaperSize' => 'a4']);

        $result = $adapter->generate('<html><body>Hello</body></html>');

        self::assertStringStartsWith('%PDF-', $result);
    }
}
