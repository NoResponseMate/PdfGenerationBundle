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
use Sylius\PdfBundle\Factory\GeneratorFactoryInterface;

final class KnpSnappyAdapterTest extends TestCase
{
    private GeneratorInterface&MockObject $snappy;

    private GeneratorFactoryInterface&MockObject $factory;

    protected function setUp(): void
    {
        $this->snappy = $this->createMock(GeneratorInterface::class);
        $this->factory = $this->createMock(GeneratorFactoryInterface::class);
        $this->factory->method('createGenerator')->willReturn($this->snappy);
    }

    #[Test]
    public function it_implements_pdf_generation_adapter_interface(): void
    {
        $adapter = new KnpSnappyAdapter($this->factory, [], 'default');

        self::assertInstanceOf(PdfGenerationAdapterInterface::class, $adapter);
    }

    #[Test]
    public function it_delegates_to_factory_and_generates_pdf(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('resolveOptions')
            ->with([])
            ->willReturn([]);

        $this->snappy
            ->expects(self::once())
            ->method('getOutputFromHtml')
            ->with('<html>Hello</html>', [])
            ->willReturn('PDF FILE');

        $adapter = new KnpSnappyAdapter($this->factory, [], 'default');

        self::assertSame('PDF FILE', $adapter->generate('<html>Hello</html>'));
    }

    #[Test]
    public function it_passes_options_to_factory_for_resolution(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('resolveOptions')
            ->with(['allowed_files' => ['logo.png']])
            ->willReturn(['margin-top' => '10mm', 'allow' => ['/resolved/logo.png']]);

        $this->snappy
            ->expects(self::once())
            ->method('getOutputFromHtml')
            ->with('<html>Hello</html>', ['margin-top' => '10mm', 'allow' => ['/resolved/logo.png']])
            ->willReturn('PDF');

        $adapter = new KnpSnappyAdapter($this->factory, ['allowed_files' => ['logo.png']], 'invoice');

        self::assertSame('PDF', $adapter->generate('<html>Hello</html>'));
    }

    #[Test]
    public function it_passes_context_to_factory(): void
    {
        $this->factory
            ->expects(self::once())
            ->method('createGenerator')
            ->with(['key' => 'value'], 'invoice')
            ->willReturn($this->snappy);

        $this->factory->method('resolveOptions')->willReturn(['key' => 'value']);
        $this->snappy->method('getOutputFromHtml')->willReturn('PDF');

        $adapter = new KnpSnappyAdapter($this->factory, ['key' => 'value'], 'invoice');

        $adapter->generate('<html>Hello</html>');
    }
}
