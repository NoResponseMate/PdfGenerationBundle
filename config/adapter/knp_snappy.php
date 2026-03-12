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

use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyAdapter;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyGeneratorProvider;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyOptionsProcessor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf.generator_provider.knp_snappy', KnpSnappyGeneratorProvider::class)
        ->args([
            service('knp_snappy.pdf'),
        ])
        ->tag('sylius_pdf.generator_provider', ['key' => KnpSnappyAdapter::NAME])
    ;

    $services->set('sylius_pdf.options_processor.knp_snappy', KnpSnappyOptionsProcessor::class)
        ->args([
            service('file_locator'),
            param('knp_snappy.pdf.options'),
        ])
        ->abstract()
    ;

    $services->set('sylius_pdf.adapter.knp_snappy', KnpSnappyAdapter::class)
        ->abstract()
        ->args([
            service('sylius_pdf.registry.generator_provider'),
            service('sylius_pdf.options_processor.composite.knp_snappy'),
            abstract_arg('context name, set by SyliusPdfExtension'),
        ])
    ;
};
