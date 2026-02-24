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

use Sylius\PdfBundle\Adapter\KnpSnappyAdapter;
use Sylius\PdfBundle\Factory\KnpSnappyGeneratorFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf.factory.knp_snappy', KnpSnappyGeneratorFactory::class)
        ->args([
            service('knp_snappy.pdf'),
            service('file_locator'),
            '%knp_snappy.pdf.options%',
        ]);

    $services->set('sylius_pdf.adapter.knp_snappy', KnpSnappyAdapter::class)
        ->abstract();
};
