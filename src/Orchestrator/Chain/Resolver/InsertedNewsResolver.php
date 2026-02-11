<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver;

use App\Application\DTO\RelatedEditorialData;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;

final readonly class InsertedNewsResolver
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
     * @return array{resolveData: array<string, mixed>}
     */
    public function resolve(Body $body, array $resolveData): array
    {
        $resolveData['insertedNews'] = [];

        /** @var BodyTagInsertedNews[] $insertedNews */
        $insertedNews = $body->bodyElementsOf(BodyTagInsertedNews::class);

        if (empty($insertedNews)) {
            return ['resolveData' => $resolveData];
        }

        $editorialIds = [];
        foreach ($insertedNews as $insertedNew) {
            $editorialIds[] = $insertedNew->editorialId()->id();
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
                $this->logger->error("Failed to fetch inserted news editorial {$editorialId}");
                continue;
            }

            /** @var Editorial $editorial */
            $editorial = $result['value'];
            if ($editorial->isVisible()) {
                $visibleEditorials[$editorialId] = $editorial;
            }
        }

        if (empty($visibleEditorials)) {
            return ['resolveData' => $resolveData];
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
                $resolveData['insertedNews'][$editorialId] = $data->toResolveArray();
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
            }
        }

        return ['resolveData' => $resolveData];
    }
}
