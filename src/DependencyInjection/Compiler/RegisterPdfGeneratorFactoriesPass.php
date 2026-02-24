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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterPdfGeneratorFactoriesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sylius_pdf.deferred_factory_contexts')) {
            return;
        }

        /** @var array<string, string> $deferredContexts */
        $deferredContexts = $container->getParameter('sylius_pdf.deferred_factory_contexts');

        $taggedServices = $container->findTaggedServiceIds('sylius_pdf.factory');
        $factoryMap = $this->buildFactoryMap($taggedServices);

        foreach ($deferredContexts as $contextName => $factoryKey) {
            if (!isset($factoryMap[$factoryKey])) {
                throw new \InvalidArgumentException(sprintf(
                    'The PDF generator factory "%s" used in context "%s" is not registered. ' .
                    'Did you forget to tag your service with "sylius_pdf.factory" ' .
                    'or use the #[AsPdfGeneratorFactory] attribute?',
                    $factoryKey,
                    $contextName,
                ));
            }

            $factoryServiceId = sprintf('sylius_pdf.factory.%s', $contextName);
            $container->setAlias($factoryServiceId, $factoryMap[$factoryKey]);
        }

        $container->getParameterBag()->remove('sylius_pdf.deferred_factory_contexts');
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $taggedServices
     *
     * @return array<string, string>
     */
    private function buildFactoryMap(array $taggedServices): array
    {
        $factoryMap = [];

        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['key']) || !is_string($attributes['key']) || '' === $attributes['key']) {
                    throw new \InvalidArgumentException(sprintf(
                        'The service "%s" tagged with "sylius_pdf.factory" must have a "key" attribute.',
                        $serviceId,
                    ));
                }

                $key = $attributes['key'];

                if (isset($factoryMap[$key])) {
                    throw new \InvalidArgumentException(sprintf(
                        'The PDF generator factory key "%s" is already registered by service "%s". ' .
                        'Service "%s" cannot use the same key.',
                        $key,
                        $factoryMap[$key],
                        $serviceId,
                    ));
                }

                $factoryMap[$key] = $serviceId;
            }
        }

        return $factoryMap;
    }
}
