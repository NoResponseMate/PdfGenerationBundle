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

use Sylius\PdfGenerationBundle\Core\Generator\PdfFileGenerator;
use Sylius\PdfGenerationBundle\Core\Generator\PdfFileGeneratorInterface;
use Sylius\PdfGenerationBundle\Core\Filesystem\Manager\PdfFileManager;
use Sylius\PdfGenerationBundle\Core\Filesystem\Manager\PdfFileManagerInterface;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistry;
use Sylius\PdfGenerationBundle\Core\Registry\GeneratorProviderRegistryInterface;
use Sylius\PdfGenerationBundle\Core\Renderer\HtmlToPdfRenderer;
use Sylius\PdfGenerationBundle\Core\Renderer\HtmlToPdfRendererInterface;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRenderer;
use Sylius\PdfGenerationBundle\Core\Renderer\TwigToPdfRendererInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_pdf_generation.renderer.html', HtmlToPdfRenderer::class)
        ->args([
            abstract_arg('adapter service locator, set by SyliusPdfGenerationExtension'),
        ])
    ;
    $services->alias(HtmlToPdfRendererInterface::class, 'sylius_pdf_generation.renderer.html');

    $services->set('sylius_pdf_generation.manager', PdfFileManager::class)
        ->args([
            abstract_arg('storage service locator, set by SyliusPdfGenerationExtension'),
        ])
    ;
    $services->alias(PdfFileManagerInterface::class, 'sylius_pdf_generation.manager');

    $services->set('sylius_pdf_generation.generator', PdfFileGenerator::class)
        ->args([
            service('sylius_pdf_generation.manager'),
        ])
    ;
    $services->alias(PdfFileGeneratorInterface::class, 'sylius_pdf_generation.generator');

    $services->set('sylius_pdf_generation.registry.generator_provider', GeneratorProviderRegistry::class)
        ->args([
            abstract_arg('provider service locator, set by RegisterGeneratorProvidersPass'),
        ])
    ;
    $services->alias(GeneratorProviderRegistryInterface::class, 'sylius_pdf_generation.registry.generator_provider');

    $services->set('sylius_pdf_generation.renderer.twig', TwigToPdfRenderer::class)
        ->args([
            service('twig'),
            service('sylius_pdf_generation.renderer.html'),
        ]);
    $services->alias(TwigToPdfRendererInterface::class, 'sylius_pdf_generation.renderer.twig');
};
