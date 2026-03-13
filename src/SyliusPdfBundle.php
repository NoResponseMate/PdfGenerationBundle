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

use Sylius\PdfBundle\Core\Attribute\AsPdfGenerationAdapter;
use Sylius\PdfBundle\Core\Attribute\AsPdfGeneratorProvider;
use Sylius\PdfBundle\Core\Attribute\AsPdfOptionsProcessor;
use Sylius\PdfBundle\DependencyInjection\Compiler\RegisterGeneratorProvidersPass;
use Sylius\PdfBundle\DependencyInjection\Compiler\RegisterKnpSnappyPrototypePass;
use Sylius\PdfBundle\DependencyInjection\Compiler\RegisterOptionsProcessorsPass;
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

        $container->registerAttributeForAutoconfiguration(
            AsPdfGeneratorProvider::class,
            static function (ChildDefinition $definition, AsPdfGeneratorProvider $attribute): void {
                $definition->addTag('sylius_pdf.generator_provider', [
                    'key' => $attribute->key,
                    'context' => $attribute->context,
                ]);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            AsPdfOptionsProcessor::class,
            static function (ChildDefinition $definition, AsPdfOptionsProcessor $attribute): void {
                $definition->addTag('sylius_pdf.options_processor', [
                    'adapter' => $attribute->adapter,
                    'context' => $attribute->context,
                    'priority' => $attribute->priority,
                ]);
            },
        );

        $container->addCompilerPass(new RegisterKnpSnappyPrototypePass());
        $container->addCompilerPass(new RegisterGeneratorProvidersPass());
        $container->addCompilerPass(new RegisterOptionsProcessorsPass());
        $container->addCompilerPass(new RegisterPdfGenerationAdaptersPass());
    }
}
