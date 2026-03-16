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

final class MultiContextStorageTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Dompdf::class)) {
            self::markTestSkipped('dompdf/dompdf is not installed.');
        }
    }

    private string $defaultDirectory;

    private string $invoiceDirectory;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel(['environment' => 'test_contexts']);

        /** @var string $cacheDir */
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
        $this->defaultDirectory = $cacheDir . '/pdf';
        $this->invoiceDirectory = $cacheDir . '/flysystem_invoices';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ([$this->defaultDirectory, $this->invoiceDirectory . '/invoices'] as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*.pdf');
                if (false !== $files) {
                    array_map('unlink', $files);
                }
                rmdir($dir);
            }
        }
    }

    #[Test]
    public function it_saves_to_default_context(): void
    {
        $manager = $this->getManager();

        $manager->save(new PdfFile('default.pdf', 'default-content'));

        self::assertTrue($manager->has('default.pdf'));
        self::assertFileExists($this->defaultDirectory . '/default.pdf');
    }

    #[Test]
    public function it_saves_to_named_context(): void
    {
        $manager = $this->getManager();

        $manager->save(new PdfFile('invoice.pdf', 'invoice-content'), 'invoice');

        self::assertTrue($manager->has('invoice.pdf', 'invoice'));
        self::assertFileExists($this->invoiceDirectory . '/invoices/invoice.pdf');
    }

    #[Test]
    public function it_isolates_files_between_contexts(): void
    {
        $manager = $this->getManager();

        $manager->save(new PdfFile('document.pdf', 'default-content'));
        $manager->save(new PdfFile('document.pdf', 'invoice-content'), 'invoice');

        $defaultFile = $manager->get('document.pdf');
        $invoiceFile = $manager->get('document.pdf', 'invoice');

        self::assertSame('default-content', $defaultFile->content());
        self::assertSame('invoice-content', $invoiceFile->content());
    }

    #[Test]
    public function it_removes_from_specific_context_without_affecting_other(): void
    {
        $manager = $this->getManager();

        $manager->save(new PdfFile('document.pdf', 'default-content'));
        $manager->save(new PdfFile('document.pdf', 'invoice-content'), 'invoice');

        $manager->remove('document.pdf', 'invoice');

        self::assertTrue($manager->has('document.pdf'));
        self::assertFalse($manager->has('document.pdf', 'invoice'));
    }

    #[Test]
    public function it_throws_exception_for_unknown_context(): void
    {
        $manager = $this->getManager();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown PDF context "nonexistent".');

        $manager->has('file.pdf', 'nonexistent');
    }

    #[Test]
    public function it_uses_default_context_when_none_specified(): void
    {
        $manager = $this->getManager();

        $manager->save(new PdfFile('implicit-default.pdf', 'content'));

        self::assertTrue($manager->has('implicit-default.pdf'));
        self::assertTrue($manager->has('implicit-default.pdf', 'default'));
        self::assertFalse($manager->has('implicit-default.pdf', 'invoice'));
    }

    private function getManager(): PdfFileManagerInterface
    {
        $manager = self::getContainer()->get('test.sylius_pdf.manager');
        self::assertInstanceOf(PdfFileManagerInterface::class, $manager);

        return $manager;
    }
}
