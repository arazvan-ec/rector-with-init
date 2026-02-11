<?php

/**
 * @copyright
 */

namespace App\Tests\Orchestrator\Chain;

use App\Application\DataTransformer\Apps\AppsDataTransformer;
use App\Application\DataTransformer\Apps\Media\MediaDataTransformerHandler;
use App\Application\DataTransformer\Apps\MultimediaDataTransformer;
use App\Application\DataTransformer\Apps\RecommendedEditorialsDataTransformer;
use App\Application\DataTransformer\Apps\StandfirstDataTransformer;
use App\Application\DataTransformer\BodyDataTransformer;
use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Exception\EditorialNotPublishedYetException;
use App\Orchestrator\Chain\EditorialOrchestrator;
use App\Orchestrator\Chain\Resolver\InsertedNewsResolver;
use App\Orchestrator\Chain\Resolver\MembershipLinkResolver;
use App\Orchestrator\Chain\Resolver\MembershipLinkPromise;
use App\Orchestrator\Chain\Resolver\MultimediaResolver;
use App\Orchestrator\Chain\Resolver\RecommendedEditorialsResolver;
use App\Orchestrator\Chain\Resolver\SignatureResolver;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\RecommendedEditorials;
use Ec\Editorial\Domain\Model\Signatures;
use Ec\Editorial\Domain\Model\SourceEditorial;
use Ec\Editorial\Domain\Model\Standfirst;
use Ec\Editorial\Domain\Model\Tag;
use Ec\Editorial\Domain\Model\Tags;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\QueryTagClient;
use Ec\Tag\Domain\Model\Tag as TagAlias;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(EditorialOrchestrator::class)]
class EditorialOrchestratorTest extends TestCase
{
    private QueryEditorialClient|MockObject $queryEditorialClient;
    private QueryLegacyClient|MockObject $queryLegacyClient;
    private QuerySectionClient|MockObject $querySectionClient;
    private AppsDataTransformer|MockObject $appsDataTransformer;
    private QueryTagClient|MockObject $queryTagClient;
    private BodyDataTransformer|MockObject $bodyDataTransformer;
    private LoggerInterface|MockObject $logger;
    private MultimediaDataTransformer|MockObject $multimediaDataTransformer;
    private StandfirstDataTransformer|MockObject $standfirstDataTransformer;
    private RecommendedEditorialsDataTransformer|MockObject $recommendedEditorialsDataTransformer;
    private MediaDataTransformerHandler|MockObject $mediaDataTransformerHandler;
    private SignatureResolver|MockObject $signatureResolver;
    private MembershipLinkResolver|MockObject $membershipLinkResolver;
    private MultimediaResolver|MockObject $multimediaResolver;
    private InsertedNewsResolver|MockObject $insertedNewsResolver;
    private RecommendedEditorialsResolver|MockObject $recommendedEditorialsResolver;
    private EditorialOrchestrator $editorialOrchestrator;

    protected function setUp(): void
    {
        $this->queryEditorialClient = $this->createMock(QueryEditorialClient::class);
        $this->queryLegacyClient = $this->createMock(QueryLegacyClient::class);
        $this->querySectionClient = $this->createMock(QuerySectionClient::class);
        $this->appsDataTransformer = $this->createMock(AppsDataTransformer::class);
        $this->queryTagClient = $this->createMock(QueryTagClient::class);
        $this->bodyDataTransformer = $this->createMock(BodyDataTransformer::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->multimediaDataTransformer = $this->createMock(MultimediaDataTransformer::class);
        $this->standfirstDataTransformer = $this->createMock(StandfirstDataTransformer::class);
        $this->recommendedEditorialsDataTransformer = $this->createMock(RecommendedEditorialsDataTransformer::class);
        $this->mediaDataTransformerHandler = $this->createMock(MediaDataTransformerHandler::class);
        $this->signatureResolver = $this->createMock(SignatureResolver::class);
        $this->membershipLinkResolver = $this->createMock(MembershipLinkResolver::class);
        $this->multimediaResolver = $this->createMock(MultimediaResolver::class);
        $this->insertedNewsResolver = $this->createMock(InsertedNewsResolver::class);
        $this->recommendedEditorialsResolver = $this->createMock(RecommendedEditorialsResolver::class);

        $this->editorialOrchestrator = new EditorialOrchestrator(
            $this->queryLegacyClient,
            $this->queryEditorialClient,
            $this->querySectionClient,
            $this->appsDataTransformer,
            $this->queryTagClient,
            $this->bodyDataTransformer,
            $this->logger,
            $this->multimediaDataTransformer,
            $this->standfirstDataTransformer,
            $this->recommendedEditorialsDataTransformer,
            $this->mediaDataTransformerHandler,
            $this->signatureResolver,
            $this->membershipLinkResolver,
            $this->multimediaResolver,
            $this->insertedNewsResolver,
            $this->recommendedEditorialsResolver,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->editorialOrchestrator,
            $this->queryLegacyClient,
            $this->queryEditorialClient,
            $this->querySectionClient,
            $this->appsDataTransformer,
            $this->queryTagClient,
            $this->bodyDataTransformer,
            $this->logger,
            $this->multimediaDataTransformer,
            $this->standfirstDataTransformer,
            $this->recommendedEditorialsDataTransformer,
            $this->mediaDataTransformerHandler,
            $this->signatureResolver,
            $this->membershipLinkResolver,
            $this->multimediaResolver,
            $this->insertedNewsResolver,
            $this->recommendedEditorialsResolver,
        );
    }

    #[Test]
    public function executeShouldThrowEditorialNotPublishedWhenIsNotVisible(): void
    {
        $id = '12345';
        $requestMock = $this->createMock(Request::class);
        $requestMock
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn($id);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->expects($this->once())
            ->method('isVisible')
            ->willReturn(false);

        $sourceEditorialMock = $this->createMock(SourceEditorial::class);
        $editorialMock->expects($this->once())
            ->method('sourceEditorial')
            ->willReturn($sourceEditorialMock);

        $this->queryEditorialClient->expects($this->once())
            ->method('findEditorialById')
            ->with($id)
            ->willReturn($editorialMock);

        $this->expectException(EditorialNotPublishedYetException::class);

        $this->editorialOrchestrator->execute($requestMock);
    }

    #[Test]
    public function executeShouldReturnEditorialFromLegacyClientWhenSourceIsNull(): void
    {
        $id = '12345';
        $editorial = $this->createMock(Editorial::class);

        $this->queryEditorialClient
            ->expects($this->once())
            ->method('findEditorialById')
            ->with($id)
            ->willReturn($editorial);

        $editorial
            ->expects($this->once())
            ->method('sourceEditorial')
            ->willReturn(null);

        $legacyResponse = ['editorial' => ['id' => $id]];

        $this->queryLegacyClient
            ->expects($this->once())
            ->method('findEditorialById')
            ->with($id)
            ->willReturn($legacyResponse);

        $requestMock = $this->createMock(Request::class);
        $requestMock
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn($id);

        $result = $this->editorialOrchestrator->execute($requestMock);

        $this->assertSame($legacyResponse, $result);
    }

    #[Test]
    public function executeShouldReturnCorrectDataForBasicEditorial(): void
    {
        $id = 'editorialId';
        $sectionId = 'editorialSectionId';
        $siteId = 'siteId';

        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(static::once())->method('get')->with('id')->willReturn($id);

        $bodyMock = $this->createMock(Body::class);
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn('');
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $multimediaMock->method('id')->willReturn($multimediaIdMock);
        $openingMock = $this->createMock(Opening::class);
        $signaturesMock = $this->createMock(Signatures::class);
        $recommendedMock = $this->createMock(RecommendedEditorials::class);
        $standfirstMock = $this->createMock(Standfirst::class);

        $editorialTag = $this->createMock(Tag::class);
        $tagsMock = new Tags();
        $tagsMock->addItem($editorialTag);

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->expects(static::once())->method('sourceEditorial')->willReturn($this->createMock(SourceEditorial::class));
        $editorialMock->expects(static::once())->method('isVisible')->willReturn(true);
        $editorialMock->expects(static::once())->method('sectionId')->willReturn($sectionId);
        $editorialMock->expects(static::exactly(3))->method('body')->willReturn($bodyMock);
        $editorialMock->expects(static::once())->method('recommendedEditorials')->willReturn($recommendedMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->expects(static::once())->method('signatures')->willReturn($signaturesMock);
        $editorialMock->expects(static::once())->method('tags')->willReturn($tagsMock);
        $editorialMock->expects(static::once())->method('editorialType')->willReturn('news');
        $editorialMock->expects(static::once())->method('standFirst')->willReturn($standfirstMock);

        $this->queryEditorialClient->expects(static::once())
            ->method('findEditorialById')
            ->with($id)
            ->willReturn($editorialMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->expects(static::once())->method('siteId')->willReturn($siteId);
        $this->querySectionClient->expects(static::once())
            ->method('findSectionById')
            ->with($sectionId)
            ->willReturn($sectionMock);

        $membershipPromise = new MembershipLinkPromise(null, []);
        $this->membershipLinkResolver->expects(static::once())
            ->method('createPromise')
            ->with($editorialMock, $siteId)
            ->willReturn($membershipPromise);

        $resolveDataAfterInserted = ['multimedia' => [], 'multimediaOpening' => [], 'insertedNews' => []];
        $this->insertedNewsResolver->expects(static::once())
            ->method('resolve')
            ->with($bodyMock, ['multimedia' => [], 'multimediaOpening' => []])
            ->willReturn(['resolveData' => $resolveDataAfterInserted]);

        $resolveDataAfterRecommended = array_merge($resolveDataAfterInserted, ['recommendedEditorials' => []]);
        $this->recommendedEditorialsResolver->expects(static::once())
            ->method('resolve')
            ->with($recommendedMock, $resolveDataAfterInserted)
            ->willReturn(['editorials' => [], 'resolveData' => $resolveDataAfterRecommended]);

        $this->multimediaResolver->expects(static::once())
            ->method('fetchOpening')
            ->willReturn($resolveDataAfterRecommended);
        $this->multimediaResolver->expects(static::once())
            ->method('addAsyncMultimedia')
            ->willReturn($resolveDataAfterRecommended);
        $this->multimediaResolver->expects(static::once())
            ->method('settlePromises')
            ->willReturn($resolveDataAfterRecommended);
        $this->multimediaResolver->expects(static::once())
            ->method('fetchBodyTagPhotos')
            ->with($bodyMock)
            ->willReturn([]);

        $resolvedTag = $this->createMock(TagAlias::class);
        $this->queryTagClient->expects(static::once())
            ->method('findTagById')
            ->with($editorialTag->id()->id())
            ->willReturn($resolvedTag);

        $expectedAppsResult = [
            'id' => $id,
            'section' => ['id' => $sectionId, 'name' => 'Mercados', 'url' => 'https://www.elconfidencial.dev/mercados'],
            'tags' => [['id' => '15919', 'name' => 'Bolsas']],
            'multimedia' => [],
        ];
        $this->appsDataTransformer->expects(static::once())
            ->method('write')
            ->with($editorialMock, $sectionMock, [$resolvedTag])
            ->willReturnSelf();
        $this->appsDataTransformer->expects(static::once())
            ->method('read')
            ->willReturn($expectedAppsResult);

        $this->queryLegacyClient->expects(static::once())
            ->method('findCommentsByEditorialId')
            ->with($id)
            ->willReturn(['options' => ['totalrecords' => 5]]);

        $expectedSignatures = [['journalistId' => '1', 'name' => 'Test Author']];
        $this->signatureResolver->expects(static::once())
            ->method('resolveSignatures')
            ->with($signaturesMock, $sectionMock, false)
            ->willReturn($expectedSignatures);

        $this->membershipLinkResolver->expects(static::once())
            ->method('resolve')
            ->with($membershipPromise)
            ->willReturn([]);

        $expectedBody = [['type' => 'paragraph', 'content' => 'Hello']];
        $this->bodyDataTransformer->expects(static::once())
            ->method('execute')
            ->willReturn($expectedBody);

        $this->standfirstDataTransformer->expects(static::once())->method('write')->willReturnSelf();
        $this->standfirstDataTransformer->expects(static::once())->method('read')->willReturn([]);

        $this->recommendedEditorialsDataTransformer->expects(static::once())->method('write')->willReturnSelf();
        $this->recommendedEditorialsDataTransformer->expects(static::once())->method('read')->willReturn([]);

        $result = $this->editorialOrchestrator->execute($requestMock);

        static::assertSame($id, $result['id']);
        static::assertSame(5, $result['countComments']);
        static::assertSame($expectedSignatures, $result['signatures']);
        static::assertSame($expectedBody, $result['body']);
        static::assertNull($result['multimedia']);
        static::assertSame([], $result['standfirst']);
        static::assertSame([], $result['recommendedEditorials']);
    }

    #[Test]
    public function executeShouldReturnCorrectDataWithRecommendedEditorials(): void
    {
        $id = 'editorialId';
        $sectionId = 'editorialSectionId';
        $siteId = 'siteId';

        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(static::once())->method('get')->with('id')->willReturn($id);

        $bodyMock = $this->createMock(Body::class);
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn('');
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $multimediaMock->method('id')->willReturn($multimediaIdMock);
        $openingMock = $this->createMock(Opening::class);
        $signaturesMock = $this->createMock(Signatures::class);
        $recommendedMock = $this->createMock(RecommendedEditorials::class);
        $standfirstMock = $this->createMock(Standfirst::class);
        $tagsMock = new Tags();

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->expects(static::once())->method('sourceEditorial')->willReturn($this->createMock(SourceEditorial::class));
        $editorialMock->expects(static::once())->method('isVisible')->willReturn(true);
        $editorialMock->expects(static::once())->method('sectionId')->willReturn($sectionId);
        $editorialMock->expects(static::exactly(3))->method('body')->willReturn($bodyMock);
        $editorialMock->expects(static::once())->method('recommendedEditorials')->willReturn($recommendedMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->expects(static::once())->method('signatures')->willReturn($signaturesMock);
        $editorialMock->expects(static::once())->method('tags')->willReturn($tagsMock);
        $editorialMock->expects(static::once())->method('editorialType')->willReturn('news');
        $editorialMock->expects(static::once())->method('standFirst')->willReturn($standfirstMock);

        $this->queryEditorialClient->expects(static::once())
            ->method('findEditorialById')
            ->with($id)
            ->willReturn($editorialMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->expects(static::once())->method('siteId')->willReturn($siteId);
        $this->querySectionClient->expects(static::once())
            ->method('findSectionById')
            ->with($sectionId)
            ->willReturn($sectionMock);

        $membershipPromise = new MembershipLinkPromise(null, []);
        $this->membershipLinkResolver->expects(static::once())
            ->method('createPromise')
            ->willReturn($membershipPromise);

        $resolveDataAfterInserted = ['multimedia' => [], 'multimediaOpening' => [], 'insertedNews' => []];
        $this->insertedNewsResolver->expects(static::once())
            ->method('resolve')
            ->willReturn(['resolveData' => $resolveDataAfterInserted]);

        $recommendedEditorialMock = $this->createMock(Editorial::class);
        $resolveDataAfterRecommended = array_merge($resolveDataAfterInserted, [
            'recommendedEditorials' => ['rec-1' => ['editorial' => $recommendedEditorialMock, 'section' => $sectionMock, 'signatures' => [], 'multimediaId' => '']],
        ]);
        $this->recommendedEditorialsResolver->expects(static::once())
            ->method('resolve')
            ->willReturn(['editorials' => [$recommendedEditorialMock], 'resolveData' => $resolveDataAfterRecommended]);

        $this->multimediaResolver->method('fetchOpening')->willReturn($resolveDataAfterRecommended);
        $this->multimediaResolver->method('addAsyncMultimedia')->willReturn($resolveDataAfterRecommended);
        $this->multimediaResolver->method('settlePromises')->willReturn($resolveDataAfterRecommended);
        $this->multimediaResolver->method('fetchBodyTagPhotos')->willReturn([]);

        $expectedAppsResult = ['id' => $id, 'section' => [], 'tags' => [], 'multimedia' => []];
        $this->appsDataTransformer->method('write')->willReturnSelf();
        $this->appsDataTransformer->method('read')->willReturn($expectedAppsResult);
        $this->queryLegacyClient->method('findCommentsByEditorialId')->willReturn(['options' => ['totalrecords' => 0]]);
        $this->signatureResolver->method('resolveSignatures')->willReturn([]);
        $this->membershipLinkResolver->method('resolve')->willReturn([]);
        $this->bodyDataTransformer->method('execute')->willReturn([]);
        $this->standfirstDataTransformer->method('write')->willReturnSelf();
        $this->standfirstDataTransformer->method('read')->willReturn([]);

        $expectedRecommended = [['type' => 'recommendededitorial', 'editorialId' => 'rec-1']];
        $this->recommendedEditorialsDataTransformer->expects(static::once())
            ->method('write')
            ->with([$recommendedEditorialMock], static::anything())
            ->willReturnSelf();
        $this->recommendedEditorialsDataTransformer->expects(static::once())
            ->method('read')
            ->willReturn($expectedRecommended);

        $result = $this->editorialOrchestrator->execute($requestMock);

        static::assertSame($expectedRecommended, $result['recommendedEditorials']);
    }

    #[Test]
    public function executeShouldDelegateToInsertedNewsResolver(): void
    {
        $id = 'editorialId';
        $requestMock = $this->createMock(Request::class);
        $requestMock->method('get')->willReturn($id);

        $bodyMock = $this->createMock(Body::class);
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn('');
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $multimediaMock->method('id')->willReturn($multimediaIdMock);
        $signaturesMock = $this->createMock(Signatures::class);
        $recommendedMock = $this->createMock(RecommendedEditorials::class);
        $standfirstMock = $this->createMock(Standfirst::class);
        $tagsMock = new Tags();

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->method('sourceEditorial')->willReturn($this->createMock(SourceEditorial::class));
        $editorialMock->method('isVisible')->willReturn(true);
        $editorialMock->method('sectionId')->willReturn('sectionId');
        $editorialMock->method('body')->willReturn($bodyMock);
        $editorialMock->method('recommendedEditorials')->willReturn($recommendedMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->method('signatures')->willReturn($signaturesMock);
        $editorialMock->method('tags')->willReturn($tagsMock);
        $editorialMock->method('editorialType')->willReturn('news');
        $editorialMock->method('standFirst')->willReturn($standfirstMock);

        $this->queryEditorialClient->method('findEditorialById')->willReturn($editorialMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('siteId');
        $this->querySectionClient->method('findSectionById')->willReturn($sectionMock);

        $this->membershipLinkResolver->method('createPromise')->willReturn(new MembershipLinkPromise(null, []));

        $expectedInsertedNews = [
            'editorial-3' => ['editorial' => $this->createMock(Editorial::class), 'section' => $sectionMock, 'signatures' => [], 'multimediaId' => ''],
        ];
        $resolveDataWithInserted = ['multimedia' => [], 'multimediaOpening' => [], 'insertedNews' => $expectedInsertedNews];
        $this->insertedNewsResolver->expects(static::once())
            ->method('resolve')
            ->with($bodyMock, ['multimedia' => [], 'multimediaOpening' => []])
            ->willReturn(['resolveData' => $resolveDataWithInserted]);

        $this->recommendedEditorialsResolver->method('resolve')
            ->willReturn(['editorials' => [], 'resolveData' => array_merge($resolveDataWithInserted, ['recommendedEditorials' => []])]);

        $this->multimediaResolver->method('fetchOpening')->willReturnArgument(1);
        $this->multimediaResolver->method('addAsyncMultimedia')->willReturnArgument(1);
        $this->multimediaResolver->method('settlePromises')->willReturnArgument(0);
        $this->multimediaResolver->method('fetchBodyTagPhotos')->willReturn([]);

        $this->appsDataTransformer->method('write')->willReturnSelf();
        $this->appsDataTransformer->method('read')->willReturn(['id' => $id]);
        $this->queryLegacyClient->method('findCommentsByEditorialId')->willReturn(['options' => ['totalrecords' => 0]]);
        $this->signatureResolver->method('resolveSignatures')->willReturn([]);
        $this->membershipLinkResolver->method('resolve')->willReturn([]);
        $this->bodyDataTransformer->expects(static::once())
            ->method('execute')
            ->with($bodyMock, static::callback(function (array $resolveData) use ($expectedInsertedNews) {
                return $resolveData['insertedNews'] === $expectedInsertedNews;
            }))
            ->willReturn([]);
        $this->standfirstDataTransformer->method('write')->willReturnSelf();
        $this->standfirstDataTransformer->method('read')->willReturn([]);
        $this->recommendedEditorialsDataTransformer->method('write')->willReturnSelf();
        $this->recommendedEditorialsDataTransformer->method('read')->willReturn([]);

        $this->editorialOrchestrator->execute($requestMock);
    }

    #[Test]
    public function canOrchestrateShouldReturnExpectedValue(): void
    {
        static::assertSame('editorial', $this->editorialOrchestrator->canOrchestrate());
    }

    #[Test]
    public function shouldTransformMultimediaReturnsMediaOpeningData(): void
    {
        $resolveData = ['multimediaOpening' => ['foo']];
        $openingMock = $this->createMock(Opening::class);
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('opening')->willReturn($openingMock);

        $this->mediaDataTransformerHandler
            ->expects($this->once())
            ->method('execute')
            ->with(['foo'], $openingMock)
            ->willReturn(['result' => 'media']);

        $method = new \ReflectionMethod($this->editorialOrchestrator, 'transformMultimedia');
        static::assertTrue($method->isPrivate());

        $method->setAccessible(true);
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        $this->assertEquals(['result' => 'media'], $result);
    }

    #[Test]
    public function shouldTransformMultimediaReturnsMultimediaData(): void
    {
        $resolveData = ['multimedia' => ['bar']];

        $editorial = $this->createMock(Editorial::class);
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $editorial->method('multimedia')->willReturn($multimediaMock);

        $this->multimediaDataTransformer
            ->expects(static::once())
            ->method('write')
            ->with(['bar'], $multimediaMock)
            ->willReturnSelf();
        $this->multimediaDataTransformer
            ->method('read')
            ->willReturn(['result' => 'data']);

        $method = new \ReflectionMethod($this->editorialOrchestrator, 'transformMultimedia');
        static::assertTrue($method->isPrivate());

        $method->setAccessible(true);
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        static::assertEquals(['result' => 'data'], $result);
    }

    #[Test]
    public function shouldTransformMultimediaReturnsNullWhenNoData(): void
    {
        $resolveData = [];

        $editorial = $this->createMock(Editorial::class);

        $method = new \ReflectionMethod($this->editorialOrchestrator, 'transformMultimedia');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        static::assertNull($result);
    }

    #[Test]
    public function fetchTagsShouldReturnResolvedTags(): void
    {
        $editorialTag1 = $this->createMock(Tag::class);
        $editorialTag2 = $this->createMock(Tag::class);
        $tagsMock = new Tags();
        $tagsMock->addItem($editorialTag1);
        $tagsMock->addItem($editorialTag2);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('tags')->willReturn($tagsMock);

        $resolvedTag1 = $this->createMock(TagAlias::class);
        $resolvedTag2 = $this->createMock(TagAlias::class);

        $callArgs = [];
        $this->queryTagClient->expects(static::exactly(2))
            ->method('findTagById')
            ->willReturnCallback(function ($tagId) use (&$callArgs, $editorialTag1, $editorialTag2, $resolvedTag1, $resolvedTag2) {
                $callArgs[] = $tagId;
                if ($tagId === $editorialTag1->id()->id()) {
                    return $resolvedTag1;
                }

                return $resolvedTag2;
            });

        $method = new \ReflectionMethod($this->editorialOrchestrator, 'fetchTags');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial]);

        static::assertCount(2, $result);
        static::assertSame($resolvedTag1, $result[0]);
        static::assertSame($resolvedTag2, $result[1]);
    }

    #[Test]
    public function fetchTagsShouldContinueOnException(): void
    {
        $editorialTag1 = $this->createMock(Tag::class);
        $editorialTag2 = $this->createMock(Tag::class);
        $tagsMock = new Tags();
        $tagsMock->addItem($editorialTag1);
        $tagsMock->addItem($editorialTag2);

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('tags')->willReturn($tagsMock);

        $resolvedTag2 = $this->createMock(TagAlias::class);

        $callCount = 0;
        $this->queryTagClient->expects(static::exactly(2))
            ->method('findTagById')
            ->willReturnCallback(function () use (&$callCount, $resolvedTag2) {
                ++$callCount;
                if (1 === $callCount) {
                    throw new \Exception('Tag not found');
                }

                return $resolvedTag2;
            });

        $method = new \ReflectionMethod($this->editorialOrchestrator, 'fetchTags');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial]);

        static::assertCount(1, $result);
        static::assertSame($resolvedTag2, $result[0]);
    }

    #[Test]
    public function executeShouldPassHasTwitterTrueForBlogEditorials(): void
    {
        $id = 'editorialId';
        $requestMock = $this->createMock(Request::class);
        $requestMock->method('get')->willReturn($id);

        $bodyMock = $this->createMock(Body::class);
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn('');
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $multimediaMock->method('id')->willReturn($multimediaIdMock);
        $signaturesMock = $this->createMock(Signatures::class);
        $recommendedMock = $this->createMock(RecommendedEditorials::class);
        $standfirstMock = $this->createMock(Standfirst::class);
        $tagsMock = new Tags();

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->method('sourceEditorial')->willReturn($this->createMock(SourceEditorial::class));
        $editorialMock->method('isVisible')->willReturn(true);
        $editorialMock->method('sectionId')->willReturn('sectionId');
        $editorialMock->method('body')->willReturn($bodyMock);
        $editorialMock->method('recommendedEditorials')->willReturn($recommendedMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->method('signatures')->willReturn($signaturesMock);
        $editorialMock->method('tags')->willReturn($tagsMock);
        $editorialMock->method('editorialType')->willReturn('blog');
        $editorialMock->method('standFirst')->willReturn($standfirstMock);

        $this->queryEditorialClient->method('findEditorialById')->willReturn($editorialMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('siteId');
        $this->querySectionClient->method('findSectionById')->willReturn($sectionMock);

        $this->membershipLinkResolver->method('createPromise')->willReturn(new MembershipLinkPromise(null, []));
        $this->insertedNewsResolver->method('resolve')
            ->willReturn(['resolveData' => ['multimedia' => [], 'multimediaOpening' => [], 'insertedNews' => []]]);
        $this->recommendedEditorialsResolver->method('resolve')
            ->willReturn(['editorials' => [], 'resolveData' => ['multimedia' => [], 'multimediaOpening' => [], 'insertedNews' => [], 'recommendedEditorials' => []]]);
        $this->multimediaResolver->method('fetchOpening')->willReturnArgument(1);
        $this->multimediaResolver->method('addAsyncMultimedia')->willReturnArgument(1);
        $this->multimediaResolver->method('settlePromises')->willReturnArgument(0);
        $this->multimediaResolver->method('fetchBodyTagPhotos')->willReturn([]);
        $this->appsDataTransformer->method('write')->willReturnSelf();
        $this->appsDataTransformer->method('read')->willReturn(['id' => $id]);
        $this->queryLegacyClient->method('findCommentsByEditorialId')->willReturn(['options' => ['totalrecords' => 0]]);
        $this->membershipLinkResolver->method('resolve')->willReturn([]);
        $this->bodyDataTransformer->method('execute')->willReturn([]);
        $this->standfirstDataTransformer->method('write')->willReturnSelf();
        $this->standfirstDataTransformer->method('read')->willReturn([]);
        $this->recommendedEditorialsDataTransformer->method('write')->willReturnSelf();
        $this->recommendedEditorialsDataTransformer->method('read')->willReturn([]);

        $this->signatureResolver->expects(static::once())
            ->method('resolveSignatures')
            ->with($signaturesMock, $sectionMock, true)
            ->willReturn([]);

        $this->editorialOrchestrator->execute($requestMock);
    }

    #[Test]
    public function executeShouldReturnMultimediaDataWhenMultimediaOpeningExists(): void
    {
        $id = 'editorialId';
        $requestMock = $this->createMock(Request::class);
        $requestMock->method('get')->willReturn($id);

        $bodyMock = $this->createMock(Body::class);
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn('');
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $multimediaMock->method('id')->willReturn($multimediaIdMock);
        $openingMock = $this->createMock(Opening::class);
        $signaturesMock = $this->createMock(Signatures::class);
        $recommendedMock = $this->createMock(RecommendedEditorials::class);
        $standfirstMock = $this->createMock(Standfirst::class);
        $tagsMock = new Tags();

        $editorialMock = $this->createMock(NewsBase::class);
        $editorialMock->method('sourceEditorial')->willReturn($this->createMock(SourceEditorial::class));
        $editorialMock->method('isVisible')->willReturn(true);
        $editorialMock->method('sectionId')->willReturn('sectionId');
        $editorialMock->method('body')->willReturn($bodyMock);
        $editorialMock->method('recommendedEditorials')->willReturn($recommendedMock);
        $editorialMock->method('multimedia')->willReturn($multimediaMock);
        $editorialMock->method('signatures')->willReturn($signaturesMock);
        $editorialMock->method('tags')->willReturn($tagsMock);
        $editorialMock->method('editorialType')->willReturn('news');
        $editorialMock->method('standFirst')->willReturn($standfirstMock);
        $editorialMock->method('opening')->willReturn($openingMock);

        $this->queryEditorialClient->method('findEditorialById')->willReturn($editorialMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('siteId');
        $this->querySectionClient->method('findSectionById')->willReturn($sectionMock);

        $this->membershipLinkResolver->method('createPromise')->willReturn(new MembershipLinkPromise(null, []));
        $this->insertedNewsResolver->method('resolve')
            ->willReturn(['resolveData' => ['multimedia' => [], 'multimediaOpening' => [], 'insertedNews' => []]]);

        $resolveDataWithOpening = ['multimedia' => [], 'multimediaOpening' => ['123' => ['opening' => 'data']], 'insertedNews' => [], 'recommendedEditorials' => []];
        $this->recommendedEditorialsResolver->method('resolve')
            ->willReturn(['editorials' => [], 'resolveData' => ['multimedia' => [], 'multimediaOpening' => [], 'insertedNews' => [], 'recommendedEditorials' => []]]);

        $this->multimediaResolver->method('fetchOpening')->willReturn($resolveDataWithOpening);
        $this->multimediaResolver->method('addAsyncMultimedia')->willReturn($resolveDataWithOpening);
        $this->multimediaResolver->method('settlePromises')->willReturn($resolveDataWithOpening);
        $this->multimediaResolver->method('fetchBodyTagPhotos')->willReturn([]);

        $this->appsDataTransformer->method('write')->willReturnSelf();
        $this->appsDataTransformer->method('read')->willReturn(['id' => $id]);
        $this->queryLegacyClient->method('findCommentsByEditorialId')->willReturn(['options' => ['totalrecords' => 0]]);
        $this->signatureResolver->method('resolveSignatures')->willReturn([]);
        $this->membershipLinkResolver->method('resolve')->willReturn([]);
        $this->bodyDataTransformer->method('execute')->willReturn([]);
        $this->standfirstDataTransformer->method('write')->willReturnSelf();
        $this->standfirstDataTransformer->method('read')->willReturn([]);
        $this->recommendedEditorialsDataTransformer->method('write')->willReturnSelf();
        $this->recommendedEditorialsDataTransformer->method('read')->willReturn([]);

        $expectedMultimedia = ['type' => 'photo', 'id' => '123'];
        $this->mediaDataTransformerHandler->expects(static::once())
            ->method('execute')
            ->with(['123' => ['opening' => 'data']], $openingMock)
            ->willReturn($expectedMultimedia);

        $result = $this->editorialOrchestrator->execute($requestMock);

        static::assertSame($expectedMultimedia, $result['multimedia']);
    }
}
