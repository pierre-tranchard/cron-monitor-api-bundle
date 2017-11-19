<?php

namespace Tranchard\CronMonitorApiBundle\Math;

class Statistics
{

    /**
     * @param array $source
     *
     * @return float
     */
    public static function averageDuration(array $source): float
    {
        return array_sum($source) / count($source);
    }
}
