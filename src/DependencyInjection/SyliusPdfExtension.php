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

use Sylius\PdfBundle\Adapter\DompdfAdapter;
use Sylius\PdfBundle\Adapter\KnpSnappyAdapter;
use Sylius\PdfBundle\Adapter\PdfGenerationAdapterInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class SyliusPdfExtension extends Extension
{
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
        $deferredContexts = [];

        if ($this->registerAdapter($container, 'default', $config['default'])) {
            $adapterReferences['default'] = new Reference('sylius_pdf.adapter.default');
            $container->setAlias(PdfGenerationAdapterInterface::class, 'sylius_pdf.adapter.default');
        } else {
            $deferredContexts['default'] = $config['default']['adapter'];
        }

        $contextDirectories = [
            'default' => $config['default']['pdf_files_directory'] ?? $rootPdfFilesDirectory,
        ];

        foreach ($config['contexts'] as $contextName => $contextConfig) {
            $contextName = (string) $contextName;

            if ($this->registerAdapter($container, $contextName, $contextConfig)) {
                $adapterReferences[$contextName] = new Reference(sprintf('sylius_pdf.adapter.%s', $contextName));
            } else {
                $deferredContexts[$contextName] = $contextConfig['adapter'];
            }

            $contextDirectories[$contextName] = $contextConfig['pdf_files_directory'] ?? $rootPdfFilesDirectory;
        }

        $container->setParameter('sylius_pdf.context_pdf_files_directories', $contextDirectories);

        $managerDefinition = $container->getDefinition('sylius_pdf.manager.filesystem');
        $managerDefinition->setArgument(0, $contextDirectories);

        $rendererDefinition = $container->getDefinition('sylius_pdf.renderer.html');
        $rendererDefinition->setArgument(0, new ServiceLocatorArgument($adapterReferences));

        if ([] !== $deferredContexts) {
            $container->setParameter('sylius_pdf.deferred_adapter_contexts', $deferredContexts);
        }
    }

    /**
     * @param array{adapter: string, options: array<string, mixed>} $contextConfig
     *
     * @return bool Whether the adapter was registered (true for built-in, false for custom)
     */
    private function registerAdapter(ContainerBuilder $container, string $contextName, array $contextConfig): bool
    {
        $serviceId = sprintf('sylius_pdf.adapter.%s', $contextName);

        return match ($contextConfig['adapter']) {
            'knp_snappy' => $this->registerKnpSnappyAdapter($container, $serviceId, $contextConfig['options']),
            'dompdf' => $this->registerDompdfAdapter($container, $serviceId, $contextConfig['options']),
            default => false,
        };
    }

    /** @param array<string, mixed> $options */
    private function registerKnpSnappyAdapter(ContainerBuilder $container, string $serviceId, array $options): true
    {
        $definition = new Definition(KnpSnappyAdapter::class, [
            new Reference('file_locator'),
            new Reference('knp_snappy.pdf'),
            '%knp_snappy.pdf.options%',
            $options,
        ]);

        $container->setDefinition($serviceId, $definition);

        return true;
    }

    /** @param array<string, mixed> $options */
    private function registerDompdfAdapter(ContainerBuilder $container, string $serviceId, array $options): true
    {
        $definition = new Definition(DompdfAdapter::class, [
            $options,
        ]);

        $container->setDefinition($serviceId, $definition);

        return true;
    }
}
