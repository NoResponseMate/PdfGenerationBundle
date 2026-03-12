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
use Symfony\Component\DependencyInjection\Reference;

final class RegisterOptionsProcessorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sylius_pdf.registry.options_processor')) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('sylius_pdf.options_processor');

        if ([] === $taggedServices) {
            return;
        }

        /** @var array<string, array<string, list<array{service_id: string, priority: int}>>> $grouped */
        $grouped = [];

        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['adapter']) || !is_string($attributes['adapter']) || '' === $attributes['adapter']) {
                    throw new \InvalidArgumentException(sprintf(
                        'The service "%s" tagged with "sylius_pdf.options_processor" must have an "adapter" attribute.',
                        $serviceId,
                    ));
                }

                $adapter = $attributes['adapter'];
                $context = isset($attributes['context']) && is_string($attributes['context']) && '' !== $attributes['context']
                    ? $attributes['context']
                    : '';
                $priority = isset($attributes['priority']) ? (int) $attributes['priority'] : 0;

                $grouped[$adapter][$context][] = [
                    'service_id' => $serviceId,
                    'priority' => $priority,
                ];
            }
        }

        $registryDefinition = $container->getDefinition('sylius_pdf.registry.options_processor');

        foreach ($grouped as $adapterType => $contexts) {
            foreach ($contexts as $contextKey => $entries) {
                usort($entries, static fn (array $a, array $b): int => $b['priority'] <=> $a['priority']);

                $references = array_map(
                    static fn (array $entry): Reference => new Reference($entry['service_id']),
                    $entries,
                );

                $registryDefinition->addMethodCall(
                    'registerProcessors',
                    [$adapterType, '' === $contextKey ? 'default' : $contextKey, $references],
                );
            }
        }
    }
}
