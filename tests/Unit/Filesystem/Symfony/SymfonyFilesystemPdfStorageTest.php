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

namespace Tests\Sylius\PdfBundle\Unit\Filesystem\Symfony;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Core\Filesystem\Storage\PdfStorageInterface;
use Sylius\PdfBundle\Core\Model\PdfFile;
use Sylius\PdfBundle\Filesystem\Symfony\SymfonyFilesystemPdfStorage;
use Symfony\Component\Filesystem\Filesystem;

final class SymfonyFilesystemPdfStorageTest extends TestCase
{
    private string $tempDir;

    private SymfonyFilesystemPdfStorage $storage;

    protected function setUp(): void
    {
        $this->tempDir = __DIR__ . '/sylius_pdf_test_' . uniqid();
        $this->storage = new SymfonyFilesystemPdfStorage(new Filesystem(), $this->tempDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
    }

    #[Test]
    public function it_implements_pdf_storage_interface(): void
    {
        self::assertInstanceOf(PdfStorageInterface::class, $this->storage);
    }

    #[Test]
    public function it_saves_a_pdf_file(): void
    {
        $file = new PdfFile('invoice.pdf', 'PDF content');
        $this->storage->save($file);

        self::assertFileExists($this->tempDir . '/invoice.pdf');
        self::assertSame('PDF content', file_get_contents($this->tempDir . '/invoice.pdf'));
        self::assertSame($this->tempDir . '/invoice.pdf', $file->storagePath());
    }

    #[Test]
    public function it_removes_a_pdf_file(): void
    {
        $this->storage->save(new PdfFile('invoice.pdf', 'PDF content'));
        self::assertFileExists($this->tempDir . '/invoice.pdf');

        $this->storage->remove('invoice.pdf');

        self::assertFileDoesNotExist($this->tempDir . '/invoice.pdf');
    }

    #[Test]
    public function it_checks_if_a_pdf_file_exists(): void
    {
        self::assertFalse($this->storage->has('invoice.pdf'));

        $this->storage->save(new PdfFile('invoice.pdf', 'PDF content'));

        self::assertTrue($this->storage->has('invoice.pdf'));
    }

    #[Test]
    public function it_gets_a_pdf_file(): void
    {
        $this->storage->save(new PdfFile('invoice.pdf', 'PDF content'));

        $file = $this->storage->get('invoice.pdf');

        self::assertSame('invoice.pdf', $file->filename());
        self::assertSame('PDF content', $file->content());
        self::assertSame($this->tempDir . '/invoice.pdf', $file->storagePath());
    }

    #[Test]
    public function it_throws_when_getting_a_non_existent_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PDF file "non_existent.pdf" not found.');

        $this->storage->get('non_existent.pdf');
    }

    #[Test]
    public function it_rejects_filenames_with_directory_traversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid PDF filename "../../etc/passwd": directory separators are not allowed.');

        $this->storage->save(new PdfFile('../../etc/passwd', 'malicious content'));
    }

    #[Test]
    public function it_rejects_filenames_with_directory_separators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('directory separators are not allowed');

        $this->storage->has('subdir/file.pdf');
    }
}
