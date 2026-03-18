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

use Sylius\PdfGenerationBundle\Adapter\Dompdf\DompdfAdapter;
use Sylius\PdfGenerationBundle\Adapter\Dompdf\DompdfGeneratorProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf_generation.generator_provider.dompdf', DompdfGeneratorProvider::class)
        ->tag('sylius_pdf_generation.generator_provider', ['adapter' => DompdfAdapter::NAME])
    ;

    $services->set('sylius_pdf_generation.adapter.dompdf', DompdfAdapter::class)
        ->abstract()
        ->args([
            service('sylius_pdf_generation.registry.generator_provider'),
            service('sylius_pdf_generation.options_processor.composite.dompdf'),
            abstract_arg('context name, set by SyliusPdfGenerationExtension'),
        ])
    ;
};
