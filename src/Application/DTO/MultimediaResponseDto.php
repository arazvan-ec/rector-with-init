<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class MultimediaResponseDto
{
    /**
     * @param \stdClass|object $shots
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $caption,
        public object $shots,
        public string $photo,
    ) {
    }

    /**
     * @return array{id: string, type: string, caption: string, shots: object, photo: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'caption' => $this->caption,
            'shots' => $this->shots,
            'photo' => $this->photo,
        ];
    }
}
