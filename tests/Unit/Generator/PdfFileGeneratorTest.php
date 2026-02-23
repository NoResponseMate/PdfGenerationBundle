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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Generator;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Generator\PdfFileGenerator;
use Sylius\PdfGenerationBundle\Generator\PdfFileGeneratorInterface;
use Sylius\PdfGenerationBundle\Manager\PdfFileManagerInterface;
use Sylius\PdfGenerationBundle\Model\PdfFile;

final class PdfFileGeneratorTest extends TestCase
{
    #[Test]
    public function it_implements_pdf_file_generator_interface(): void
    {
        $generator = new PdfFileGenerator(
            $this->createMock(PdfFileManagerInterface::class),
        );

        self::assertInstanceOf(PdfFileGeneratorInterface::class, $generator);
    }

    #[Test]
    public function it_creates_a_pdf_file_and_saves_it_through_the_manager(): void
    {
        $manager = $this->createMock(PdfFileManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(fn (PdfFile $file) => $file->filename() === 'invoice.pdf' && $file->content() === 'PDF content'),
                'default',
            );

        $generator = new PdfFileGenerator($manager);
        $result = $generator->generate('invoice.pdf', 'PDF content');

        self::assertSame('invoice.pdf', $result->filename());
        self::assertSame('PDF content', $result->content());
    }

    #[Test]
    public function it_passes_context_to_the_manager(): void
    {
        $manager = $this->createMock(PdfFileManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(fn (PdfFile $file) => $file->filename() === 'report.pdf' && $file->content() === 'PDF data'),
                'invoice',
            );

        $generator = new PdfFileGenerator($manager);
        $result = $generator->generate('report.pdf', 'PDF data', 'invoice');

        self::assertSame('report.pdf', $result->filename());
        self::assertSame('PDF data', $result->content());
    }
}
