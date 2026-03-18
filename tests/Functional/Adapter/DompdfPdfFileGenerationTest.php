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

namespace Tests\Sylius\PdfGenerationBundle\Functional\Bridge;

use Dompdf\Dompdf;
use PHPUnit\Framework\Attributes\Test;
use Sylius\PdfGenerationBundle\Core\Filesystem\Manager\PdfFileManagerInterface;
use Sylius\PdfGenerationBundle\Core\Generator\PdfFileGeneratorInterface;
use Sylius\PdfGenerationBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Sylius\PdfGenerationBundle\Application\Kernel;

final class DompdfPdfFileGenerationTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Dompdf::class)) {
            self::markTestSkipped('dompdf/dompdf is not installed.');
        }
    }

    private string $pdfDirectory;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel(['environment' => 'test_dompdf']);

        /** @var string $cacheDir */
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
        $this->pdfDirectory = $cacheDir . '/pdf';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->pdfDirectory)) {
            $files = glob($this->pdfDirectory . '/*.pdf');
            if (false !== $files) {
                array_map('unlink', $files);
            }
            rmdir($this->pdfDirectory);
        }
    }

    #[Test]
    public function it_generates_a_pdf_file_and_persists_it_to_disk(): void
    {
        $renderer = $this->getRenderer();
        $pdfContent = $renderer->render('<html><body><h1>Invoice #001</h1><p>Amount: $100.00</p></body></html>');

        self::assertStringStartsWith('%PDF-', $pdfContent);

        $generator = $this->getGenerator();
        $pdfFile = $generator->generate('invoice-001.pdf', $pdfContent);

        self::assertSame($this->pdfDirectory . '/invoice-001.pdf', $pdfFile->storagePath());
        self::assertFileExists($pdfFile->storagePath());
    }

    #[Test]
    public function it_reads_back_persisted_pdf_with_identical_content(): void
    {
        $pdfContent = $this->getRenderer()->render('<html><body><h1>Roundtrip test</h1></body></html>');

        $this->getGenerator()->generate('roundtrip.pdf', $pdfContent);

        $manager = $this->getManager();

        self::assertTrue($manager->has('roundtrip.pdf'));

        $retrieved = $manager->get('roundtrip.pdf');
        self::assertSame('roundtrip.pdf', $retrieved->filename());
        self::assertSame($pdfContent, $retrieved->content());
    }

    #[Test]
    public function it_removes_a_persisted_pdf_file(): void
    {
        $pdfContent = $this->getRenderer()->render('<html><body><p>Temporary</p></body></html>');

        $pdfFile = $this->getGenerator()->generate('temporary.pdf', $pdfContent);
        $manager = $this->getManager();

        self::assertTrue($manager->has('temporary.pdf'));

        $manager->remove('temporary.pdf');

        self::assertFalse($manager->has('temporary.pdf'));
        self::assertNotNull($pdfFile->storagePath());
        self::assertFileDoesNotExist($pdfFile->storagePath());
    }

    #[Test]
    public function it_generates_a_pdf_file_from_twig_template_and_persists_it_to_disk(): void
    {
        $twigRenderer = $this->getTwigRenderer();
        $pdfContent = $twigRenderer->render('test/invoice.html.twig', ['number' => '002', 'amount' => '250.00']);

        self::assertStringStartsWith('%PDF-', $pdfContent);

        $generator = $this->getGenerator();
        $pdfFile = $generator->generate('invoice-002.pdf', $pdfContent);

        self::assertSame($this->pdfDirectory . '/invoice-002.pdf', $pdfFile->storagePath());
        self::assertFileExists($pdfFile->storagePath());
    }

    #[Test]
    public function it_reads_back_twig_rendered_pdf_with_identical_content(): void
    {
        $pdfContent = $this->getTwigRenderer()->render('test/invoice.html.twig', ['number' => '003', 'amount' => '75.00']);

        $this->getGenerator()->generate('twig-roundtrip.pdf', $pdfContent);

        $manager = $this->getManager();

        self::assertTrue($manager->has('twig-roundtrip.pdf'));

        $retrieved = $manager->get('twig-roundtrip.pdf');
        self::assertSame('twig-roundtrip.pdf', $retrieved->filename());
        self::assertSame($pdfContent, $retrieved->content());
    }

    #[Test]
    public function it_generates_valid_pdf_structure(): void
    {
        $pdfContent = $this->getRenderer()->render('<html><body><h1>Structure check</h1></body></html>');

        $pdfFile = $this->getGenerator()->generate('structure.pdf', $pdfContent);

        self::assertNotNull($pdfFile->storagePath());
        $fileContent = file_get_contents($pdfFile->storagePath());

        self::assertNotFalse($fileContent);
        self::assertStringStartsWith('%PDF-', $fileContent);
        self::assertStringContainsString('%%EOF', $fileContent);
        self::assertGreaterThan(0, strlen($fileContent));
    }

    private function getTwigRenderer(): TwigToPdfRendererInterface
    {
        $renderer = self::getContainer()->get('test.sylius_pdf_generation.renderer.twig');
        self::assertInstanceOf(TwigToPdfRendererInterface::class, $renderer);

        return $renderer;
    }

    private function getRenderer(): HtmlToPdfRendererInterface
    {
        $renderer = self::getContainer()->get('test.sylius_pdf_generation.renderer.html');
        self::assertInstanceOf(HtmlToPdfRendererInterface::class, $renderer);

        return $renderer;
    }

    private function getGenerator(): PdfFileGeneratorInterface
    {
        $generator = self::getContainer()->get('test.sylius_pdf_generation.generator');
        self::assertInstanceOf(PdfFileGeneratorInterface::class, $generator);

        return $generator;
    }

    private function getManager(): PdfFileManagerInterface
    {
        $manager = self::getContainer()->get('test.sylius_pdf_generation.manager');
        self::assertInstanceOf(PdfFileManagerInterface::class, $manager);

        return $manager;
    }
}
