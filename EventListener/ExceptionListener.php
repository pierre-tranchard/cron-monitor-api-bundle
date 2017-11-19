<?php

namespace Tranchard\CronMonitorApiBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->isXmlHttpRequest()
            && !in_array('application/json', $request->getAcceptableContentTypes())
            && ($request->getContentType() !== "json")
        ) {
            return;
        }

        $exception = $event->getException();
        $statusCode = ($exception instanceof HttpExceptionInterface) ?
            $exception->getStatusCode() : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $responseData = [
            'success' => false,
            'message' => [
                'code'    => $statusCode,
                'message' => $exception->getMessage(),
                'stack'   => $exception->getTraceAsString(),
            ],
        ];
        $event->setResponse(new JsonResponse($responseData, $statusCode));
    }
}
