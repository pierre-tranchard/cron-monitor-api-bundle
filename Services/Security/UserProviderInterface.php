<?php

namespace Tranchard\CronMonitorApiBundle\Services\Security;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserProviderInterface
{

    /**
     * @param string $role
     *
     * @return UserInterface[]
     */
    public function getByRole(string $role);
}
