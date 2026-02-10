<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class RecommendedEditorialDto
{
    /**
     * @param array<int, array<string, mixed>> $signatures
     * @param array<string, string>            $shots
     */
    public function __construct(
        public string $type,
        public string $editorialId,
        public array $signatures,
        public string $editorial,
        public string $title,
        public array $shots,
        public string $photo,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'editorialId' => $this->editorialId,
            'signatures' => $this->signatures,
            'editorial' => $this->editorial,
            'title' => $this->title,
            'shots' => $this->shots,
            'photo' => $this->photo,
        ];
    }
}
