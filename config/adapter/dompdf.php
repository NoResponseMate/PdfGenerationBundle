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

use Sylius\PdfBundle\Adapter\DompdfAdapter;
use Sylius\PdfBundle\Factory\DompdfGeneratorFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf.factory.dompdf', DompdfGeneratorFactory::class);

    $services->set('sylius_pdf.adapter.dompdf', DompdfAdapter::class)
        ->abstract();
};
