<?php

namespace Tranchard\CronMonitorApiBundle\Services\Checkers;

use Tranchard\CronMonitorApiBundle\Document\CronReporter;

class CriticalChecker extends Checker implements CheckerInterface
{

    /**
     * @inheritdoc
     */
    public function check(CronReporter $cronReporter, array $configuration): bool
    {
        if ($configuration['enabled'] === false) {
            return false;
        }

        $recipients = $this->computeRecipients($cronReporter);
        if (!empty($recipients)) {
            return $this->notificationSystem->sendCronMonitorCriticalMessage($recipients, $cronReporter);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'critical';
    }
}
