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

namespace Tests\Sylius\PdfBundle\Unit\Bridge\KnpSnappy;

use Knp\Snappy\AbstractGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyOptionsProcessor;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;

final class KnpSnappyOptionsProcessorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(AbstractGenerator::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }
    }

    #[Test]
    public function it_implements_options_processor_interface(): void
    {
        $processor = new KnpSnappyOptionsProcessor([]);

        self::assertInstanceOf(OptionsProcessorInterface::class, $processor);
    }

    #[Test]
    public function it_sets_knp_snappy_options_on_generator(): void
    {
        $generator = $this->createMock(AbstractGenerator::class);
        $generator
            ->expects(self::once())
            ->method('setOptions')
            ->with(['margin-top' => '10mm']);

        $processor = new KnpSnappyOptionsProcessor(['margin-top' => '10mm']);

        $processor->process($generator);
    }

    #[Test]
    public function it_sets_empty_options_on_generator(): void
    {
        $generator = $this->createMock(AbstractGenerator::class);
        $generator
            ->expects(self::once())
            ->method('setOptions')
            ->with([]);

        $processor = new KnpSnappyOptionsProcessor([]);

        $processor->process($generator);
    }
}
