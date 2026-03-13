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

namespace Sylius\PdfBundle\DependencyInjection\Compiler;

use Sylius\PdfBundle\Core\Adapter\PdfGenerationAdapterInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterPdfGenerationAdaptersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('.sylius_pdf.deferred_adapter_contexts')) {
            return;
        }

        /** @var array<string, string> $deferredContexts */
        $deferredContexts = $container->getParameter('.sylius_pdf.deferred_adapter_contexts');

        $taggedServices = $container->findTaggedServiceIds('sylius_pdf.adapter');
        $adapterMap = $this->buildAdapterMap($taggedServices);

        $rendererDefinition = $container->getDefinition('sylius_pdf.renderer.html');

        /** @var ServiceLocatorArgument $locatorArgument */
        $locatorArgument = $rendererDefinition->getArgument(0);
        $existingReferences = $locatorArgument->getValues();

        foreach ($deferredContexts as $contextName => $adapterKey) {
            if (!isset($adapterMap[$adapterKey])) {
                throw new \InvalidArgumentException(sprintf(
                    'The PDF generation adapter "%s" used in context "%s" is not registered. ' .
                    'Did you forget to tag your service with "sylius_pdf.adapter" ' .
                    'or use the #[AsPdfGenerationAdapter] attribute?',
                    $adapterKey,
                    $contextName,
                ));
            }

            $existingReferences[$contextName] = new Reference($adapterMap[$adapterKey]);
        }

        $rendererDefinition->setArgument(0, new ServiceLocatorArgument($existingReferences));

        if (isset($deferredContexts['default'])) {
            $container->setAlias(
                PdfGenerationAdapterInterface::class,
                $adapterMap[$deferredContexts['default']],
            );
        }
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $taggedServices
     *
     * @return array<string, string>
     */
    private function buildAdapterMap(array $taggedServices): array
    {
        $adapterMap = [];

        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['key']) || !is_string($attributes['key']) || '' === $attributes['key']) {
                    throw new \InvalidArgumentException(sprintf(
                        'The service "%s" tagged with "sylius_pdf.adapter" must have a "key" attribute.',
                        $serviceId,
                    ));
                }

                $key = $attributes['key'];

                if (isset($adapterMap[$key])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The PDF generation adapter key "%s" is already registered by service "%s". ' .
                        'Service "%s" cannot use the same key.',
                        $key,
                        $adapterMap[$key],
                        $serviceId,
                    ));
                }

                $adapterMap[$key] = $serviceId;
            }
        }

        return $adapterMap;
    }
}
