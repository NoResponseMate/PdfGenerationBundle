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

namespace Sylius\PdfGenerationBundle\DependencyInjection\Compiler;

use Sylius\PdfGenerationBundle\Core\Processor\CompositeOptionsProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterOptionsProcessorsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('sylius_pdf_generation.options_processor');

        if ([] === $taggedServices) {
            return;
        }

        /** @var array<string, array<string, list<array{service_id: string, priority: int}>>> $grouped */
        $grouped = [];

        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['adapter']) || !is_string($attributes['adapter']) || '' === $attributes['adapter']) {
                    throw new \InvalidArgumentException(sprintf(
                        'The service "%s" tagged with "sylius_pdf_generation.options_processor" must have an "adapter" attribute.',
                        $serviceId,
                    ));
                }

                $adapter = $attributes['adapter'];
                $context = isset($attributes['context']) && is_string($attributes['context']) && '' !== $attributes['context']
                    ? $attributes['context']
                    : 'default';
                $priority = isset($attributes['priority']) ? (int) $attributes['priority'] : 0;

                $grouped[$adapter][$context][] = [
                    'service_id' => $serviceId,
                    'priority' => $priority,
                ];
            }
        }

        foreach ($grouped as $adapterType => $contexts) {
            $compositeId = sprintf('sylius_pdf_generation.options_processor.composite.%s', $adapterType);

            /** @var array<string, list<Reference>> $contextReferences */
            $contextReferences = [];

            foreach ($contexts as $contextKey => $entries) {
                usort($entries, static fn (array $a, array $b): int => $b['priority'] <=> $a['priority']);

                $contextReferences[$contextKey] = array_map(
                    static fn (array $entry): Reference => new Reference($entry['service_id']),
                    $entries,
                );
            }

            $container->setDefinition(
                $compositeId,
                new Definition(CompositeOptionsProcessor::class, [$contextReferences]),
            );
        }
    }
}
