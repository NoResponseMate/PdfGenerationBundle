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

namespace Sylius\PdfBundle;

use Sylius\PdfBundle\Attribute\AsPdfGenerationAdapter;
use Sylius\PdfBundle\DependencyInjection\Compiler\RegisterPdfGenerationAdaptersPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SyliusPdfBundle extends Bundle
{
    public function getPath(): string
    {
        return __DIR__;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerAttributeForAutoconfiguration(
            AsPdfGenerationAdapter::class,
            static function (ChildDefinition $definition, AsPdfGenerationAdapter $attribute): void {
                $definition->addTag('sylius_pdf.adapter', ['key' => $attribute->key]);
            },
        );

        $container->addCompilerPass(new RegisterPdfGenerationAdaptersPass());
    }
}
