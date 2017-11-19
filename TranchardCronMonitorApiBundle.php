<?php

namespace Tranchard\CronMonitorApiBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tranchard\CronMonitorApiBundle\DependencyInjection\Compiler\CheckerPass;

class TranchardCronMonitorApiBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CheckerPass());
        parent::build($container);
    }
}
