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

use Sylius\PdfGenerationBundle\Adapter\Gotenberg\GotenbergAdapter;
use Sylius\PdfGenerationBundle\Adapter\Gotenberg\GotenbergGeneratorProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf_generation.generator_provider.gotenberg', GotenbergGeneratorProvider::class)
        ->args([
            param('sylius_pdf_generation.gotenberg.base_url'),
        ])
        ->tag('sylius_pdf_generation.generator_provider', ['adapter' => GotenbergAdapter::NAME])
    ;

    $services->set('sylius_pdf_generation.adapter.gotenberg', GotenbergAdapter::class)
        ->abstract()
        ->args([
            service('sylius_pdf_generation.registry.generator_provider'),
            service('sylius_pdf_generation.options_processor.composite.gotenberg'),
            abstract_arg('context name, set by SyliusPdfGenerationExtension'),
        ])
    ;
};
