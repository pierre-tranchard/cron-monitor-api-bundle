<?php

namespace Tranchard\CronMonitorApiBundle\Services\Checkers;

use Tranchard\CronMonitorApiBundle\Document\CronReporter;

interface CheckerInterface
{

    /**
     * @param CronReporter $cronReporter
     * @param array        $configuration
     *
     * @return bool
     */
    public function check(CronReporter $cronReporter, array $configuration): bool;

    /**
     * @return string
     */
    public static function getName(): string;
}
