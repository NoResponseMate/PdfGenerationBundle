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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sylius_pdf');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('gotenberg')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_url')->defaultValue('http://localhost:3000')->end()
                    ->end()
                ->end()
                ->append($this->addContextNode('default', withStorageDefaults: true))
                ->arrayNode('contexts')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('adapter')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->append($this->addStorageNode())
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function (array $contexts): bool {
                            return array_key_exists('default', $contexts);
                        })
                        ->thenInvalid('The context name "default" is reserved. Use the "default" key at the root level instead.')
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function addContextNode(string $name, bool $withStorageDefaults = false): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition($name);

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('adapter')
                    ->defaultNull()
                ->end()
                ->append($withStorageDefaults ? $this->addStorageNodeWithDefaults() : $this->addStorageNode())
            ->end()
        ;

        return $node;
    }

    private function addStorageNodeWithDefaults(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('storage');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode('type')
                    ->values(['flysystem', 'filesystem', 'gaufrette'])
                    ->defaultValue('filesystem')
                ->end()
                ->scalarNode('filesystem')
                    ->defaultNull()
                ->end()
                ->scalarNode('prefix')
                    ->defaultValue('pdf')
                ->end()
                ->scalarNode('directory')
                    ->defaultValue('%kernel.project_dir%/var/pdf')
                ->end()
                ->scalarNode('local_cache_directory')
                    ->defaultValue('%kernel.cache_dir%/pdf')
                ->end()
            ->end()
            ->validate()
                ->ifTrue(function (array $storage): bool {
                    return 'filesystem' === $storage['type'] && null === $storage['directory'];
                })
                ->thenInvalid('The "directory" option is required when storage type is "filesystem".')
            ->end()
            ->validate()
                ->ifTrue(function (array $storage): bool {
                    return in_array($storage['type'], ['flysystem', 'gaufrette'], true) && null === $storage['filesystem'];
                })
                ->thenInvalid('The "filesystem" option is required when storage type is "flysystem" or "gaufrette".')
            ->end()
        ;

        return $node;
    }

    private function addStorageNode(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('storage');

        $node
            ->children()
                ->enumNode('type')
                    ->values(['flysystem', 'filesystem', 'gaufrette'])
                ->end()
                ->scalarNode('filesystem')
                    ->defaultNull()
                ->end()
                ->scalarNode('prefix')
                    ->defaultNull()
                ->end()
                ->scalarNode('directory')
                    ->defaultNull()
                ->end()
                ->scalarNode('local_cache_directory')
                    ->defaultNull()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(function (array $storage): bool {
                    return 'filesystem' === ($storage['type'] ?? null) && null === ($storage['directory'] ?? null);
                })
                ->thenInvalid('The "directory" option is required when storage type is "filesystem".')
            ->end()
            ->validate()
                ->ifTrue(function (array $storage): bool {
                    return in_array($storage['type'] ?? null, ['flysystem', 'gaufrette'], true) && null === ($storage['filesystem'] ?? null);
                })
                ->thenInvalid('The "filesystem" option is required when storage type is "flysystem" or "gaufrette".')
            ->end()
        ;

        return $node;
    }
}
