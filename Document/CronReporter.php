<?php

namespace Tranchard\CronMonitorApiBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Annotation\Groups;
use Tranchard\CronMonitorApiBundle\Exception\UnexpectedValueException;

/**
 * @MongoDB\Document(repositoryClass="Tranchard\CronMonitorApiBundle\Repository\CronReporterRepository")
 */
class CronReporter
{

    const STATUS_STARTED  = 'started';
    const STATUS_FAILED   = 'failed';
    const STATUS_SUCCESS  = 'success';
    const STATUS_LOCKED   = 'locked';
    const STATUS_CRITICAL = 'critical';

    /**
     * @var array
     */
    private static $statuses = [
        self::STATUS_STARTED  => self::STATUS_STARTED,
        self::STATUS_FAILED   => self::STATUS_FAILED,
        self::STATUS_SUCCESS  => self::STATUS_SUCCESS,
        self::STATUS_LOCKED   => self::STATUS_LOCKED,
        self::STATUS_CRITICAL => self::STATUS_CRITICAL,
    ];

    /**
     * @var string
     * @MongoDB\Id()
     * @Groups({"list", "display"})
     */
    private $id;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=false)
     * @MongoDB\Index()
     * @Groups({"list", "display"})
     */
    private $project;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=false)
     * @MongoDB\Index()
     * @Groups({"list", "display"})
     */
    private $job;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=true)
     * @Groups({"display"})
     */
    private $description;

    /**
     * @var \DateTimeInterface
     * @MongoDB\Field(type="date", nullable=false)
     * @MongoDB\Index()
     * @Groups({"list", "display"})
     */
    private $createdAt;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=false)
     * @MongoDB\Index()
     * @Groups({"list", "display"})
     */
    private $status;

    /**
     * @var int
     * @MongoDB\Field(type="int")
     * @Groups({"list", "display"})
     */
    private $duration;

    /**
     * @var array
     * @MongoDB\Field(type="hash")
     * @Groups({"display"})
     */
    private $extraPayload;

    /**
     * @var string
     * @MongoDB\Field(type="string", nullable=false)
     * @MongoDB\Index()
     * @Groups({"list", "display"})
     */
    private $environment;

    /**
     * CronReporter constructor.
     */
    public function __construct()
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->setStatus(self::STATUS_STARTED);
        $this->setDuration(0);
        $this->setExtraPayload([]);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param string $project
     *
     * @return CronReporter
     */
    public function setProject(string $project): CronReporter
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param string $job
     *
     * @return CronReporter
     */
    public function setJob(string $job): CronReporter
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return CronReporter
     */
    public function setDescription(string $description): CronReporter
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     *
     * @return CronReporter
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): CronReporter
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return CronReporter
     * @throws UnexpectedValueException
     */
    public function setStatus(string $status): CronReporter
    {
        if (!in_array($status, self::$statuses)) {
            throw UnexpectedValueException::unexpectedStatus($status, self::$statuses);
        }
        $this->status = $status;

        return $this;
    }

    /**
     * @param string $status
     *
     * @return bool
     * @throws UnexpectedValueException
     */
    public function isStatus(string $status): bool
    {
        if (!in_array($status, self::$statuses)) {
            throw UnexpectedValueException::unexpectedStatus($status, self::$statuses);
        }

        return $this->status === $status;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     *
     * @return CronReporter
     */
    public function setDuration(int $duration): CronReporter
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtraPayload(): array
    {
        return $this->extraPayload;
    }

    /**
     * @param array $extraPayload
     *
     * @return CronReporter
     */
    public function setExtraPayload(array $extraPayload): CronReporter
    {
        $this->extraPayload = $extraPayload;

        return $this;
    }

    /**
     * @param array $extraPayload
     *
     * @return CronReporter
     */
    public function addExtraPayload(array $extraPayload): CronReporter
    {
        $this->extraPayload = array_merge($this->extraPayload, $extraPayload);

        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param string $environment
     *
     * @return CronReporter
     */
    public function setEnvironment(string $environment): CronReporter
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @return array
     */
    public static function getStatuses(): array
    {
        return self::$statuses;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->is(self::STATUS_LOCKED);
    }

    /**
     * @return bool
     */
    public function hasSucceeded(): bool
    {
        return $this->is(self::STATUS_SUCCESS);
    }

    /**
     * @return bool
     */
    public function hasFailed(): bool
    {
        return $this->is(self::STATUS_FAILED);
    }

    /**
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->is(self::STATUS_CRITICAL);
    }

    /**
     * @param string $status
     *
     * @return bool
     */
    private function is(string $status): bool
    {
        return $this->getStatus() === $status;
    }
}
