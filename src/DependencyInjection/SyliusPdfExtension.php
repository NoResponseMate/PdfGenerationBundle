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

namespace Sylius\PdfBundle\DependencyInjection;

use Sylius\PdfBundle\Adapter\PdfGenerationAdapterInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class SyliusPdfExtension extends Extension
{
    private const BUILT_IN_ADAPTERS = ['knp_snappy', 'dompdf'];

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var ConfigurationInterface $configuration */
        $configuration = $this->getConfiguration([], $container);
        $config = $this->processConfiguration($configuration, $configs);

        $rootPdfFilesDirectory = $config['pdf_files_directory'];
        $container->setParameter('sylius_pdf.pdf_files_directory', $rootPdfFilesDirectory);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        $adapterReferences = [];
        $deferredAdapterContexts = [];
        $deferredFactoryContexts = [];
        $loadedAdapterFiles = [];

        $this->storeContextOptions($container, 'default', $config['default']['options']);

        if ($this->registerAdapter($container, $loader, 'default', $config['default'], $deferredFactoryContexts, $loadedAdapterFiles)) {
            $adapterReferences['default'] = new Reference('sylius_pdf.adapter.default');
            $container->setAlias(PdfGenerationAdapterInterface::class, 'sylius_pdf.adapter.default');
        } else {
            $deferredAdapterContexts['default'] = $config['default']['adapter'];
        }

        $contextDirectories = [
            'default' => $config['default']['pdf_files_directory'] ?? $rootPdfFilesDirectory,
        ];

        foreach ($config['contexts'] as $contextName => $contextConfig) {
            $contextName = (string) $contextName;

            $this->storeContextOptions($container, $contextName, $contextConfig['options']);

            if ($this->registerAdapter($container, $loader, $contextName, $contextConfig, $deferredFactoryContexts, $loadedAdapterFiles)) {
                $adapterReferences[$contextName] = new Reference(sprintf('sylius_pdf.adapter.%s', $contextName));
            } else {
                $deferredAdapterContexts[$contextName] = $contextConfig['adapter'];
            }

            $contextDirectories[$contextName] = $contextConfig['pdf_files_directory'] ?? $rootPdfFilesDirectory;
        }

        $container->setParameter('sylius_pdf.context_pdf_files_directories', $contextDirectories);

        $managerDefinition = $container->getDefinition('sylius_pdf.manager.filesystem');
        $managerDefinition->setArgument(0, $contextDirectories);

        $rendererDefinition = $container->getDefinition('sylius_pdf.renderer.html');
        $rendererDefinition->setArgument(0, new ServiceLocatorArgument($adapterReferences));

        if ([] !== $deferredAdapterContexts) {
            $container->setParameter('sylius_pdf.deferred_adapter_contexts', $deferredAdapterContexts);
        }

        if ([] !== $deferredFactoryContexts) {
            $container->setParameter('sylius_pdf.deferred_factory_contexts', $deferredFactoryContexts);
        }
    }

    /**
     * @param array{adapter: string, factory: ?string, options: array<string, mixed>} $contextConfig
     * @param array<string, string> $deferredFactoryContexts
     * @param array<string, true> $loadedAdapterFiles
     */
    private function registerAdapter(
        ContainerBuilder $container,
        PhpFileLoader $loader,
        string $contextName,
        array $contextConfig,
        array &$deferredFactoryContexts,
        array &$loadedAdapterFiles,
    ): bool {
        $adapterName = $contextConfig['adapter'];

        if (!in_array($adapterName, self::BUILT_IN_ADAPTERS, true)) {
            return false;
        }

        $this->loadAdapterServices($loader, $adapterName, $loadedAdapterFiles);

        $factoryServiceId = sprintf('sylius_pdf.factory.%s', $contextName);

        if (null === $contextConfig['factory'] || $adapterName === $contextConfig['factory']) {
            $container->setAlias($factoryServiceId, sprintf('sylius_pdf.factory.%s', $adapterName));
        } else {
            $deferredFactoryContexts[$contextName] = $contextConfig['factory'];
        }

        $adapterServiceId = sprintf('sylius_pdf.adapter.%s', $contextName);
        $container->setDefinition(
            $adapterServiceId,
            (new ChildDefinition(sprintf('sylius_pdf.adapter.%s', $adapterName)))
                ->setArguments([
                    new Reference($factoryServiceId),
                    $contextConfig['options'],
                    $contextName,
                ]),
        );

        return true;
    }

    /**
     * @param array<string, true> $loadedAdapterFiles
     */
    private function loadAdapterServices(PhpFileLoader $loader, string $adapterName, array &$loadedAdapterFiles): void
    {
        if (isset($loadedAdapterFiles[$adapterName])) {
            return;
        }

        $loader->load(sprintf('adapter/%s.php', $adapterName));
        $loadedAdapterFiles[$adapterName] = true;
    }

    /** @param array<string, mixed> $options */
    private function storeContextOptions(ContainerBuilder $container, string $contextName, array $options): void
    {
        $container->setParameter(sprintf('sylius_pdf.adapter.%s.options', $contextName), $options);
    }
}
