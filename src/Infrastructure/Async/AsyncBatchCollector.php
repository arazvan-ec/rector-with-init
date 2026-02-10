<?php

declare(strict_types=1);

namespace App\Infrastructure\Async;

use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;

final class AsyncBatchCollector implements AsyncBatchCollectorInterface
{
    /** @var array<string, array<string, callable>> */
    private array $callables = [];

    /** @var array<string, array<string, mixed>> */
    private array $results = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function add(string $group, string $key, callable $callable): void
    {
        $this->callables[$group][$key] ??= $callable;
    }

    public function settle(string $group): void
    {
        $promises = [];
        foreach ($this->callables[$group] ?? [] as $key => $callable) {
            $promises[$key] = $callable();
        }

        $settled = Utils::settle($promises)->wait(true);

        $this->results[$group] ??= [];
        foreach ($settled as $key => $result) {
            if (Promise::FULFILLED === $result['state']) {
                $this->results[$group][$key] = $result['value'];
            } else {
                $error = $result['reason'] ?? 'Unknown error';
                $errorMessage = $error instanceof \Throwable ? $error->getMessage() : (string) $error;

                $this->logger->warning('AsyncBatchCollector: promise rejected', [
                    'group' => $group,
                    'key' => $key,
                    'error' => $errorMessage,
                ]);
            }
        }

        unset($this->callables[$group]);
    }

    public function get(string $group, string $key): mixed
    {
        if (!$this->has($group, $key)) {
            throw new \RuntimeException(sprintf(
                'AsyncBatchCollector: key "%s" not found in group "%s". Either the group was not settled, the key was not added, or the promise was rejected.',
                $key,
                $group,
            ));
        }

        return $this->results[$group][$key];
    }

    public function getGroup(string $group): array
    {
        return $this->results[$group] ?? [];
    }

    public function has(string $group, string $key): bool
    {
        return isset($this->results[$group][$key]);
    }
}
