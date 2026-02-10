<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;

class RelatedEditorialData
{
    /**
     * @param array<int, array<string, mixed>> $signatures
     */
    public function __construct(
        public readonly Editorial $editorial,
        public readonly Section $section,
        public readonly array $signatures,
        public readonly string $multimediaId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toResolveArray(): array
    {
        return [
            'editorial' => $this->editorial,
            'section' => $this->section,
            'signatures' => $this->signatures,
            'multimediaId' => $this->multimediaId,
        ];
    }
}
