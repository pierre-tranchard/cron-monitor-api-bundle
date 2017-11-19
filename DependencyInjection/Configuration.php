<?php

namespace Tranchard\CronMonitorApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::getRootNode());

        $rootNode->children()
                 ->scalarNode('secret')
                     ->info('The secret shared with the clients')
                     ->defaultNull()
                 ->end()
                 ->arrayNode('monitoring')
                     ->requiresAtLeastOneElement()
                     ->prototype('array')
                         ->children()
                             ->arrayNode('environments')
                                 ->requiresAtLeastOneElement()
                                 ->prototype('array')
                                     ->children()
                                         ->arrayNode('cron')
                                             ->prototype('array')
                                                 ->children()
                                                     ->arrayNode('options')
                                                         ->children()
                                                             ->scalarNode('dashboard_name')
                                                             ->defaultNull()
                                                         ->end()
                                                     ->end()
                                                 ->end()
                                                 ->arrayNode('checkers')
                                                     ->children()
                                                         ->arrayNode('threshold')
                                                         ->children()
                                                             ->integerNode('max_failed')
                                                             ->info('Number of failed authorized before a notification is sent')
                                                         ->end()
                                                         ->integerNode('duration_interval')
                                                            ->info('Duration interval between 2 notifications are sent')
                                                         ->end()
                                                     ->end()
                                                 ->end()
                                                 ->arrayNode('duration')
                                                    ->children()
                                                        ->scalarNode('auto_monitor_duration')
                                                            ->info('Enable auto monitor duration')
                                                            ->defaultFalse()
                                                        ->end()
                                                        ->scalarNode('use_cron_tokens')
                                                            ->info('Refine or not the auto monitor duration based on cron tokens')
                                                            ->defaultFalse()
                                                        ->end()
                                                        ->scalarNode('max_execution_duration')
                                                            ->info('Duration maximum of the cron execution, can be null if disabled')
                                                            ->defaultNull()
                                                        ->end()
                                                        ->integerNode('duration_interval')
                                                            ->info('Duration interval between 2 notifications are sent')
                                                        ->end()
                                                        ->scalarNode('duration_tolerance')
                                                            ->info('Decimal representation for the percentage of tolerance')
                                                            ->defaultValue(0)
                                                        ->end()
                                                    ->end()
                                                 ->end()
                                                 ->arrayNode('lock')
                                                    ->children()
                                                        ->scalarNode('use_cron_tokens')
                                                            ->info('Refine or not the auto monitor duration based on cron tokens')
                                                            ->defaultFalse()
                                                        ->end()
                                                        ->integerNode('duration_interval')
                                                            ->info('Duration interval between 2 notifications are sent')
                                                        ->end()
                                                    ->end()
                                                 ->end()
                                                 ->arrayNode('critical')
                                                     ->children()
                                                         ->scalarNode('enabled')
                                                             ->info('If critical check is enabled or not')
                                                             ->defaultFalse()
                                                         ->end()
                                                     ->end()
                                                 ->end()
                                             ->end()
                                         ->end()
                                     ->end()
                                 ->end()
                             ->end()
                         ->end()
                     ->end()
                 ->end();

        return $treeBuilder;
    }

    /**
     * @return string
     */
    public static function getRootNode(): string
    {
        return 'tranchard_cron_monitor_api';
    }
}
