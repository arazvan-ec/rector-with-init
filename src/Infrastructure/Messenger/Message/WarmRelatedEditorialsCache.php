<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final readonly class WarmRelatedEditorialsCache
{
    /**
     * @param string[] $editorialIds
     */
    public function __construct(
        public array $editorialIds,
        public string $siteId,
    ) {
    }
}
