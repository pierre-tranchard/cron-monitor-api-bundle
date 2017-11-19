<?php

namespace Tranchard\CronMonitorApiBundle\EventListener\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Tranchard\CronMonitorApiBundle\Document\CronReporter;

class CheckEvent extends Event
{

    /**
     * @var Request
     */
    private $request;

    /**
     * @var CronReporter
     */
    private $cronReporter;

    /**
     * CheckEvent constructor.
     *
     * @param Request      $request
     * @param CronReporter $cronReporter
     */
    public function __construct(Request $request, CronReporter $cronReporter)
    {
        $this->request = $request;
        $this->cronReporter = $cronReporter;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return CronReporter
     */
    public function getCronReporter(): CronReporter
    {
        return $this->cronReporter;
    }
}
