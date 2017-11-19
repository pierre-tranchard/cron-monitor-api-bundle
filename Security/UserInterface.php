<?php

namespace Tranchard\CronMonitorApiBundle\Services\Security;

interface UserInterface
{

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getUsername();
}
