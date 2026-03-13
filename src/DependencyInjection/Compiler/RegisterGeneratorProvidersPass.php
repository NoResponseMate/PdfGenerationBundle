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

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterGeneratorProvidersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sylius_pdf.registry.generator_provider')) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('sylius_pdf.generator_provider');

        if ([] === $taggedServices) {
            return;
        }

        /** @var array<string, Reference> $locatorMap */
        $locatorMap = [];

        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['adapter']) || !is_string($attributes['adapter']) || '' === $attributes['adapter']) {
                    throw new \InvalidArgumentException(sprintf(
                        'The service "%s" tagged with "sylius_pdf.generator_provider" must have an "adapter" attribute.',
                        $serviceId,
                    ));
                }

                $key = $attributes['adapter'];
                $context = isset($attributes['context']) && is_string($attributes['context']) && '' !== $attributes['context'] && 'default' !== $attributes['context']
                    ? $attributes['context']
                    : null;

                $locatorKey = null !== $context ? $key . '.' . $context : $key;
                $locatorMap[$locatorKey] = new Reference($serviceId);
            }
        }

        $registryDefinition = $container->getDefinition('sylius_pdf.registry.generator_provider');
        $registryDefinition->setArgument(0, new ServiceLocatorArgument($locatorMap));
    }
}
