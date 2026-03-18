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

use Sylius\PdfGenerationBundle\Adapter\KnpSnappy\KnpSnappyAdapter;
use Sylius\PdfGenerationBundle\Adapter\KnpSnappy\KnpSnappyGeneratorProvider;
use Sylius\PdfGenerationBundle\Adapter\KnpSnappy\KnpSnappyOptionsProcessor;
use Sylius\PdfGenerationBundle\DependencyInjection\Compiler\RegisterKnpSnappyPrototypePass;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service_closure;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf_generation.generator_provider.knp_snappy', KnpSnappyGeneratorProvider::class)
        ->args([
            service_closure(RegisterKnpSnappyPrototypePass::PROTOTYPE_SERVICE_ID),
        ])
        ->tag('sylius_pdf_generation.generator_provider', ['adapter' => KnpSnappyAdapter::NAME])
    ;

    $services->set('sylius_pdf_generation.options_processor.knp_snappy', KnpSnappyOptionsProcessor::class)
        ->args([
            param('knp_snappy.pdf.options'),
        ])
        ->abstract()
    ;

    $services->set('sylius_pdf_generation.adapter.knp_snappy', KnpSnappyAdapter::class)
        ->abstract()
        ->args([
            service('sylius_pdf_generation.registry.generator_provider'),
            service('sylius_pdf_generation.options_processor.composite.knp_snappy'),
            abstract_arg('context name, set by SyliusPdfGenerationExtension'),
        ])
    ;
};
