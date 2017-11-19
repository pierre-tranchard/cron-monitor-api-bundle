<?php

namespace Tranchard\CronMonitorApiBundle\Services\Security;

interface UserProviderInterface
{

    /**
     * @param string $role
     *
     * @return UserInterface[]
     */
    public function getByRole(string $role);
}
