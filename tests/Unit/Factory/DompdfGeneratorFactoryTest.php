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

use Dompdf\Dompdf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Factory\DompdfGeneratorFactory;
use Sylius\PdfBundle\Factory\GeneratorFactoryInterface;

final class DompdfGeneratorFactoryTest extends TestCase
{
    #[Test]
    public function it_implements_generator_factory_interface(): void
    {
        $factory = new DompdfGeneratorFactory();

        self::assertInstanceOf(GeneratorFactoryInterface::class, $factory);
    }

    #[Test]
    public function it_creates_dompdf_instance(): void
    {
        $factory = new DompdfGeneratorFactory();

        $result = $factory->createGenerator([], 'default');

        self::assertInstanceOf(Dompdf::class, $result);
    }

    #[Test]
    public function it_creates_dompdf_with_options(): void
    {
        $factory = new DompdfGeneratorFactory();

        $result = $factory->createGenerator(['defaultPaperSize' => 'a4'], 'invoice');

        self::assertInstanceOf(Dompdf::class, $result);
        self::assertSame('a4', $result->getOptions()->getDefaultPaperSize());
    }

    #[Test]
    public function it_creates_fresh_instance_per_call(): void
    {
        $factory = new DompdfGeneratorFactory();

        $first = $factory->createGenerator([], 'default');
        $second = $factory->createGenerator([], 'default');

        self::assertNotSame($first, $second);
    }

    #[Test]
    public function it_returns_options_unchanged_from_resolve_options(): void
    {
        $factory = new DompdfGeneratorFactory();

        $options = ['defaultPaperSize' => 'a4', 'isRemoteEnabled' => true];

        self::assertSame($options, $factory->resolveOptions($options));
    }
}
