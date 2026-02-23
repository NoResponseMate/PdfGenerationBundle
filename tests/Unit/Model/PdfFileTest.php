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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Model;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Model\PdfFile;

final class PdfFileTest extends TestCase
{
    #[Test]
    public function it_returns_the_filename(): void
    {
        $file = new PdfFile('invoice_001.pdf', 'PDF content');

        self::assertSame('invoice_001.pdf', $file->filename());
    }

    #[Test]
    public function it_returns_the_content(): void
    {
        $file = new PdfFile('invoice_001.pdf', 'PDF content');

        self::assertSame('PDF content', $file->content());
    }
}
