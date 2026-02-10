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

                $signatures = [];
                /** @var Signature $signature */
                foreach ($insertedEditorials->signatures()->getArrayCopy() as $signature) {
                    $result = $this->retrieveAliasFormat($signature->id()->id(), $sectionInserted);
                    if (!empty($result)) {
                        $signatures[] = $result;
                    }
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
                    'signatures' => $signatures,
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

                    $signatures = [];
                    /** @var Signature $signature */
                    foreach ($recommendedEditorial->signatures()->getArrayCopy() as $signature) {
                        $result = $this->retrieveAliasFormat($signature->id()->id(), $sectionInserted);
                        if (!empty($result)) {
                            $signatures[] = $result;
                        }
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
                        'signatures' => $signatures,
                        'multimediaId' => $multimediaId,
                    ];
                    $recommendedNews[] = $recommendedEditorial;
                }
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
                continue;
            }
        }

        /** @var array<string, ?array{multimedia: array<string, array<int, Promise>>}> $resolveData */
        $resolveData = $this->getOpening($editorial, $resolveData);
        /** @var array{multimedia?: array<string, array<int, Promise>>} $resolveData */
        $resolveData = $this->getAsyncMultimedia($editorial->multimedia(), $resolveData); // @phpstan-ignore argument.type
        if (!empty($resolveData['multimedia'])
            && !($editorial->multimedia() instanceof Widget)
        ) {
            $resolveData['multimedia'] = Utils::settle($resolveData['multimedia'])
                ->then($this->createCallback([$this, 'fulfilledMultimedia']))
                ->wait(self::UNWRAPPED);
        }
        $resolveData['photoFromBodyTags'] = $this->retrievePhotosFromBodyTags($editorial->body());

        $tags = [];
        foreach ($editorial->tags()->getArrayCopy() as $tag) {
            try {
                /** @var Tag[] $tags */
                $tags[] = $this->queryTagClient->findTagById($tag->id());
            } catch (\Throwable $exception) {
                continue;
            }
        }

        $editorialResult = $this->detailsAppsDataTransformer->write(
            $editorial,
            $section,
            $tags
        )->read();

        /** @var array{options: array{totalrecords?:int}} $comments */
        $comments = $this->queryLegacyClient->findCommentsByEditorialId($id);
        $editorialResult['countComments'] = $comments['options']['totalrecords'] ?? 0;
        $editorialResult['signatures'] = [];

        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $hasTwitter = \in_array($editorial->editorialType(), self::TWITTER_TYPES);
            $result = $this->retrieveAliasFormat(
                $signature->id()->id(),
                $section,
                $hasTwitter
            );
            if (!empty($result)) {
                $editorialResult['signatures'][] = $result;
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
     * @return array<mixed>
     */
    private function retrieveAliasFormat(string $aliasId, Section $section, bool $hasTwitter = false): array
    {
        $signature = [];
        $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);

        try {
            /** @var Journalist $journalist */
            $journalist = $this->queryJournalistClient->findJournalistByAliasId($aliasIdModel);

            $signature = $this->journalistsDataTransformer->write($aliasId, $journalist, $section, $hasTwitter)->read();
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }

        return $signature;
    }

    public function canOrchestrate(): string
    {
        return 'editorial';
    }

    /**
     * @return array<mixed>
     */
    private function retrievePhotosFromBodyTags(Body $body): array
    {
        $result = [];
        /** @var BodyTagPicture[] $arrayOfBodyTagPicture */
        $arrayOfBodyTagPicture = $body->bodyElementsOf(BodyTagPicture::class);
        foreach ($arrayOfBodyTagPicture as $bodyTagPicture) {
            $result = $this->addPhotoToArray($bodyTagPicture->id()->id(), $result);
        }

        /** @var BodyTagMembershipCard[] $arrayOfBodyTagMembershipCard */
        $arrayOfBodyTagMembershipCard = $body->bodyElementsOf(BodyTagMembershipCard::class);
        foreach ($arrayOfBodyTagMembershipCard as $bodyTagMembershipCard) {
            $id = $bodyTagMembershipCard->bodyTagPictureMembership()->id()->id();
            $result = $this->addPhotoToArray($id, $result);
        }

        return $result;
    }

    /**
     * @param array<mixed> $result
     *
     * @return array<mixed>
     */
    private function addPhotoToArray(string $id, array $result): array
    {
        try {
            $photo = $this->queryMultimediaClient->findPhotoById($id);
            $result[$id] = $photo;
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }

        return $result;
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
     * @param array<string, array<int|string, array<int|string, mixed>|AbstractMultimedia|Promise>> $resolveData
     *
     * @return array<string, array<int, Promise|AbstractMultimedia>>
     */
    private function getOpening(Editorial $editorial, array $resolveData): array
    {
        /** @var NewsBase $editorial */
        $opening = $editorial->opening();
        if (!empty($opening->multimediaId())) {
            try {
                /** @var AbstractMultimedia $multimedia */
                $multimedia = $this->queryMultimediaOpeningClient->findMultimediaById($opening->multimediaId());
                $resolveData['multimediaOpening'] = $this->multimediaTypeOrchestratorHandler->handler($multimedia);
            } catch (OrchestratorTypeNotExistException|InvalidBodyException $e) {
                $this->logger->warning($e->getMessage());
            }
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
