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

namespace Tests\Sylius\PdfBundle\Unit\Core\Filesystem\Manager;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Core\Filesystem\Manager\PdfFileManager;
use Sylius\PdfBundle\Core\Filesystem\Manager\PdfFileManagerInterface;
use Sylius\PdfBundle\Core\Filesystem\Storage\PdfStorageInterface;
use Sylius\PdfBundle\Core\Model\PdfFile;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class PdfFileManagerTest extends TestCase
{
    /** @var PdfStorageInterface&MockObject */
    private MockObject $defaultStorage;

    /** @var PdfStorageInterface&MockObject */
    private MockObject $invoiceStorage;

    private PdfFileManager $manager;

    protected function setUp(): void
    {
        $this->defaultStorage = $this->createMock(PdfStorageInterface::class);
        $this->invoiceStorage = $this->createMock(PdfStorageInterface::class);

        $this->manager = new PdfFileManager(new ServiceLocator([
            'default' => fn () => $this->defaultStorage,
            'invoice' => fn () => $this->invoiceStorage,
        ]));
    }

    #[Test]
    public function it_implements_pdf_file_manager_interface(): void
    {
        self::assertInstanceOf(PdfFileManagerInterface::class, $this->manager);
    }

    #[Test]
    public function it_delegates_save_to_default_storage(): void
    {
        $file = new PdfFile('test.pdf', 'content');

        $this->defaultStorage->expects(self::once())
            ->method('save')
            ->with($file);

        $this->manager->save($file);
    }

    #[Test]
    public function it_delegates_save_to_context_storage(): void
    {
        $file = new PdfFile('test.pdf', 'content');

        $this->invoiceStorage->expects(self::once())
            ->method('save')
            ->with($file);

        $this->manager->save($file, 'invoice');
    }

    #[Test]
    public function it_delegates_remove_to_correct_storage(): void
    {
        $this->invoiceStorage->expects(self::once())
            ->method('remove')
            ->with('test.pdf');

        $this->manager->remove('test.pdf', 'invoice');
    }

    #[Test]
    public function it_delegates_has_to_correct_storage(): void
    {
        $this->defaultStorage->expects(self::once())
            ->method('has')
            ->with('test.pdf')
            ->willReturn(true);

        self::assertTrue($this->manager->has('test.pdf'));
    }

    #[Test]
    public function it_delegates_get_to_correct_storage(): void
    {
        $expected = new PdfFile('test.pdf', 'content');

        $this->invoiceStorage->expects(self::once())
            ->method('get')
            ->with('test.pdf')
            ->willReturn($expected);

        self::assertSame($expected, $this->manager->get('test.pdf', 'invoice'));
    }

    #[Test]
    public function it_throws_for_unknown_context(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown PDF context "unknown".');

        $this->manager->save(new PdfFile('test.pdf', 'content'), 'unknown');
    }
}
