<?php

namespace Tranchard\CronMonitorApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tranchard\CronMonitorApiBundle\Services\Checkers\Checker;

class CheckerPass implements CompilerPassInterface
{

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $userProvider = $container->getParameter('tranchard_cron_monitor_api.user_provider');
        $notificationSystem = $container->getParameter('tranchard_cron_monitor_api.notification_system');

        $baseCheckerDefinition = new Definition(
            Checker::class,
            [
                new Reference($notificationSystem),
                new Reference('doctrine_mongodb.odm.default_document_manager'),
            ]
        );
        $baseCheckerDefinition->addMethodCall('setUserProvider', [new Reference($userProvider)]);
        $baseCheckerDefinition->setAbstract(true);
        $baseCheckerDefinition->setPrivate(true);
        $container->setDefinition(Checker::class, $baseCheckerDefinition);
    }
}
