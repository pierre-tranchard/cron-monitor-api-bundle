<?php

namespace Tranchard\CronMonitorApiBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @MongoDB\Document(repositoryClass="Tranchard\CronMonitorApiBundle\Repository\NotificationRepository")
 */
class Notification
{

    /**
     * @var string
     * @MongoDB\Id()
     * @Groups({"list", "display"})
     */
    private $id;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=false)
     * @Groups({"list", "display"})
     * @MongoDB\Index()
     */
    private $project;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=false)
     * @Groups({"list", "display"})
     * @MongoDB\Index()
     */
    private $job;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=false)
     * @Groups({"list", "display"})
     */
    private $environment;

    /**
     * @var \DateTimeInterface
     * @MongoDB\Field(type="date", nullable=false)
     * @Groups({"list", "display"})
     * @MongoDB\Index()
     */
    private $lastNotificationSent;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=false)
     * @Groups({"list", "display"})
     * @MongoDB\Index()
     */
    private $type;

    /**
     * Notification constructor.
     *
     * @param string                  $project
     * @param string                  $job
     * @param string                  $environment
     * @param string                  $type
     * @param null|\DateTimeInterface $lastNotificationSent
     */
    public function __construct(
        string $project,
        string $job,
        string $environment,
        string $type,
        \DateTimeInterface $lastNotificationSent = null
    ) {
        $this->setProject($project);
        $this->setJob($job);
        $this->setEnvironment($environment);
        $this->setType($type);
        $this->setLastNotificationSent($lastNotificationSent ?? new \DateTimeImmutable());
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getProject(): string
    {
        return $this->project;
    }

    /**
     * @param string $project
     *
     * @return self
     */
    public function setProject(string $project): Notification
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return string
     */
    public function getJob(): string
    {
        return $this->job;
    }

    /**
     * @param string $jobName
     *
     * @return self
     */
    public function setJob(string $jobName): Notification
    {
        $this->job = $jobName;

        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @param string $environment
     *
     * @return self
     */
    public function setEnvironment(string $environment): Notification
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastNotificationSent(): \DateTimeInterface
    {
        return $this->lastNotificationSent;
    }

    /**
     * @param \DateTimeInterface $lastNotificationSent
     *
     * @return self
     */
    public function setLastNotificationSent(\DateTimeInterface $lastNotificationSent): Notification
    {
        $this->lastNotificationSent = $lastNotificationSent;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): Notification
    {
        $this->type = $type;

        return $this;
    }
}
