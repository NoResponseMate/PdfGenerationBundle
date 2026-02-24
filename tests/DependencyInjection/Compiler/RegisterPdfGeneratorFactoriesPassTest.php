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

namespace Tests\Sylius\PdfBundle\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\DependencyInjection\Compiler\RegisterPdfGeneratorFactoriesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class RegisterPdfGeneratorFactoriesPassTest extends TestCase
{
    #[Test]
    public function it_does_nothing_when_deferred_parameter_is_absent(): void
    {
        $container = new ContainerBuilder();

        $pass = new RegisterPdfGeneratorFactoriesPass();
        $pass->process($container);

        self::assertFalse($container->hasAlias('sylius_pdf.factory.invoice'));
    }

    #[Test]
    public function it_resolves_deferred_factory_to_tagged_service(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('sylius_pdf.deferred_factory_contexts', [
            'invoice' => 'my_custom',
        ]);

        $customFactoryDefinition = new Definition(\stdClass::class);
        $customFactoryDefinition->addTag('sylius_pdf.factory', ['key' => 'my_custom']);
        $container->setDefinition('app.factory.custom', $customFactoryDefinition);

        $pass = new RegisterPdfGeneratorFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasAlias('sylius_pdf.factory.invoice'));
        self::assertSame('app.factory.custom', (string) $container->getAlias('sylius_pdf.factory.invoice'));
    }

    #[Test]
    public function it_resolves_multiple_deferred_factories(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('sylius_pdf.deferred_factory_contexts', [
            'default' => 'custom_a',
            'invoice' => 'custom_b',
        ]);

        $factoryA = new Definition(\stdClass::class);
        $factoryA->addTag('sylius_pdf.factory', ['key' => 'custom_a']);
        $container->setDefinition('app.factory.a', $factoryA);

        $factoryB = new Definition(\stdClass::class);
        $factoryB->addTag('sylius_pdf.factory', ['key' => 'custom_b']);
        $container->setDefinition('app.factory.b', $factoryB);

        $pass = new RegisterPdfGeneratorFactoriesPass();
        $pass->process($container);

        self::assertSame('app.factory.a', (string) $container->getAlias('sylius_pdf.factory.default'));
        self::assertSame('app.factory.b', (string) $container->getAlias('sylius_pdf.factory.invoice'));
    }

    #[Test]
    public function it_throws_for_unknown_factory_key(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('sylius_pdf.deferred_factory_contexts', [
            'invoice' => 'nonexistent_factory',
        ]);

        $pass = new RegisterPdfGeneratorFactoriesPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The PDF generator factory "nonexistent_factory" used in context "invoice" is not registered.');

        $pass->process($container);
    }

    #[Test]
    public function it_throws_for_tag_missing_key_attribute(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('sylius_pdf.deferred_factory_contexts', [
            'invoice' => 'my_custom',
        ]);

        $customFactoryDefinition = new Definition(\stdClass::class);
        $customFactoryDefinition->addTag('sylius_pdf.factory');
        $container->setDefinition('app.factory.custom', $customFactoryDefinition);

        $pass = new RegisterPdfGeneratorFactoriesPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The service "app.factory.custom" tagged with "sylius_pdf.factory" must have a "key" attribute.');

        $pass->process($container);
    }

    #[Test]
    public function it_throws_for_duplicate_factory_keys(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('sylius_pdf.deferred_factory_contexts', [
            'invoice' => 'my_custom',
        ]);

        $firstDefinition = new Definition(\stdClass::class);
        $firstDefinition->addTag('sylius_pdf.factory', ['key' => 'my_custom']);
        $container->setDefinition('app.factory.first', $firstDefinition);

        $secondDefinition = new Definition(\stdClass::class);
        $secondDefinition->addTag('sylius_pdf.factory', ['key' => 'my_custom']);
        $container->setDefinition('app.factory.second', $secondDefinition);

        $pass = new RegisterPdfGeneratorFactoriesPass();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The PDF generator factory key "my_custom" is already registered');

        $pass->process($container);
    }

    #[Test]
    public function it_cleans_up_the_temporary_parameter(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('sylius_pdf.deferred_factory_contexts', [
            'invoice' => 'my_custom',
        ]);

        $customFactoryDefinition = new Definition(\stdClass::class);
        $customFactoryDefinition->addTag('sylius_pdf.factory', ['key' => 'my_custom']);
        $container->setDefinition('app.factory.custom', $customFactoryDefinition);

        $pass = new RegisterPdfGeneratorFactoriesPass();
        $pass->process($container);

        self::assertFalse($container->hasParameter('sylius_pdf.deferred_factory_contexts'));
    }
}
