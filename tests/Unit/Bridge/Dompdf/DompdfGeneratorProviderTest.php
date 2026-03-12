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

namespace Tests\Sylius\PdfBundle\Unit\Bridge\Dompdf;

use Dompdf\Dompdf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\Dompdf\DompdfGeneratorProvider;
use Sylius\PdfBundle\Core\Provider\GeneratorProviderInterface;

final class DompdfGeneratorProviderTest extends TestCase
{
    #[Test]
    public function it_implements_generator_provider_interface(): void
    {
        $provider = new DompdfGeneratorProvider();

        self::assertInstanceOf(GeneratorProviderInterface::class, $provider);
    }

    #[Test]
    public function it_creates_dompdf_instance(): void
    {
        $provider = new DompdfGeneratorProvider();

        $result = $provider->get('default');

        self::assertInstanceOf(Dompdf::class, $result);
    }

    #[Test]
    public function it_creates_fresh_instance_per_call(): void
    {
        $provider = new DompdfGeneratorProvider();

        $first = $provider->get('default');
        $second = $provider->get('default');

        self::assertNotSame($first, $second);
    }
}
