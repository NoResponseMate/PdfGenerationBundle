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

namespace Tests\Sylius\PdfBundle\Unit\Factory;

use Knp\Snappy\GeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Factory\GeneratorFactoryInterface;
use Sylius\PdfBundle\Factory\KnpSnappyGeneratorFactory;
use Symfony\Component\Config\FileLocatorInterface;

final class KnpSnappyGeneratorFactoryTest extends TestCase
{
    private GeneratorInterface&MockObject $snappy;

    private FileLocatorInterface&MockObject $fileLocator;

    protected function setUp(): void
    {
        $this->snappy = $this->createMock(GeneratorInterface::class);
        $this->fileLocator = $this->createMock(FileLocatorInterface::class);
    }

    #[Test]
    public function it_implements_generator_factory_interface(): void
    {
        $factory = new KnpSnappyGeneratorFactory($this->snappy, $this->fileLocator);

        self::assertInstanceOf(GeneratorFactoryInterface::class, $factory);
    }

    #[Test]
    public function it_returns_the_snappy_generator(): void
    {
        $factory = new KnpSnappyGeneratorFactory($this->snappy, $this->fileLocator);

        $result = $factory->createGenerator([], 'default');

        self::assertSame($this->snappy, $result);
    }

    #[Test]
    public function it_resolves_options_with_no_allowed_files(): void
    {
        $this->fileLocator->expects(self::never())->method('locate');

        $factory = new KnpSnappyGeneratorFactory(
            $this->snappy,
            $this->fileLocator,
            ['margin-top' => '10mm'],
        );

        $result = $factory->resolveOptions(['custom_key' => 'value']);

        self::assertSame(['margin-top' => '10mm', 'custom_key' => 'value'], $result);
    }

    #[Test]
    public function it_resolves_allowed_files_and_merges_with_knp_snappy_options(): void
    {
        $this->fileLocator
            ->expects(self::once())
            ->method('locate')
            ->with('swans.png')
            ->willReturn('/resolved/swans.png');

        $factory = new KnpSnappyGeneratorFactory(
            $this->snappy,
            $this->fileLocator,
            ['margin-top' => '10mm'],
        );

        $result = $factory->resolveOptions(['allowed_files' => ['swans.png']]);

        self::assertSame(['margin-top' => '10mm', 'allow' => ['/resolved/swans.png']], $result);
    }

    #[Test]
    public function it_merges_allowed_files_with_existing_allow_option(): void
    {
        $this->fileLocator
            ->method('locate')
            ->with('new.png')
            ->willReturn('/resolved/new.png');

        $factory = new KnpSnappyGeneratorFactory(
            $this->snappy,
            $this->fileLocator,
            ['allow' => '/existing/path.png'],
        );

        $result = $factory->resolveOptions(['allowed_files' => ['new.png']]);

        self::assertSame(['allow' => ['/existing/path.png', '/resolved/new.png']], $result);
    }

    #[Test]
    public function it_removes_allowed_files_key_from_resolved_options(): void
    {
        $this->fileLocator
            ->method('locate')
            ->willReturn('/resolved/file.png');

        $factory = new KnpSnappyGeneratorFactory($this->snappy, $this->fileLocator);

        $result = $factory->resolveOptions(['allowed_files' => ['file.png'], 'other' => 'value']);

        self::assertArrayNotHasKey('allowed_files', $result);
        self::assertSame('value', $result['other']);
    }
}
