<?php

namespace Tranchard\CronMonitorApiBundle\Exception;

class UnexpectedValueException extends \UnexpectedValueException implements ExceptionInterface
{

    /**
     * @param string          $status
     * @param array           $values
     * @param int             $code
     * @param \Throwable|null $previous
     *
     * @return UnexpectedValueException
     */
    public static function unexpectedStatus(string $status, array $values, int $code = 0, \Throwable $previous = null)
    {
        return new self(
            sprintf(
                'The status "%s" does not exist. Existing statuses: %s',
                $status,
                implode(',', $values)
            ),
            $code,
            $previous
        );
    }
}
