<?php

/**
 * @copyright
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class CacheControlSubscriber implements EventSubscriberInterface
{
    use CacheControl;

    private const SMAXAGE = 7200;
    private const MAXAGE = 60;
    private const STALE_WHILE_REVALIDATE = 60;
    private const STALE_IF_ERROR = 259200;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response->headers->hasCacheControlDirective('s-maxage')) {
            $this->setHttpCache(
                $response,
                self::SMAXAGE,
                self::MAXAGE,
                self::STALE_WHILE_REVALIDATE,
                self::STALE_IF_ERROR
            );
        }
    }
}
