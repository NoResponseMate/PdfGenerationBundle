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

namespace Tests\Sylius\PdfBundle\Unit\Bridge\Gotenberg;

use Gotenberg\Gotenberg;
use Gotenberg\Modules\ChromiumPdf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\Bridge\Gotenberg\GotenbergGeneratorProvider;
use Sylius\PdfBundle\Core\Provider\GeneratorProviderInterface;

final class GotenbergGeneratorProviderTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Gotenberg::class)) {
            self::markTestSkipped('gotenberg/gotenberg-php is not installed.');
        }
    }

    #[Test]
    public function it_implements_generator_provider_interface(): void
    {
        $provider = new GotenbergGeneratorProvider('http://localhost:3000');

        self::assertInstanceOf(GeneratorProviderInterface::class, $provider);
    }

    #[Test]
    public function it_creates_chromium_pdf_instance(): void
    {
        $provider = new GotenbergGeneratorProvider('http://localhost:3000');

        $result = $provider->get('default');

        self::assertInstanceOf(ChromiumPdf::class, $result);
    }

    #[Test]
    public function it_creates_fresh_instance_per_call(): void
    {
        $provider = new GotenbergGeneratorProvider('http://localhost:3000');

        $first = $provider->get('default');
        $second = $provider->get('default');

        self::assertNotSame($first, $second);
    }
}
