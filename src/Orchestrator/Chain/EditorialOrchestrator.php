<?php

/**
 * @copyright
 */

namespace App\Orchestrator\Chain;

use App\Application\DataTransformer\Apps\AppsDataTransformer;
use App\Application\DataTransformer\Apps\Media\MediaDataTransformerHandler;
use App\Application\DataTransformer\Apps\MultimediaDataTransformer;
use App\Application\DataTransformer\Apps\RecommendedEditorialsDataTransformer;
use App\Application\DataTransformer\Apps\StandfirstDataTransformer;
use App\Application\DataTransformer\BodyDataTransformer;
use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Exception\EditorialNotPublishedYetException;
use App\Orchestrator\Chain\Resolver\InsertedNewsResolver;
use App\Orchestrator\Chain\Resolver\MembershipLinkResolver;
use App\Orchestrator\Chain\Resolver\MultimediaResolver;
use App\Orchestrator\Chain\Resolver\RecommendedEditorialsResolver;
use App\Orchestrator\Chain\Resolver\SignatureResolver;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialBlog;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\QueryTagClient;
use Ec\Tag\Domain\Model\Tag;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class EditorialOrchestrator implements EditorialOrchestratorInterface
{
    public const TWITTER_TYPES = [EditorialBlog::EDITORIAL_TYPE];

    public function __construct(
        private readonly QueryLegacyClient $queryLegacyClient,
        private readonly QueryEditorialClient $queryEditorialClient,
        private readonly QuerySectionClient $querySectionClient,
        private readonly AppsDataTransformer $detailsAppsDataTransformer,
        private readonly QueryTagClient $queryTagClient,
        private readonly BodyDataTransformer $bodyDataTransformer,
        private readonly LoggerInterface $logger,
        private readonly MultimediaDataTransformer $multimediaDataTransformer,
        private readonly StandfirstDataTransformer $standFirstDataTransformer,
        private readonly RecommendedEditorialsDataTransformer $recommendedEditorialsDataTransformer,
        private readonly MediaDataTransformerHandler $mediaDataTransformerHandler,
        private readonly SignatureResolver $signatureResolver,
        private readonly MembershipLinkResolver $membershipLinkResolver,
        private readonly MultimediaResolver $multimediaResolver,
        private readonly InsertedNewsResolver $insertedNewsResolver,
        private readonly RecommendedEditorialsResolver $recommendedEditorialsResolver,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function execute(Request $request): array
    {
        /** @var string $id */
        $id = $request->get('id');

        /** @var NewsBase $editorial */
        $editorial = $this->queryEditorialClient->findEditorialById($id);

        if (null === $editorial->sourceEditorial()) {
            return $this->queryLegacyClient->findEditorialById($id);
        }

        if (!$editorial->isVisible()) {
            throw new EditorialNotPublishedYetException();
        }

        /** @var Section $section */
        $section = $this->querySectionClient->findSectionById($editorial->sectionId());

        $membershipPromise = $this->membershipLinkResolver->createPromise($editorial, $section->siteId());

        /** @var array<string, mixed> $resolveData */
        $resolveData = ['multimedia' => [], 'multimediaOpening' => []];

        $resolveData = $this->insertedNewsResolver->resolve($editorial->body(), $resolveData)['resolveData'];

        $recommendedResult = $this->recommendedEditorialsResolver->resolve(
            $editorial->recommendedEditorials(),
            $resolveData
        );
        $resolveData = $recommendedResult['resolveData'];
        $recommendedNews = $recommendedResult['editorials'];

        $resolveData = $this->multimediaResolver->fetchOpening($editorial, $resolveData);
        $resolveData = $this->multimediaResolver->addAsyncMultimedia($editorial->multimedia(), $resolveData);
        $resolveData = $this->multimediaResolver->settlePromises(
            $resolveData,
            $editorial->multimedia() instanceof Widget
        );
        $resolveData['photoFromBodyTags'] = $this->multimediaResolver->fetchBodyTagPhotos($editorial->body());

        $tags = $this->fetchTags($editorial);

        $editorialResult = $this->detailsAppsDataTransformer->write($editorial, $section, $tags)->read();

        /** @var array{options: array{totalrecords?:int}} $comments */
        $comments = $this->queryLegacyClient->findCommentsByEditorialId($id);
        $editorialResult['countComments'] = $comments['options']['totalrecords'] ?? 0;

        $editorialResult['signatures'] = $this->signatureResolver->resolveSignatures(
            $editorial->signatures(),
            $section,
            \in_array($editorial->editorialType(), self::TWITTER_TYPES)
        );

        $resolveData['membershipLinkCombine'] = $this->membershipLinkResolver->resolve($membershipPromise);

        $editorialResult['body'] = $this->bodyDataTransformer->execute($editorial->body(), $resolveData);
        $editorialResult['multimedia'] = $this->transformMultimedia($editorial, $resolveData);
        $editorialResult['standfirst'] = $this->standFirstDataTransformer
            ->write($editorial->standFirst())
            ->read();

        /** @var array<string, array<string, array<string, mixed>>> $resolveData */
        $editorialResult['recommendedEditorials'] = $this->recommendedEditorialsDataTransformer
            ->write($recommendedNews, $resolveData)
            ->read();

        return $editorialResult;
    }

    public function canOrchestrate(): string
    {
        return 'editorial';
    }

    /**
     * @return Tag[]
     */
    private function fetchTags(Editorial $editorial): array
    {
        $tags = [];
        foreach ($editorial->tags()->getArrayCopy() as $tag) {
            try {
                /** @var Tag[] $tags */
                $tags[] = $this->queryTagClient->findTagById($tag->id());
            } catch (\Throwable) {
                continue;
            }
        }

        return $tags;
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return ?array<string, mixed>
     */
    private function transformMultimedia(Editorial $editorial, array $resolveData): ?array
    {
        /** @var NewsBase $editorial */
        if (!empty($resolveData['multimediaOpening'])) {
            return $this->mediaDataTransformerHandler->execute(
                $resolveData['multimediaOpening'],
                $editorial->opening()
            );
        }

        if (!empty($resolveData['multimedia'])) {
            return $this->multimediaDataTransformer
                ->write($resolveData['multimedia'], $editorial->multimedia())
                ->read();
        }

        return null;
    }
}
