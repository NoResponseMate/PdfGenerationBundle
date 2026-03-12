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

use Sylius\PdfBundle\Bridge\Dompdf\DompdfAdapter;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyAdapter;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
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
    private const BUILT_IN_ADAPTERS = [KnpSnappyAdapter::NAME, DompdfAdapter::NAME];

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
        $loadedAdapterFiles = [];

        if ($this->registerAdapter($container, $loader, 'default', $config['default']['adapter'], $config['default']['options'] ?? [], $loadedAdapterFiles)) {
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

            if ($this->registerAdapter($container, $loader, $contextName, $contextConfig['adapter'], $contextConfig['options'] ?? [], $loadedAdapterFiles)) {
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
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, true> $loadedAdapterFiles
     */
    private function registerAdapter(
        ContainerBuilder $container,
        PhpFileLoader $loader,
        string $contextName,
        string $adapterName,
        array $options,
        array &$loadedAdapterFiles,
    ): bool {
        if (!in_array($adapterName, self::BUILT_IN_ADAPTERS, true)) {
            return false;
        }

        $this->loadAdapterServices($loader, $adapterName, $loadedAdapterFiles);

        $adapterServiceId = sprintf('sylius_pdf.adapter.%s', $contextName);

        $container->setDefinition(
            $adapterServiceId,
            (new ChildDefinition(sprintf('sylius_pdf.adapter.%s', $adapterName)))
                ->replaceArgument('$context', $contextName),
        );

        $this->registerProcessor($container, $contextName, $adapterName, $options);

        return true;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function registerProcessor(
        ContainerBuilder $container,
        string $contextName,
        string $adapterName,
        array $options,
    ): void {
        $processorServiceId = sprintf('sylius_pdf.options_processor.%s.%s', $adapterName, $contextName);
        $processorDefinition = new ChildDefinition(sprintf('sylius_pdf.options_processor.%s', $adapterName));

        if (KnpSnappyAdapter::NAME === $adapterName) {
            $processorDefinition->setArgument('$allowedFiles', $options['allowed_files'] ?? []);
        }

        if (DompdfAdapter::NAME === $adapterName) {
            $processorDefinition->setArgument('$options', $options);
        }

        $tagAttributes = ['adapter' => $adapterName];
        if ('default' !== $contextName) {
            $tagAttributes['context'] = $contextName;
        }
        $processorDefinition->addTag('sylius_pdf.options_processor', $tagAttributes);

        $container->setDefinition($processorServiceId, $processorDefinition);
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
}
