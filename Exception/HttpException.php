<?php

namespace Tranchard\CronMonitorApiBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException as BaseException;

class HttpException extends BaseException
{

    /**
     * @param string          $id
     * @param null|\Throwable $previous
     * @param array           $headers
     * @param int|null        $code
     *
     * @return HttpException
     */
    public static function cronReporterNotFoundException(
        string $id,
        \Throwable $previous = null,
        array $headers = [],
        ?int $code = 0
    ) {
        return new self(
            Response::HTTP_NOT_FOUND,
            sprintf('Cannot find a cron reporter object with id "%s"', $id),
            $previous,
            $headers,
            $code
        );
    }
}
