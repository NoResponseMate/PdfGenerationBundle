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

use Sylius\PdfBundle\Core\Renderer\TwigToPdfRenderer;
use Sylius\PdfBundle\Core\Renderer\TwigToPdfRendererInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf.renderer.twig', TwigToPdfRenderer::class)
        ->args([
            service('twig'),
            service('sylius_pdf.renderer.html'),
        ]);
    $services->alias(TwigToPdfRendererInterface::class, 'sylius_pdf.renderer.twig');
};
