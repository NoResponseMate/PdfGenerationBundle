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

namespace Sylius\PdfGenerationBundle;

use Sylius\PdfGenerationBundle\Core\Attribute\AsPdfGenerationAdapter;
use Sylius\PdfGenerationBundle\Core\Attribute\AsPdfGeneratorProvider;
use Sylius\PdfGenerationBundle\Core\Attribute\AsPdfOptionsProcessor;
use Sylius\PdfGenerationBundle\DependencyInjection\Compiler\RegisterGeneratorProvidersPass;
use Sylius\PdfGenerationBundle\DependencyInjection\Compiler\RegisterKnpSnappyPrototypePass;
use Sylius\PdfGenerationBundle\DependencyInjection\Compiler\RegisterOptionsProcessorsPass;
use Sylius\PdfGenerationBundle\DependencyInjection\Compiler\RegisterPdfGenerationAdaptersPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SyliusPdfGenerationBundle extends Bundle
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
                $definition->addTag('sylius_pdf_generation.adapter', ['key' => $attribute->key]);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            AsPdfGeneratorProvider::class,
            static function (ChildDefinition $definition, AsPdfGeneratorProvider $attribute): void {
                $definition->addTag('sylius_pdf_generation.generator_provider', [
                    'adapter' => $attribute->adapter,
                    'context' => $attribute->context,
                ]);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            AsPdfOptionsProcessor::class,
            static function (ChildDefinition $definition, AsPdfOptionsProcessor $attribute): void {
                $definition->addTag('sylius_pdf_generation.options_processor', [
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
