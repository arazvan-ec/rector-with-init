<?php

/**
 * @copyright
 */

namespace App\Tests\Ec\Snaapi\Infrastructure\Client\Http;

use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use Http\Mock\Client;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(QueryLegacyClient::class)]
class QueryLegacyClientTest extends TestCase
{
    private const HTTP_HOSTNAME_TEST = 'https://api.elconfidencial.com';

    private Client $httpClient;

    private QueryLegacyClient $queryLegacyClient;

    protected function setUp(): void
    {
        $this->httpClient = new Client();
        $this->queryLegacyClient = new QueryLegacyClient(
            self::HTTP_HOSTNAME_TEST,
            self::HTTP_HOSTNAME_TEST,
            $this->httpClient
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->httpClient, $this->queryLegacyClient);
    }

    #[Test]
    public function findEditorialByIdShouldReturnEditorialArray(): void
    {
        $statusCode = 200;
        $id = '12345';
        $requestUrlExpected = self::HTTP_HOSTNAME_TEST.'/service/content/'.$id.'/';
        $responseData = [];

        /** @var non-empty-string $bodyResponse */
        $bodyResponse = json_encode($responseData, JSON_THROW_ON_ERROR);
        $responseMock = $this->getResponseMock($statusCode, $bodyResponse);
        $this->httpClient->addResponse($responseMock);

        $result = $this->queryLegacyClient->findEditorialById($id);
        $request = $this->httpClient->getLastRequest();

        static::assertSame($responseData, $result);
        static::assertSame($requestUrlExpected, (string) $request->getUri());
    }

    #[Test]
    public function retrieveEditorialShouldCallExecuteWithDefaultValuesAndReturnEditorialModel(): void
    {
        $id = '12345';

        $queryClientMock = $this->getMockBuilder(QueryLegacyClient::class)
            ->setConstructorArgs([
                self::HTTP_HOSTNAME_TEST,
                self::HTTP_HOSTNAME_TEST,
                $this->httpClient,
            ])
            ->onlyMethods(['createRequest', 'execute'])
            ->getMock();

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock->method('then')->willReturn($promiseMock);
        $promiseMock->method('wait')->willReturn([]);

        $requestMock = $this->createMock(RequestInterface::class);
        $queryClientMock->method('createRequest')->willReturn($requestMock);
        $queryClientMock->expects(static::once())
            ->method('execute')
            ->with($requestMock, true, false, 60)
            ->willReturn($promiseMock);

        $queryClientMock->findEditorialById($id);
    }

    private function getResponseMock(int $statusCode, string $body): ResponseInterface
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->expects(static::once())->method('__toString')->willReturn($body);

        $responseMock->expects(static::once())->method('getBody')->willReturn($streamMock);
        $responseMock->method('getStatusCode')->willReturn($statusCode);

        return $responseMock;
    }

    #[Test]
    public function findCommentsEditorialByIdShouldReturnCommentsArray(): void
    {
        $statusCode = 200;
        $id = '12345';
        $requestUrlExpected = self::HTTP_HOSTNAME_TEST.'/service/community/comments/editorial/'.$id.'/0/0/';
        $responseData = [];

        /** @var non-empty-string $bodyResponse */
        $bodyResponse = json_encode($responseData, JSON_THROW_ON_ERROR);
        $responseMock = $this->getResponseMock($statusCode, $bodyResponse);
        $this->httpClient->addResponse($responseMock);

        $result = $this->queryLegacyClient->findCommentsByEditorialId($id);
        $request = $this->httpClient->getLastRequest();

        static::assertSame($responseData, $result);
        static::assertSame($requestUrlExpected, (string) $request->getUri());
    }
}
