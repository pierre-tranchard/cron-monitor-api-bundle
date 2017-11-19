<?php

namespace Tranchard\CronMonitorApiBundle\Services\Checkers;

use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Document\Notification;
use Tranchard\CronMonitorApiBundle\Repository\CronReporterRepository;
use Tranchard\CronMonitorApiBundle\Repository\NotificationRepository;

class ThresholdChecker extends Checker implements CheckerInterface
{

    /**
     * @inheritdoc
     */
    public function check(CronReporter $cronReporter, array $configuration): bool
    {
        $dayInterval = new \DateInterval('P1D');
        $dayInterval->invert = 1;
        $yesterday = (new \DateTime('now'))->add($dayInterval);
        $now = new \DateTime('now');

        /** @var CronReporterRepository $cronReporterRepository */
        $cronReporterRepository = $this->documentManager->getRepository(CronReporter::class);

        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->documentManager->getRepository(Notification::class);

        $count = $cronReporterRepository->countBy(
            [
                'project'     => $cronReporter->getProject(),
                'jobName'     => $cronReporter->getJob(),
                'environment' => $cronReporter->getEnvironment(),
                'createdAt'   => [['method' => 'gte', 'value' => $yesterday], ['method' => 'lte', 'value' => $now]],
                'status'      => CronReporter::STATUS_FAILED,
            ]
        );

        if ($count > $configuration['max_failed']) {
            $canSendNotification = $notificationRepository->canSendNotification(
                $cronReporter->getProject(),
                $cronReporter->getJob(),
                $cronReporter->getEnvironment(),
                self::getName(),
                $configuration['duration_interval']
            );

            if ($canSendNotification) {
                /** @var null|Notification $notification */
                $notification = $notificationRepository->findOneBy(
                    [
                        'project'     => $cronReporter->getProject(),
                        'jobName'     => $cronReporter->getJob(),
                        'environment' => $cronReporter->getEnvironment(),
                        'type'        => self::getName(),
                    ]
                );
                if (is_null($notification)) {
                    $notification = new Notification(
                        $cronReporter->getProject(), $cronReporter->getJob(),
                        $cronReporter->getEnvironment(), self::getName()
                    );
                }
                $notification->setLastNotificationSent(new \DateTime());
                $this->documentManager->persist($notification);
                $this->documentManager->flush();

                $recipients = $this->computeRecipients($cronReporter);
                if (!empty($recipients)) {
                    return $this->notificationSystem->sendCronMonitorFailureMessage(
                        $recipients,
                        $cronReporter,
                        $yesterday,
                        $now,
                        $count
                    );
                }
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'threshold';
    }
}
