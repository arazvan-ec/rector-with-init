<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EditorialTypeDto
{
    public function __construct(
        public int|string $id,
        public string $name,
    ) {
    }

    /**
     * @return array{id: int|string, name: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
