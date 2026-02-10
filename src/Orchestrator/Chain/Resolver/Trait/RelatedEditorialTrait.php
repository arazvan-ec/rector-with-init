<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver\Trait;

use App\Application\DTO\RelatedEditorialData;
use App\Orchestrator\Chain\Resolver\MultimediaResolver;
use App\Orchestrator\Chain\Resolver\SignatureResolver;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;

trait RelatedEditorialTrait
{
    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array{data: ?RelatedEditorialData, editorial: ?Editorial, resolveData: array<string, mixed>}
     */
    private function resolveRelatedEditorial(
        string $editorialId,
        QueryEditorialClient $queryEditorialClient,
        QuerySectionClient $querySectionClient,
        SignatureResolver $signatureResolver,
        MultimediaResolver $multimediaResolver,
        array $resolveData,
    ): array {
        /** @var Editorial $editorial */
        $editorial = $queryEditorialClient->findEditorialById($editorialId);

        if (!$editorial->isVisible()) {
            return ['data' => null, 'editorial' => null, 'resolveData' => $resolveData];
        }

        /** @var Section $section */
        $section = $querySectionClient->findSectionById($editorial->sectionId());

        $signatures = $signatureResolver->resolveSignatures($editorial->signatures(), $section);

        if (!empty($editorial->multimedia()->id()->id())) {
            $resolveData = $multimediaResolver->addAsyncMultimedia($editorial->multimedia(), $resolveData);
            $multimediaId = $editorial->multimedia()->id()->id();
        } else {
            $resolveData = $multimediaResolver->fetchMetaImage($editorial, $resolveData);
            $multimediaId = $editorial->metaImage();
        }

        $data = new RelatedEditorialData($editorial, $section, $signatures, $multimediaId);

        return ['data' => $data, 'editorial' => $editorial, 'resolveData' => $resolveData];
    }
}
