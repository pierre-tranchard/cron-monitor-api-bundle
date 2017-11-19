<?php

namespace Tranchard\CronMonitorApiBundle\Services\Checkers;

use Doctrine\ODM\MongoDB\DocumentManager;
use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Services\Notification\NotificationSystemInterface;
use Tranchard\CronMonitorApiBundle\Services\Security\UserInterface;
use Tranchard\CronMonitorApiBundle\Services\Security\UserProviderInterface;

abstract class Checker
{

    /**
     * @var NotificationSystemInterface
     */
    protected $notificationSystem;

    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * Checker constructor.
     *
     * @param NotificationSystemInterface $notificationSystem
     * @param DocumentManager             $documentManager
     */
    public function __construct(NotificationSystemInterface $notificationSystem, DocumentManager $documentManager)
    {
        $this->notificationSystem = $notificationSystem;
        $this->documentManager = $documentManager;
    }

    /**
     * @param UserProviderInterface $userProvider
     *
     * @return $this
     */
    public function setUserProvider(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;

        return $this;
    }

    /**
     * @param CronReporter $cronReporter
     * @param bool         $useTokens
     *
     * @return string
     */
    protected function computeJob(CronReporter $cronReporter, bool $useTokens = false): string
    {
        $job = $cronReporter->getJob();
        if ($useTokens) {
            $tokens = $cronReporter->getExtraPayload()['tokens'] ?? ['arguments' => [], 'options' => []];
            $arguments = $tokens['arguments'] ?? [];
            $options = $tokens['options'] ?? [];

            foreach ($arguments as $argument) {
                $job .= " ".$argument;
            }

            foreach ($options as $option => $value) {
                $job .= " --".$option."=".$value;
            }

            $job = trim($job);
        }

        return $job;
    }

    /**
     * @param CronReporter $cronReporter
     *
     * @return array
     */
    protected function computeRecipients(CronReporter $cronReporter): array
    {
        $recipients = [];
        $users = $this->userProvider->getByRole('ROLE_CRON_MONITOR');
        foreach ($users as $user) {
            if ($user instanceof UserInterface) {
                $recipients[] = $user->getEmail();
            }
        }

        return $recipients;
    }
}
