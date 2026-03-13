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
use Knp\Snappy\GeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyGeneratorProvider;
use Sylius\PdfBundle\Core\Provider\GeneratorProviderInterface;

final class KnpSnappyGeneratorProviderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!interface_exists(GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }
    }

    #[Test]
    public function it_implements_generator_provider_interface(): void
    {
        $provider = new KnpSnappyGeneratorProvider(fn () => $this->createMock(AbstractGenerator::class));

        self::assertInstanceOf(GeneratorProviderInterface::class, $provider);
    }

    #[Test]
    public function it_returns_the_snappy_generator(): void
    {
        $snappy = $this->createMock(AbstractGenerator::class);
        $provider = new KnpSnappyGeneratorProvider(fn () => $snappy);

        $result = $provider->get('default');

        self::assertSame($snappy, $result);
    }

    #[Test]
    public function it_returns_fresh_instance_for_each_call(): void
    {
        $provider = new KnpSnappyGeneratorProvider(fn () => $this->createMock(AbstractGenerator::class));

        self::assertNotSame($provider->get('default'), $provider->get('invoice'));
    }
}
