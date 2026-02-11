<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Chain\Resolver;

use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use App\Orchestrator\Chain\Resolver\MultimediaResolver;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureId;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\Multimedia\Video;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Multimedia\ResourceId;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(MultimediaResolver::class)]
class MultimediaResolverTest extends TestCase
{
    private QueryMultimediaClient&MockObject $queryMultimediaClient;
    private QueryMultimediaOpeningClient&MockObject $queryMultimediaOpeningClient;
    private MultimediaOrchestratorHandler&MockObject $multimediaTypeOrchestratorHandler;
    private LoggerInterface&MockObject $logger;
    private MultimediaResolver $resolver;

    protected function setUp(): void
    {
        $this->queryMultimediaClient = $this->createMock(QueryMultimediaClient::class);
        $this->queryMultimediaOpeningClient = $this->createMock(QueryMultimediaOpeningClient::class);
        $this->multimediaTypeOrchestratorHandler = $this->createMock(MultimediaOrchestratorHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->resolver = new MultimediaResolver(
            $this->queryMultimediaClient,
            $this->queryMultimediaOpeningClient,
            $this->multimediaTypeOrchestratorHandler,
            $this->logger,
        );
    }

    #[Test]
    public function getMultimediaIdShouldReturnIdForPhotoExist(): void
    {
        $multimediaId = new MultimediaId('photo-123');

        $photoExist = $this->createMock(PhotoExist::class);
        $photoExist->method('id')->willReturn($multimediaId);

        $result = $this->resolver->getMultimediaId($photoExist);

        static::assertSame($multimediaId, $result);
    }

    #[Test]
    public function getMultimediaIdShouldReturnPhotoIdForVideoWithPhotoExist(): void
    {
        $multimediaId = new MultimediaId('video-photo-456');

        $photoExist = $this->createMock(PhotoExist::class);
        $photoExist->method('id')->willReturn($multimediaId);

        $video = $this->createMock(Video::class);
        $video->method('photo')->willReturn($photoExist);

        $result = $this->resolver->getMultimediaId($video);

        static::assertSame($multimediaId, $result);
    }

    #[Test]
    public function getMultimediaIdShouldReturnPhotoIdForWidgetWithPhotoExist(): void
    {
        $multimediaId = new MultimediaId('widget-photo-789');

        $photoExist = $this->createMock(PhotoExist::class);
        $photoExist->method('id')->willReturn($multimediaId);

        $widget = $this->createMock(Widget::class);
        $widget->method('photo')->willReturn($photoExist);

        $result = $this->resolver->getMultimediaId($widget);

        static::assertSame($multimediaId, $result);
    }

    #[Test]
    public function getMultimediaIdShouldReturnNullForUnsupportedType(): void
    {
        $multimedia = $this->createMock(Multimedia::class);

        $result = $this->resolver->getMultimediaId($multimedia);

        static::assertNull($result);
    }

    #[Test]
    public function addAsyncMultimediaShouldAddPromiseToResolveData(): void
    {
        $multimediaId = new MultimediaId('photo-123');

        $photoExist = $this->createMock(PhotoExist::class);
        $photoExist->method('id')->willReturn($multimediaId);

        $promiseMock = 'promise-placeholder';

        $this->queryMultimediaClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($multimediaId, true)
            ->willReturn($promiseMock);

        $resolveData = [];
        $result = $this->resolver->addAsyncMultimedia($photoExist, $resolveData);

        static::assertArrayHasKey('multimedia', $result);
        static::assertCount(1, $result['multimedia']);
        static::assertSame($promiseMock, $result['multimedia'][0]);
    }

    #[Test]
    public function addAsyncMultimediaShouldNotAddWhenMultimediaIdIsNull(): void
    {
        $multimedia = $this->createMock(Multimedia::class);

        $this->queryMultimediaClient
            ->expects(static::never())
            ->method('findMultimediaById');

        $resolveData = ['existingKey' => 'existingValue'];
        $result = $this->resolver->addAsyncMultimedia($multimedia, $resolveData);

        static::assertSame($resolveData, $result);
        static::assertArrayNotHasKey('multimedia', $result);
    }

    #[Test]
    public function fetchOpeningShouldAddMultimediaOpeningToResolveData(): void
    {
        $multimediaId = 'opening-multimedia-123';
        $expectedHandlerResult = ['123' => ['opening' => 'data']];

        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn($multimediaId);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('opening')->willReturn($opening);

        $multimedia = $this->createMock(AbstractMultimedia::class);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($multimediaId)
            ->willReturn($multimedia);

        $this->multimediaTypeOrchestratorHandler
            ->expects(static::once())
            ->method('handler')
            ->with($multimedia)
            ->willReturn($expectedHandlerResult);

        $resolveData = [];
        $result = $this->resolver->fetchOpening($editorial, $resolveData);

        static::assertArrayHasKey('multimediaOpening', $result);
        static::assertSame($expectedHandlerResult, $result['multimediaOpening']);
    }

    #[Test]
    public function fetchOpeningShouldReturnUnchangedWhenOpeningIsEmpty(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('');

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('opening')->willReturn($opening);

        $this->queryMultimediaOpeningClient
            ->expects(static::never())
            ->method('findMultimediaById');

        $this->multimediaTypeOrchestratorHandler
            ->expects(static::never())
            ->method('handler');

        $resolveData = ['existingKey' => 'existingValue'];
        $result = $this->resolver->fetchOpening($editorial, $resolveData);

        static::assertSame($resolveData, $result);
        static::assertArrayNotHasKey('multimediaOpening', $result);
    }

    #[Test]
    public function fetchOpeningShouldLogWarningOnOrchestratorTypeNotExist(): void
    {
        $multimediaId = 'opening-multimedia-456';
        $exceptionMessage = 'Orchestrator unsupported-type not exist';

        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn($multimediaId);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('opening')->willReturn($opening);

        $multimedia = $this->createMock(AbstractMultimedia::class);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($multimediaId)
            ->willReturn($multimedia);

        $this->multimediaTypeOrchestratorHandler
            ->expects(static::once())
            ->method('handler')
            ->with($multimedia)
            ->willThrowException(new OrchestratorTypeNotExistException($exceptionMessage));

        $this->logger
            ->expects(static::once())
            ->method('warning')
            ->with($exceptionMessage);

        $resolveData = [];
        $result = $this->resolver->fetchOpening($editorial, $resolveData);

        static::assertIsArray($result);
        static::assertArrayNotHasKey('multimediaOpening', $result);
    }

    #[Test]
    public function fetchMetaImageShouldAddResourceAndOpeningForMultimediaPhoto(): void
    {
        $metaImageId = 'meta-image-123';

        $resourceId = $this->createMock(ResourceId::class);
        $resourceId->method('id')->willReturn('resource-789');

        $multimediaPhoto = $this->createMock(MultimediaPhoto::class);
        $multimediaPhoto->method('resourceId')->willReturn($resourceId);

        $photo = $this->createMock(Photo::class);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('metaImage')->willReturn($metaImageId);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($metaImageId)
            ->willReturn($multimediaPhoto);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findPhotoById')
            ->with($resourceId)
            ->willReturn($photo);

        $resolveData = [];
        $result = $this->resolver->fetchMetaImage($editorial, $resolveData);

        static::assertArrayHasKey('multimediaOpening', $result);
        static::assertArrayHasKey($metaImageId, $result['multimediaOpening']);
        static::assertSame($photo, $result['multimediaOpening'][$metaImageId]['resource']);
        static::assertSame($multimediaPhoto, $result['multimediaOpening'][$metaImageId]['opening']);
    }

    #[Test]
    public function fetchMetaImageShouldReturnUnchangedWhenNotMultimediaPhoto(): void
    {
        $metaImageId = 'meta-image-456';

        $multimedia = $this->createMock(AbstractMultimedia::class);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('metaImage')->willReturn($metaImageId);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($metaImageId)
            ->willReturn($multimedia);

        $this->queryMultimediaOpeningClient
            ->expects(static::never())
            ->method('findPhotoById');

        $resolveData = ['existingKey' => 'existingValue'];
        $result = $this->resolver->fetchMetaImage($editorial, $resolveData);

        static::assertSame($resolveData, $result);
    }

    #[Test]
    public function fetchMetaImageShouldReturnUnchangedWhenMetaImageIsEmpty(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $editorial->method('metaImage')->willReturn('');

        $this->queryMultimediaOpeningClient
            ->expects(static::never())
            ->method('findMultimediaById');

        $this->queryMultimediaOpeningClient
            ->expects(static::never())
            ->method('findPhotoById');

        $resolveData = ['existingKey' => 'existingValue'];
        $result = $this->resolver->fetchMetaImage($editorial, $resolveData);

        static::assertSame($resolveData, $result);
    }

    #[Test]
    public function fetchBodyTagPhotosShouldReturnPhotos(): void
    {
        $pictureId = 'picture-123';
        $membershipPictureId = 'membership-picture-456';

        $bodyTagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodyTagPictureId->method('id')->willReturn($pictureId);

        $bodyTagPicture = $this->createMock(BodyTagPicture::class);
        $bodyTagPicture->method('id')->willReturn($bodyTagPictureId);

        $membershipPictureIdMock = $this->createMock(BodyTagPictureId::class);
        $membershipPictureIdMock->method('id')->willReturn($membershipPictureId);

        $bodyTagPictureMembership = $this->createMock(BodyTagPictureMembership::class);
        $bodyTagPictureMembership->method('id')->willReturn($membershipPictureIdMock);

        $bodyTagMembershipCard = $this->createMock(BodyTagMembershipCard::class);
        $bodyTagMembershipCard->method('bodyTagPictureMembership')->willReturn($bodyTagPictureMembership);

        $photo1 = $this->createMock(Photo::class);
        $photo2 = $this->createMock(Photo::class);

        $body = $this->createMock(Body::class);
        $body->expects(static::exactly(2))
            ->method('bodyElementsOf')
            ->willReturnCallback(function (string $class) use ($bodyTagPicture, $bodyTagMembershipCard): array {
                if (BodyTagPicture::class === $class) {
                    return [$bodyTagPicture];
                }

                if (BodyTagMembershipCard::class === $class) {
                    return [$bodyTagMembershipCard];
                }

                return [];
            });

        $callCount = 0;
        $this->queryMultimediaClient
            ->expects(static::exactly(2))
            ->method('findPhotoById')
            ->willReturnCallback(function (string $id) use ($pictureId, $membershipPictureId, $photo1, $photo2, &$callCount) {
                ++$callCount;
                if ($pictureId === $id) {
                    return $photo1;
                }

                if ($membershipPictureId === $id) {
                    return $photo2;
                }

                return null;
            });

        $result = $this->resolver->fetchBodyTagPhotos($body);

        static::assertCount(2, $result);
        static::assertArrayHasKey($pictureId, $result);
        static::assertSame($photo1, $result[$pictureId]);
        static::assertArrayHasKey($membershipPictureId, $result);
        static::assertSame($photo2, $result[$membershipPictureId]);
    }

    #[Test]
    public function fetchBodyTagPhotosShouldLogErrorOnException(): void
    {
        $pictureId = 'failing-picture-123';
        $errorMessage = 'Photo not found';

        $bodyTagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodyTagPictureId->method('id')->willReturn($pictureId);

        $bodyTagPicture = $this->createMock(BodyTagPicture::class);
        $bodyTagPicture->method('id')->willReturn($bodyTagPictureId);

        $body = $this->createMock(Body::class);
        $body->expects(static::exactly(2))
            ->method('bodyElementsOf')
            ->willReturnCallback(function (string $class) use ($bodyTagPicture): array {
                if (BodyTagPicture::class === $class) {
                    return [$bodyTagPicture];
                }

                return [];
            });

        $this->queryMultimediaClient
            ->expects(static::once())
            ->method('findPhotoById')
            ->with($pictureId)
            ->willThrowException(new \RuntimeException($errorMessage));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with($errorMessage);

        $result = $this->resolver->fetchBodyTagPhotos($body);

        static::assertEmpty($result);
    }
}
