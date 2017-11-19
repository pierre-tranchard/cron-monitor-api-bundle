<?php

namespace Tranchard\CronMonitorApiBundle\EventListener\Events;

final class CheckEvents
{

    const THRESHOLD = 'tranchard.cron_monitor_api.event_listener.events.check_events.threshold';
    const DURATION  = 'tranchard.cron_monitor_api.event_listener.events.check_events.duration';
    const LOCK      = 'tranchard.cron_monitor_api.event_listener.events.check_events.lock';
    const CRITICAL  = 'tranchard.cron_monitor_api.event_listener.events.check_events.critical';
}
