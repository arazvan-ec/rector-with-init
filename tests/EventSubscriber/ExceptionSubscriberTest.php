<?php

/**
 * @copyright
 */

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\ExceptionSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
#[CoversClass(ExceptionSubscriber::class)]
class ExceptionSubscriberTest extends TestCase
{
    #[Test]
    public function getSubscribedEventsShouldReturnExpectedArray(): void
    {
        $expected = ['kernel.exception' => [['onKernelException', -128]]];

        static::assertSame($expected, ExceptionSubscriber::getSubscribedEvents());
    }

    #[Test]
    public function onKernelExceptionShouldNotChangeResponseInDevEnvironment(): void
    {
        $exception = new \Exception('Test message');
        $subscriber = new ExceptionSubscriber('dev');
        $event = $this->createExceptionEvent($exception);

        $subscriber->onKernelException($event);

        static::assertNull($event->getResponse());
    }

    #[Test]
    #[DataProvider('productionEnvironmentsProvider')]
    public function onKernelExceptionShouldCreateJsonResponseInProductionEnvironments(string $environment): void
    {
        $message = 'Test error message';
        $exception = new \Exception($message);
        $event = $this->createExceptionEvent($exception);

        $subscriber = new ExceptionSubscriber($environment);
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        static::assertInstanceOf(JsonResponse::class, $response);
        static::assertSame('{"errors":["'.$message.'"]}', $response->getContent());
    }

    #[Test]
    #[DataProvider('httpExceptionsProvider')]
    public function onKernelExceptionShouldUseHttpExceptionStatusCode(int $statusCode): void
    {
        $message = 'HTTP exception message';
        $exception = new HttpException($statusCode, $message);
        $event = $this->createExceptionEvent($exception);

        $subscriber = new ExceptionSubscriber('prod');
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        static::assertNotNull($response);
        static::assertSame($statusCode, $response->getStatusCode());
        static::assertSame('{"errors":["'.$message.'"]}', $response->getContent());
    }

    #[Test]
    #[DataProvider('invalidStatusCodesProvider')]
    public function onKernelExceptionShouldUseInternalServerErrorForInvalidStatusCodes(int $invalidCode): void
    {
        $exception = new \Exception('Test message', $invalidCode);
        $event = $this->createExceptionEvent($exception);

        $subscriber = new ExceptionSubscriber('prod');
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        static::assertNotNull($response);
        static::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    #[Test]
    public function onKernelExceptionShouldHandleExceptionWithValidStatusCode(): void
    {
        $validStatusCode = 422;
        $exception = new \Exception('Validation error', $validStatusCode);
        $event = $this->createExceptionEvent($exception);

        $subscriber = new ExceptionSubscriber('prod');
        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        static::assertNotNull($response);
        static::assertSame($validStatusCode, $response->getStatusCode());
    }

    /**
     * @return array<string, array<string>>
     */
    public static function productionEnvironmentsProvider(): array
    {
        return [
            'prod environment' => ['prod'],
            'test environment' => ['pre'],
        ];
    }

    /**
     * @return array<string, array<int>>
     */
    public static function httpExceptionsProvider(): array
    {
        return [
            '100 status' => [100],
            'Bad Request' => [400],
            'Unauthorized' => [401],
            'Forbidden' => [403],
            'Not Found' => [404],
            'Method Not Allowed' => [405],
            'Conflict' => [409],
            'Unprocessable Entity' => [422],
            'Internal Server Error' => [500],
            'Bad Gateway' => [502],
            'Service Unavailable' => [503],
            '599 status' => [599],
        ];
    }

    /**
     * @return array<int, array<int>>
     */
    public static function invalidStatusCodesProvider(): array
    {
        return [
            [0],
            [99],
            [600],
        ];
    }

    private function createExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }
}
