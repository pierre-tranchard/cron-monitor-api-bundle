<?php

namespace Tranchard\CronMonitorApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class TranchardCronMonitorApiExtension extends Extension
{

    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!isset($config['monitoring'])) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The node monitoring must be configured under %s',
                    Configuration::getRootNode()
                )
            );
        }

        foreach ($config['monitoring'] as $project => $environments) {
            foreach ($environments['environments'] as $environment => $environmentConfiguration) {
                if (!isset($environmentConfiguration['cron']['default'])) {
                    throw new InvalidConfigurationException(
                        sprintf(
                            'Default values must be configured under %s.monitoring.%s.environments.%s.cron',
                            Configuration::getRootNode(),
                            $project,
                            $environment
                        )
                    );
                }
                $defaultConfiguration = $environmentConfiguration['cron']['default'];
                $resolvers = [];
                foreach ($defaultConfiguration as $kind => $settings) {
                    foreach ($settings as $name => $setting) {
                        $resolvers[$name] = $setting;
                    }
                }
                foreach ($environmentConfiguration['cron'] as $cron => $cronConfiguration) {
                    if ($cron !== "default") {
                        foreach ($cronConfiguration as $kind => $settings) {
                            foreach ($resolvers as $name => $defaultSetting) {
                                $config['monitoring'][$project]['environments'][$environment]['cron'][$cron][$kind][$name] = array_merge(
                                    $defaultSetting,
                                    $settings[$name] ?? []
                                );
                            }
                        }
                    }
                }
            }
        }

        $this->storeConfiguration($container, $config);

        $xmlLoader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $xmlLoader->load('repositories.xml');
        $xmlLoader->load('checkers.xml');
        $xmlLoader->load('notification_system.xml');
        $xmlLoader->load('controllers.xml');
        $xmlLoader->load('event_services.xml');
        $xmlLoader->load('authenticator.xml');
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function storeConfiguration(ContainerBuilder $container, array $config)
    {
        foreach ($config as $key => $value) {
            $container->setParameter(sprintf('%s.%s', Configuration::getRootNode(), $key), $value);
        }
    }
}
