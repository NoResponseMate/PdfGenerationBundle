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

use Knp\Snappy\GeneratorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\PdfBundle\DependencyInjection\Compiler\RegisterKnpSnappyPrototypePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class RegisterKnpSnappyPrototypePassTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!interface_exists(GeneratorInterface::class)) {
            self::markTestSkipped('knplabs/knp-snappy-bundle is not installed.');
        }
    }

    #[Test]
    public function it_does_nothing_when_knp_snappy_pdf_is_not_defined(): void
    {
        $container = new ContainerBuilder();
        $pass = new RegisterKnpSnappyPrototypePass();

        $pass->process($container);

        self::assertFalse($container->hasDefinition(RegisterKnpSnappyPrototypePass::PROTOTYPE_SERVICE_ID));
    }

    #[Test]
    public function it_registers_non_shared_prototype_from_knp_snappy_pdf_definition(): void
    {
        $container = new ContainerBuilder();
        $originalDefinition = new Definition(GeneratorInterface::class);
        $container->setDefinition('knp_snappy.pdf', $originalDefinition);

        $pass = new RegisterKnpSnappyPrototypePass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition(RegisterKnpSnappyPrototypePass::PROTOTYPE_SERVICE_ID));

        $prototypeDefinition = $container->getDefinition(RegisterKnpSnappyPrototypePass::PROTOTYPE_SERVICE_ID);
        self::assertFalse($prototypeDefinition->isShared());
    }

    #[Test]
    public function it_preserves_original_definition_as_shared(): void
    {
        $container = new ContainerBuilder();
        $originalDefinition = new Definition(GeneratorInterface::class);
        $container->setDefinition('knp_snappy.pdf', $originalDefinition);

        $pass = new RegisterKnpSnappyPrototypePass();
        $pass->process($container);

        self::assertTrue($container->getDefinition('knp_snappy.pdf')->isShared());
    }

    #[Test]
    public function it_does_nothing_when_prototype_already_exists(): void
    {
        $container = new ContainerBuilder();
        $originalDefinition = new Definition(GeneratorInterface::class);
        $container->setDefinition('knp_snappy.pdf', $originalDefinition);

        $existingPrototype = new Definition(GeneratorInterface::class);
        $existingPrototype->setShared(true);
        $container->setDefinition(RegisterKnpSnappyPrototypePass::PROTOTYPE_SERVICE_ID, $existingPrototype);

        $pass = new RegisterKnpSnappyPrototypePass();
        $pass->process($container);

        self::assertTrue(
            $container->getDefinition(RegisterKnpSnappyPrototypePass::PROTOTYPE_SERVICE_ID)->isShared(),
            'Existing prototype definition should not be overwritten.',
        );
    }

    #[Test]
    public function it_resolves_prototype_through_alias(): void
    {
        $container = new ContainerBuilder();
        $realDefinition = new Definition(GeneratorInterface::class);
        $container->setDefinition('knp_snappy.pdf.inner', $realDefinition);
        $container->setAlias('knp_snappy.pdf', 'knp_snappy.pdf.inner');

        $pass = new RegisterKnpSnappyPrototypePass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition(RegisterKnpSnappyPrototypePass::PROTOTYPE_SERVICE_ID));
        self::assertFalse($container->getDefinition(RegisterKnpSnappyPrototypePass::PROTOTYPE_SERVICE_ID)->isShared());
    }
}
