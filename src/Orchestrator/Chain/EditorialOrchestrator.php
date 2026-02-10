<?php

/**
 * @copyright
 */

namespace App\Orchestrator\Chain;

use App\Application\DataTransformer\Apps\AppsDataTransformer;
use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use App\Application\DataTransformer\Apps\Media\MediaDataTransformerHandler;
use App\Application\DataTransformer\Apps\MultimediaDataTransformer;
use App\Application\DataTransformer\Apps\RecommendedEditorialsDataTransformer;
use App\Application\DataTransformer\Apps\StandfirstDataTransformer;
use App\Application\DataTransformer\BodyDataTransformer;
use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Infrastructure\Async\AsyncBatchCollectorInterface;
use App\Exception\EditorialNotPublishedYetException;
use App\Infrastructure\Enum\SitesEnum;
use App\Infrastructure\Trait\MultimediaTrait;
use App\Infrastructure\Trait\UrlGeneratorTrait;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialBlog;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Editorial\Exceptions\MultimediaDataTransformerNotFoundException;
use Ec\Infrastructure\Client\Exceptions\InvalidBodyException;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use Ec\Membership\Infrastructure\Client\Http\QueryMembershipClient;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\QueryTagClient;
use Ec\Tag\Domain\Model\Tag;
use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class EditorialOrchestrator implements EditorialOrchestratorInterface
{
    use UrlGeneratorTrait;
    use MultimediaTrait;

    public const ASYNC = true;
    public const TWITTER_TYPES = [EditorialBlog::EDITORIAL_TYPE];
    public const UNWRAPPED = true;

    public function __construct(
        private readonly QueryLegacyClient $queryLegacyClient,
        private readonly QueryEditorialClient $queryEditorialClient,
        private readonly QuerySectionClient $querySectionClient,
        private readonly QueryMultimediaClient $queryMultimediaClient,
        private readonly AppsDataTransformer $detailsAppsDataTransformer,
        private readonly QueryTagClient $queryTagClient,
        private readonly BodyDataTransformer $bodyDataTransformer,
        private readonly UriFactoryInterface $uriFactory,
        private readonly QueryMembershipClient $queryMembershipClient,
        private readonly LoggerInterface $logger,
        private readonly JournalistsDataTransformer $journalistsDataTransformer,
        private readonly QueryJournalistClient $queryJournalistClient,
        private readonly JournalistFactory $journalistFactory,
        private readonly MultimediaDataTransformer $multimediaDataTransformer,
        private readonly StandfirstDataTransformer $standFirstDataTransformer,
        private readonly RecommendedEditorialsDataTransformer $recommendedEditorialsDataTransformer,
        private readonly QueryMultimediaOpeningClient $queryMultimediaOpeningClient,
        private readonly MediaDataTransformerHandler $mediaDataTransformerHandler,
        private readonly MultimediaOrchestratorHandler $multimediaTypeOrchestratorHandler,
        private readonly AsyncBatchCollectorInterface $asyncBatchCollector,
        string $extension,
    ) {
        $this->setExtension($extension);
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

        [$promise, $links] = $this->getPromiseMembershipLinks($editorial, $section->siteId());

        /** @var array<string, array<string, array<string, mixed|array<string>>>> $resolveData */
        $resolveData = [];
        $resolveData['multimedia'] = [];
        $resolveData['multimediaOpening'] = [];

        $resolveData['insertedNews'] = [];
        /** @var BodyTagInsertedNews[] $insertedNews */
        $insertedNews = $editorial->body()->bodyElementsOf(BodyTagInsertedNews::class);
        foreach ($insertedNews as $insertedNew) {
            $idInserted = $insertedNew->editorialId()->id();

            /** @var Editorial $insertedEditorials */
            $insertedEditorials = $this->queryEditorialClient->findEditorialById($idInserted);
            if ($insertedEditorials->isVisible()) {
                /** @var Section $sectionInserted */
                $sectionInserted = $this->querySectionClient->findSectionById($insertedEditorials->sectionId());

                $insertedAliasIds = [];
                /** @var Signature $signature */
                foreach ($insertedEditorials->signatures()->getArrayCopy() as $signature) {
                    $aliasId = $signature->id()->id();
                    $insertedAliasIds[] = $aliasId;
                    $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);
                    $this->asyncBatchCollector->add(
                        'principal',
                        'journalist_ins_' . $idInserted . '_' . $aliasId,
                        fn () => $this->queryJournalistClient->findJournalistByAliasId($aliasIdModel, self::ASYNC),
                    );
                }

                if (!empty($insertedEditorials->multimedia()->id()->id())) {
                    /** @var array<string, array<int|string, array<int|string, array<int|string, array<int|string, mixed>>>|AbstractMultimedia|Promise>> $resolveData */
                    $resolveData = $this->getAsyncMultimedia($insertedEditorials->multimedia(), $resolveData);
                    $multimediaId = $insertedEditorials->multimedia()->id()->id();
                } else {
                    $resolveData = $this->getMetaImage($insertedEditorials, $resolveData); // @phpstan-ignore argument.type
                    $multimediaId = $insertedEditorials->metaImage();
                }

                $resolveData['insertedNews'][$idInserted] = [
                    'editorial' => $insertedEditorials,
                    'section' => $sectionInserted,
                    'signatures' => [],
                    'signatureAliasIds' => $insertedAliasIds,
                    'multimediaId' => $multimediaId,
                ];
            }
        }

        $resolveData['recommendedEditorials'] = [];
        $recommendedEditorials = $editorial->recommendedEditorials();
        $recommendedNews = [];
        /** @var EditorialId $recommendedEditorialId */
        foreach ($recommendedEditorials->editorialIds() as $recommendedEditorialId) {
            try {
                $idRecommended = $recommendedEditorialId->id();

                /** @var Editorial $recommendedEditorial */
                $recommendedEditorial = $this->queryEditorialClient->findEditorialById($idRecommended);
                if ($recommendedEditorial->isVisible()) {
                    /** @var Section $sectionInserted */
                    $sectionInserted = $this->querySectionClient->findSectionById($recommendedEditorial->sectionId());

                    $recommendedAliasIds = [];
                    /** @var Signature $signature */
                    foreach ($recommendedEditorial->signatures()->getArrayCopy() as $signature) {
                        $aliasId = $signature->id()->id();
                        $recommendedAliasIds[] = $aliasId;
                        $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);
                        $this->asyncBatchCollector->add(
                            'principal',
                            'journalist_rec_' . $idRecommended . '_' . $aliasId,
                            fn () => $this->queryJournalistClient->findJournalistByAliasId($aliasIdModel, self::ASYNC),
                        );
                    }

                    if (!empty($recommendedEditorial->multimedia()->id()->id())) {
                        /** @var array<string, array<int|string, array<int|string, array<int|string, array<int|string, mixed>>>|AbstractMultimedia|Promise>> $resolveData */
                        $resolveData = $this->getAsyncMultimedia($recommendedEditorial->multimedia(), $resolveData);
                        $multimediaId = $recommendedEditorial->multimedia()->id()->id();
                    } else {
                        $resolveData = $this->getMetaImage($recommendedEditorial, $resolveData); // @phpstan-ignore argument.type
                        $multimediaId = $recommendedEditorial->metaImage();
                    }

                    $resolveData['recommendedEditorials'][$idRecommended] = [
                        'editorial' => $recommendedEditorial,
                        'section' => $sectionInserted,
                        'signatures' => [],
                        'signatureAliasIds' => $recommendedAliasIds,
                        'multimediaId' => $multimediaId,
                    ];
                    $recommendedNews[] = $recommendedEditorial;
                }
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
                continue;
            }
        }

        /** @var array{multimedia?: array<string, array<int, Promise>>} $resolveData */
        $resolveData = $this->getAsyncMultimedia($editorial->multimedia(), $resolveData); // @phpstan-ignore argument.type

        $this->accumulateOpening($editorial);
        $this->accumulateComments($id);

        $editorialTags = $editorial->tags()->getArrayCopy();
        foreach ($editorialTags as $tag) {
            $tagId = $tag->id();
            $this->asyncBatchCollector->add(
                'principal',
                'tag_' . $tagId,
                fn () => $this->queryTagClient->findTagById($tagId, self::ASYNC),
            );
        }

        $editorialSignatures = $editorial->signatures()->getArrayCopy();
        $hasTwitter = \in_array($editorial->editorialType(), self::TWITTER_TYPES);
        foreach ($editorialSignatures as $signature) {
            $aliasId = $signature->id()->id();
            $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);
            $this->asyncBatchCollector->add(
                'principal',
                'journalist_' . $aliasId,
                fn () => $this->queryJournalistClient->findJournalistByAliasId($aliasIdModel, self::ASYNC),
            );
        }

        $body = $editorial->body();
        /** @var BodyTagPicture[] $bodyTagPictures */
        $bodyTagPictures = $body->bodyElementsOf(BodyTagPicture::class);
        /** @var BodyTagMembershipCard[] $bodyTagMembershipCards */
        $bodyTagMembershipCards = $body->bodyElementsOf(BodyTagMembershipCard::class);
        $this->accumulatePhotos($bodyTagPictures, $bodyTagMembershipCards);

        $this->asyncBatchCollector->settle('principal');

        if (!empty($resolveData['multimedia'])
            && !($editorial->multimedia() instanceof Widget)
        ) {
            $resolveData['multimedia'] = Utils::settle($resolveData['multimedia'])
                ->then($this->createCallback([$this, 'fulfilledMultimedia']))
                ->wait(self::UNWRAPPED);
        }

        $resolveData['multimediaOpening'] = $this->resolveOpeningMultimedia();
        $resolveData['photoFromBodyTags'] = $this->resolvePhotos($bodyTagPictures, $bodyTagMembershipCards);

        $this->resolveSubEditorialSignatures($resolveData['insertedNews'], 'ins');
        $this->resolveSubEditorialSignatures($resolveData['recommendedEditorials'], 'rec');

        $tags = [];
        foreach ($editorialTags as $tag) {
            $tagId = $tag->id();
            if ($this->asyncBatchCollector->has('principal', 'tag_' . $tagId)) {
                /** @var Tag $resolvedTag */
                $resolvedTag = $this->asyncBatchCollector->get('principal', 'tag_' . $tagId);
                $tags[] = $resolvedTag;
            }
        }

        $editorialResult = $this->detailsAppsDataTransformer->write(
            $editorial,
            $section,
            $tags
        )->read();

        $editorialResult['countComments'] = $this->resolveComments();

        $editorialResult['signatures'] = [];
        foreach ($editorialSignatures as $signature) {
            $aliasId = $signature->id()->id();
            if ($this->asyncBatchCollector->has('principal', 'journalist_' . $aliasId)) {
                /** @var Journalist $journalist */
                $journalist = $this->asyncBatchCollector->get('principal', 'journalist_' . $aliasId);
                $result = $this->journalistsDataTransformer->write($aliasId, $journalist, $section, $hasTwitter)->read();
                if (!empty($result)) {
                    $editorialResult['signatures'][] = $result;
                }
            }
        }

        /** @var array{multimedia: array<string, mixed>} $resolveData */
        $resolveData['membershipLinkCombine'] = $this->resolvePromiseMembershipLinks($promise, $links);

        $editorialResult['body'] = $this->bodyDataTransformer->execute(
            $editorial->body(),
            $resolveData
        );

        /** @var array{multimedia: array<string, array<string, mixed>>} $resolveData */
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

    /**
     * @param array<string, array{editorial: Editorial, section: Section, signatures: array<mixed>, signatureAliasIds: array<string>, multimediaId: string}> $subEditorials
     */
    private function resolveSubEditorialSignatures(array &$subEditorials, string $prefix): void
    {
        foreach ($subEditorials as $subEditorialId => &$data) {
            /** @var Section $subSection */
            $subSection = $data['section'];

            $signatures = [];
            /** @var array<string> $aliasIds */
            $aliasIds = $data['signatureAliasIds'];
            foreach ($aliasIds as $aliasId) {
                $key = 'journalist_' . $prefix . '_' . $subEditorialId . '_' . $aliasId;
                if ($this->asyncBatchCollector->has('principal', $key)) {
                    /** @var Journalist $journalist */
                    $journalist = $this->asyncBatchCollector->get('principal', $key);
                    $result = $this->journalistsDataTransformer->write($aliasId, $journalist, $subSection, false)->read();
                    if (!empty($result)) {
                        $signatures[] = $result;
                    }
                }
            }
            $data['signatures'] = $signatures;
        }
    }

    public function canOrchestrate(): string
    {
        return 'editorial';
    }

    private function accumulateOpening(NewsBase $editorial): void
    {
        $opening = $editorial->opening();
        if (!empty($opening->multimediaId())) {
            $openingMultimediaId = $opening->multimediaId();
            $this->asyncBatchCollector->add(
                'principal',
                'opening_multimedia',
                fn () => $this->queryMultimediaOpeningClient->findMultimediaById($openingMultimediaId, self::ASYNC),
            );
        }
    }

    private function accumulateComments(string $editorialId): void
    {
        $this->asyncBatchCollector->add(
            'principal',
            'comments',
            fn () => $this->queryLegacyClient->findCommentsByEditorialId($editorialId, self::ASYNC),
        );
    }

    /**
     * @param BodyTagPicture[]        $bodyTagPictures
     * @param BodyTagMembershipCard[] $bodyTagMembershipCards
     */
    private function accumulatePhotos(array $bodyTagPictures, array $bodyTagMembershipCards): void
    {
        foreach ($bodyTagPictures as $bodyTagPicture) {
            $photoId = $bodyTagPicture->id()->id();
            $this->asyncBatchCollector->add(
                'principal',
                'photo_' . $photoId,
                fn () => $this->queryMultimediaClient->findPhotoById($photoId, self::ASYNC),
            );
        }

        foreach ($bodyTagMembershipCards as $bodyTagMembershipCard) {
            $photoId = $bodyTagMembershipCard->bodyTagPictureMembership()->id()->id();
            $this->asyncBatchCollector->add(
                'principal',
                'photo_' . $photoId,
                fn () => $this->queryMultimediaClient->findPhotoById($photoId, self::ASYNC),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveOpeningMultimedia(): array
    {
        if (!$this->asyncBatchCollector->has('principal', 'opening_multimedia')) {
            return [];
        }

        try {
            /** @var AbstractMultimedia $multimedia */
            $multimedia = $this->asyncBatchCollector->get('principal', 'opening_multimedia');

            return $this->multimediaTypeOrchestratorHandler->handler($multimedia);
        } catch (OrchestratorTypeNotExistException|InvalidBodyException $e) {
            $this->logger->warning($e->getMessage());

            return [];
        }
    }

    /**
     * @param BodyTagPicture[]        $bodyTagPictures
     * @param BodyTagMembershipCard[] $bodyTagMembershipCards
     *
     * @return array<mixed>
     */
    private function resolvePhotos(array $bodyTagPictures, array $bodyTagMembershipCards): array
    {
        $result = [];
        foreach ($bodyTagPictures as $bodyTagPicture) {
            $photoId = $bodyTagPicture->id()->id();
            if ($this->asyncBatchCollector->has('principal', 'photo_' . $photoId)) {
                $result[$photoId] = $this->asyncBatchCollector->get('principal', 'photo_' . $photoId);
            }
        }

        foreach ($bodyTagMembershipCards as $bodyTagMembershipCard) {
            $photoId = $bodyTagMembershipCard->bodyTagPictureMembership()->id()->id();
            if ($this->asyncBatchCollector->has('principal', 'photo_' . $photoId)) {
                $result[$photoId] = $this->asyncBatchCollector->get('principal', 'photo_' . $photoId);
            }
        }

        return $result;
    }

    private function resolveComments(): int
    {
        if (!$this->asyncBatchCollector->has('principal', 'comments')) {
            return 0;
        }

        /** @var array{options: array{totalrecords?:int}} $comments */
        $comments = $this->asyncBatchCollector->get('principal', 'comments');

        return $comments['options']['totalrecords'] ?? 0;
    }

    /**
     * @return array<mixed>
     */
    private function getLinksOfBodyTagMembership(Body $body): array
    {
        $linksData = [];

        $bodyElementsMembership = $body->bodyElementsOf(BodyTagMembershipCard::class);
        /** @var BodyTagMembershipCard $bodyElement */
        foreach ($bodyElementsMembership as $bodyElement) {
            /** @var MembershipCardButton $button */
            foreach ($bodyElement->buttons()->buttons() as $button) {
                $linksData[] = $button->urlMembership();
                $linksData[] = $button->url();
            }
        }

        return $linksData;
    }

    /**
     * @return array<mixed>
     */
    private function getLinksFromBody(Body $body): array
    {
        return $this->getLinksOfBodyTagMembership($body);
    }

    /**
     * @return array{0: Promise|null, 1: array<int, string>}
     */
    private function getPromiseMembershipLinks(Editorial $editorial, string $siteId): array
    {
        $linksData = $this->getLinksFromBody($editorial->body());

        $links = [];
        $uris = [];
        /** @var string $membershipLink */
        foreach ($linksData as $membershipLink) {
            $uris[] = $this->uriFactory->createUri($membershipLink);
            /** array<int, string> $links */
            $links[] = $membershipLink;
        }

        /** @var Promise $promise */
        $promise = $this->queryMembershipClient->getMembershipUrl(
            $editorial->id()->id(),
            $uris,
            SitesEnum::getEncodenameById($siteId),
            true
        );

        return [$promise, $links];
    }

    /**
     * @param array<int, string> $links
     *
     * @return array<mixed>
     */
    private function resolvePromiseMembershipLinks(?Promise $promise, array $links): array
    {
        $membershipLinkResult = [];
        if ($promise) {
            try {
                /** @var array<string, mixed> $membershipLinkResult */
                $membershipLinkResult = $promise->wait();
            } catch (\Throwable $throwable) {
                return [];
            }
        }

        if (empty($membershipLinkResult)) {
            return [];
        }

        return array_combine($links, $membershipLinkResult);
    }

    /**
     * @param array<string, array<int|string, array<int|string, mixed>|AbstractMultimedia|Promise>> $resolveData
     *
     * @return array<string, array<string, array<int, Promise>>>
     */
    private function getAsyncMultimedia(Multimedia $multimedia, array $resolveData): array
    {
        $multimediaId = $this->getMultimediaId($multimedia);

        if (null !== $multimediaId) {
            $resolveData['multimedia'][] = $this->queryMultimediaClient->findMultimediaById($multimediaId, self::ASYNC);
        }

        return $resolveData; // @phpstan-ignore return.type
    }

    /**
     * @param array<string, array<string, array<int, Promise>>> $resolveData
     *
     * @return array<string, array<int, Promise|AbstractMultimedia>>
     */
    private function getMetaImage(Editorial $editorial, array $resolveData): array
    {
        if (!empty($editorial->metaImage())) {
            /** @var Multimedia $multimedia */
            $multimedia = $this->queryMultimediaOpeningClient->findMultimediaById($editorial->metaImage());
            if (!$multimedia instanceof MultimediaPhoto) {
                return $resolveData; // @phpstan-ignore return.type
            }

            $resource = $this->queryMultimediaOpeningClient->findPhotoById($multimedia->resourceId());
            $resolveData['multimediaOpening'][$editorial->metaImage()]['resource'] = $resource;
            $resolveData['multimediaOpening'][$editorial->metaImage()]['opening'] = $multimedia;
        }

        return $resolveData; // @phpstan-ignore return.type
    }

    /**
     * @param array<string, string> ...$parameters
     */
    protected function createCallback(callable $callable, ...$parameters): \Closure
    {
        return static function ($element) use ($callable, $parameters) {
            return $callable($element, ...$parameters);
        };
    }

    /**
     * @param array<string, mixed> $promises
     *
     * @return array<string, \Ec\Multimedia\Domain\Model\Multimedia>
     */
    protected function fulfilledMultimedia(array $promises): array
    {
        $result = [];
        /** @var array<string, string> $promise */
        foreach ($promises as $promise) {
            if (Promise::FULFILLED === $promise['state']) {
                /** @var \Ec\Multimedia\Domain\Model\Multimedia $multimedia */
                $multimedia = $promise['value'];
                $result[$multimedia->id()] = $multimedia;
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, array<string, mixed>> > $resolveData
     *
     * @return ?array<string, mixed>
     *
     * @throws MultimediaDataTransformerNotFoundException
     */
    protected function transformMultimedia(Editorial $editorial, array $resolveData): ?array
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
