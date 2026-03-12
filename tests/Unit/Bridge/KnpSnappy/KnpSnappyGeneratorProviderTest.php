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

use Knp\Snappy\GeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyGeneratorProvider;
use Sylius\PdfBundle\Core\Provider\GeneratorProviderInterface;

final class KnpSnappyGeneratorProviderTest extends TestCase
{
    #[Test]
    public function it_implements_generator_provider_interface(): void
    {
        $snappy = $this->createMock(GeneratorInterface::class);
        $provider = new KnpSnappyGeneratorProvider($snappy);

        self::assertInstanceOf(GeneratorProviderInterface::class, $provider);
    }

    #[Test]
    public function it_returns_the_snappy_generator(): void
    {
        $snappy = $this->createMock(GeneratorInterface::class);
        $provider = new KnpSnappyGeneratorProvider($snappy);

        $result = $provider->get('default');

        self::assertSame($snappy, $result);
    }

    #[Test]
    public function it_returns_same_instance_regardless_of_context(): void
    {
        $snappy = $this->createMock(GeneratorInterface::class);
        $provider = new KnpSnappyGeneratorProvider($snappy);

        self::assertSame($provider->get('default'), $provider->get('invoice'));
    }
}
