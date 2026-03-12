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

use Sylius\PdfBundle\Core\Generator\PdfFileGenerator;
use Sylius\PdfBundle\Core\Generator\PdfFileGeneratorInterface;
use Sylius\PdfBundle\Core\Manager\FilesystemPdfFileManager;
use Sylius\PdfBundle\Core\Manager\PdfFileManagerInterface;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistry;
use Sylius\PdfBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRenderer;
use Sylius\PdfBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfBundle\Core\Renderer\TwigToPdfRenderer;
use Sylius\PdfBundle\Core\Renderer\TwigToPdfRendererInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
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

    $services->set('sylius_pdf.manager.filesystem', FilesystemPdfFileManager::class)
        ->args([
            abstract_arg('context directories, set by SyliusPdfExtension'),
            service('filesystem'),
        ])
    ;

    $services->alias('sylius_pdf.manager', 'sylius_pdf.manager.filesystem');
    $services->alias(PdfFileManagerInterface::class, 'sylius_pdf.manager');

    $services->set('sylius_pdf.generator', PdfFileGenerator::class)
        ->args([
            service('sylius_pdf.manager'),
        ])
    ;
    $services->alias(PdfFileGeneratorInterface::class, 'sylius_pdf.generator');

    $services->set('sylius_pdf.registry.generator_provider', GeneratorProviderRegistry::class)
        ->args([
            abstract_arg('provider service locator, set by RegisterGeneratorProvidersPass'),
        ])
    ;
    $services->alias(GeneratorProviderRegistryInterface::class, 'sylius_pdf.registry.generator_provider');
};
