<?php

namespace Tranchard\CronMonitorApiBundle\Exception;

class CheckerException extends \RuntimeException implements ExceptionInterface
{

    /**
     * @param string          $checker
     * @param int             $code
     * @param \Throwable|null $previous
     *
     * @return CheckerException
     */
    public static function notFoundCheckerException(string $checker, int $code = 0, \Throwable $previous = null)
    {
        return new self(sprintf('Checker "%s" not found', $checker), $code, $previous);
    }
}
