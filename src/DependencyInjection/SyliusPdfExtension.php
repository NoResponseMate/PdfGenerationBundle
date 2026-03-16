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
use Sylius\PdfBundle\Core\Processor\CompositeOptionsProcessor;
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
    private const BUILT_IN_ADAPTERS = [KnpSnappyAdapter::NAME, DompdfAdapter::NAME];

    private const ADAPTER_REQUIRED_CLASSES = [
        KnpSnappyAdapter::NAME => \Knp\Snappy\GeneratorInterface::class,
        DompdfAdapter::NAME => \Dompdf\Dompdf::class,
    ];

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

        if ($this->registerAdapter($container, $loader, 'default', $config['default']['adapter'], $loadedAdapterFiles)) {
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

            if ($this->registerAdapter($container, $loader, $contextName, $contextConfig['adapter'], $loadedAdapterFiles)) {
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
            $container->setParameter('.sylius_pdf.deferred_adapter_contexts', $deferredAdapterContexts);
        }
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
        if (!in_array($adapterName, self::BUILT_IN_ADAPTERS, true)) {
            return false;
        }

        $requiredClass = self::ADAPTER_REQUIRED_CLASSES[$adapterName];
        if (!class_exists($requiredClass) && !interface_exists($requiredClass)) {
            throw new \LogicException(sprintf(
                'The "%s" adapter is configured for the "%s" context, but its required dependency "%s" is not installed. Try running "composer require %s".',
                $adapterName,
                $contextName,
                $requiredClass,
                KnpSnappyAdapter::NAME === $adapterName ? 'knplabs/knp-snappy-bundle' : 'dompdf/dompdf',
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
