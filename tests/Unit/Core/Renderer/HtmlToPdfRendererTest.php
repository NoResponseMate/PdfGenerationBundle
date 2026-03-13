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

namespace Tests\Sylius\PdfBundle\Unit\Core\Renderer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRenderer;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class HtmlToPdfRendererTest extends TestCase
{
    #[Test]
    public function it_implements_html_to_pdf_renderer_interface(): void
    {
        $renderer = new HtmlToPdfRenderer(
            new ServiceLocator([]),
        );

        self::assertInstanceOf(HtmlToPdfRendererInterface::class, $renderer);
    }

    #[Test]
    public function it_delegates_to_adapter_for_default_context(): void
    {
        $adapter = $this->createMock(PdfGenerationAdapterInterface::class);
        $adapter
            ->expects(self::once())
            ->method('generate')
            ->with('<html>Hello</html>')
            ->willReturn('PDF FILE');

        $locator = new ServiceLocator([
            'default' => fn () => $adapter,
        ]);

        $renderer = new HtmlToPdfRenderer($locator);

        $result = $renderer->render('<html>Hello</html>');

        self::assertSame('PDF FILE', $result);
    }

    #[Test]
    public function it_delegates_to_correct_adapter_for_named_context(): void
    {
        $adapter = $this->createMock(PdfGenerationAdapterInterface::class);
        $adapter
            ->expects(self::once())
            ->method('generate')
            ->with('<html>Invoice</html>')
            ->willReturn('INVOICE PDF');

        $locator = new ServiceLocator([
            'invoice' => fn () => $adapter,
        ]);

        $renderer = new HtmlToPdfRenderer($locator);

        $result = $renderer->render('<html>Invoice</html>', 'invoice');

        self::assertSame('INVOICE PDF', $result);
    }

    #[Test]
    public function it_throws_for_unknown_context(): void
    {
        $renderer = new HtmlToPdfRenderer(new ServiceLocator([]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown PDF generation context "unknown".');

        $renderer->render('<html>Hello</html>', 'unknown');
    }
}
