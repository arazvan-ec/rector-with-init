<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver;

use App\Orchestrator\Chain\Resolver\Trait\RelatedEditorialTrait;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\RecommendedEditorials;
use Ec\Section\Domain\Model\QuerySectionClient;
use Psr\Log\LoggerInterface;

final readonly class RecommendedEditorialsResolver
{
    use RelatedEditorialTrait;

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

        /** @var EditorialId $recommendedEditorialId */
        foreach ($recommendedEditorials->editorialIds() as $recommendedEditorialId) {
            try {
                $editorialId = $recommendedEditorialId->id();

                $result = $this->resolveRelatedEditorial(
                    $editorialId,
                    $this->queryEditorialClient,
                    $this->querySectionClient,
                    $this->signatureResolver,
                    $this->multimediaResolver,
                    $resolveData,
                );

                if (null !== $result['data']) {
                    $resolveData = $result['resolveData'];
                    $resolveData['recommendedEditorials'][$editorialId] = $result['data']->toResolveArray();
                    $editorials[] = $result['editorial'];
                }
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
                continue;
            }
        }

        return ['editorials' => $editorials, 'resolveData' => $resolveData];
    }
}
