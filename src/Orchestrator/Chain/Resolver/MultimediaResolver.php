<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver;

use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\Multimedia\Video;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Infrastructure\Client\Exceptions\InvalidBodyException;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;

final readonly class MultimediaResolver
{
    private const ASYNC = true;
    private const UNWRAPPED = true;

    public function __construct(
        private QueryMultimediaClient $queryMultimediaClient,
        private QueryMultimediaOpeningClient $queryMultimediaOpeningClient,
        private MultimediaOrchestratorHandler $multimediaTypeOrchestratorHandler,
        private LoggerInterface $logger,
    ) {
    }

    public function getMultimediaId(Multimedia $multimedia): ?MultimediaId
    {
        if ($multimedia instanceof PhotoExist) {
            return $multimedia->id();
        }

        if (
            ($multimedia instanceof Video || $multimedia instanceof Widget)
            && ($multimedia->photo() instanceof PhotoExist)
        ) {
            return $multimedia->photo()->id();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array<string, mixed>
     */
    public function addAsyncMultimedia(Multimedia $multimedia, array $resolveData): array
    {
        $multimediaId = $this->getMultimediaId($multimedia);

        if (null !== $multimediaId) {
            $resolveData['multimedia'][] = $this->queryMultimediaClient->findMultimediaById($multimediaId, self::ASYNC);
        }

        return $resolveData;
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array<string, mixed>
     */
    public function fetchOpening(Editorial $editorial, array $resolveData): array
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

        return $resolveData;
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array<string, mixed>
     */
    public function fetchMetaImage(Editorial $editorial, array $resolveData): array
    {
        if (!empty($editorial->metaImage())) {
            /** @var AbstractMultimedia $multimedia */
            $multimedia = $this->queryMultimediaOpeningClient->findMultimediaById($editorial->metaImage());
            if (!$multimedia instanceof MultimediaPhoto) {
                return $resolveData;
            }

            $resource = $this->queryMultimediaOpeningClient->findPhotoById($multimedia->resourceId());
            $resolveData['multimediaOpening'][$editorial->metaImage()]['resource'] = $resource;
            $resolveData['multimediaOpening'][$editorial->metaImage()]['opening'] = $multimedia;
        }

        return $resolveData;
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array<string, mixed>
     */
    public function settlePromises(array $resolveData, bool $isWidget): array
    {
        if (!empty($resolveData['multimedia']) && !$isWidget) {
            $resolveData['multimedia'] = Utils::settle($resolveData['multimedia'])
                ->then($this->createFulfilledCallback())
                ->wait(self::UNWRAPPED);
        }

        return $resolveData;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchBodyTagPhotos(Body $body): array
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
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
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

    private function createFulfilledCallback(): \Closure
    {
        return static function (array $promises): array {
            $result = [];
            /** @var array<string, mixed> $promise */
            foreach ($promises as $promise) {
                if (Promise::FULFILLED === $promise['state']) {
                    /** @var \Ec\Multimedia\Domain\Model\Multimedia $multimedia */
                    $multimedia = $promise['value'];
                    $result[$multimedia->id()] = $multimedia;
                }
            }

            return $result;
        };
    }
}
