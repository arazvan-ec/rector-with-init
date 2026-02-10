<?php

declare(strict_types=1);

namespace App\Infrastructure\Async;

interface AsyncBatchCollectorInterface
{
    /**
     * Registers a callable that returns a Promise in a named group.
     * If the key already exists in the group, it is NOT overwritten (natural dedup).
     */
    public function add(string $group, string $key, callable $callable): void;

    /**
     * Executes all callables in the group, obtains promises,
     * resolves with Utils::settle(), and stores only fulfilled results.
     * Rejected promises are logged as warnings and excluded from results.
     */
    public function settle(string $group): void;

    /**
     * Gets the resolved result for a key in a group.
     *
     * @throws \RuntimeException if the group has not been settled or the key does not exist/was rejected
     */
    public function get(string $group, string $key): mixed;

    /**
     * Gets all fulfilled results from a group.
     *
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array;

    /**
     * Checks if a key in a group was resolved successfully (fulfilled).
     */
    public function has(string $group, string $key): bool;
}
