<?php

/**
 * @copyright
 */

namespace App\Ec\Snaapi\Infrastructure\Client\Http;

use Ec\Infrastructure\Client\Http\ServiceClient;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class QueryLegacyClient extends ServiceClient
{
    private string $legacyHostHeader;

    public function __construct(
        string $hostname,
        string $legacyHostHeader,
        ?HttpAsyncClient $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?CacheInterface $cacheAdapter = null,
    ) {
        $this->legacyHostHeader = $legacyHostHeader;

        parent::__construct(
            $hostname,
            $client,
            $requestFactory,
            $responseFactory,
            [],
            [],
            '1',
            $cacheAdapter,
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function findEditorialById(
        string $editorialIdString,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array {
        $url = $this->buildUrl("/service/content/{$editorialIdString}/");

        $request = $this->createRequest('GET', $url, [
            'Host' => $this->legacyHostHeader,
        ]);

        /** @var Promise $promise */
        $promise = $this->execute($request, true, $cached, $ttlCache);

        $promise = $promise->then($this->createCallback([$this, 'buildEditorialFromArray'], $request));

        return $async ? $promise : $promise->wait(true); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildEditorialFromArray(ResponseInterface $response, RequestInterface $request): array
    {
        /** @var array<string, mixed> $editorialData */
        $editorialData = json_decode($response->getBody()->__toString(), true);

        return $editorialData;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function findCommentsByEditorialId(
        string $editorialIdString,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array {
        $url = $this->buildUrl("/service/community/comments/editorial/{$editorialIdString}/0/0/");

        $request = $this->createRequest('GET', $url, [
            'Host' => $this->legacyHostHeader,
        ]);

        /** @var Promise $promise */
        $promise = $this->execute($request, true, $cached, $ttlCache);

        $promise = $promise->then($this->createCallback([$this, 'buildEditorialFromArray'], $request));

        return $async ? $promise : $promise->wait(true); // @phpstan-ignore return.type
    }
}
