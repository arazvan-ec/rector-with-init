<?php

/**
 * @copyright
 */

namespace App\Tests\Orchestrator\Chain;

use App\Application\DataTransformer\Apps\AppsDataTransformer;
use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use App\Application\DataTransformer\Apps\Media\MediaDataTransformerHandler;
use App\Application\DataTransformer\Apps\MultimediaDataTransformer;
use App\Application\DataTransformer\Apps\RecommendedEditorialsDataTransformer;
use App\Application\DataTransformer\Apps\StandfirstDataTransformer;
use App\Application\DataTransformer\BodyDataTransformer;
use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Exception\EditorialNotPublishedYetException;
use App\Orchestrator\Chain\EditorialOrchestrator;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use App\Tests\Orchestrator\Chain\DataProvider\EditorialOrchestratorDataProvider;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Body\MembershipCardButtons;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\RecommendedEditorials;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Editorial\Domain\Model\SignatureId;
use Ec\Editorial\Domain\Model\Signatures;
use Ec\Editorial\Domain\Model\SourceEditorial;
use Ec\Editorial\Domain\Model\Standfirst;
use Ec\Editorial\Domain\Model\Tag;
use Ec\Editorial\Domain\Model\Tags;
use Ec\Journalist\Domain\Model\AliasId;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use Ec\Membership\Infrastructure\Client\Http\QueryMembershipClient;
use Ec\Multimedia\Domain\Model\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Multimedia\ResourceId;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Ec\Section\Domain\Model\SectionId;
use Ec\Tag\Domain\Model\QueryTagClient;
use Ec\Tag\Domain\Model\Tag as TagAlias;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(EditorialOrchestrator::class)]
class EditorialOrchestratorTest extends TestCase
{
    /** @var QueryEditorialClient|MockObject */
    private QueryEditorialClient $queryEditorialClient;

    /** @var QueryLegacyClient|MockObject */
    private QueryLegacyClient $queryLegacyClient;

    private EditorialOrchestrator $editorialOrchestrator;

    /** @var QuerySectionClient|MockObject */
    private QuerySectionClient $querySectionClient;

    /** @var QueryMultimediaClient|MockObject */
    private QueryMultimediaClient $queryMultimediaClient;

    /** @var QueryJournalistClient|MockObject */
    private QueryJournalistClient $queryJournalistClient;

    /** @var JournalistFactory|MockObject */
    private JournalistFactory $journalistFactory;

    /** @var JournalistsDataTransformer|MockObject */
    private JournalistsDataTransformer $journalistsDataTransformer;

    /** @var AppsDataTransformer|MockObject */
    private AppsDataTransformer $appsDataTransformer;

    /** @var BodyDataTransformer|MockObject */
    private BodyDataTransformer $bodyDataTransformer;

    /** @var MultimediaDataTransformer|MockObject */
    private MultimediaDataTransformer $multimediaDataTransformer;

    /** @var QueryTagClient|MockObject */
    private QueryTagClient $queryTagClient;

    /** @var UriFactoryInterface|MockObject */
    private UriFactoryInterface $uriFactory;

    /** @var MockObject|LoggerInterface */
    private LoggerInterface $logger;

    /** @var QueryMembershipClient|MockObject */
    private QueryMembershipClient $queryMembershipClient;

    /**
     * @var StandfirstDataTransformer|MockObject
     */
    private StandfirstDataTransformer $standfirstDataTransformer;

    /**
     * @var RecommendedEditorialsDataTransformer|MockObject
     */
    private RecommendedEditorialsDataTransformer $recommendedEditorialsDataTransformer;

    /**
     * @var QueryMultimediaOpeningClient|MockObject
     */
    private QueryMultimediaOpeningClient $queryMultimediaOpeningClient;
    /**
     * @var MediaDataTransformerHandler|MockObject
     */
    private MediaDataTransformerHandler $mediaDataTransformerHandler;

    /**
     * @var MultimediaOrchestratorHandler|MockObject
     */
    private MultimediaOrchestratorHandler $multimediaOrchestratorHandler;

    protected function setUp(): void
    {
        $this->queryEditorialClient = $this->createMock(QueryEditorialClient::class);
        $this->queryLegacyClient = $this->createMock(QueryLegacyClient::class);
        $this->querySectionClient = $this->createMock(QuerySectionClient::class);
        $this->queryMultimediaClient = $this->createMock(QueryMultimediaClient::class);
        $this->journalistsDataTransformer = $this->createMock(JournalistsDataTransformer::class);
        $this->appsDataTransformer = $this->createMock(AppsDataTransformer::class);
        $this->bodyDataTransformer = $this->createMock(BodyDataTransformer::class);
        $this->queryTagClient = $this->createMock(QueryTagClient::class);
        $this->uriFactory = $this->createMock(UriFactoryInterface::class);
        $this->queryMembershipClient = $this->createMock(QueryMembershipClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->queryJournalistClient = $this->createMock(QueryJournalistClient::class);
        $this->journalistFactory = $this->createMock(JournalistFactory::class);
        $this->multimediaDataTransformer = $this->createMock(MultimediaDataTransformer::class);
        $this->standfirstDataTransformer = $this->createMock(StandfirstDataTransformer::class);
        $this->recommendedEditorialsDataTransformer = $this->createMock(RecommendedEditorialsDataTransformer::class);
        $this->queryMultimediaOpeningClient = $this->createMock(QueryMultimediaOpeningClient::class);
        $this->mediaDataTransformerHandler = $this->createMock(MediaDataTransformerHandler::class);
        $this->multimediaOrchestratorHandler = $this->createMock(MultimediaOrchestratorHandler::class);
        $this->editorialOrchestrator = new EditorialOrchestrator(
            $this->queryLegacyClient,
            $this->queryEditorialClient,
            $this->querySectionClient,
            $this->queryMultimediaClient,
            $this->appsDataTransformer,
            $this->queryTagClient,
            $this->bodyDataTransformer,
            $this->uriFactory,
            $this->queryMembershipClient,
            $this->logger,
            $this->journalistsDataTransformer,
            $this->queryJournalistClient,
            $this->journalistFactory,
            $this->multimediaDataTransformer,
            $this->standfirstDataTransformer,
            $this->recommendedEditorialsDataTransformer,
            $this->queryMultimediaOpeningClient,
            $this->mediaDataTransformerHandler,
            $this->multimediaOrchestratorHandler,
            'dev'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->editorialOrchestrator,
            $this->queryLegacyClient,
            $this->queryEditorialClient,
            $this->queryJournalistClient,
            $this->querySectionClient,
            $this->queryMultimediaClient,
            $this->journalistFactory,
            $this->appsDataTransformer,
            $this->queryTagClient,
            $this->bodyDataTransformer,
            $this->uriFactory,
            $this->queryMembershipClient,
            $this->logger,
            $this->journalistsDataTransformer,
            $this->multimediaDataTransformer,
            $this->standfirstDataTransformer,
            $this->recommendedEditorialsDataTransformer,
            $this->queryMultimediaOpeningClient,
            $this->multimediaMediaDataTransformer,
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

    /**
     * @param array{
     *      id: string,
     *      sectionId: string,
     *      signatures: array<int, string>,
     *      insertedNews: array<int, array{
     *          id: string,
     *          sectionId: string,
     *          signatures: array<int, string>,
     *          multimediaId: string
     *      }>,
     *      recommender: array<int, array{
     *          id: string,
     *          sectionId: string,
     *          signatures: array<int, string>,
     *          multimediaId: string
     *      }>,
     *      membershipCards: array<int, array{
     *          btns: array<int, array{
     *              urlMembership: string,
     *              url: string
     *          }>
     *      }>,
     *      bodyExpected: array<array<string, mixed>>,
     *      standfirstExpected: array<array<string, mixed>>,
     *      recommenderExpected: array<array<string, string>>
     *  } $editorial
     * @param array<int, array<string, string>> $allJournalistExpected
     * @param array<int, array<string, string>> $allJournalistEditorialExpected
     * @param array<string, string>             $membershipLinkCombine
     * @param array<int, array<int, string>>    $expectedJournalistAliasIds
     * @param array<mixed>                      $expectedPhotoFromBodyTags
     * @param array<string, string>             $expectedOpeningMultimedia
     */
    #[DataProviderExternal(EditorialOrchestratorDataProvider::class, 'getData')]
    #[Test]
    public function executeShouldReturnCorrectData(
        array $editorial,
        array $allJournalistExpected,
        array $allJournalistEditorialExpected,
        array $membershipLinkCombine,
        array $expectedJournalistAliasIds,
        array $expectedPhotoFromBodyTags,
        ?array $expectedOpeningMultimedia,
    ): void {
        $journalistsEditorial = $editorial['signatures'];

        /** @var Request $requestMock */
        $requestMock = $this->getRequestMock($editorial['id']);

        /** @var MockObject $editorialMock */
        $editorialMock = $this->getEditorialMock($editorial);
        $promisesEditorials[] = $editorialMock;
        $withEditorials[] = $editorial['id'];

        $sectionMock = $this->getSectionMock($editorial['sectionId']);
        $promisesSections[] = $sectionMock;
        $withSections[] = $editorial['sectionId'];

        $callArgumentsCreateUri = [];
        [
            $membershipCardsPromise,
            $expectedArgumentsCreateUri,
        ] = $this->getBodyTagsMembershipCardsByEditorial($editorial, $membershipLinkCombine, $callArgumentsCreateUri);

        [
            $bodyTagsInsertedNews,
            $expectedInsertedNews,
            $promisesEditorialsInserted,
            $withEditorialsInserted,
            $promisesSectionsInserted,
            $withSectionsInserted,
            $withAliasIdsInserted,
        ] = $this->getBodyTagsInsertedNewsByEditorial($editorial, $allJournalistExpected);

        $withEditorials = array_merge($withEditorials, $withEditorialsInserted);
        $promisesEditorials = array_merge($promisesEditorials, $promisesEditorialsInserted);
        $promisesSections = array_merge($promisesSections, $promisesSectionsInserted);
        $withSections = array_merge($withSections, $withSectionsInserted);

        $withBodyTags = [];
        $withBodyTags[] = BodyTagMembershipCard::class;
        $withBodyTags[] = BodyTagInsertedNews::class;
        $withBodyTags[] = BodyTagPicture::class;
        $withBodyTags[] = BodyTagMembershipCard::class;
        $promiseBodyTagPictures = [];

        $arrayMocks = [
            [BodyTagMembershipCard::class => $membershipCardsPromise],
            [BodyTagInsertedNews::class => $bodyTagsInsertedNews],
            [BodyTagPicture::class => $promiseBodyTagPictures],
            [BodyTagMembershipCard::class => $membershipCardsPromise],
        ];
        $expectedArgumentsBodyTags = $withBodyTags;
        $callArgumentsBodyElements = [];
        $bodyMock = $this->createMock(Body::class);
        $bodyMock->expects(static::exactly(\count($expectedArgumentsBodyTags)))
            ->method('bodyElementsOf')
            ->willReturnCallback(function ($strClass) use (&$callArgumentsBodyElements, $arrayMocks) {
                $callArgumentsBodyElements[] = $strClass;

                return $arrayMocks[\count($callArgumentsBodyElements) - 1][$strClass];
            });

        $editorialMock->expects(static::exactly(4))
            ->method('body')
            ->willReturn($bodyMock);
        $openingMock = $this->createMock(Opening::class);
        $editorialMock->method('opening')
            ->willReturn($openingMock);

        [
            $expectedRecommendedNews,
            $promisesEditorialsRecommended,
            $withEditorialsRecommended,
            $promisesSectionsRecommended,
            $withSectionsRecommended,
            $withAliasIdsRecommended,
            $editorialMock,
        ] = $this->getRecommendedNewsByEditorial($editorial, $editorialMock, $allJournalistExpected);

        $withEditorials = array_merge($withEditorials, $withEditorialsRecommended);
        $promisesEditorials = array_merge($promisesEditorials, $promisesEditorialsRecommended);
        $promisesSections = array_merge($promisesSections, $promisesSectionsRecommended);
        $withSections = array_merge($withSections, $withSectionsRecommended);

        $tags = [$this->generateTagMock($editorialMock)];

        $this->appsDataTransformer
            ->expects(static::once())
            ->method('write')
            ->with($editorialMock, $sectionMock, $tags)
            ->willReturnSelf();

        $tags = [
            [
                'id' => '15919',
                'name' => 'Bolsas',
                'url' => 'https://www.elconfidencial.dev/tags/temas/bolsas-15919',
            ],
        ];
        $expectedResult = [
            'id' => $editorial['id'],
            'section' => [
                'id' => $editorial['sectionId'],
                'name' => 'Mercados',
                'url' => 'https://www.elconfidencial.dev/mercados',
            ],
            'countComments' => 0,
            'tags' => $tags,
            'multimedia' => [],
        ];

        $this->appsDataTransformer
            ->expects(static::once())
            ->method('read')
            ->willReturn($expectedResult);

        $this->queryLegacyClient
            ->expects($this->once())
            ->method('findCommentsByEditorialId')
            ->with($editorial['id'])
            ->willReturn(['options' => ['totalrecords' => 0]]);

        $editorialMock = $this->getSignaturesMockByEditorial($editorial, $editorialMock);

        $withAliasIds = array_merge($withAliasIdsInserted, $withAliasIdsRecommended);

        /** @var array<int, string> $withAliasIds */
        $withAliasIds = array_merge($withAliasIds, $editorial['signatures']);

        [
            $promisesJournalist,
            $promisesAliasIds,
        ] = $this->getJournalistPromisesMock($withAliasIds);

        $callArgumentsAlias = [];
        $expectedArgumentsAlias = $this->resolveSignatures(
            $withAliasIds,
            $promisesJournalist,
            $promisesAliasIds,
            $allJournalistExpected,
            $callArgumentsAlias,
            $expectedJournalistAliasIds
        );

        $journalistEditorialExpected = [];
        foreach ($journalistsEditorial as $journalistEditorialId) {
            if ('10' !== $journalistEditorialId) {
                $journalistEditorialExpected[] = $allJournalistExpected[$journalistEditorialId];
            }
        }

        /** @var array<string> $withSections */
        $arrayMocks = array_combine($withSections, $promisesSections);
        $expectedArgumentsSections = $withSections;
        $callArgumentsSections = [];
        $this->querySectionClient->expects(static::exactly(\count($expectedArgumentsSections)))
            ->method('findSectionById')
            ->willReturnCallback(function ($strClass) use (&$callArgumentsSections, $arrayMocks) {
                $callArgumentsSections[] = $strClass;

                return $arrayMocks[$strClass];
            });

        /** @var array<string> $withEditorials */
        $arrayMocks = array_combine($withEditorials, $promisesEditorials);
        $expectedArgumentsEditorials = $withEditorials;
        $callArgumentsEditorials = [];
        $this->queryEditorialClient->expects(static::exactly(\count($expectedArgumentsEditorials)))
            ->method('findEditorialById')
            ->willReturnCallback(function ($strClass) use (&$callArgumentsEditorials, $arrayMocks) {
                $callArgumentsEditorials[] = $strClass;

                return $arrayMocks[$strClass];
            });

        $resolveData['photoFromBodyTags'] = $expectedPhotoFromBodyTags;
        $resolveData['membershipLinkCombine'] = $membershipLinkCombine;
        $resolveData['insertedNews'] = $expectedInsertedNews;
        $resolveData['multimedia'] = [];
        $resolveData['recommendedEditorials'] = $expectedRecommendedNews;
        $resolveData['multimediaOpening'] = [];

        $this->bodyDataTransformer->expects(static::once())
            ->method('execute')
            ->with($bodyMock, $resolveData)
            ->willReturn($editorial['bodyExpected']);

        $expectedResult['signatures'] = $journalistEditorialExpected;
        $expectedResult['body'] = $editorial['bodyExpected'];

        $standfirst = $this->createMock(Standfirst::class);

        $editorialMock
            ->expects(static::once())
            ->method('standfirst')
            ->willReturn($standfirst);

        $this->standfirstDataTransformer
            ->expects(static::once())
            ->method('write')
            ->willReturnSelf();
        $this->standfirstDataTransformer
            ->expects(static::once())
            ->method('read')
            ->willReturn($editorial['standfirstExpected']);

        $this->recommendedEditorialsDataTransformer
            ->expects(static::once())
            ->method('write')
            ->willReturnSelf();
        $this->recommendedEditorialsDataTransformer
            ->expects(static::once())
            ->method('read')
            ->willReturn($editorial['recommenderExpected']);

        $expectedResult['standfirst'] = $editorial['standfirstExpected'];
        $expectedResult['recommendedEditorials'] = $editorial['recommenderExpected'];
        $expectedResult['multimedia'] = $expectedOpeningMultimedia;

        $result = $this->editorialOrchestrator->execute($requestMock);

        $this->assertSame($expectedArgumentsBodyTags, $callArgumentsBodyElements);
        $this->assertSame($expectedArgumentsCreateUri, $callArgumentsCreateUri);
        $this->assertSame($expectedArgumentsSections, $callArgumentsSections);
        $this->assertSame($expectedArgumentsAlias, $callArgumentsAlias);
        $this->assertSame($expectedArgumentsEditorials, $callArgumentsEditorials);
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function canOrchestrateShouldReturnExpectedValue(): void
    {
        static::assertSame('editorial', $this->editorialOrchestrator->canOrchestrate());
    }

    /**
     * @param array<int, string>                $withAliasIds
     * @param array<int, Journalist|MockObject> $promisesJournalist
     * @param array<string>                     $promisesAliasIds
     * @param array<int, array<string, string>> $allJournalistsExpected
     * @param array<int, string>                $callArgumentsAlias
     * @param array<int, array<int, string>>    $expectedJournalistAliasIds
     *
     * @return array<int, string>
     */
    private function resolveSignatures(
        array $withAliasIds,
        array $promisesJournalist,
        array $promisesAliasIds,
        array $allJournalistsExpected,
        array &$callArgumentsAlias,
        array $expectedJournalistAliasIds,
    ): array {
        $expectedArgumentsAlias = $withAliasIds;
        $arrayMocks = array_combine($withAliasIds, $promisesAliasIds);

        $this->setupJournalistFactoryMock($expectedArgumentsAlias, $callArgumentsAlias, $arrayMocks);
        $this->setupQueryJournalistClientMock($promisesAliasIds, $promisesJournalist);
        $this->setupJournalistsDataTransformerMock(
            $withAliasIds,
            $promisesJournalist,
            $allJournalistsExpected,
            $expectedJournalistAliasIds
        );

        return $expectedArgumentsAlias;
    }

    /**
     * @param array<string>         $expectedArgumentsAlias
     * @param array<int, string>    $callArgumentsAlias
     * @param array<string, string> $arrayMocks
     *
     * @return void
     */
    private function setupJournalistFactoryMock(
        array $expectedArgumentsAlias,
        array &$callArgumentsAlias,
        array $arrayMocks,
    ): void {
        $this->journalistFactory->expects(static::exactly(\count($expectedArgumentsAlias)))
            ->method('buildAliasId')
            ->willReturnCallback(function ($strClass) use (&$callArgumentsAlias, $arrayMocks) {
                $callArgumentsAlias[] = $strClass;

                return $arrayMocks[$strClass];
            });
    }

    /**
     * @param array<string>                     $promisesAliasIds
     * @param array<int, Journalist|MockObject> $promisesJournalist
     *
     * @return void
     */
    private function setupQueryJournalistClientMock(
        array $promisesAliasIds,
        array $promisesJournalist,
    ): void {
        $withConsecutiveArgs = array_map(function ($aliasId) {
            return [$aliasId];
        }, $promisesAliasIds);

        $invokedCount = static::exactly(\count($promisesJournalist));
        $this->queryJournalistClient->expects($invokedCount)
            ->method('findJournalistByAliasId')
             ->willReturnCallback(function ($aliasId) use ($promisesJournalist, $withConsecutiveArgs, $invokedCount) {
                 static::assertEquals($withConsecutiveArgs[$invokedCount->numberOfInvocations() - 1][0], $aliasId);

                 return $promisesJournalist[$invokedCount->numberOfInvocations() - 1];
             });
    }

    /**
     * @param array<int, string>                $withAliasIds
     * @param array<int, Journalist|MockObject> $promisesJournalist
     * @param array<int, array<string, string>> $allJournalistExpected
     * @param array<int, array<int, string>>    $expectedJournalistAliasIds
     *
     * @return void
     */
    private function setupJournalistsDataTransformerMock(
        array $withAliasIds,
        array $promisesJournalist,
        array $allJournalistExpected,
        array $expectedJournalistAliasIds,
    ): void {
        $index = \count($promisesJournalist);
        $this->journalistsDataTransformer->expects(static::exactly($index))
            ->method('write')
            ->willReturnSelf();

        $this->journalistsDataTransformer->expects(static::exactly($index))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                ...$expectedJournalistAliasIds
            );
    }

    /**
     * @param MockObject $editorialMock
     *
     * @return MockObject|TagAlias
     */
    private function generateTagMock(MockObject $editorialMock): MockObject|TagAlias
    {
        $editorialTag = $this->createMock(Tag::class);
        $tag = $this->createMock(TagAlias::class);

        $tags = new Tags();
        $tags->addItem($editorialTag);

        $editorialMock
            ->expects(static::once())
            ->method('tags')
            ->willReturn($tags);

        $this->queryTagClient
            ->expects($this->once())
            ->method('findTagById')
            ->with($editorialTag->id()->id())
            ->willReturn($tag);

        return $tag;
    }

    private function getRequestMock(string $editorialId): MockObject|Request
    {
        $requestMock = $this->createMock(Request::class);
        $requestMock
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn($editorialId);

        return $requestMock;
    }

    /**
     * @param array{
     *       id: string,
     *       sectionId: string,
     *       signatures: array<int, string>,
     *       insertedNews: array<int, array{
     *           id: string,
     *           sectionId: string,
     *           signatures: array<int, string>,
     *           multimediaId: string
     *       }>,
     *       membershipCards: array<int, array{
     *           btns: array<int, array{
     *               urlMembership: string,
     *               url: string
     *           }>
     *       }>,
     *       bodyExpected: array<array<string, mixed>>
     *   } $editorial
     */
    private function getEditorialMock(array $editorial): MockObject
    {
        $editorialMock = $this->createMock(NewsBase::class);
        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->expects(static::exactly(1))
            ->method('id')
            ->willReturn($editorial['id']);
        $sourceEditorialMock = $this->createMock(SourceEditorial::class);
        $editorialMock->expects(static::exactly(1))
            ->method('id')
            ->willReturn($editorialIdMock);
        $editorialMock->expects(static::once())
            ->method('sourceEditorial')
            ->willReturn($sourceEditorialMock);
        $editorialMock->expects(static::once())
            ->method('isVisible')
            ->willReturn(true);
        $editorialMock->expects(static::once())
            ->method('sectionId')
            ->willReturn($editorial['sectionId']);

        return $editorialMock;
    }

    private function getSectionMock(string $id): MockObject
    {
        $sectionMock = $this->createMock(Section::class);
        $sectionIdMock = $this->createMock(SectionId::class);
        $sectionIdMock
            ->method('id')
            ->willReturn($id);
        $sectionMock
            ->method('id')
            ->willReturn($sectionIdMock);

        $sectionMock->expects(static::once())
            ->method('siteId')
            ->willReturn('siteId');

        return $sectionMock;
    }

    /**
     * @param array{
     *       id: string,
     *       sectionId: string,
     *       signatures: array<int, string>,
     *       insertedNews: array<int, array{
     *           id: string,
     *           sectionId: string,
     *           signatures: array<int, string>,
     *           multimediaId: string
     *       }>,
     *       membershipCards: array<int, array{
     *           btns: array<int, array{
     *               urlMembership: string,
     *               url: string
     *           }>
     *       }>,
     *       bodyExpected: array<array<string, mixed>>
     *   } $editorial
     * @param MockObject $editorialMock
     *
     * @return MockObject
     */
    private function getSignaturesMockByEditorial(array $editorial, MockObject $editorialMock): MockObject
    {
        $signaturesEditorialMocksArray = [];
        foreach ($editorial['signatures'] as $journalist) {
            $signatureMock = $this->createMock(Signature::class);
            $signatureIdMock = $this->createMock(SignatureId::class);
            $signatureIdMock->expects(static::once())
                ->method('id')
                ->willReturn($journalist);
            $signatureMock->expects(static::once())
                ->method('id')
                ->willReturn($signatureIdMock);
            $signaturesEditorialMocksArray[] = $signatureMock;
        }

        $signaturesEditorialsMock = $this->createMock(Signatures::class);
        $signaturesEditorialsMock->expects(static::once())
            ->method('getArrayCopy')
            ->willReturn($signaturesEditorialMocksArray);

        $editorialMock->expects(static::once())
            ->method('signatures')
            ->willReturn($signaturesEditorialsMock);

        return $editorialMock;
    }

    /**
     * @param array{
     *       id: string,
     *       sectionId: string,
     *       signatures: array<int, string>,
     *       insertedNews: array<int, array{
     *           id: string,
     *           sectionId: string,
     *           signatures: array<int, string>,
     *           multimediaId: string
     *       }>,
     *       membershipCards: array<int, array{
     *           btns: array<int, array{
     *               urlMembership: string,
     *               url: string
     *           }>
     *       }>,
     *       bodyExpected: array<array<string, mixed>>
     *   } $editorial
     * @param array<int, array<string, string>> $allJournalistsExpected
     *
     * @return array{
     *      0: array<int, BodyTagInsertedNews|MockObject>,
     *      1: array<string, array{
     *          editorial: Editorial|MockObject,
     *          section: Section|MockObject,
     *          multimediaId: string,
     *          signatures: array<int, array<string, mixed>>
     *      }>,
     *      2: array<int, Editorial|MockObject>,
     *      3: array<int, string>,
     *      4: array<int, Section|MockObject>,
     *      5: array<int, string>,
     *      6: array<int, string>
     *  }
     *   */
    private function getBodyTagsInsertedNewsByEditorial(array $editorial, array $allJournalistsExpected): array
    {
        $expectedInsertedNews = [];
        $bodyTagsInsertedNews = [];
        $promisesEditorials = [];
        $withEditorials = [];
        $promisesSections = [];
        $withSections = [];
        $withJournalistId = [];
        foreach ($editorial['insertedNews'] as $bodyTag) {
            $bodyElementMock = $this->createMock(BodyTagInsertedNews::class);

            $openingMock = $this->createMock(Opening::class);

            $bodyElementEditorialIdInsertedMock = $this->createMock(EditorialId::class);
            $bodyElementEditorialIdInsertedMock->expects(static::once())
                ->method('id')
                ->willReturn($bodyTag['id']);
            $bodyElementMock->expects(static::once())
                ->method('editorialId')
                ->willReturn($bodyElementEditorialIdInsertedMock);

            $editorialInsertedMock = $this->createMock(NewsBase::class);
            $editorialInsertedMock->expects(static::once())
                ->method('isVisible')
                ->willReturn(true);

            $editorialInsertedMock
                ->method('opening')
                ->willReturn($openingMock);

            $promisesEditorials[] = $editorialInsertedMock;
            $withEditorials[] = $bodyTag['id'];
            $sectionInsertedMock = $this->createMock(Section::class);
            $editorialInsertedMock->expects(static::once())
                ->method('sectionId')
                ->willReturn($bodyTag['sectionId']);
            $promisesSections[] = $sectionInsertedMock;
            $withSections[] = $bodyTag['sectionId'];
            $signaturesInsertedEditorialArray = [];
            $signaturesInsertedEditorialMocksArray = [];
            foreach ($bodyTag['signatures'] as $signatureInsertedId) {
                $signatureInsertedMock = $this->createMock(Signature::class);
                $signatureInsertedIdMock = $this->createMock(SignatureId::class);
                $signatureInsertedIdMock->expects(static::once())
                    ->method('id')
                    ->willReturn($signatureInsertedId);
                $withJournalistId[] = $signatureInsertedId;
                $signatureInsertedMock->expects(static::once())
                    ->method('id')
                    ->willReturn($signatureInsertedIdMock);

                $signaturesInsertedEditorialMocksArray[] = $signatureInsertedMock;
                $signaturesInsertedEditorialArray[] = $allJournalistsExpected[$signatureInsertedId];
            }
            $signaturesInsertedEditorialsMock = $this->createMock(Signatures::class);
            $editorialInsertedMock->expects(static::once())
                ->method('signatures')
                ->willReturn($signaturesInsertedEditorialsMock);
            $signaturesInsertedEditorialsMock->expects(static::once())
                ->method('getArrayCopy')
                ->willReturn($signaturesInsertedEditorialMocksArray);
            $expectedInsertedNews[$bodyTag['id']] = [
                'editorial' => $editorialInsertedMock,
                'section' => $sectionInsertedMock,
                'multimediaId' => '',
                'signatures' => $signaturesInsertedEditorialArray,
            ];
            $bodyTagsInsertedNews[] = $bodyElementMock;
        }

        return [
            $bodyTagsInsertedNews,
            $expectedInsertedNews,
            $promisesEditorials,
            $withEditorials,
            $promisesSections,
            $withSections,
            $withJournalistId,
        ];
    }

    /**
     * @param array{
     *        id: string,
     *        sectionId: string,
     *        signatures: array<int, string>,
     *        insertedNews: array<int, array{
     *            id: string,
     *            sectionId: string,
     *            signatures: array<int, string>,
     *            multimediaId: string
     *        }>,
     *        recommender: array<int, array{
     *            id: string,
     *            sectionId: string,
     *            signatures: array<int, string>,
     *            multimediaId: string
     *        }>,
     *        membershipCards: array<int, array{
     *           btns: array<int, array{
     *               urlMembership: string,
     *               url: string
     *           }>
     *        }>,
     *        bodyExpected: array<array<string, mixed>>,
     *        standfirstExpected: array<array<string, mixed>>,
     *        recommenderExpected: array<array<string, string>>
     *     } $editorial
     * @param array<int, array<string, string>> $allJournalistsExpected
     *
     * @return array{
     *     0: array<int, mixed>,
     *     1: array<int, mixed>,
     *     2: array<int, mixed>,
     *     3: array<int, mixed>,
     *     4: array<int, mixed>,
     *     5: array<int, mixed>,
     *     6: MockObject,
     * }
     *   */
    private function getRecommendedNewsByEditorial(array $editorial, MockObject $editorialMock, array $allJournalistsExpected): array
    {
        $expectedRecommendedNews = [];
        $promisesEditorials = [];
        $withEditorials = [];
        $promisesSections = [];
        $withSections = [];
        $withJournalistId = [];
        $recommenderIds = [];

        foreach ($editorial['recommender'] as $editorialRecommended) {
            $editorialId = $editorialRecommended['id'];
            $editorialIdRecommendedMock = $this->createMock(EditorialId::class);
            $editorialIdRecommendedMock->expects(static::once())
                ->method('id')
                ->willReturn($editorialId);
            $recommenderIds[] = $editorialIdRecommendedMock;
            $openingMock = $this->createMock(Opening::class);
            $editorialRecommendedMock = $this->createMock(NewsBase::class);
            $editorialRecommendedMock->expects(static::once())
                ->method('isVisible')
                ->willReturn(true);
            $editorialRecommendedMock->method('opening')
                ->willReturn($openingMock);
            $promisesEditorials[] = $editorialRecommendedMock;
            $withEditorials[] = $editorialId;
            $sectionRecommendedMock = $this->createMock(Section::class);
            $editorialRecommendedMock->expects(static::once())
                ->method('sectionId')
                ->willReturn($editorialRecommended['sectionId']);
            $promisesSections[] = $sectionRecommendedMock;
            $withSections[] = $editorialRecommended['sectionId'];
            $signaturesRecommendedEditorialArray = [];
            $signaturesRecommendedEditorialMocksArray = [];
            foreach ($editorialRecommended['signatures'] as $signatureRecommended) {
                $signatureRecommendedMock = $this->createMock(Signature::class);
                $signatureRecommendedIdMock = $this->createMock(SignatureId::class);
                $signatureRecommendedIdMock->expects(static::once())
                    ->method('id')
                    ->willReturn($signatureRecommended);
                $withJournalistId[] = $signatureRecommended;
                $signatureRecommendedMock->expects(static::once())
                    ->method('id')
                    ->willReturn($signatureRecommendedIdMock);

                $signaturesRecommendedEditorialMocksArray[] = $signatureRecommendedMock;
                $signaturesRecommendedEditorialArray[] = $allJournalistsExpected[$signatureRecommended];
            }
            $signaturesRecommendedEditorialsMock = $this->createMock(Signatures::class);
            $editorialRecommendedMock->expects(static::once())
                ->method('signatures')
                ->willReturn($signaturesRecommendedEditorialsMock);
            $signaturesRecommendedEditorialsMock->expects(static::once())
                ->method('getArrayCopy')
                ->willReturn($signaturesRecommendedEditorialMocksArray);
            $expectedRecommendedNews[$editorialId] = [
                'editorial' => $editorialRecommendedMock,
                'section' => $sectionRecommendedMock,
                'multimediaId' => '',
                'signatures' => $signaturesRecommendedEditorialArray,
            ];
        }

        $recommenderMock = $this->createMock(RecommendedEditorials::class);
        $recommenderMock->expects(static::once())
            ->method('editorialIds')
            ->willReturn($recommenderIds);

        $editorialMock->expects(static::once())
            ->method('recommendedEditorials')
            ->willReturn($recommenderMock);

        return [// @phpstan-ignore return.type
            $expectedRecommendedNews,
            $promisesEditorials,
            $withEditorials,
            $promisesSections,
            $withSections,
            $withJournalistId,
            $editorialMock,
        ];
    }

    /**
     * @param array{
     *       id: string,
     *       sectionId: string,
     *       signatures: array<int, string>,
     *       insertedNews: array<int, array{
     *           id: string,
     *           sectionId: string,
     *           signatures: array<int, string>,
     *           multimediaId: string
     *       }>,
     *       membershipCards: array<int, array{
     *           btns: array<int, array{
     *               urlMembership: string,
     *               url: string
     *           }>
     *       }>,
     *       bodyExpected: array<array<string, mixed>>
     *   } $editorial
     * @param array<string, string> $membershipLinkCombine
     * @param array<string>         $callArgumentsCreateUri
     *
     * @return array{
     *       0: array<int, BodyTagMembershipCard|MockObject>,
     *       1: array<string>
     *   }
     */
    private function getBodyTagsMembershipCardsByEditorial(array $editorial, array $membershipLinkCombine, &$callArgumentsCreateUri): array
    {
        $membershipCardsPromise = [];
        $urisMock = [];

        $expectedArgumentsCreateUri = [];
        foreach ($editorial['membershipCards'] as $bodytagsMembershipCard) {
            $bodyTagMembershipCardMock = $this->createMock(BodyTagMembershipCard::class);
            $btnsMock = $this->createMock(MembershipCardButtons::class);
            $btnsArray = [];
            foreach ($bodytagsMembershipCard['btns'] as $btn) {
                $url1 = $btn['url'];
                $url2 = $btn['urlMembership'];
                $btnMock = $this->createMock(MembershipCardButton::class);
                $btnMock->expects(static::once())
                    ->method('url')
                    ->willReturn($url1);
                $btnMock->expects(static::once())
                    ->method('urlMembership')
                    ->willReturn($url2);
                $btnsArray[] = $btnMock;
                $expectedArgumentsCreateUri[] = $url2;
                $expectedArgumentsCreateUri[] = $url1;
            }
            $btnsMock->expects(static::once())
                ->method('buttons')
                ->willReturn($btnsArray);
            $bodyTagMembershipCardMock->expects(static::once())
                ->method('buttons')
                ->willReturn($btnsMock);
            $membershipCardsPromise[] = $bodyTagMembershipCardMock;

            $uriMock = $this->createMock(UriInterface::class);
            $callArgumentsCreateUri = [];
            $this->uriFactory->expects(static::exactly(2))
                ->method('createUri')
                ->willReturnCallback(function ($strUrl) use (&$callArgumentsCreateUri, $uriMock) {
                    $callArgumentsCreateUri[] = $strUrl;

                    return $uriMock;
                });

            $urisMock = [$uriMock, $uriMock];
        }
        $promiseMock = $this->createMock(Promise::class);

        $this->queryMembershipClient->expects(static::once())
            ->method('getMembershipUrl')
            ->with(
                $editorial['id'],
                $urisMock,
                'el-confidencial',
                true
            )
            ->willReturn($promiseMock);

        $promiseMock->expects(static::once())
            ->method('wait')
            ->willReturn($membershipLinkCombine);

        return [$membershipCardsPromise, $expectedArgumentsCreateUri];
    }

    /**
     * @param array<int, string> $aliasIds
     *
     * @return array{
     *        0: array<int, Journalist|MockObject>,
     *        1: array<string>
     *    }
     */
    private function getJournalistPromisesMock(array $aliasIds): array
    {
        $withAlias = [];
        $promisesJournalist = [];
        foreach ($aliasIds as $aliasId) {
            $journalistMockArray = $this->createMock(Journalist::class);
            $aliasIdMock = $this->createMock(AliasId::class);
            $aliasIdMock
                ->method('id')
                ->willReturn($aliasId);
            $withAlias[] = $aliasIdMock;

            $promisesJournalist[] = $journalistMockArray;
        }

        return [$promisesJournalist, $withAlias];
    }

    #[Test]
    public function shouldGetOpeningWithOpeningAndResourceWhenIsTypeMultimediaPhoto(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('123');
        $editorial->method('opening')->willReturn($opening);

        $resourceIdMock = $this->createMock(ResourceId::class);
        $resourceIdMock->method('id')->willReturn('456');
        $multimedia = $this->createMock(MultimediaPhoto::class);
        $multimedia->method('resourceId')->willReturn($resourceIdMock);

        $photoMock = $this->createMock(Photo::class);

        $this->queryMultimediaOpeningClient
            ->method('findMultimediaById')
            ->with('123')
            ->willReturn($multimedia);

        $this->multimediaOrchestratorHandler
            ->expects(static::once())
            ->method('handler')
            ->with($multimedia)
            ->willReturn(['123' => [
                'opening' => $multimedia,
                'resource' => $photoMock,
            ]]);

        $resolveData = [];
        $reflection = new \ReflectionClass($this->editorialOrchestrator);

        $method = $reflection->getMethod('getOpening');
        $method->setAccessible(true);
        /** @var array{
         *      multimediaOpening?: array{123?: array{opening: MultimediaPhoto, resource: Photo}}
         * } $result
         */
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        $this->assertArrayHasKey('multimediaOpening', $result);
        $this->assertArrayHasKey('123', $result['multimediaOpening']);
        $this->assertSame($multimedia, $result['multimediaOpening']['123']['opening']);
        $this->assertSame($photoMock, $result['multimediaOpening']['123']['resource']);
    }

    #[Test]
    public function shouldGetOpeningWithOpeningAndWithoutResourceWhenIsNotTypeMultimediaPhoto(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $opening = $this->createMock(Opening::class);
        $opening->expects(static::exactly(2))
            ->method('multimediaId')
            ->willReturn('123');
        $editorial
            ->expects(static::once())
            ->method('opening')
            ->willReturn($opening);

        $multimedia = $this->createMock(Multimedia\MultimediaEmbedVideo::class);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with('123')
            ->willReturn($multimedia);

        $this->multimediaOrchestratorHandler
            ->expects(static::once())
            ->method('handler')
            ->with($multimedia)
            ->willReturn(['123' => [
                'opening' => $multimedia,
            ]]);

        $resolveData = [];
        $reflection = new \ReflectionClass($this->editorialOrchestrator);

        $method = $reflection->getMethod('getOpening');

        /** @var array{
         *      multimediaOpening?: array{123?: array{opening: Multimedia\MultimediaEmbedVideo}}
         * } $result
         */
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);
        $this->assertArrayHasKey('multimediaOpening', $result);
        $this->assertArrayHasKey('123', $result['multimediaOpening']);
        $this->assertSame($multimedia, $result['multimediaOpening']['123']['opening']);
    }

    #[Test]
    public function shouldReturnOnlyFulfilledMultimedia(): void
    {
        $mm1 = $this->createMock(Multimedia::class);
        $mm1->method('id')->willReturn('id1');

        $mm2 = $this->createMock(Multimedia::class);
        $mm2->method('id')->willReturn('id2');

        $mm3 = $this->createMock(Multimedia::class);
        $mm3->method('id')->willReturn('id3');

        $promises = [
            [
                'state' => 'fulfilled',
                'value' => $mm1,
            ],
            [
                'state' => 'rejected',
                'value' => $mm2,
            ],
            [
                'state' => 'fulfilled',
                'value' => $mm3,
            ],
        ];

        $method = new \ReflectionMethod($this->editorialOrchestrator, 'fulfilledMultimedia');
        static::assertFalse($method->isPrivate());
        static::assertTrue($method->isProtected());
        $method->setAccessible(true);
        $result = $method->invoke($this->editorialOrchestrator, $promises);

        static::assertSame([
            'id1' => $mm1,
            'id3' => $mm3,
        ], $result);
    }

    #[Test]
    public function createCallbackInvokesCallableWithParameters(): void
    {
        $callable = function ($element, ...$params) {
            return [$element, $params];
        };

        $element = 'foo';
        $params = ['bar' => 'baz', 'qux' => 'quux'];

        $method = new \ReflectionMethod($this->editorialOrchestrator, 'createCallback');
        static::assertFalse($method->isPrivate());
        static::assertTrue($method->isProtected());
        $method->setAccessible(true);

        $callback = $method->invokeArgs($this->editorialOrchestrator, [$callable, ...$params]);

        static::assertInstanceOf(\Closure::class, $callback);

        $result = $callback($element);

        static::assertEquals(['foo', $params], $result);
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
        static::assertFalse($method->isPrivate());
        static::assertTrue($method->isProtected());

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
        static::assertFalse($method->isPrivate());
        static::assertTrue($method->isProtected());

        $method->setAccessible(true);
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        static::assertEquals(['result' => 'data'], $result);
    }

    #[Test]
    public function addPhotoToArrayShouldFullfilledArray(): void
    {
        $id = 'photoId';
        $photo = $this->createMock(Photo::class);

        $this->queryMultimediaClient->expects($this->once())
            ->method('findPhotoById')
            ->with($id)
            ->willReturn($photo);

        $reflection = new \ReflectionMethod($this->editorialOrchestrator, 'addPhotoToArray');
        $reflection->setAccessible(true);

        $inputArray = [];
        /** @var array<string, Photo> $result */
        $result = $reflection->invoke($this->editorialOrchestrator, $id, $inputArray);

        $this->assertArrayHasKey($id, $result);
        $this->assertSame($photo, $result[$id]);
    }

    #[Test]
    public function addPhotoToArrayShouldThrowException(): void
    {
        $id = 'photoId';
        $exceptionMessage = 'Some error';

        $this->queryMultimediaClient->expects($this->once())
            ->method('findPhotoById')
            ->with($id)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exceptionMessage);

        $reflection = new \ReflectionMethod($this->editorialOrchestrator, 'addPhotoToArray');
        $reflection->setAccessible(true);

        $inputArray = [];
        $result = $reflection->invoke($this->editorialOrchestrator, $id, $inputArray);

        $this->assertSame($inputArray, $result);
    }

    #[Test]
    public function getMultimediaShouldRequestViaAsync(): void
    {
        $multimediaId = 'existing-id';
        $multimediaIdDomain = new MultimediaId($multimediaId);
        $mockMultimedia = $this->createMock(PhotoExist::class);
        $mockMultimedia->method('id')->willReturn($multimediaIdDomain);

        $this->queryMultimediaClient
            ->method('findMultimediaById')
            ->with($multimediaId, true)
            ->willReturn(new FulfilledPromise($mockMultimedia));

        $getAsyncMultimedia = new \ReflectionMethod($this->editorialOrchestrator, 'getAsyncMultimedia');
        static::assertTrue($getAsyncMultimedia->isPrivate());
        $getAsyncMultimedia->setAccessible(true);
        $promise = $getAsyncMultimedia->invokeArgs($this->editorialOrchestrator, [$mockMultimedia, ['multimedia' => []]]);
    }

    #[Test]
    public function shouldGetMetaImageWithResourceWhenIsTypeMultimediaPhoto(): void
    {
        $metaImageId = '456';

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('metaImage')->willReturn($metaImageId);

        $resourceIdMock = $this->createMock(ResourceId::class);
        $resourceIdMock->method('id')->willReturn('789');

        $multimedia = $this->createMock(MultimediaPhoto::class);
        $multimedia->method('resourceId')->willReturn($resourceIdMock);

        $photoMock = $this->createMock(Photo::class);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($metaImageId)
            ->willReturn($multimedia);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findPhotoById')
            ->with($resourceIdMock)
            ->willReturn($photoMock);

        $resolveData = [];
        $reflection = new \ReflectionClass($this->editorialOrchestrator);

        $method = $reflection->getMethod('getMetaImage');
        $method->setAccessible(true);

        /** @var array{
         *      multimediaOpening?: array{456?: array{opening: MultimediaPhoto, resource: Photo}}
         * } $result
         */
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        $this->assertArrayHasKey('multimediaOpening', $result);
        $this->assertArrayHasKey($metaImageId, $result['multimediaOpening']);
        $this->assertSame($multimedia, $result['multimediaOpening'][$metaImageId]['opening']);
        $this->assertSame($photoMock, $result['multimediaOpening'][$metaImageId]['resource']);
    }

    #[Test]
    public function shouldGetMetaImageReturnUnchangedResolveDataWhenIsNotTypeMultimediaPhoto(): void
    {
        $metaImageId = '456';

        $editorial = $this->createMock(Editorial::class);
        $editorial->method('metaImage')->willReturn($metaImageId);

        $multimedia = $this->createMock(Multimedia\MultimediaEmbedVideo::class);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($metaImageId)
            ->willReturn($multimedia);

        $this->queryMultimediaOpeningClient
            ->expects(static::never())
            ->method('findPhotoById');

        $resolveData = ['existingKey' => 'existingValue'];
        $reflection = new \ReflectionClass($this->editorialOrchestrator);

        $method = $reflection->getMethod('getMetaImage');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        $this->assertSame($resolveData, $result);
        $this->assertArrayNotHasKey('multimediaOpening', $result);
    }

    #[Test]
    public function shouldGetMetaImageReturnUnchangedResolveDataWhenMetaImageIsEmpty(): void
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
        $reflection = new \ReflectionClass($this->editorialOrchestrator);

        $method = $reflection->getMethod('getMetaImage');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        $this->assertSame($resolveData, $result);
    }

    #[Test]
    public function shouldLogErrorAndContinueWhenRecommendedEditorialThrowsException(): void
    {
        $mainEditorialId = 'main-editorial-123';
        $recommendedEditorialId = 'recommended-456';
        $errorMessage = 'Recommended editorial not found';

        $requestMock = $this->createMock(Request::class);
        $requestMock->method('get')->with('id')->willReturn($mainEditorialId);

        $recommendedEditorialIdMock = $this->createMock(EditorialId::class);
        $recommendedEditorialIdMock->method('id')->willReturn($recommendedEditorialId);

        $recommendedEditorialsMock = $this->createMock(RecommendedEditorials::class);
        $recommendedEditorialsMock->method('editorialIds')->willReturn([$recommendedEditorialIdMock]);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock->method('bodyElementsOf')->willReturn([]);

        $openingMock = $this->createMock(Opening::class);
        $openingMock->method('multimediaId')->willReturn('');

        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn('');
        $multimediaMock = $this->createMock(MultimediaEditorial::class);
        $multimediaMock->method('id')->willReturn($multimediaIdMock);

        $signaturesMock = $this->createMock(Signatures::class);
        $signaturesMock->method('getArrayCopy')->willReturn([]);

        $tagsMock = new Tags();

        $sourceEditorialMock = $this->createMock(SourceEditorial::class);

        $mainEditorialMock = $this->createMock(NewsBase::class);
        $mainEditorialMock->method('sourceEditorial')->willReturn($sourceEditorialMock);
        $mainEditorialMock->method('isVisible')->willReturn(true);
        $mainEditorialMock->method('sectionId')->willReturn('section-1');
        $mainEditorialMock->method('body')->willReturn($bodyMock);
        $mainEditorialMock->method('recommendedEditorials')->willReturn($recommendedEditorialsMock);
        $mainEditorialMock->method('opening')->willReturn($openingMock);
        $mainEditorialMock->method('multimedia')->willReturn($multimediaMock);
        $mainEditorialMock->method('signatures')->willReturn($signaturesMock);
        $mainEditorialMock->method('tags')->willReturn($tagsMock);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('el-confidencial');

        $this->queryEditorialClient->method('findEditorialById')
            ->willReturnCallback(function ($id) use ($mainEditorialId, $mainEditorialMock, $recommendedEditorialId, $errorMessage) {
                if ($id === $mainEditorialId) {
                    return $mainEditorialMock;
                }
                if ($id === $recommendedEditorialId) {
                    throw new \Exception($errorMessage);
                }

                return $mainEditorialMock;
            });

        $this->querySectionClient->method('findSectionById')->willReturn($sectionMock);

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock->method('wait')->willReturn([]);
        $this->queryMembershipClient->method('getMembershipUrl')->willReturn($promiseMock);

        $this->appsDataTransformer->method('write')->willReturnSelf();
        $this->appsDataTransformer->method('read')->willReturn([
            'id' => $mainEditorialId,
            'section' => [],
            'countComments' => 0,
            'tags' => [],
            'multimedia' => [],
        ]);
        $this->bodyDataTransformer->method('execute')->willReturn([]);
        $this->standfirstDataTransformer->method('write')->willReturnSelf();
        $this->standfirstDataTransformer->method('read')->willReturn([]);
        $this->recommendedEditorialsDataTransformer->method('write')->willReturnSelf();
        $this->recommendedEditorialsDataTransformer->method('read')->willReturn([]);

        $this->queryLegacyClient->method('findCommentsByEditorialId')->willReturn(['options' => ['totalrecords' => 0]]);

        $this->logger->expects(static::once())
            ->method('error')
            ->with($errorMessage);

        $result = $this->editorialOrchestrator->execute($requestMock);

        static::assertArrayHasKey('id', $result);
    }

    #[Test]
    public function shouldLogErrorWhenResolvingMembershipLinksThrowsException(): void
    {
        $errorMessage = 'Promise resolution failed';
        $exception = new \Exception($errorMessage);

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock
            ->expects(static::once())
            ->method('wait')
            ->willThrowException($exception);

        $reflection = new \ReflectionClass($this->editorialOrchestrator);
        $method = $reflection->getMethod('resolvePromiseMembershipLinks');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->editorialOrchestrator, [$promiseMock, []]);

        static::assertSame([], $result);
    }

    #[Test]
    public function getOpeningLogsWarningWhenOrchestratorTypeNotExist(): void
    {
        $editorialId = '123';
        $multimediaId = 'multimedia-456';
        $multimediaType = 'unsupported-type';

        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn($multimediaId);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('opening')->willReturn($opening);

        $multimedia = $this->createMock(Multimedia\Multimedia::class);
        $multimedia->method('type')->willReturn($multimediaType);

        $this->queryMultimediaOpeningClient
            ->expects(static::once())
            ->method('findMultimediaById')
            ->with($multimediaId)
            ->willReturn($multimedia);

        $this->multimediaOrchestratorHandler
            ->expects(static::once())
            ->method('handler')
            ->with($multimedia)
            ->willThrowException(new OrchestratorTypeNotExistException());

        $this->logger
            ->expects(static::once())
            ->method('warning');

        $reflection = new \ReflectionClass($this->editorialOrchestrator);
        $method = $reflection->getMethod('getOpening');
        $method->setAccessible(true);

        $resolveData = [];
        $result = $method->invokeArgs($this->editorialOrchestrator, [$editorial, $resolveData]);

        static::assertIsArray($result);
        static::assertArrayNotHasKey('multimediaOpening', $result);
    }
}
