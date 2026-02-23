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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Manager;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Manager\FilesystemPdfFileManager;
use Sylius\PdfGenerationBundle\Manager\PdfFileManagerInterface;
use Sylius\PdfGenerationBundle\Model\PdfFile;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemPdfFileManagerTest extends TestCase
{
    private string $tempDir;

    private string $invoiceTempDir;

    private FilesystemPdfFileManager $manager;

    protected function setUp(): void
    {
        $this->tempDir = __DIR__ . '/sylius_pdf_test_' . uniqid();
        $this->invoiceTempDir = __DIR__ . '/sylius_pdf_test_invoice_' . uniqid();
        $this->manager = new FilesystemPdfFileManager([
            'default' => $this->tempDir,
            'invoice' => $this->invoiceTempDir,
        ], new Filesystem());
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
        $filesystem->remove($this->invoiceTempDir);
    }

    #[Test]
    public function it_implements_pdf_file_manager_interface(): void
    {
        self::assertInstanceOf(PdfFileManagerInterface::class, $this->manager);
    }

    #[Test]
    public function it_saves_a_pdf_file(): void
    {
        $file = new PdfFile('invoice.pdf', 'PDF content');
        $this->manager->save($file);

        self::assertFileExists($this->tempDir . '/invoice.pdf');
        self::assertSame('PDF content', file_get_contents($this->tempDir . '/invoice.pdf'));
        self::assertSame($this->tempDir . '/invoice.pdf', $file->fullPath());
    }

    #[Test]
    public function it_removes_a_pdf_file(): void
    {
        $this->manager->save(new PdfFile('invoice.pdf', 'PDF content'));
        self::assertFileExists($this->tempDir . '/invoice.pdf');

        $this->manager->remove('invoice.pdf');

        self::assertFileDoesNotExist($this->tempDir . '/invoice.pdf');
    }

    #[Test]
    public function it_checks_if_a_pdf_file_exists(): void
    {
        self::assertFalse($this->manager->has('invoice.pdf'));

        $this->manager->save(new PdfFile('invoice.pdf', 'PDF content'));

        self::assertTrue($this->manager->has('invoice.pdf'));
    }

    #[Test]
    public function it_gets_a_pdf_file(): void
    {
        $this->manager->save(new PdfFile('invoice.pdf', 'PDF content'));

        $file = $this->manager->get('invoice.pdf');

        self::assertSame('invoice.pdf', $file->filename());
        self::assertSame('PDF content', $file->content());
        self::assertSame($this->tempDir . '/invoice.pdf', $file->fullPath());
    }

    #[Test]
    public function it_throws_an_exception_when_getting_a_non_existent_file(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PDF file "non_existent.pdf" not found.');

        $this->manager->get('non_existent.pdf');
    }

    #[Test]
    public function it_saves_a_pdf_file_to_a_specific_context_directory(): void
    {
        $this->manager->save(new PdfFile('report.pdf', 'Invoice PDF'), 'invoice');

        self::assertFileExists($this->invoiceTempDir . '/report.pdf');
        self::assertSame('Invoice PDF', file_get_contents($this->invoiceTempDir . '/report.pdf'));
        self::assertFileDoesNotExist($this->tempDir . '/report.pdf');
    }

    #[Test]
    public function it_gets_a_pdf_file_from_a_specific_context(): void
    {
        $this->manager->save(new PdfFile('report.pdf', 'Invoice PDF'), 'invoice');

        $file = $this->manager->get('report.pdf', 'invoice');

        self::assertSame('report.pdf', $file->filename());
        self::assertSame('Invoice PDF', $file->content());
    }

    #[Test]
    public function it_checks_if_a_pdf_file_exists_in_a_specific_context(): void
    {
        self::assertFalse($this->manager->has('report.pdf', 'invoice'));

        $this->manager->save(new PdfFile('report.pdf', 'Invoice PDF'), 'invoice');

        self::assertTrue($this->manager->has('report.pdf', 'invoice'));
        self::assertFalse($this->manager->has('report.pdf'));
    }

    #[Test]
    public function it_removes_a_pdf_file_from_a_specific_context(): void
    {
        $this->manager->save(new PdfFile('report.pdf', 'Invoice PDF'), 'invoice');
        self::assertFileExists($this->invoiceTempDir . '/report.pdf');

        $this->manager->remove('report.pdf', 'invoice');

        self::assertFileDoesNotExist($this->invoiceTempDir . '/report.pdf');
    }

    #[Test]
    public function it_throws_an_exception_for_unknown_context(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown PDF context "unknown".');

        $this->manager->save(new PdfFile('report.pdf', 'PDF content'), 'unknown');
    }
}
