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

use Sylius\PdfBundle\Bridge\KnpSnappy\KnpSnappyAdapter;
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
                ->scalarNode('pdf_files_directory')
                    ->defaultValue('%kernel.project_dir%/private/pdf')
                ->end()
                ->append($this->addContextNode('default'))
                ->arrayNode('contexts')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('adapter')
                                ->defaultValue(KnpSnappyAdapter::NAME)
                            ->end()
                            ->scalarNode('pdf_files_directory')
                                ->defaultNull()
                            ->end()
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

    private function addContextNode(string $name): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition($name);

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('adapter')
                    ->defaultValue(KnpSnappyAdapter::NAME)
                ->end()
                ->scalarNode('pdf_files_directory')
                    ->defaultNull()
                ->end()
            ->end()
        ;

        return $node;
    }
}
