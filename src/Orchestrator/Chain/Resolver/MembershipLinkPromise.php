<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver;

use Http\Promise\Promise;

final readonly class MembershipLinkPromise
{
    /**
     * @param array<int, string> $originalLinks
     */
    public function __construct(
        public ?Promise $promise,
        public array $originalLinks,
    ) {
    }

    public function hasLinks(): bool
    {
        return !empty($this->originalLinks);
    }
}
