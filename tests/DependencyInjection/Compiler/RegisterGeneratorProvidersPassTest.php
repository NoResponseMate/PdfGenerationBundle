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

namespace Tests\Sylius\PdfGenerationBundle\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfGenerationBundle\Core\Provider\GeneratorProviderInterface;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistry;
use Sylius\PdfGenerationBundle\DependencyInjection\Compiler\RegisterGeneratorProvidersPass;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterGeneratorProvidersPassTest extends TestCase
{
    #[Test]
    public function it_does_nothing_when_registry_is_not_defined(): void
    {
        $container = new ContainerBuilder();
        $pass = new RegisterGeneratorProvidersPass();

        $pass->process($container);

        self::assertFalse($container->hasDefinition('sylius_pdf_generation.registry.generator_provider'));
    }

    #[Test]
    public function it_does_nothing_when_no_tagged_services_exist(): void
    {
        $container = new ContainerBuilder();
        $registryDefinition = new Definition(GeneratorProviderRegistry::class);
        $container->setDefinition('sylius_pdf_generation.registry.generator_provider', $registryDefinition);

        $pass = new RegisterGeneratorProvidersPass();
        $pass->process($container);

        self::assertEmpty($registryDefinition->getArguments());
    }

    #[Test]
    public function it_registers_tagged_provider_without_context(): void
    {
        $container = new ContainerBuilder();
        $registryDefinition = new Definition(GeneratorProviderRegistry::class);
        $container->setDefinition('sylius_pdf_generation.registry.generator_provider', $registryDefinition);

        $providerDefinition = new Definition(GeneratorProviderInterface::class);
        $providerDefinition->addTag('sylius_pdf_generation.generator_provider', ['adapter' => 'dompdf']);
        $container->setDefinition('app.my_provider', $providerDefinition);

        $pass = new RegisterGeneratorProvidersPass();
        $pass->process($container);

        /** @var ServiceLocatorArgument $locatorArg */
        $locatorArg = $registryDefinition->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $locatorArg);

        $values = $locatorArg->getValues();
        self::assertArrayHasKey('dompdf', $values);
        self::assertEquals(new Reference('app.my_provider'), $values['dompdf']);
    }

    #[Test]
    public function it_registers_tagged_provider_with_context(): void
    {
        $container = new ContainerBuilder();
        $registryDefinition = new Definition(GeneratorProviderRegistry::class);
        $container->setDefinition('sylius_pdf_generation.registry.generator_provider', $registryDefinition);

        $providerDefinition = new Definition(GeneratorProviderInterface::class);
        $providerDefinition->addTag('sylius_pdf_generation.generator_provider', ['adapter' => 'dompdf', 'context' => 'invoice']);
        $container->setDefinition('app.my_provider', $providerDefinition);

        $pass = new RegisterGeneratorProvidersPass();
        $pass->process($container);

        /** @var ServiceLocatorArgument $locatorArg */
        $locatorArg = $registryDefinition->getArgument(0);
        $values = $locatorArg->getValues();
        self::assertArrayHasKey('dompdf.invoice', $values);
        self::assertEquals(new Reference('app.my_provider'), $values['dompdf.invoice']);
    }

    #[Test]
    public function it_throws_when_adapter_attribute_is_missing(): void
    {
        $container = new ContainerBuilder();
        $registryDefinition = new Definition(GeneratorProviderRegistry::class);
        $container->setDefinition('sylius_pdf_generation.registry.generator_provider', $registryDefinition);

        $providerDefinition = new Definition(GeneratorProviderInterface::class);
        $providerDefinition->addTag('sylius_pdf_generation.generator_provider', []);
        $container->setDefinition('app.my_provider', $providerDefinition);

        $pass = new RegisterGeneratorProvidersPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The service "app.my_provider" tagged with "sylius_pdf_generation.generator_provider" must have an "adapter" attribute.');

        $pass->process($container);
    }

    #[Test]
    public function it_registers_multiple_providers(): void
    {
        $container = new ContainerBuilder();
        $registryDefinition = new Definition(GeneratorProviderRegistry::class);
        $container->setDefinition('sylius_pdf_generation.registry.generator_provider', $registryDefinition);

        $provider1 = new Definition(GeneratorProviderInterface::class);
        $provider1->addTag('sylius_pdf_generation.generator_provider', ['adapter' => 'dompdf']);
        $container->setDefinition('app.provider1', $provider1);

        $provider2 = new Definition(GeneratorProviderInterface::class);
        $provider2->addTag('sylius_pdf_generation.generator_provider', ['adapter' => 'knp_snappy']);
        $container->setDefinition('app.provider2', $provider2);

        $pass = new RegisterGeneratorProvidersPass();
        $pass->process($container);

        /** @var ServiceLocatorArgument $locatorArg */
        $locatorArg = $registryDefinition->getArgument(0);
        $values = $locatorArg->getValues();
        self::assertCount(2, $values);
        self::assertArrayHasKey('dompdf', $values);
        self::assertArrayHasKey('knp_snappy', $values);
    }
}
