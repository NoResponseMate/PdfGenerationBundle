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

use Sylius\PdfGenerationBundle\Generator\PdfFileGenerator;
use Sylius\PdfGenerationBundle\Generator\PdfFileGeneratorInterface;
use Sylius\PdfGenerationBundle\Manager\FilesystemPdfFileManager;
use Sylius\PdfGenerationBundle\Manager\PdfFileManagerInterface;
use Sylius\PdfGenerationBundle\Renderer\HtmlToPdfRenderer;
use Sylius\PdfGenerationBundle\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfGenerationBundle\Renderer\TwigToPdfRenderer;
use Sylius\PdfGenerationBundle\Renderer\TwigToPdfRendererInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf_generation.renderer.html', HtmlToPdfRenderer::class);
    $services->alias(HtmlToPdfRendererInterface::class, 'sylius_pdf_generation.renderer.html');

    $services->set('sylius_pdf_generation.renderer.twig', TwigToPdfRenderer::class)
        ->args([
            service('twig'),
            service('sylius_pdf_generation.renderer.html'),
        ]);
    $services->alias(TwigToPdfRendererInterface::class, 'sylius_pdf_generation.renderer.twig');

    $services->set('sylius_pdf_generation.manager.filesystem', FilesystemPdfFileManager::class);

    $services->alias('sylius_pdf_generation.manager', 'sylius_pdf_generation.manager.filesystem');
    $services->alias(PdfFileManagerInterface::class, 'sylius_pdf_generation.manager');

    $services->set('sylius_pdf_generation.generator', PdfFileGenerator::class)
        ->args([
            service('sylius_pdf_generation.manager'),
        ]);
    $services->alias(PdfFileGeneratorInterface::class, 'sylius_pdf_generation.generator');
};
