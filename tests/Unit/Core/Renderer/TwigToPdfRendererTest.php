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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Core\Renderer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRenderer;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface;
use Twig\Environment;

final class TwigToPdfRendererTest extends TestCase
{
    /** @var Environment&MockObject */
    private MockObject $twig;

    /** @var HtmlToPdfRendererInterface&MockObject */
    private MockObject $htmlToPdfRenderer;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->htmlToPdfRenderer = $this->createMock(HtmlToPdfRendererInterface::class);
    }

    #[Test]
    public function it_implements_twig_to_pdf_renderer_interface(): void
    {
        $renderer = new TwigToPdfRenderer($this->twig, $this->htmlToPdfRenderer);

        self::assertInstanceOf(TwigToPdfRendererInterface::class, $renderer);
    }

    #[Test]
    public function it_renders_twig_template_to_pdf(): void
    {
        $renderer = new TwigToPdfRenderer($this->twig, $this->htmlToPdfRenderer);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('@SyliusInvoicing/invoice.html.twig', ['invoiceNumber' => '001'])
            ->willReturn('<html>Invoice 001</html>');

        $this->htmlToPdfRenderer
            ->expects(self::once())
            ->method('render')
            ->with('<html>Invoice 001</html>', 'default')
            ->willReturn('PDF FILE');

        $result = $renderer->render('@SyliusInvoicing/invoice.html.twig', ['invoiceNumber' => '001']);

        self::assertSame('PDF FILE', $result);
    }

    #[Test]
    public function it_renders_twig_template_to_pdf_with_no_parameters(): void
    {
        $renderer = new TwigToPdfRenderer($this->twig, $this->htmlToPdfRenderer);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('template.html.twig', [])
            ->willReturn('<html>Hello</html>');

        $this->htmlToPdfRenderer
            ->expects(self::once())
            ->method('render')
            ->with('<html>Hello</html>', 'default')
            ->willReturn('PDF FILE');

        $result = $renderer->render('template.html.twig');

        self::assertSame('PDF FILE', $result);
    }

    #[Test]
    public function it_forwards_context_parameter_to_html_renderer(): void
    {
        $renderer = new TwigToPdfRenderer($this->twig, $this->htmlToPdfRenderer);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with('coupon.html.twig', ['code' => 'ABC'])
            ->willReturn('<html>Coupon ABC</html>');

        $this->htmlToPdfRenderer
            ->expects(self::once())
            ->method('render')
            ->with('<html>Coupon ABC</html>', 'coupon')
            ->willReturn('COUPON PDF');

        $result = $renderer->render('coupon.html.twig', ['code' => 'ABC'], 'coupon');

        self::assertSame('COUPON PDF', $result);
    }
}
