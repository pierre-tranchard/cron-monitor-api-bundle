<?php

namespace Tranchard\CronMonitorApiBundle\Services\Notification;

use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Services\Security\UserInterface;

interface NotificationSystemInterface
{

    /**
     * @param UserInterface[]    $recipients
     * @param CronReporter       $cronReporter
     * @param int                $countFailed
     * @param \DateTime          $from
     * @param \DateTime          $to
     * @param UserInterface|null $sender
     *
     * @return bool
     */
    public function sendCronMonitorFailureMessage(
        array $recipients,
        CronReporter $cronReporter,
        \DateTime $from,
        \DateTime $to,
        int $countFailed = 0,
        UserInterface $sender = null
    ): bool;

    /**
     * @param UserInterface[]    $recipients
     * @param CronReporter       $cronReporter
     * @param float              $adjustedAverageDuration
     * @param float              $averageDuration
     * @param UserInterface|null $sender
     *
     * @return bool
     */
    public function sendCronMonitorDurationExceededMessage(
        array $recipients,
        CronReporter $cronReporter,
        float $adjustedAverageDuration,
        float $averageDuration,
        UserInterface $sender = null
    ): bool;

    /**
     * @param UserInterface[]    $recipients
     * @param CronReporter       $cronReporter
     * @param UserInterface|null $sender
     *
     * @return bool
     */
    public function sendCronMonitorLockedMessage(
        array $recipients,
        CronReporter $cronReporter,
        UserInterface $sender = null
    ): bool;

    /**
     * @param UserInterface[]    $recipients
     * @param CronReporter       $cronReporter
     * @param UserInterface|null $sender
     *
     * @return bool
     */
    public function sendCronMonitorCriticalMessage(
        array $recipients,
        CronReporter $cronReporter,
        UserInterface $sender = null
    ): bool;
}
