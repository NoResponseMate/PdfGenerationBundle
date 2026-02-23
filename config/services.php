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

use Sylius\PdfBundle\Generator\PdfFileGenerator;
use Sylius\PdfBundle\Generator\PdfFileGeneratorInterface;
use Sylius\PdfBundle\Manager\FilesystemPdfFileManager;
use Sylius\PdfBundle\Manager\PdfFileManagerInterface;
use Sylius\PdfBundle\Renderer\HtmlToPdfRenderer;
use Sylius\PdfBundle\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfBundle\Renderer\TwigToPdfRenderer;
use Sylius\PdfBundle\Renderer\TwigToPdfRendererInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf.renderer.html', HtmlToPdfRenderer::class);
    $services->alias(HtmlToPdfRendererInterface::class, 'sylius_pdf.renderer.html');

    $services->set('sylius_pdf.renderer.twig', TwigToPdfRenderer::class)
        ->args([
            service('twig'),
            service('sylius_pdf.renderer.html'),
        ]);
    $services->alias(TwigToPdfRendererInterface::class, 'sylius_pdf.renderer.twig');

    $services->set('sylius_pdf.manager.filesystem', FilesystemPdfFileManager::class);

    $services->alias('sylius_pdf.manager', 'sylius_pdf.manager.filesystem');
    $services->alias(PdfFileManagerInterface::class, 'sylius_pdf.manager');

    $services->set('sylius_pdf.generator', PdfFileGenerator::class)
        ->args([
            service('sylius_pdf.manager'),
        ]);
    $services->alias(PdfFileGeneratorInterface::class, 'sylius_pdf.generator');
};
