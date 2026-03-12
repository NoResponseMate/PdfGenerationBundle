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

namespace Tests\Sylius\PdfBundle\Unit\Core\Registry;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;
use Sylius\PdfBundle\Core\Registry\OptionsProcessorRegistry;
use Sylius\PdfBundle\Core\Registry\OptionsProcessorRegistryInterface;

final class OptionsProcessorRegistryTest extends TestCase
{
    #[Test]
    public function it_implements_registry_interface(): void
    {
        $registry = new OptionsProcessorRegistry();

        self::assertInstanceOf(OptionsProcessorRegistryInterface::class, $registry);
    }

    #[Test]
    public function it_does_nothing_when_no_processors_registered(): void
    {
        $registry = new OptionsProcessorRegistry();
        $generator = new \stdClass();

        $registry->process($generator, 'knp_snappy', 'default');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_runs_all_registered_processors(): void
    {
        $registry = new OptionsProcessorRegistry();
        $generator = new \stdClass();

        $processor1 = $this->createMock(OptionsProcessorInterface::class);
        $processor1->expects(self::once())->method('process')->with(self::identicalTo($generator));

        $processor2 = $this->createMock(OptionsProcessorInterface::class);
        $processor2->expects(self::once())->method('process')->with(self::identicalTo($generator));

        $registry->registerProcessors('knp_snappy', 'default', [$processor1, $processor2]);

        $registry->process($generator, 'knp_snappy', 'default');
    }

    #[Test]
    public function it_includes_default_and_context_specific_processors(): void
    {
        $registry = new OptionsProcessorRegistry();
        $generator = new \stdClass();

        $defaultProcessor = $this->createMock(OptionsProcessorInterface::class);
        $defaultProcessor->expects(self::once())->method('process');

        $contextProcessor = $this->createMock(OptionsProcessorInterface::class);
        $contextProcessor->expects(self::once())->method('process');

        $registry->registerProcessors('knp_snappy', 'default', [$defaultProcessor]);
        $registry->registerProcessors('knp_snappy', 'invoice', [$contextProcessor]);

        $registry->process($generator, 'knp_snappy', 'invoice');
    }

    #[Test]
    public function it_only_includes_default_processors_for_default_context(): void
    {
        $registry = new OptionsProcessorRegistry();
        $generator = new \stdClass();

        $defaultProcessor = $this->createMock(OptionsProcessorInterface::class);
        $defaultProcessor->expects(self::once())->method('process');

        $contextProcessor = $this->createMock(OptionsProcessorInterface::class);
        $contextProcessor->expects(self::never())->method('process');

        $registry->registerProcessors('knp_snappy', 'default', [$defaultProcessor]);
        $registry->registerProcessors('knp_snappy', 'invoice', [$contextProcessor]);

        $registry->process($generator, 'knp_snappy', 'default');
    }

    #[Test]
    public function it_does_not_mix_processors_from_different_adapter_types(): void
    {
        $registry = new OptionsProcessorRegistry();
        $generator = new \stdClass();

        $knpProcessor = $this->createMock(OptionsProcessorInterface::class);
        $knpProcessor->expects(self::never())->method('process');

        $registry->registerProcessors('knp_snappy', 'default', [$knpProcessor]);

        $registry->process($generator, 'dompdf', 'default');
    }

    #[Test]
    public function it_passes_generator_to_each_processor(): void
    {
        $registry = new OptionsProcessorRegistry();
        $generator = new \stdClass();

        $processor = $this->createMock(OptionsProcessorInterface::class);
        $processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($generator));

        $registry->registerProcessors('dompdf', 'default', [$processor]);

        $registry->process($generator, 'dompdf', 'default');
    }
}
