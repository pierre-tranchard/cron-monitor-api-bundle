<?php

namespace Tranchard\CronMonitorApiBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class JsonRequestListener
{

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();

        if (!$request->isMethod(Request::METHOD_GET) && !is_null($request->getContent())) {
            $data = json_decode($request->getContent(), true);
            if (is_array($data)) {
                $request->request->add($data);
            }
        }
    }
}
