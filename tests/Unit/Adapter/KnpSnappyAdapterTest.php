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

namespace Tests\Sylius\PdfBundle\Unit\Adapter;

use Knp\Snappy\GeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Adapter\KnpSnappyAdapter;
use Sylius\PdfBundle\Adapter\PdfGenerationAdapterInterface;
use Symfony\Component\Config\FileLocatorInterface;

final class KnpSnappyAdapterTest extends TestCase
{
    private GeneratorInterface&MockObject $snappy;

    private FileLocatorInterface&MockObject $fileLocator;

    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new KnpSnappyAdapter(
            $this->createMock(FileLocatorInterface::class),
            $this->createMock(GeneratorInterface::class),
        );

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_generates_pdf_with_resolved_options(): void
    {
        $this->snappy = $this->createMock(GeneratorInterface::class);
        $this->fileLocator = $this->createMock(FileLocatorInterface::class);

        $adapter = new KnpSnappyAdapter(
            $this->fileLocator,
            $this->snappy,
            ['allow' => 'allowed_file_in_knp_snappy_config.png'],
            ['allowed_files' => ['swans.png']],
        );

        $this->fileLocator
            ->expects(self::once())
            ->method('locate')
            ->with('swans.png')
            ->willReturn('located-path/swans.png');

        $this->snappy
            ->expects(self::once())
            ->method('getOutputFromHtml')
            ->with(
                '<html>Hello</html>',
                ['allow' => ['allowed_file_in_knp_snappy_config.png', 'located-path/swans.png']],
            )
            ->willReturn('PDF FILE');

        $result = $adapter->generate('<html>Hello</html>');

        self::assertSame('PDF FILE', $result);
    }

    #[Test]
    public function it_generates_pdf_without_allowed_files_in_options(): void
    {
        $this->snappy = $this->createMock(GeneratorInterface::class);
        $this->fileLocator = $this->createMock(FileLocatorInterface::class);

        $adapter = new KnpSnappyAdapter(
            $this->fileLocator,
            $this->snappy,
            ['margin-top' => '10mm'],
            [],
        );

        $this->fileLocator
            ->expects(self::never())
            ->method('locate');

        $this->snappy
            ->expects(self::once())
            ->method('getOutputFromHtml')
            ->with('<html>Hello</html>', ['margin-top' => '10mm'])
            ->willReturn('PDF FILE');

        $result = $adapter->generate('<html>Hello</html>');

        self::assertSame('PDF FILE', $result);
    }

    #[Test]
    public function it_generates_pdf_with_no_options(): void
    {
        $this->snappy = $this->createMock(GeneratorInterface::class);
        $this->fileLocator = $this->createMock(FileLocatorInterface::class);

        $adapter = new KnpSnappyAdapter(
            $this->fileLocator,
            $this->snappy,
        );

        $this->snappy
            ->expects(self::once())
            ->method('getOutputFromHtml')
            ->with('<html>Hello</html>', [])
            ->willReturn('PDF FILE');

        $result = $adapter->generate('<html>Hello</html>');

        self::assertSame('PDF FILE', $result);
    }
}
