<?php

/**
 * @copyright
 */

namespace App\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
trait CacheControl
{
    private function setHttpCache(Response $response, int $sMaxAge, int $maxAge, int $staleRevalidate, int $staleError): Response
    {
        $date = $this->getCurrentTime();

        $response->setCache([
            'public' => true,
            's_maxage' => $sMaxAge,
            'max_age' => $maxAge,
            'stale_while_revalidate' => $staleRevalidate,
            'stale_if_error' => $staleError,
        ]);

        $response->setLastModified($date);

        $expiresDate = $date->setTimezone(new \DateTimeZone('GMT'));
        $expiresDate = $expiresDate->add(new \DateInterval('PT'.$sMaxAge.'S'));
        $response->setExpires($expiresDate);

        return $response;
    }

    protected function getCurrentTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
