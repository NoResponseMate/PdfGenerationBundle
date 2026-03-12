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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyOptionsProcessor;
use Sylius\PdfBundle\Core\Processor\OptionsProcessorInterface;
use Symfony\Component\Config\FileLocatorInterface;

final class KnpSnappyOptionsProcessorTest extends TestCase
{
    private FileLocatorInterface&MockObject $fileLocator;

    protected function setUp(): void
    {
        $this->fileLocator = $this->createMock(FileLocatorInterface::class);
    }

    #[Test]
    public function it_implements_options_processor_interface(): void
    {
        $processor = new KnpSnappyOptionsProcessor($this->fileLocator, []);

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

        $processor = new KnpSnappyOptionsProcessor($this->fileLocator, ['margin-top' => '10mm']);

        $processor->process($generator);
    }

    #[Test]
    public function it_does_not_set_allow_option_when_no_allowed_files_configured(): void
    {
        $this->fileLocator->expects(self::never())->method('locate');

        $generator = $this->createMock(AbstractGenerator::class);
        $generator->expects(self::never())->method('setOption');

        $processor = new KnpSnappyOptionsProcessor($this->fileLocator, []);

        $processor->process($generator);
    }

    #[Test]
    public function it_sets_allow_option_on_generator(): void
    {
        $this->fileLocator
            ->expects(self::once())
            ->method('locate')
            ->with('swans.png')
            ->willReturn('/resolved/swans.png');

        $generator = $this->createMock(AbstractGenerator::class);
        $generator
            ->expects(self::once())
            ->method('setOption')
            ->with('allow', ['/resolved/swans.png']);

        $processor = new KnpSnappyOptionsProcessor($this->fileLocator, [], ['swans.png']);

        $processor->process($generator);
    }

    #[Test]
    public function it_resolves_multiple_allowed_files(): void
    {
        $this->fileLocator
            ->method('locate')
            ->willReturnCallback(fn (string $file): string => '/resolved/' . $file);

        $generator = $this->createMock(AbstractGenerator::class);
        $generator
            ->expects(self::once())
            ->method('setOption')
            ->with('allow', ['/resolved/file1.png', '/resolved/file2.png']);

        $processor = new KnpSnappyOptionsProcessor($this->fileLocator, [], ['file1.png', 'file2.png']);

        $processor->process($generator);
    }
}
