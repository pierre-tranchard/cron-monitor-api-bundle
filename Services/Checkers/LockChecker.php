<?php

namespace Tranchard\CronMonitorApiBundle\Services\Checkers;

use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Document\Notification;
use Tranchard\CronMonitorApiBundle\Repository\NotificationRepository;

class LockChecker extends Checker implements CheckerInterface
{

    /**
     * @inheritdoc
     */
    public function check(CronReporter $cronReporter, array $configuration): bool
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->documentManager->getRepository(Notification::class);

        $job = $this->computeJob($cronReporter, $configuration['use_cron_tokens']);

        $canSendNotification = $notificationRepository->canSendNotification(
            $cronReporter->getProject(),
            $job,
            $cronReporter->getEnvironment(),
            self::getName(),
            $configuration['duration_interval']
        );

        if ($canSendNotification) {
            $notification = $notificationRepository->findOneBy(
                [
                    'project'     => $cronReporter->getProject(),
                    'job'         => $job,
                    'environment' => $cronReporter->getEnvironment(),
                    'type'        => self::getName(),
                ]
            );
            if (is_null($notification)) {
                $notification = new Notification(
                    $cronReporter->getProject(),
                    $job,
                    $cronReporter->getEnvironment(),
                    self::getName()
                );
            }
            $notification->setLastNotificationSent(new \DateTimeImmutable());
            $this->documentManager->persist($notification);
            $this->documentManager->flush();

            $recipients = $this->computeRecipients($cronReporter);
            if (!empty($recipients)) {
                return $this->notificationSystem->sendCronMonitorLockedMessage($recipients, $cronReporter);
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'lock';
    }
}
