<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class EditorialTitlesDto
{
    public function __construct(
        public string $title,
        public string $preTitle,
        public string $urlTitle,
        public string $mobileTitle,
    ) {
    }

    /**
     * @return array{title: string, preTitle: string, urlTitle: string, mobileTitle: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'preTitle' => $this->preTitle,
            'urlTitle' => $this->urlTitle,
            'mobileTitle' => $this->mobileTitle,
        ];
    }
}
