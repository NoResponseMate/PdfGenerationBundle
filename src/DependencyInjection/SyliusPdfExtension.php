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
use Sylius\PdfBundle\Bridge\Gotenberg\GotenbergAdapter;
use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyAdapter;
use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Sylius\PdfBundle\Core\Processor\CompositeOptionsProcessor;
use Sylius\PdfBundle\Filesystem\Flysystem\FlysystemPdfStorage;
use Sylius\PdfBundle\Filesystem\Gaufrette\GaufrettePdfStorage;
use Sylius\PdfBundle\Filesystem\Symfony\SymfonyFilesystemPdfStorage;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class SyliusPdfExtension extends Extension
{
    private const ADAPTER_REQUIRED = [
        KnpSnappyAdapter::NAME => ['class' => \Knp\Snappy\GeneratorInterface::class, 'package' => 'knplabs/knp-snappy-bundle'],
        DompdfAdapter::NAME => ['class' => \Dompdf\Dompdf::class, 'package' => 'dompdf/dompdf'],
        GotenbergAdapter::NAME => ['class' => \Gotenberg\Gotenberg::class, 'package' => 'gotenberg/gotenberg-php'],
    ];

    private const STORAGE_REQUIRED = [
        'gaufrette' => ['class' => 'Gaufrette\Filesystem', 'package' => 'knplabs/knp-gaufrette-bundle'],
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var ConfigurationInterface $configuration */
        $configuration = $this->getConfiguration([], $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        /** @var string $gotenbergBaseUrl */
        $gotenbergBaseUrl = $config['gotenberg']['base_url'];
        $container->setParameter('sylius_pdf.gotenberg.base_url', $gotenbergBaseUrl);

        $adapterReferences = [];
        $deferredAdapterContexts = [];
        $loadedAdapterFiles = [];

        /** @var string|null $defaultAdapter */
        $defaultAdapter = $config['default']['adapter'];

        if (null !== $defaultAdapter) {
            if ($this->registerAdapter($container, $loader, 'default', $defaultAdapter, $loadedAdapterFiles)) {
                $adapterReferences['default'] = new Reference('sylius_pdf.adapter.default');
                $container->setAlias(PdfGenerationAdapterInterface::class, 'sylius_pdf.adapter.default');
            } else {
                $deferredAdapterContexts['default'] = $defaultAdapter;
            }
        }

        foreach ($config['contexts'] as $contextName => $contextConfig) {
            $contextName = (string) $contextName;

            if ($this->registerAdapter($container, $loader, $contextName, $contextConfig['adapter'], $loadedAdapterFiles)) {
                $adapterReferences[$contextName] = new Reference(sprintf('sylius_pdf.adapter.%s', $contextName));
            } else {
                $deferredAdapterContexts[$contextName] = $contextConfig['adapter'];
            }
        }

        $rendererDefinition = $container->getDefinition('sylius_pdf.renderer.html');
        $rendererDefinition->setArgument(0, new ServiceLocatorArgument($adapterReferences));

        if ([] !== $deferredAdapterContexts) {
            $container->setParameter('.sylius_pdf.deferred_adapter_contexts', $deferredAdapterContexts);
        }

        $defaultStorageConfig = $config['default']['storage'];
        $storageReferences = [];

        $storageReferences['default'] = new Reference(
            $this->registerStorage($container, 'default', $defaultStorageConfig),
        );

        foreach ($config['contexts'] as $contextName => $contextConfig) {
            $contextName = (string) $contextName;
            $storageReferences[$contextName] = new Reference(
                $this->registerStorage($container, $contextName, $contextConfig['storage'] ?? $defaultStorageConfig),
            );
        }

        $container->getDefinition('sylius_pdf.manager')
            ->setArgument(0, new ServiceLocatorArgument($storageReferences));
    }

    /**
     * @param array<string, true> $loadedAdapterFiles
     */
    private function registerAdapter(
        ContainerBuilder $container,
        PhpFileLoader $loader,
        string $contextName,
        string $adapterName,
        array &$loadedAdapterFiles,
    ): bool {
        if (!isset(self::ADAPTER_REQUIRED[$adapterName])) {
            return false;
        }

        $required = self::ADAPTER_REQUIRED[$adapterName];
        if (!class_exists($required['class']) && !interface_exists($required['class'])) {
            throw new \LogicException(sprintf(
                'The "%s" adapter is configured for the "%s" context, but its required dependency "%s" is not installed. Try running "composer require %s".',
                $adapterName,
                $contextName,
                $required['class'],
                $required['package'],
            ));
        }

        $this->loadAdapterServices($loader, $adapterName, $loadedAdapterFiles);
        $this->ensureCompositeProcessorExists($container, $adapterName);

        $adapterServiceId = sprintf('sylius_pdf.adapter.%s', $contextName);

        $container->setDefinition(
            $adapterServiceId,
            (new ChildDefinition(sprintf('sylius_pdf.adapter.%s', $adapterName)))
                ->replaceArgument('$context', $contextName),
        );

        $this->registerProcessor($container, $contextName, $adapterName);

        return true;
    }

    /**
     * @param array<string, mixed> $storageConfig
     */
    private function registerStorage(
        ContainerBuilder $container,
        string $context,
        array $storageConfig,
    ): string {
        /** @var string $type */
        $type = $storageConfig['type'];

        if (isset(self::STORAGE_REQUIRED[$type])) {
            $required = self::STORAGE_REQUIRED[$type];
            if (!class_exists($required['class'])) {
                throw new \LogicException(sprintf(
                    'The "%s" storage type is configured for the "%s" context, but its required dependency "%s" is not installed. Try running "composer require %s".',
                    $type,
                    $context,
                    $required['class'],
                    $required['package'],
                ));
            }
        }

        $serviceId = sprintf('sylius_pdf.storage.%s', $context);

        /** @var string $filesystemServiceId */
        $filesystemServiceId = $storageConfig['filesystem'] ?? '';
        /** @var string $prefix */
        $prefix = $storageConfig['prefix'] ?? '';

        /** @var string|null $localCacheDirectory */
        $localCacheDirectory = $storageConfig['local_cache_directory'] ?? null;

        $definition = match ($type) {
            'flysystem' => new Definition(FlysystemPdfStorage::class, [
                new Reference($filesystemServiceId),
                new Reference('filesystem'),
                $prefix,
                $localCacheDirectory,
            ]),
            'filesystem' => new Definition(SymfonyFilesystemPdfStorage::class, [
                new Reference('filesystem'),
                $storageConfig['directory'],
            ]),
            'gaufrette' => new Definition(GaufrettePdfStorage::class, [
                new Reference($filesystemServiceId),
                new Reference('filesystem'),
                $prefix,
                $localCacheDirectory,
            ]),
            default => throw new \InvalidArgumentException(sprintf('Unknown storage type "%s".', $type)),
        };

        $container->setDefinition($serviceId, $definition);

        return $serviceId;
    }

    private function registerProcessor(
        ContainerBuilder $container,
        string $contextName,
        string $adapterName,
    ): void {
        $parentId = sprintf('sylius_pdf.options_processor.%s', $adapterName);

        if (!$container->hasDefinition($parentId)) {
            return;
        }

        $processorServiceId = sprintf('sylius_pdf.options_processor.%s.%s', $adapterName, $contextName);
        $processorDefinition = new ChildDefinition($parentId);

        $tagAttributes = ['adapter' => $adapterName];
        if ('default' !== $contextName) {
            $tagAttributes['context'] = $contextName;
        }
        $processorDefinition->addTag('sylius_pdf.options_processor', $tagAttributes);

        $container->setDefinition($processorServiceId, $processorDefinition);
    }

    private function ensureCompositeProcessorExists(ContainerBuilder $container, string $adapterName): void
    {
        $compositeId = sprintf('sylius_pdf.options_processor.composite.%s', $adapterName);

        if ($container->hasDefinition($compositeId)) {
            return;
        }

        $container->setDefinition($compositeId, new Definition(CompositeOptionsProcessor::class, [[]]));
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
