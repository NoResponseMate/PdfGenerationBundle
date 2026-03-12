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

namespace Tests\Sylius\PdfBundle\Unit\Core\Processor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Core\Processor\CompositeOptionsProcessor;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;

final class CompositeOptionsProcessorTest extends TestCase
{
    #[Test]
    public function it_implements_options_processor_interface(): void
    {
        $composite = new CompositeOptionsProcessor();

        self::assertInstanceOf(OptionsProcessorInterface::class, $composite);
    }

    #[Test]
    public function it_does_nothing_when_empty(): void
    {
        $composite = new CompositeOptionsProcessor();
        $generator = new \stdClass();

        $composite->process($generator);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_runs_default_processors_for_default_context(): void
    {
        $generator = new \stdClass();

        $processor = $this->createMock(OptionsProcessorInterface::class);
        $processor->expects(self::once())->method('process')->with(self::identicalTo($generator), 'default');

        $composite = new CompositeOptionsProcessor([
            'default' => [$processor],
        ]);

        $composite->process($generator, 'default');
    }

    #[Test]
    public function it_runs_default_and_context_specific_processors(): void
    {
        $generator = new \stdClass();
        $order = [];

        $defaultProcessor = $this->createMock(OptionsProcessorInterface::class);
        $defaultProcessor->method('process')->willReturnCallback(function () use (&$order): void { $order[] = 'default'; });

        $invoiceProcessor = $this->createMock(OptionsProcessorInterface::class);
        $invoiceProcessor->method('process')->willReturnCallback(function () use (&$order): void { $order[] = 'invoice'; });

        $composite = new CompositeOptionsProcessor([
            'default' => [$defaultProcessor],
            'invoice' => [$invoiceProcessor],
        ]);

        $composite->process($generator, 'invoice');

        self::assertSame(['default', 'invoice'], $order);
    }

    #[Test]
    public function it_does_not_run_context_specific_processors_for_default_context(): void
    {
        $generator = new \stdClass();

        $defaultProcessor = $this->createMock(OptionsProcessorInterface::class);
        $defaultProcessor->expects(self::once())->method('process');

        $invoiceProcessor = $this->createMock(OptionsProcessorInterface::class);
        $invoiceProcessor->expects(self::never())->method('process');

        $composite = new CompositeOptionsProcessor([
            'default' => [$defaultProcessor],
            'invoice' => [$invoiceProcessor],
        ]);

        $composite->process($generator, 'default');
    }

    #[Test]
    public function it_handles_context_with_no_default_processors(): void
    {
        $generator = new \stdClass();

        $invoiceProcessor = $this->createMock(OptionsProcessorInterface::class);
        $invoiceProcessor->expects(self::once())->method('process')->with(self::identicalTo($generator), 'invoice');

        $composite = new CompositeOptionsProcessor([
            'invoice' => [$invoiceProcessor],
        ]);

        $composite->process($generator, 'invoice');
    }

    #[Test]
    public function it_handles_unknown_context_by_running_only_defaults(): void
    {
        $generator = new \stdClass();

        $defaultProcessor = $this->createMock(OptionsProcessorInterface::class);
        $defaultProcessor->expects(self::once())->method('process')->with(self::identicalTo($generator), 'unknown');

        $composite = new CompositeOptionsProcessor([
            'default' => [$defaultProcessor],
        ]);

        $composite->process($generator, 'unknown');
    }
}
