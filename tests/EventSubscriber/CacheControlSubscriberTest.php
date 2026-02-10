<?php

/**
 * @copyright
 */

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\CacheControlSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
#[CoversClass(CacheControlSubscriber::class)]
class CacheControlSubscriberTest extends TestCase
{
    private const SMAXAGE = 7200;
    private const MAXAGE = 60;
    private const STALE_WHILE_REVALIDATE = 60;
    private const STALE_IF_ERROR = 259200;

    /** @var CacheControlSubscriber|MockObject */
    private CacheControlSubscriber $subscriber;
    private ResponseEvent $event;
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();

        $this->event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            HttpKernelInterface::MAIN_REQUEST,
            $this->response
        );

        $this->subscriber = $this->getMockBuilder(CacheControlSubscriber::class)
            ->onlyMethods(['getCurrentTime'])
            ->getMock();
    }

    protected function tearDown(): void
    {
        unset($this->subscriber, $this->event, $this->response);
    }

    #[Test]
    public function onKernelResponseSetsCacheHeaders(): void
    {
        $dateTime = new \DateTimeImmutable('now');
        $expiresDate = $dateTime->add(new \DateInterval('PT'.self::SMAXAGE.'S'));

        $this->subscriber->expects(static::once())
            ->method('getCurrentTime')
            ->willReturn($dateTime);

        $this->subscriber->onKernelResponse($this->event);

        static::assertTrue($this->response->headers->hasCacheControlDirective('public'));
        static::assertEquals(self::SMAXAGE, $this->response->headers->getCacheControlDirective('s-maxage'));
        static::assertEquals(self::MAXAGE, $this->response->headers->getCacheControlDirective('max-age'));
        static::assertEquals(
            self::STALE_WHILE_REVALIDATE,
            $this->response->headers->getCacheControlDirective('stale-while-revalidate')
        );

        static::assertEquals(self::STALE_IF_ERROR, $this->response->headers->getCacheControlDirective('stale-if-error'));
        /** @var \DateTimeImmutable $lastModified */
        $lastModified = $this->response->getLastModified();
        static::assertEquals($dateTime->getTimestamp(), $lastModified->getTimestamp());
        /** @var \DateTimeImmutable $expires */
        $expires = $this->response->getExpires();
        static::assertEquals($expiresDate->getTimestamp(), $expires->getTimestamp());
    }

    #[Test]
    public function onKernelResponseDoesNotOverrideExistingCacheHeaders(): void
    {
        $cache = 5;
        $this->response->setCache(['s_maxage' => $cache]);

        $this->subscriber->onKernelResponse($this->event);

        static::assertEquals($cache, $this->response->headers->getCacheControlDirective('s-maxage'));
    }
}
