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

namespace Tests\Sylius\PdfBundle\Unit\Bridge\Dompdf;

use Dompdf\Dompdf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\Dompdf\DompdfOptionsProcessor;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;

final class DompdfOptionsProcessorTest extends TestCase
{
    #[Test]
    public function it_implements_options_processor_interface(): void
    {
        $processor = new DompdfOptionsProcessor();

        self::assertInstanceOf(OptionsProcessorInterface::class, $processor);
    }

    #[Test]
    public function it_does_nothing_when_no_options_configured(): void
    {
        $processor = new DompdfOptionsProcessor();
        $dompdf = new Dompdf();

        $defaultPaperSize = $dompdf->getOptions()->getDefaultPaperSize();

        $processor->process($dompdf);

        self::assertSame($defaultPaperSize, $dompdf->getOptions()->getDefaultPaperSize());
    }

    #[Test]
    public function it_applies_constructor_options_to_dompdf_instance(): void
    {
        $processor = new DompdfOptionsProcessor(['defaultPaperSize' => 'a4']);
        $dompdf = new Dompdf();

        $processor->process($dompdf);

        self::assertSame('a4', $dompdf->getOptions()->getDefaultPaperSize());
    }
}
