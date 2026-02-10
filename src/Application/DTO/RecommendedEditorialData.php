<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;

final readonly class RecommendedEditorialData extends RelatedEditorialData
{
    /**
     * @param array<int, array<string, mixed>> $signatures
     */
    public function __construct(
        Editorial $editorial,
        Section $section,
        array $signatures,
        string $multimediaId,
    ) {
        parent::__construct($editorial, $section, $signatures, $multimediaId);
    }
}
