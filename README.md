# Cron Monitor API Bundle

This package is the server side for the cron monitor system.

Install the bundle in your server app as you're used to do and declare the configuration like the following.

## Configuration
```yaml
parameters:
    mailer_default_sender: "no-reply@your-mta"

tranchard_cron_monitor_api:
    secret: '%cron-monitor-secret%'
    user_provider: YOUR-SERVICE-ID
    notification_system: YOUR-NOTIFICATION-SYSTEM-SERVICE-ID
    monitoring:
        Dummy-Project:
            environments:
                prod:
                    cron:
                        default:
                            checkers:
                                threshold:
                                    max_failed: 1
                                    duration_interval: 3600
                                duration:
                                    max_execution_duration: ~
                                    duration_interval: 3600
                                lock:
                                    use_cron_tokens: true
                                    duration_interval: 3600
                                critical:
                                    enabled: true
                staging:
                    cron:
                        default:
                            checkers:
                                threshold:
                                    max_failed: 1
                                    duration_interval: 7200
                                duration:
                                    max_execution_duration: ~
                                    duration_interval: 7200
                                lock:
                                    use_cron_tokens: true
                                    duration_interval: 900
                                critical:
                                    enabled: true

```
* As you may have noticed, the project name declared in the client must match the node name under the monitoring node.
* Foreach environment, you declare every single command you want to monitor and you specify a default setting as fallback.
* You also need to import some routes to make it works
```yaml
tranchard_cron_monitor_api:
    resource: "@TranchardCronMonitorApiBundle/Resources/routing/public.yaml"
    prefix:   /api

tranchard_cron_monitor_internal:
    resource: "@TranchardCronMonitorApiBundle/Resources/routing/internal.yaml"
    prefix:   /internal
```
* The first route are public route, the other one should only be available through your private network. It's a route to get the status
* You must create a user provider that implements the `UserProviderInterface`, and your user entity class must implement `UserInterface`. These two interfaces are provided in the bundle.
* Finally, you need to declare a default sender email and provide a service that implements the `NotificationSystemInterface`. The bundle provides a mailer with the service id `Tranchard\CronMonitorApiBundle\Services\Notification\Mailer`

* Optional: the secret node is to secure the exchange between the client and the server. It's nullable.

## Checkers
4 checkers are provided by default, and each of them has its own configuration settings.

* DurationChecker is designed to be notified if the cron has taken more than a specified amount of time (+ eventually a % of tolerance), or you can choose to auto monitor the duration (using the cron tokens or not).

Here's the full configuration for this checker
```yaml
duration:
    auto_monitor_duration: true|false # Enable the auto monitor duration
    use_cron_tokens: true|false # Refine or not the auto monitor duration based on the command tokens
    duration_tolerance: 0 # 0.10 for 10% for tolerance margin 
    max_execution_duration: ~ # if null, the you must set the auto monitor duration to true
    duration_interval: 7200 # meantime between 2 notifications sent
```

* ThresholdChecker is designed to be notified if a cron has failed more than a defined time during a given interval.

Here's the full configuration for this checker
```yaml
threshold:
    max_failed: 1 # 1 fail allowed
    duration_interval: 3600 # in 3600 seconds
```

* LockChecker is designed to be notified when a cron is locked.

Here's the full configuration for this checker
```yaml
lock:
    use_cron_tokens: true|false # Refine or notthe check based on the command tokens
    duration_interval: 900 # meantime between 2 notifications sent
```

* CriticalChecker is designed to be notified when a cron has a specific exit code. You'll be notified systematically, no matters you were notified 10 minutes before.

Here's the full configuration for this checker
```yaml
critical:
    enabled: true|false # activate this check on this cron or not 
```

## Create your own checker
* Create a service that implements `CheckerInterface` and extends `Checker` abstract class.
* Tag this service with the following tag `tranchard.cron_monitor_api.checkers`. You can set a priority too.
* Declare the new event in `CheckEvents` class
* Update the CheckSubscriber to subscribe to the new event
* If it needs a new status, declare it in the client bundle `CronReporter` model and in the `CronReporter` document in this bundle.
* Add the new status to the enumeration to make it valid during form validation
* Update the edit action of the `CronReporterController` to use your new event. 
