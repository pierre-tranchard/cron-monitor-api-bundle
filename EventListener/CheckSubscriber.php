<?php

namespace Tranchard\CronMonitorApiBundle\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\EventListener\Event\CheckEvent;
use Tranchard\CronMonitorApiBundle\EventListener\Events\CheckEvents;
use Tranchard\CronMonitorApiBundle\Services\Checkers\ChainChecker;
use Tranchard\CronMonitorApiBundle\Services\Checkers\CriticalChecker;
use Tranchard\CronMonitorApiBundle\Services\Checkers\DurationChecker;
use Tranchard\CronMonitorApiBundle\Services\Checkers\LockChecker;
use Tranchard\CronMonitorApiBundle\Services\Checkers\ThresholdChecker;

class CheckSubscriber implements EventSubscriberInterface
{

    /**
     * @var ChainChecker
     */
    private $chainChecker;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CheckSubscriber constructor.
     *
     * @param ChainChecker         $chainChecker
     * @param array                $configuration
     * @param LoggerInterface|null $logger
     */
    public function __construct(ChainChecker $chainChecker, array $configuration, LoggerInterface $logger = null)
    {
        $this->chainChecker = $chainChecker;
        $this->configuration = $configuration;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            CheckEvents::THRESHOLD => [
                ['thresholdCheck', 10],
                ['logCheck', 0],
            ],
            CheckEvents::DURATION  => [
                ['durationCheck', 10],
                ['logCheck', 0],
            ],
            CheckEvents::LOCK      => [
                ['lockCheck', 10],
                ['logCheck', 0],
            ],
            CheckEvents::CRITICAL  => [
                ['criticalCheck', 10],
                ['logCheck', 0],
            ],
        ];

    }

    /**
     * @param CheckEvent $event
     *
     * @return bool
     */
    public function criticalCheck(CheckEvent $event): bool
    {
        $cronReporter = $event->getCronReporter();

        $configuration = $this->getConfiguration($cronReporter);

        if (empty($configuration)
            || !isset($configuration['checkers'])
            || !isset($configuration['checkers'][CriticalChecker::getName()])
        ) {
            return false;
        }

        return $this->chainChecker->get(CriticalChecker::getName())->check(
            $cronReporter,
            $configuration['checkers'][CriticalChecker::getName()]
        );
    }

    /**
     * @param CheckEvent $event
     *
     * @return bool
     */
    public function lockCheck(CheckEvent $event): bool
    {
        $cronReporter = $event->getCronReporter();

        $configuration = $this->getConfiguration($cronReporter);

        if (empty($configuration)
            || !isset($configuration['checkers'])
            || !isset($configuration['checkers'][LockChecker::getName()])
        ) {
            return false;
        }

        return $this->chainChecker->get(LockChecker::getName())->check(
            $cronReporter,
            $configuration['checkers'][LockChecker::getName()]
        );
    }

    /**
     * @param CheckEvent $event
     *
     * @return bool
     */
    public function thresholdCheck(CheckEvent $event): bool
    {
        $cronReporter = $event->getCronReporter();

        $configuration = $this->getConfiguration($cronReporter);

        if (empty($configuration)
            || !isset($configuration['checkers'])
            || !isset($configuration['checkers'][ThresholdChecker::getName()])
        ) {
            return false;
        }

        return $this->chainChecker->get(ThresholdChecker::getName())->check(
            $cronReporter,
            $configuration['checkers'][ThresholdChecker::getName()]
        );
    }

    /**
     * @param CheckEvent $event
     *
     * @return bool
     */
    public function durationCheck(CheckEvent $event): bool
    {
        $cronReporter = $event->getCronReporter();

        $configuration = $this->getConfiguration($cronReporter);

        if (empty($configuration)
            || !isset($configuration['checkers'])
            || !isset($configuration['checkers'][DurationChecker::getName()])
        ) {
            return false;
        }

        return $this->chainChecker->get(DurationChecker::getName())->check(
            $cronReporter,
            $configuration['checkers'][DurationChecker::getName()]
        );
    }

    /**
     * @param CheckEvent $event
     */
    public function logCheck(CheckEvent $event)
    {
        $cronReporter = $event->getCronReporter();
        $method = 'info';
        $context = [
            'jobName'      => $cronReporter->getJob(),
            'environment'  => $cronReporter->getEnvironment(),
            'project'      => $cronReporter->getProject(),
            'duration'     => $cronReporter->getDuration(),
            'extraPayload' => $cronReporter->getExtraPayload(),
        ];
        switch ($cronReporter->getStatus()) {
            case CronReporter::STATUS_FAILED:
                $method = 'critical';
                $message = sprintf(
                    '%s for project %s has failed in %s',
                    $cronReporter->getJob(),
                    $cronReporter->getProject(),
                    $cronReporter->getEnvironment()
                );
                break;
            case CronReporter::STATUS_SUCCESS:
                $message = sprintf(
                    '%s for project %s has succeed in %s',
                    $cronReporter->getJob(),
                    $cronReporter->getProject(),
                    $cronReporter->getEnvironment()
                );
                break;
            case CronReporter::STATUS_LOCKED:
                $method = 'warning';
                $message = sprintf(
                    '%s for project %s is locked in %s',
                    $cronReporter->getJob(),
                    $cronReporter->getProject(),
                    $cronReporter->getEnvironment()
                );
                break;
            case CronReporter::STATUS_CRITICAL:
                $method = 'critical';
                $message = sprintf(
                    '%s for project %s is critical in %s',
                    $cronReporter->getJob(),
                    $cronReporter->getProject(),
                    $cronReporter->getEnvironment()
                );
                break;
            default:
                $message = sprintf(
                    '%s for project %s is running in %s',
                    $cronReporter->getJob(),
                    $cronReporter->getProject(),
                    $cronReporter->getEnvironment()
                );
                break;
        }

        $this->logger->{$method}($message, $context);
    }

    /**
     * @param CronReporter $cronReporter
     *
     * @return array
     */
    private function getConfiguration(CronReporter $cronReporter): array
    {
        $configuration = $this->configuration[$cronReporter->getProject()] ?? [];
        $configuration = $configuration['environments'][$cronReporter->getEnvironment()] ?? [];

        if (empty($configuration)) {
            return [];
        }

        return $configuration['cron'][$cronReporter->getJob()] ?? $configuration['cron']['default'];
    }
}
