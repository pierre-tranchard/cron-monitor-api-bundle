<?php

namespace Tranchard\CronMonitorApiBundle\Services\Notification;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Services\Security\UserInterface;

class Mailer implements NotificationSystemInterface
{

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * Mailer constructor.
     *
     * @param \Swift_Mailer   $mailer
     * @param Router          $router
     * @param EngineInterface $engine
     * @param array           $parameters
     */
    public function __construct(\Swift_Mailer $mailer, Router $router, EngineInterface $engine, array $parameters = [])
    {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->engine = $engine;
        $this->parameters = $parameters;
    }


    /**
     * @inheritdoc
     */
    public function sendCronMonitorFailureMessage(
        array $recipients,
        CronReporter $cronReporter,
        \DateTime $from,
        \DateTime $to,
        int $countFailed = 0,
        UserInterface $sender = null
    ): bool {
        $recipients = $this->getRecipients($recipients);
        $template = $this->engine->render(
            $this->parameters['templates']['cron_monitor_failure'],
            ['cronReporter' => $cronReporter, 'countFailed' => $countFailed, 'from' => $from, 'to' => $to]
        );

        return $this->sendMessage($template, $recipients, $sender);
    }

    /**
     * @inheritdoc
     */
    public function sendCronMonitorDurationExceededMessage(
        array $recipients,
        CronReporter $cronReporter,
        float $adjustedAverageDuration,
        float $averageDuration,
        UserInterface $sender = null
    ): bool {
        $recipients = $this->getRecipients($recipients);
        $template = $this->engine->render(
            $this->parameters['templates']['cron_monitor_duration_exceeded'],
            [
                'cronReporter'            => $cronReporter,
                'adjustedAverageDuration' => $adjustedAverageDuration,
                'averageDuration'         => $averageDuration,
            ]
        );

        return $this->sendMessage($template, $recipients, $sender);
    }

    /**
     * @inheritdoc
     */
    public function sendCronMonitorLockedMessage(
        array $recipients,
        CronReporter $cronReporter,
        UserInterface $sender = null
    ): bool {
        $recipients = $this->getRecipients($recipients);
        $template = $this->engine->render(
            $this->parameters['templates']['cron_monitor_lock'],
            [
                'cronReporter' => $cronReporter,
            ]
        );

        return $this->sendMessage($template, $recipients, $sender);
    }

    /**
     * @inheritdoc
     */
    public function sendCronMonitorCriticalMessage(
        array $recipients,
        CronReporter $cronReporter,
        UserInterface $sender = null
    ): bool {
        $recipients = $this->getRecipients($recipients);
        $template = $this->engine->render(
            $this->parameters['templates']['cron_monitor_critical'],
            [
                'cronReporter' => $cronReporter,
            ]
        );

        return $this->sendMessage($template, $recipients, $sender);
    }

    /**
     * @param string             $renderedTemplate
     * @param string|string[]    $toEmail
     * @param UserInterface|null $sender
     *
     * @return bool
     */
    protected function sendMessage(string $renderedTemplate, $toEmail, UserInterface $sender = null)
    {
        $fromEmail = !is_null($sender) ? $sender->getEmail() : $this->parameters['from_email'];

        $renderedLines = explode("\n", trim($renderedTemplate));
        $subject = $renderedLines[0];
        $body = implode("\n", array_slice($renderedLines, 1));
        /** @var \Swift_Message $message */
        $message = new \Swift_Message();
        $message
            ->setSubject($subject)
            ->setFrom($fromEmail)
            ->setTo($toEmail)
            ->setBody($body, 'text/html');

        return $this->mailer->send($message) > 0;
    }

    /**
     * @param array $recipients
     *
     * @return array
     */
    protected function getRecipients(array $recipients): array
    {
        $users = [];
        foreach ($recipients as $user) {
            /** @var UserInterface $user */
            $users[$user->getEmail()] = $user->getUsername();
        }

        return $users;
    }
}
