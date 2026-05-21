<?php

declare(strict_types=1);

namespace SuluContentImportExportBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sulu_content_import_export');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('csrf_token_id')
                    ->defaultValue('sulu_content_import_export')
                ->end()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('page')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                            ->end()
                        ->end()
                        ->arrayNode('snippet')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                            ->end()
                        ->end()
                        ->arrayNode('article')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->arrayNode('types')
                                    ->scalarPrototype()->end()
                                    ->defaultValue(['article'])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
