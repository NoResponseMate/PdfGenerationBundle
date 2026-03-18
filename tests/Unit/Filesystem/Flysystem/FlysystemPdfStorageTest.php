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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Filesystem\Flysystem;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Core\Filesystem\Storage\PdfStorageInterface;
use Sylius\PdfGenerationBundle\Core\Model\PdfFile;
use Sylius\PdfGenerationBundle\Filesystem\Flysystem\FlysystemPdfStorage;
use Symfony\Component\Filesystem\Filesystem;

final class FlysystemPdfStorageTest extends TestCase
{
    /** @var FilesystemOperator&MockObject */
    private MockObject $filesystem;

    public static function setUpBeforeClass(): void
    {
        if (!interface_exists(FilesystemOperator::class)) {
            self::markTestSkipped('league/flysystem is not installed.');
        }
    }

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(FilesystemOperator::class);
    }

    #[Test]
    public function it_implements_pdf_storage_interface(): void
    {
        self::assertInstanceOf(PdfStorageInterface::class, new FlysystemPdfStorage($this->filesystem, new Filesystem()));
    }

    #[Test]
    public function it_saves_a_file_with_prefix(): void
    {
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem(), 'pdf');

        $this->filesystem->expects(self::once())
            ->method('write')
            ->with('pdf/invoice.pdf', 'PDF content');

        $storage->save(new PdfFile('invoice.pdf', 'PDF content'));
    }

    #[Test]
    public function it_saves_a_file_without_prefix(): void
    {
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem());

        $this->filesystem->expects(self::once())
            ->method('write')
            ->with('invoice.pdf', 'PDF content');

        $storage->save(new PdfFile('invoice.pdf', 'PDF content'));
    }

    #[Test]
    public function it_removes_a_file(): void
    {
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem(), 'pdf');

        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with('pdf/invoice.pdf');

        $storage->remove('invoice.pdf');
    }

    #[Test]
    public function it_checks_if_a_file_exists(): void
    {
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem(), 'pdf');

        $this->filesystem->expects(self::once())
            ->method('fileExists')
            ->with('pdf/invoice.pdf')
            ->willReturn(true);

        self::assertTrue($storage->has('invoice.pdf'));
    }

    #[Test]
    public function it_gets_a_file(): void
    {
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem(), 'pdf');

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
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem(), 'pdf');

        $this->filesystem->expects(self::once())
            ->method('read')
            ->with('pdf/missing.pdf')
            ->willThrowException(UnableToReadFile::fromLocation('pdf/missing.pdf'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PDF file "missing.pdf" not found.');

        $storage->get('missing.pdf');
    }

    #[Test]
    public function it_rejects_filenames_with_directory_separators(): void
    {
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('directory separators are not allowed');

        $storage->has('subdir/file.pdf');
    }

    #[Test]
    public function it_rejects_filenames_with_directory_traversal(): void
    {
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('directory separators are not allowed');

        $storage->save(new PdfFile('../../etc/passwd', 'malicious'));
    }

    #[Test]
    public function it_throws_when_local_cache_directory_is_not_configured(): void
    {
        $storage = new FlysystemPdfStorage($this->filesystem, new Filesystem(), 'pdf');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "local_cache_directory" option must be configured');

        $storage->resolveLocalPath('invoice.pdf');
    }

    #[Test]
    public function it_resolves_local_path_by_fetching_and_caching(): void
    {
        $cacheDir = sys_get_temp_dir() . '/flysystem_test_' . uniqid();

        $storage = new FlysystemPdfStorage(
            $this->filesystem,
            new Filesystem(),
            'pdf',
            $cacheDir,
        );

        $this->filesystem->expects(self::once())
            ->method('read')
            ->with('pdf/invoice.pdf')
            ->willReturn('PDF content');

        $localPath = $storage->resolveLocalPath('invoice.pdf');

        self::assertSame($cacheDir . '/invoice.pdf', $localPath);
        self::assertFileExists($localPath);
        self::assertSame('PDF content', file_get_contents($localPath));

        // Second call should use cache (read not called again due to expects once)
        $localPath2 = $storage->resolveLocalPath('invoice.pdf');
        self::assertSame($localPath, $localPath2);

        (new Filesystem())->remove($cacheDir);
    }

    #[Test]
    public function it_invalidates_local_cache_on_save(): void
    {
        $cacheDir = sys_get_temp_dir() . '/flysystem_test_' . uniqid();
        $localFilesystem = new Filesystem();

        $storage = new FlysystemPdfStorage(
            $this->filesystem,
            $localFilesystem,
            'pdf',
            $cacheDir,
        );

        // Pre-create a cached file
        $localFilesystem->dumpFile($cacheDir . '/invoice.pdf', 'old content');
        self::assertFileExists($cacheDir . '/invoice.pdf');

        $this->filesystem->expects(self::once())
            ->method('write')
            ->with('pdf/invoice.pdf', 'new content');

        $storage->save(new PdfFile('invoice.pdf', 'new content'));

        self::assertFileDoesNotExist($cacheDir . '/invoice.pdf');

        $localFilesystem->remove($cacheDir);
    }

    #[Test]
    public function it_invalidates_local_cache_on_remove(): void
    {
        $cacheDir = sys_get_temp_dir() . '/flysystem_test_' . uniqid();
        $localFilesystem = new Filesystem();

        $storage = new FlysystemPdfStorage(
            $this->filesystem,
            $localFilesystem,
            'pdf',
            $cacheDir,
        );

        // Pre-create a cached file
        $localFilesystem->dumpFile($cacheDir . '/invoice.pdf', 'cached content');
        self::assertFileExists($cacheDir . '/invoice.pdf');

        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with('pdf/invoice.pdf');

        $storage->remove('invoice.pdf');

        self::assertFileDoesNotExist($cacheDir . '/invoice.pdf');

        $localFilesystem->remove($cacheDir);
    }
}
