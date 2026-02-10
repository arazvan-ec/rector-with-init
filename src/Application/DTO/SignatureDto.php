<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class SignatureDto
{
    /**
     * @param DepartmentDto[] $departments
     */
    public function __construct(
        public string $journalistId,
        public string $aliasId,
        public string $name,
        public bool $private,
        public string $url,
        public string $photo,
        public array $departments,
        public ?string $twitter = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'journalistId' => $this->journalistId,
            'aliasId' => $this->aliasId,
            'name' => $this->name,
            'private' => $this->private,
            'url' => $this->url,
            'photo' => $this->photo,
            'departments' => array_map(
                static fn (DepartmentDto $dept) => $dept->toArray(),
                $this->departments
            ),
        ];

        if (null !== $this->twitter) {
            $result['twitter'] = $this->twitter;
        }

        return $result;
    }
}
