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

namespace Tests\Sylius\PdfBundle\Unit\Filesystem\Gaufrette;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Core\Filesystem\Storage\PdfStorageInterface;
use Sylius\PdfBundle\Core\Model\PdfFile;
use Sylius\PdfBundle\Filesystem\Gaufrette\GaufrettePdfStorage;

final class GaufrettePdfStorageTest extends TestCase
{
    /** @var \Gaufrette\Filesystem&MockObject */
    private MockObject $filesystem;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(\Gaufrette\Filesystem::class)) {
            self::markTestSkipped('knplabs/knp-gaufrette-bundle is not installed.');
        }
    }

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(\Gaufrette\Filesystem::class);
    }

    #[Test]
    public function it_implements_pdf_storage_interface(): void
    {
        self::assertInstanceOf(PdfStorageInterface::class, new GaufrettePdfStorage($this->filesystem));
    }

    #[Test]
    public function it_saves_a_file_with_prefix(): void
    {
        $storage = new GaufrettePdfStorage($this->filesystem, 'pdf');

        $this->filesystem->expects(self::once())
            ->method('write')
            ->with('pdf/invoice.pdf', 'PDF content', true);

        $storage->save(new PdfFile('invoice.pdf', 'PDF content'));
    }

    #[Test]
    public function it_saves_a_file_without_prefix(): void
    {
        $storage = new GaufrettePdfStorage($this->filesystem);

        $this->filesystem->expects(self::once())
            ->method('write')
            ->with('invoice.pdf', 'PDF content', true);

        $storage->save(new PdfFile('invoice.pdf', 'PDF content'));
    }

    #[Test]
    public function it_removes_a_file(): void
    {
        $storage = new GaufrettePdfStorage($this->filesystem, 'pdf');

        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with('pdf/invoice.pdf');

        $storage->remove('invoice.pdf');
    }

    #[Test]
    public function it_checks_if_a_file_exists(): void
    {
        $storage = new GaufrettePdfStorage($this->filesystem, 'pdf');

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with('pdf/invoice.pdf')
            ->willReturn(true);

        self::assertTrue($storage->has('invoice.pdf'));
    }

    #[Test]
    public function it_gets_a_file(): void
    {
        $storage = new GaufrettePdfStorage($this->filesystem, 'pdf');

        $this->filesystem->expects(self::once())
            ->method('read')
            ->with('pdf/invoice.pdf')
            ->willReturn('PDF content');

        $file = $storage->get('invoice.pdf');

        self::assertSame('invoice.pdf', $file->filename());
        self::assertSame('PDF content', $file->content());
    }

    #[Test]
    public function it_throws_when_file_not_found(): void
    {
        $storage = new GaufrettePdfStorage($this->filesystem, 'pdf');

        $this->filesystem->expects(self::once())
            ->method('read')
            ->with('pdf/missing.pdf')
            ->willThrowException(new \Gaufrette\Exception\FileNotFound('pdf/missing.pdf'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PDF file "missing.pdf" not found.');

        $storage->get('missing.pdf');
    }

    #[Test]
    public function it_rejects_filenames_with_directory_separators(): void
    {
        $storage = new GaufrettePdfStorage($this->filesystem);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('directory separators are not allowed');

        $storage->has('subdir/file.pdf');
    }
}
