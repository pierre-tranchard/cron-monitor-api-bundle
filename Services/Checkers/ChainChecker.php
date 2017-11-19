<?php

namespace Tranchard\CronMonitorApiBundle\Services\Checkers;

use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Exception\CheckerException;

class ChainChecker implements CheckerInterface
{

    /**
     * @var array|CheckerInterface[]
     */
    private $checkers;

    /**
     * ChainChecker constructor.
     *
     * @param iterable $checkers
     */
    public function __construct(iterable $checkers)
    {
        foreach ($checkers as $priority => $checker) {
            $this->addChecker($checker, $priority);
        }
    }

    /**
     * @inheritdoc
     */
    public function check(CronReporter $cronReporter, array $configuration): bool
    {
        $anyFail = false;
        foreach ($this->checkers as $priority => $checkers) {
            foreach ($checkers as $name => $checker) {
                /** @var CheckerInterface $checker */
                $anyFail |= $checker->check($cronReporter, $configuration['checkers'][$name]) !== true;
            }
        }

        return $anyFail;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'chain_checker';
    }

    /**
     * @param CheckerInterface $checker
     * @param int              $priority
     *
     * @return ChainChecker
     */
    public function addChecker(CheckerInterface $checker, int $priority = 0): self
    {
        $this->checkers[$priority][$checker::getName()] = $checker;
        sort($this->checkers);

        return $this;
    }

    /**
     * @param string $checker
     *
     * @return CheckerInterface
     * @throws CheckerException
     */
    public function get(string $checker): CheckerInterface
    {
        foreach ($this->checkers as $priority => $checkers) {
            foreach (array_keys($checkers) as $name) {
                if ($name === $checker) {
                    return $this->checkers[$priority][$checker];
                }
            }
        }

        throw CheckerException::notFoundCheckerException($checker);
    }
}
