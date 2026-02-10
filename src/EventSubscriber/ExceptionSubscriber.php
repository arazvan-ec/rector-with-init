<?php

/**
 * @copyright
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class ExceptionSubscriber implements EventSubscriberInterface
{
    use CacheControl;

    private const SMAXAGE = 64000;
    private const MAXAGE = 60;
    private const STALE_WHILE_REVALIDATE = 60;
    private const STALE_IF_ERROR = 259200;

    private const KERNEL_DEV = 'dev';
    private string $appEnv;

    public function __construct(string $appEnv)
    {
        $this->appEnv = $appEnv;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', -128],
            ],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($this->isProductionEnvironment()) {
            $throwable = $event->getThrowable();
            $response = $this->createErrorResponse($throwable);

            $this->setHttpCache(
                $response,
                self::SMAXAGE,
                self::MAXAGE,
                self::STALE_WHILE_REVALIDATE,
                self::STALE_IF_ERROR
            );

            $event->setResponse($response);
        }
    }

    private function isProductionEnvironment(): bool
    {
        return self::KERNEL_DEV !== $this->appEnv;
    }

    private function createErrorResponse(\Throwable $throwable): JsonResponse
    {
        $message = [
            'errors' => [$throwable->getMessage()],
        ];

        return new JsonResponse($message, $this->getStatusCode($throwable));
    }

    private function getStatusCode(\Throwable $throwable): int
    {
        $statusCode = $throwable->getCode();
        if (method_exists($throwable, 'getStatusCode')) {
            $statusCode = $throwable->getStatusCode();
        }

        if ($this->isValidHttpStatusCode($statusCode)) {
            return $statusCode;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function isValidHttpStatusCode(int $statusCode): bool
    {
        return $statusCode >= 100 && $statusCode <= 599;
    }
}
