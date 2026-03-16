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

namespace Tests\Sylius\PdfBundle\Functional\Filesystem;

use Dompdf\Dompdf;
use PHPUnit\Framework\Attributes\Test;
use Sylius\PdfBundle\Core\Filesystem\Manager\PdfFileManagerInterface;
use Sylius\PdfBundle\Core\Model\PdfFile;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Sylius\PdfBundle\Application\Kernel;

final class FlysystemStorageTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Dompdf::class)) {
            self::markTestSkipped('dompdf/dompdf is not installed.');
        }
    }

    private string $flysystemDirectory;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel(['environment' => 'test_flysystem']);

        /** @var string $cacheDir */
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
        $this->flysystemDirectory = $cacheDir . '/flysystem';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $pdfDir = $this->flysystemDirectory . '/pdf';
        if (is_dir($pdfDir)) {
            $files = glob($pdfDir . '/*.pdf');
            if (false !== $files) {
                array_map('unlink', $files);
            }
            rmdir($pdfDir);
        }
    }

    #[Test]
    public function it_saves_a_pdf_file(): void
    {
        $manager = $this->getManager();
        $file = new PdfFile('test-save.pdf', 'pdf-content');

        $manager->save($file);

        self::assertFileExists($this->flysystemDirectory . '/pdf/test-save.pdf');
    }

    #[Test]
    public function it_checks_if_a_pdf_file_exists(): void
    {
        $manager = $this->getManager();

        self::assertFalse($manager->has('nonexistent.pdf'));

        $manager->save(new PdfFile('exists.pdf', 'pdf-content'));

        self::assertTrue($manager->has('exists.pdf'));
    }

    #[Test]
    public function it_retrieves_a_saved_pdf_file(): void
    {
        $manager = $this->getManager();
        $manager->save(new PdfFile('retrieve.pdf', 'pdf-content'));

        $retrieved = $manager->get('retrieve.pdf');

        self::assertSame('retrieve.pdf', $retrieved->filename());
        self::assertSame('pdf-content', $retrieved->content());
    }

    #[Test]
    public function it_removes_a_pdf_file(): void
    {
        $manager = $this->getManager();
        $manager->save(new PdfFile('to-remove.pdf', 'pdf-content'));

        self::assertTrue($manager->has('to-remove.pdf'));

        $manager->remove('to-remove.pdf');

        self::assertFalse($manager->has('to-remove.pdf'));
        self::assertFileDoesNotExist($this->flysystemDirectory . '/pdf/to-remove.pdf');
    }

    #[Test]
    public function it_throws_exception_when_getting_nonexistent_file(): void
    {
        $manager = $this->getManager();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PDF file "nonexistent.pdf" not found.');

        $manager->get('nonexistent.pdf');
    }

    #[Test]
    public function it_rejects_filenames_with_directory_separators(): void
    {
        $manager = $this->getManager();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('directory separators are not allowed');

        $manager->save(new PdfFile('../traversal.pdf', 'malicious'));
    }

    #[Test]
    public function it_overwrites_existing_file_on_save(): void
    {
        $manager = $this->getManager();

        $manager->save(new PdfFile('overwrite.pdf', 'original'));
        $manager->save(new PdfFile('overwrite.pdf', 'updated'));

        $retrieved = $manager->get('overwrite.pdf');
        self::assertSame('updated', $retrieved->content());
    }

    #[Test]
    public function it_stores_files_under_the_configured_prefix(): void
    {
        $manager = $this->getManager();
        $manager->save(new PdfFile('prefixed.pdf', 'content'));

        self::assertFileExists($this->flysystemDirectory . '/pdf/prefixed.pdf');
    }

    private function getManager(): PdfFileManagerInterface
    {
        $manager = self::getContainer()->get('test.sylius_pdf.manager');
        self::assertInstanceOf(PdfFileManagerInterface::class, $manager);

        return $manager;
    }
}
