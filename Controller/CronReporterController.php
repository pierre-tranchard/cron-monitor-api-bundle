<?php

namespace Tranchard\CronMonitorApiBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Tranchard\CronMonitorApiBundle\Document\CronReporter;
use Tranchard\CronMonitorApiBundle\Document\Notification;
use Tranchard\CronMonitorApiBundle\EventListener\Event\CheckEvent;
use Tranchard\CronMonitorApiBundle\EventListener\Events\CheckEvents;
use Tranchard\CronMonitorApiBundle\Exception\HttpException;
use Tranchard\CronMonitorApiBundle\Form\Type\CronReporterType;
use Tranchard\CronMonitorApiBundle\Repository\CronReporterRepository;
use Tranchard\CronMonitorApiBundle\Repository\NotificationRepository;

class CronReporterController
{

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var CronReporterRepository
     */
    protected $repository;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * CronReporterController constructor.
     *
     * @param SerializerInterface      $serializer
     * @param DocumentManager          $documentManager
     * @param FormFactoryInterface     $formFactory
     * @param CronReporterRepository   $cronReporterRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param array                    $configuration
     */
    public function __construct(
        SerializerInterface $serializer,
        DocumentManager $documentManager,
        FormFactoryInterface $formFactory,
        CronReporterRepository $cronReporterRepository,
        EventDispatcherInterface $eventDispatcher,
        array $configuration
    ) {
        $this->serializer = $serializer;
        $this->documentManager = $documentManager;
        $this->formFactory = $formFactory;
        $this->repository = $cronReporterRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->configuration = $configuration;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        $cronReporters = $this->repository
            ->getBy(
                $request->query->get('criteria', []),
                $request->query->get('order', ['createdAt' => 'DESC']),
                $request->query->getInt('limit', 10),
                $request->query->getInt('offset', 0),
                $request->query->get('distinctField', null)
            )->toArray();

        return new JsonResponse(
            $this->serializer->serialize(
                [
                    'success' => true,
                    'total'   => $this->repository->count(),
                    'items'   => count($cronReporters),
                    'data'    => $cronReporters,
                ],
                'json',
                ['groups' => ['list']]
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function statusAction(Request $request)
    {
        $projects = array_keys($this->configuration);
        $environments = array_unique(
            array_merge(
                ...array_map(
                       function ($element) {
                           return array_keys($element['environments']);
                       },
                       array_values($this->configuration)
                   )
            )
        );
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->documentManager->getRepository(Notification::class);
        $cronReporters = [];
        foreach ($environments as $environment) {
            foreach ($projects as $project) {
                $notifications = $notificationRepository->getNotifications($project, $environment);
                $jobs = iterator_to_array($notifications, false);
                foreach ($jobs as $job) {
                    if (!isset($cronReporters[$environment][$project])) {
                        $cronReporters[$environment][$project] = [];
                    }
                    /** @var \MongoDate|null $lastCheck */
                    $lastCheck = $job['lastNotificationSent'] ?? null;
                    if (!is_null($lastCheck)) {
                        $lastCheck = $lastCheck->toDateTime()->format(\DATE_W3C);
                    }
                    $cronReporters[$environment][$project] = array_merge(
                        $cronReporters[$environment][$project],
                        [
                            $job['jobName'] => [
                                $job['type'] => [
                                    'status'     => CronReporter::STATUS_SUCCESS,
                                    'last_check' => $lastCheck,
                                ],
                            ],
                        ]
                    );
                }

            }
        }

        return new JsonResponse(
            $this->serializer->serialize(
                [
                    'success' => true,
                    'data'    => $cronReporters,
                ],
                'json',
                ['groups' => ['list']]
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $cronReporter = new CronReporter();
        $form = $this->formFactory->create(CronReporterType::class, $cronReporter);

        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $this->documentManager->persist($cronReporter);
            $this->documentManager->flush();

            return new JsonResponse(
                $this->serializer->serialize($cronReporter, 'json', ['groups' => ['display']]),
                JsonResponse::HTTP_CREATED, [], true
            );
        }

        return new JsonResponse(
            $this->serializer->serialize(
                ['success' => false, 'errors' => (string)$form->getErrors(true, true)],
                'json'
            ),
            JsonResponse::HTTP_BAD_REQUEST,
            [],
            true
        );
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return JsonResponse
     * @throws HttpException
     */
    public function editAction(Request $request, string $id)
    {
        /** @var CronReporter|null $cronReporter */
        $cronReporter = $this->repository->find($id);
        if (is_null($cronReporter)) {
            throw HttpException::cronReporterNotFoundException($id);
        }
        $form = $this->formFactory->create(CronReporterType::class, $cronReporter);

        if ($form->submit($request->request->get($form->getName()), false) && $form->isValid()) {
            $this->documentManager->persist($cronReporter);
            $this->documentManager->flush();

            if ($cronReporter->isLocked()) {
                $this->eventDispatcher->dispatch(
                    CheckEvents::LOCK,
                    new CheckEvent($request, $cronReporter)
                );
            }
            if ($cronReporter->hasSucceeded()) {
                $this->eventDispatcher->dispatch(
                    CheckEvents::DURATION,
                    new CheckEvent($request, $cronReporter)
                );
            }
            if ($cronReporter->hasFailed()) {
                $this->eventDispatcher->dispatch(
                    CheckEvents::THRESHOLD,
                    new CheckEvent($request, $cronReporter)
                );
            }
            if ($cronReporter->isCritical()) {
                $this->eventDispatcher->dispatch(
                    CheckEvents::CRITICAL,
                    new CheckEvent($request, $cronReporter)
                );
            }

            return new JsonResponse(
                $this->serializer->serialize($cronReporter, 'json', ['groups' => ['display']]),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        }

        return new JsonResponse(
            $this->serializer->serialize(
                ['success' => false, 'errors' => (string)$form->getErrors(true, true)],
                'json'
            ),
            JsonResponse::HTTP_BAD_REQUEST,
            [],
            true
        );
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return JsonResponse
     * @throws HttpException
     */
    public function displayAction(Request $request, string $id)
    {
        $cronReporter = $this->repository->find($id);
        if (is_null($cronReporter)) {
            throw HttpException::cronReporterNotFoundException($id);
        }

        return new JsonResponse(
            $this->serializer->serialize($cronReporter, 'json', ['groups' => ['display']]),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }
}
