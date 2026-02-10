<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class SectionDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public string $encodeName,
    ) {
    }

    /**
     * @return array{id: string, name: string, url: string, encodeName: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'encodeName' => $this->encodeName,
        ];
    }
}
