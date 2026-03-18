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

namespace Tests\Sylius\PdfGenerationBundle\Unit\Core\Registry;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Core\Provider\GeneratorProviderInterface;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistry;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class GeneratorProviderRegistryTest extends TestCase
{
    #[Test]
    public function it_implements_registry_interface(): void
    {
        $registry = new GeneratorProviderRegistry(new ServiceLocator([]));

        self::assertInstanceOf(GeneratorProviderRegistryInterface::class, $registry);
    }

    #[Test]
    public function it_returns_generator_from_default_provider(): void
    {
        $generator = new \stdClass();
        $provider = $this->createMock(GeneratorProviderInterface::class);
        $provider->method('get')->with('default')->willReturn($generator);

        $registry = new GeneratorProviderRegistry(new ServiceLocator([
            'dompdf' => static fn () => $provider,
        ]));

        self::assertSame($generator, $registry->get('dompdf', 'default'));
    }

    #[Test]
    public function it_returns_generator_from_context_specific_provider(): void
    {
        $defaultGenerator = new \stdClass();
        $contextGenerator = new \stdClass();

        $defaultProvider = $this->createMock(GeneratorProviderInterface::class);
        $defaultProvider->method('get')->willReturn($defaultGenerator);

        $contextProvider = $this->createMock(GeneratorProviderInterface::class);
        $contextProvider->method('get')->with('invoice')->willReturn($contextGenerator);

        $registry = new GeneratorProviderRegistry(new ServiceLocator([
            'dompdf' => static fn () => $defaultProvider,
            'dompdf.invoice' => static fn () => $contextProvider,
        ]));

        self::assertSame($contextGenerator, $registry->get('dompdf', 'invoice'));
    }

    #[Test]
    public function it_falls_back_to_default_provider_for_unknown_context(): void
    {
        $generator = new \stdClass();
        $provider = $this->createMock(GeneratorProviderInterface::class);
        $provider->method('get')->with('unknown')->willReturn($generator);

        $registry = new GeneratorProviderRegistry(new ServiceLocator([
            'dompdf' => static fn () => $provider,
        ]));

        self::assertSame($generator, $registry->get('dompdf', 'unknown'));
    }

    #[Test]
    public function it_falls_back_to_default_provider_when_context_is_default(): void
    {
        $defaultGenerator = new \stdClass();
        $contextGenerator = new \stdClass();

        $defaultProvider = $this->createMock(GeneratorProviderInterface::class);
        $defaultProvider->method('get')->with('default')->willReturn($defaultGenerator);

        $contextProvider = $this->createMock(GeneratorProviderInterface::class);
        $contextProvider->method('get')->willReturn($contextGenerator);

        $registry = new GeneratorProviderRegistry(new ServiceLocator([
            'dompdf' => static fn () => $defaultProvider,
            'dompdf.invoice' => static fn () => $contextProvider,
        ]));

        self::assertSame($defaultGenerator, $registry->get('dompdf', 'default'));
    }

    #[Test]
    public function it_throws_when_no_provider_is_registered(): void
    {
        $registry = new GeneratorProviderRegistry(new ServiceLocator([]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No generator provider registered for adapter type "dompdf" (context: "default").');

        $registry->get('dompdf', 'default');
    }

    #[Test]
    public function it_throws_when_adapter_type_has_no_providers(): void
    {
        $provider = $this->createMock(GeneratorProviderInterface::class);

        $registry = new GeneratorProviderRegistry(new ServiceLocator([
            'knp_snappy' => static fn () => $provider,
        ]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No generator provider registered for adapter type "dompdf" (context: "default").');

        $registry->get('dompdf', 'default');
    }
}
