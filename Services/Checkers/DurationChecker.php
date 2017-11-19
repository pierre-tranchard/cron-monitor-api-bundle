<?php

namespace Tranchard\CronMonitorApiBundle\Services\Checkers;

use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Document\Notification;
use Tranchard\CronMonitorApiBundle\Repository\CronReporterRepository;
use Tranchard\CronMonitorApiBundle\Repository\NotificationRepository;

class DurationChecker extends Checker implements CheckerInterface
{

    /**
     * @inheritdoc
     */
    public function check(CronReporter $cronReporter, array $configuration): bool
    {
        /** @var CronReporterRepository $cronReporterRepository */
        $cronReporterRepository = $this->documentManager->getRepository(CronReporter::class);

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

        if ($this->mustCheck($cronReporter, $configuration, $cronReporterRepository) && $canSendNotification) {
            $averageDuration = $cronReporterRepository->computeAverageDuration(
                $cronReporter,
                $configuration['use_cron_tokens']
            );
            $adjustedAverageDuration = $averageDuration * (1 + floatval($configuration['duration_tolerance']));

            /** @var null|Notification $notification */
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
                    $cronReporter->getProject(), $job,
                    $cronReporter->getEnvironment(), self::getName()
                );
            }
            $notification->setLastNotificationSent(new \DateTime());
            $this->documentManager->persist($notification);
            $this->documentManager->flush();

            $recipients = $this->computeRecipients($cronReporter);
            if (!empty($recipients)) {
                return $this->notificationSystem->sendCronMonitorDurationExceededMessage(
                    $recipients,
                    $cronReporter,
                    $adjustedAverageDuration,
                    $averageDuration
                );
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'duration';
    }

    /**
     * @param CronReporter           $cronReporter
     * @param array                  $configuration
     * @param CronReporterRepository $repository
     *
     * @return bool
     */
    private function mustCheck(
        CronReporter $cronReporter,
        array $configuration,
        CronReporterRepository $repository
    ): bool {
        if (!is_null($configuration['max_execution_duration'] ?? null)) {
            return ($configuration['max_execution_duration'] * 1000000) > $cronReporter->getDuration();
        }
        if ($configuration['auto_monitor_duration']) {
            return $cronReporter->getDuration() > ($repository->computeAverageDuration(
                        $cronReporter,
                        $configuration['use_cron_tokens']
                    ) * (1 + $configuration['duration_tolerance']));
        }

        return false;
    }
}
