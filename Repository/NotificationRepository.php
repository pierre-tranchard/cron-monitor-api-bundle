<?php

namespace Tranchard\CronMonitorApiBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Tranchard\CronMonitorApiBundle\Document\Notification;

class NotificationRepository extends DocumentRepository
{

    /**
     * @param string $project
     * @param string $jobName
     * @param string $environment
     * @param string $type
     * @param int    $durationBetweenNotifications
     *
     * @return bool
     */
    public function canSendNotification(
        string $project,
        string $jobName,
        string $environment,
        string $type,
        int $durationBetweenNotifications
    ): bool {
        /** @var null|Notification $notification */
        $notification = $this->createQueryBuilder()
                             ->field('project')->equals(new \MongoRegex(sprintf('/%s/i', $project)))
                             ->field('job')->equals($jobName)
                             ->field('environment')->equals($environment)
                             ->field('type')->equals($type)
                             ->getQuery()
                             ->getSingleResult();

        if (is_null($notification)) {
            return true;
        }

        $now = new \DateTime();
        $interval = $now->diff($notification->getLastNotificationSent());
        if (($interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s) >= $durationBetweenNotifications) {
            return true;
        }

        return false;
    }

    /**
     * @param string $project
     * @param string $environment
     *
     * @return \Traversable
     */
    public function getNotifications(string $project, string $environment)
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->field('project')->equals(new \MongoRegex(sprintf('/%s/i', $project)));
        $queryBuilder->field('environment')->equals($environment);
        $queryBuilder->hydrate(false);

        return $queryBuilder->getQuery()->execute();
    }
}
