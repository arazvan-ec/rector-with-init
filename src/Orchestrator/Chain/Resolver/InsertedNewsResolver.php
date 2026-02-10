<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver;

use App\Orchestrator\Chain\Resolver\Trait\RelatedEditorialTrait;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Psr\Log\LoggerInterface;

final readonly class InsertedNewsResolver
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
     * @return array{resolveData: array<string, mixed>}
     */
    public function resolve(Body $body, array $resolveData): array
    {
        $resolveData['insertedNews'] = [];

        /** @var BodyTagInsertedNews[] $insertedNews */
        $insertedNews = $body->bodyElementsOf(BodyTagInsertedNews::class);

        foreach ($insertedNews as $insertedNew) {
            $editorialId = $insertedNew->editorialId()->id();

            try {
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
                    $resolveData['insertedNews'][$editorialId] = $result['data']->toResolveArray();
                }
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
            }
        }

        return ['resolveData' => $resolveData];
    }
}
