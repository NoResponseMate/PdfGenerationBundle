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

namespace Sylius\PdfGenerationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterKnpSnappyPrototypePass implements CompilerPassInterface
{
    public const PROTOTYPE_SERVICE_ID = 'sylius_pdf_generation.knp_snappy.pdf_prototype';

    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::PROTOTYPE_SERVICE_ID)) {
            return;
        }

        if (!$container->hasDefinition('knp_snappy.pdf') && !$container->hasAlias('knp_snappy.pdf')) {
            return;
        }

        $originalDef = $container->findDefinition('knp_snappy.pdf');
        $prototypeDef = clone $originalDef;
        $prototypeDef->setShared(false);

        $container->setDefinition(self::PROTOTYPE_SERVICE_ID, $prototypeDef);
    }
}
