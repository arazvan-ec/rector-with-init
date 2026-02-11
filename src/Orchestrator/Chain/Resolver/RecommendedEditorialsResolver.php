<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver;

use App\Application\DTO\RelatedEditorialData;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\RecommendedEditorials;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;

final readonly class RecommendedEditorialsResolver
{
    private const ASYNC = true;

    public function __construct(
        private QueryEditorialClient $queryEditorialClient,
        private QuerySectionClient $querySectionClient,
        private SignatureResolver $signatureResolver,
        private MultimediaResolver $multimediaResolver,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array{editorials: Editorial[], resolveData: array<string, mixed>}
     */
    public function resolve(RecommendedEditorials $recommendedEditorials, array $resolveData): array
    {
        $resolveData['recommendedEditorials'] = [];
        $editorials = [];

        $editorialIds = [];
        /** @var EditorialId $recommendedEditorialId */
        foreach ($recommendedEditorials->editorialIds() as $recommendedEditorialId) {
            $editorialIds[] = $recommendedEditorialId->id();
        }

        if (empty($editorialIds)) {
            return ['editorials' => $editorials, 'resolveData' => $resolveData];
        }

        $editorialPromises = [];
        foreach ($editorialIds as $editorialId) {
            $editorialPromises[$editorialId] = $this->queryEditorialClient->findEditorialById($editorialId, self::ASYNC);
        }

        /** @var array<string, array{state: string, value?: mixed}> $settledEditorials */
        $settledEditorials = Utils::settle($editorialPromises)->wait(true);

        $visibleEditorials = [];
        foreach ($settledEditorials as $editorialId => $result) {
            if (Promise::FULFILLED !== $result['state']) {
                $this->logger->error("Failed to fetch recommended editorial {$editorialId}");
                continue;
            }

            /** @var Editorial $editorial */
            $editorial = $result['value'];
            if ($editorial->isVisible()) {
                $visibleEditorials[$editorialId] = $editorial;
            }
        }

        if (empty($visibleEditorials)) {
            return ['editorials' => $editorials, 'resolveData' => $resolveData];
        }

        $sectionPromises = [];
        foreach ($visibleEditorials as $editorialId => $editorial) {
            $sectionPromises[$editorialId] = $this->querySectionClient->findSectionById(
                $editorial->sectionId(),
                self::ASYNC,
            );
        }

        /** @var array<string, array{state: string, value?: mixed}> $settledSections */
        $settledSections = Utils::settle($sectionPromises)->wait(true);

        foreach ($visibleEditorials as $editorialId => $editorial) {
            try {
                if (Promise::FULFILLED !== ($settledSections[$editorialId]['state'] ?? null)) {
                    continue;
                }

                /** @var Section $section */
                $section = $settledSections[$editorialId]['value'];

                $signatures = $this->signatureResolver->resolveSignatures($editorial->signatures(), $section);

                if (!empty($editorial->multimedia()->id()->id())) {
                    $resolveData = $this->multimediaResolver->addAsyncMultimedia($editorial->multimedia(), $resolveData);
                    $multimediaId = $editorial->multimedia()->id()->id();
                } else {
                    $resolveData = $this->multimediaResolver->fetchMetaImage($editorial, $resolveData);
                    $multimediaId = $editorial->metaImage();
                }

                $data = new RelatedEditorialData($editorial, $section, $signatures, $multimediaId);
                $resolveData['recommendedEditorials'][$editorialId] = $data->toResolveArray();
                $editorials[] = $editorial;
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
                continue;
            }
        }

        return ['editorials' => $editorials, 'resolveData' => $resolveData];
    }
}
