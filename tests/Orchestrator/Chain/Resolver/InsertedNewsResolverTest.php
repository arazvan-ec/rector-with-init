<?php

declare(strict_types=1);

namespace App\Tests\Orchestrator\Chain\Resolver;

use App\Orchestrator\Chain\Resolver\InsertedNewsResolver;
use App\Orchestrator\Chain\Resolver\MultimediaResolver;
use App\Orchestrator\Chain\Resolver\SignatureResolver;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\Signatures;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(InsertedNewsResolver::class)]
class InsertedNewsResolverTest extends TestCase
{
    private QueryEditorialClient&MockObject $queryEditorialClient;
    private QuerySectionClient&MockObject $querySectionClient;
    private SignatureResolver&MockObject $signatureResolver;
    private MultimediaResolver&MockObject $multimediaResolver;
    private LoggerInterface&MockObject $logger;
    private InsertedNewsResolver $resolver;

    protected function setUp(): void
    {
        $this->queryEditorialClient = $this->createMock(QueryEditorialClient::class);
        $this->querySectionClient = $this->createMock(QuerySectionClient::class);
        $this->signatureResolver = $this->createMock(SignatureResolver::class);
        $this->multimediaResolver = $this->createMock(MultimediaResolver::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->resolver = new InsertedNewsResolver(
            $this->queryEditorialClient,
            $this->querySectionClient,
            $this->signatureResolver,
            $this->multimediaResolver,
            $this->logger,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->resolver,
            $this->queryEditorialClient,
            $this->querySectionClient,
            $this->signatureResolver,
            $this->multimediaResolver,
            $this->logger,
        );
    }

    #[Test]
    public function resolveShouldReturnEmptyInsertedNewsWhenBodyHasNoInsertedNews(): void
    {
        $body = $this->createMock(Body::class);
        $body->expects(static::once())
            ->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([]);

        $this->queryEditorialClient->expects(static::never())
            ->method('findEditorialById');

        $resolveData = ['existingKey' => 'existingValue'];
        $result = $this->resolver->resolve($body, $resolveData);

        static::assertSame([], $result['resolveData']['insertedNews']);
        static::assertSame('existingValue', $result['resolveData']['existingKey']);
    }

    #[Test]
    public function resolveShouldReturnInsertedNewsForVisibleEditorials(): void
    {
        $editorialId = 'editorial-123';
        $sectionId = 'section-456';
        $multimediaIdValue = 'multimedia-789';
        $expectedSignatures = [['journalistId' => '1', 'name' => 'Author']];

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $insertedNewsMock = $this->createMock(BodyTagInsertedNews::class);
        $insertedNewsMock->method('editorialId')->willReturn($editorialIdMock);

        $body = $this->createMock(Body::class);
        $body->expects(static::once())
            ->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$insertedNewsMock]);

        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn($multimediaIdValue);

        $multimediaMock = $this->createMock(Multimedia::class);
        $multimediaMock->method('id')->willReturn($multimediaIdMock);

        $signaturesMock = $this->createMock(Signatures::class);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('isVisible')->willReturn(true);
        $editorialMock->method('sectionId')->willReturn($sectionId);
        $editorialMock->method('signatures')->willReturn($signaturesMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);

        $this->queryEditorialClient->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId, true)
            ->willReturn(new FulfilledPromise($editorialMock));

        $sectionMock = $this->createMock(Section::class);

        $this->querySectionClient->expects(static::once())
            ->method('findSectionById')
            ->with($sectionId, true)
            ->willReturn(new FulfilledPromise($sectionMock));

        $this->signatureResolver->expects(static::once())
            ->method('resolveSignatures')
            ->with($signaturesMock, $sectionMock)
            ->willReturn($expectedSignatures);

        $this->multimediaResolver->expects(static::once())
            ->method('addAsyncMultimedia')
            ->with($multimediaMock, static::anything())
            ->willReturnArgument(1);

        $result = $this->resolver->resolve($body, []);

        static::assertArrayHasKey('insertedNews', $result['resolveData']);
        static::assertArrayHasKey($editorialId, $result['resolveData']['insertedNews']);

        $resolvedData = $result['resolveData']['insertedNews'][$editorialId];
        static::assertSame($editorialMock, $resolvedData['editorial']);
        static::assertSame($sectionMock, $resolvedData['section']);
        static::assertSame($expectedSignatures, $resolvedData['signatures']);
        static::assertSame($multimediaIdValue, $resolvedData['multimediaId']);
    }

    #[Test]
    public function resolveShouldSkipNonVisibleEditorials(): void
    {
        $editorialId = 'editorial-hidden';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $insertedNewsMock = $this->createMock(BodyTagInsertedNews::class);
        $insertedNewsMock->method('editorialId')->willReturn($editorialIdMock);

        $body = $this->createMock(Body::class);
        $body->expects(static::once())
            ->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$insertedNewsMock]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('isVisible')->willReturn(false);

        $this->queryEditorialClient->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId, true)
            ->willReturn(new FulfilledPromise($editorialMock));

        $this->querySectionClient->expects(static::never())
            ->method('findSectionById');

        $this->signatureResolver->expects(static::never())
            ->method('resolveSignatures');

        $result = $this->resolver->resolve($body, []);

        static::assertSame([], $result['resolveData']['insertedNews']);
    }

    #[Test]
    public function resolveShouldSkipRejectedEditorialPromises(): void
    {
        $editorialId = 'editorial-failed';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $insertedNewsMock = $this->createMock(BodyTagInsertedNews::class);
        $insertedNewsMock->method('editorialId')->willReturn($editorialIdMock);

        $body = $this->createMock(Body::class);
        $body->expects(static::once())
            ->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$insertedNewsMock]);

        $this->queryEditorialClient->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId, true)
            ->willReturn(new RejectedPromise(new \Exception('Service unavailable')));

        $this->logger->expects(static::once())
            ->method('error')
            ->with("Failed to fetch inserted news editorial {$editorialId}");

        $this->querySectionClient->expects(static::never())
            ->method('findSectionById');

        $result = $this->resolver->resolve($body, []);

        static::assertSame([], $result['resolveData']['insertedNews']);
    }

    #[Test]
    public function resolveShouldSkipRejectedSectionPromises(): void
    {
        $editorialId = 'editorial-no-section';
        $sectionId = 'section-missing';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $insertedNewsMock = $this->createMock(BodyTagInsertedNews::class);
        $insertedNewsMock->method('editorialId')->willReturn($editorialIdMock);

        $body = $this->createMock(Body::class);
        $body->expects(static::once())
            ->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$insertedNewsMock]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('isVisible')->willReturn(true);
        $editorialMock->method('sectionId')->willReturn($sectionId);

        $this->queryEditorialClient->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId, true)
            ->willReturn(new FulfilledPromise($editorialMock));

        $this->querySectionClient->expects(static::once())
            ->method('findSectionById')
            ->with($sectionId, true)
            ->willReturn(new RejectedPromise(new \Exception('Section not found')));

        $this->signatureResolver->expects(static::never())
            ->method('resolveSignatures');

        $result = $this->resolver->resolve($body, []);

        static::assertSame([], $result['resolveData']['insertedNews']);
    }

    #[Test]
    public function resolveShouldLogErrorOnExceptionDuringProcessing(): void
    {
        $editorialId = 'editorial-error';
        $sectionId = 'section-ok';
        $errorMessage = 'Unexpected processing error';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $insertedNewsMock = $this->createMock(BodyTagInsertedNews::class);
        $insertedNewsMock->method('editorialId')->willReturn($editorialIdMock);

        $body = $this->createMock(Body::class);
        $body->expects(static::once())
            ->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$insertedNewsMock]);

        $signaturesMock = $this->createMock(Signatures::class);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('isVisible')->willReturn(true);
        $editorialMock->method('sectionId')->willReturn($sectionId);
        $editorialMock->method('signatures')->willReturn($signaturesMock);

        $this->queryEditorialClient->expects(static::once())
            ->method('findEditorialById')
            ->with($editorialId, true)
            ->willReturn(new FulfilledPromise($editorialMock));

        $sectionMock = $this->createMock(Section::class);

        $this->querySectionClient->expects(static::once())
            ->method('findSectionById')
            ->with($sectionId, true)
            ->willReturn(new FulfilledPromise($sectionMock));

        $this->signatureResolver->expects(static::once())
            ->method('resolveSignatures')
            ->with($signaturesMock, $sectionMock)
            ->willThrowException(new \RuntimeException($errorMessage));

        $this->logger->expects(static::once())
            ->method('error')
            ->with($errorMessage);

        $result = $this->resolver->resolve($body, []);

        static::assertSame([], $result['resolveData']['insertedNews']);
    }
}
