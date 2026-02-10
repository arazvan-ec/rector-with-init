<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps;

use App\Application\DataTransformer\Apps\DetailsAppsDataTransformer;
use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\EditorialTitles;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Editorial\Domain\Model\SignatureId;
use Ec\Editorial\Domain\Model\Signatures;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Section\Domain\Model\Section;
use Ec\Section\Domain\Model\SectionId;
use Ec\Tag\Domain\Model\Tag;
use Ec\Tag\Domain\Model\TagId;
use Ec\Tag\Domain\Model\TagType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
#[CoversClass(DetailsAppsDataTransformer::class)]
class DetailsAppsDataTransformerTest extends TestCase
{
    private DetailsAppsDataTransformer $transformer;

    /** @var QueryLegacyClient|MockObject */
    private QueryLegacyClient $queryLegacyClient;

    protected function setUp(): void
    {
        $this->queryLegacyClient = $this->createMock(QueryLegacyClient::class);
        $this->transformer = new DetailsAppsDataTransformer('dev');
    }

    #[Test]
    public function writeAndReadShouldReturnCorrectArray(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $journalist = $this->createMock(Journalist::class);
        $section = $this->createMock(Section::class);
        $tag = $this->createMock(Tag::class);

        $this->transformer->write($editorial, $section, [$tag]);
        $result = $this->transformer->read();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('section', $result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('titles', $result);
        $this->assertArrayHasKey('title', (array) $result['titles']);
        $this->assertArrayHasKey('preTitle', (array) $result['titles']);
        $this->assertArrayHasKey('urlTitle', (array) $result['titles']);
        $this->assertArrayHasKey('mobileTitle', (array) $result['titles']);
        $this->assertArrayHasKey('lead', $result);
        $this->assertArrayHasKey('publicationDate', $result);
        $this->assertArrayHasKey('updatedOn', $result);
        $this->assertArrayHasKey('endOn', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('id', (array) $result['type']);
        $this->assertArrayHasKey('name', (array) $result['type']);
        $this->assertArrayHasKey('indexable', $result);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertArrayHasKey('published', $result);
        $this->assertArrayHasKey('closingModeId', $result);
        $this->assertArrayHasKey('commentable', $result);
        $this->assertArrayHasKey('isBrand', $result);
        $this->assertArrayHasKey('isAmazonOnsite', $result);
        $this->assertArrayHasKey('contentType', $result);
        $this->assertArrayHasKey('canonicalEditorialId', $result);
        $this->assertArrayHasKey('urlDate', $result);
        $this->assertArrayHasKey('countWords', $result);
    }

    #[Test]
    public function transformerEditorialShouldReturnCorrectEditorialArray(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $editorialId = $this->createMock(EditorialId::class);

        $editorial->method('id')->willReturn($editorialId);
        $editorialId->method('id')->willReturn('12345');

        $editorial->method('editorialType')->willReturn('news');
        $editorial->method('editorialTitles')->willReturn($this->createMock(EditorialTitles::class));
        $editorial->method('lead')->willReturn('Lead text');
        $editorial->method('publicationDate')->willReturn(new \DateTime('2023-01-01 00:00:00'));
        $editorial->method('endOn')->willReturn(new \DateTime('2023-01-02 00:00:00'));
        $editorial->method('indexed')->willReturn(true);
        $editorial->method('isDeleted')->willReturn(false);
        $editorial->method('isPublished')->willReturn(true);
        $editorial->method('closingModeId')->willReturn('1');
        $editorial->method('canComment')->willReturn(true);
        $editorial->method('isBrand')->willReturn(false);
        $editorial->method('isAmazonOnsite')->willReturn(false);
        $editorial->method('contentType')->willReturn('article');
        $editorial->method('canonicalEditorialId')->willReturn('54321');
        $editorial->method('urlDate')->willReturn(new \DateTime('2023-01-01 00:00:00'));

        $this->queryLegacyClient->method('findCommentsByEditorialId')->willReturn(['options' => ['totalrecords' => 10]]);

        $this->transformer->write($editorial, $this->createMock(Section::class), []);
        $result = $this->transformer->read();

        $this->assertEquals('12345', $result['id']);
        $this->assertEquals('Lead text', $result['lead']);
        $this->assertEquals('2023-01-01 00:00:00', $result['publicationDate']);
        $this->assertEquals('2023-01-02 00:00:00', $result['endOn']);
        $this->assertEquals('registry', $result['closingModeId']);
        $this->assertTrue($result['indexable']);
        $this->assertFalse($result['deleted']);
        $this->assertTrue($result['published']);
        $this->assertTrue($result['commentable']);
        $this->assertFalse($result['isAmazonOnsite']);
        $this->assertEquals('article', $result['contentType']);
        $this->assertEquals('54321', $result['canonicalEditorialId']);
        $this->assertEquals('2023-01-01 00:00:00', $result['urlDate']);
        $this->assertCount(23, $result);
    }

    /**
     * @param Journalist[]          $signatures
     * @param array<string, string> $aliasIds
     * @param array<string, mixed>  $expected
     */
    public function transformerJournalistsNoPrivateAlias(array $signatures, array $aliasIds, array $expected): void
    {
        $signaturesArrayMock = [];
        $signaturesMock = $this->createMock(Signatures::class);
        foreach ($aliasIds as $id) {
            $signatureMock = $this->createMock(Signature::class);
            $signatureIdMock = $this->createMock(SignatureId::class);
            $signatureIdMock->expects(static::once())
                ->method('id')
                ->willReturn($id);
            $signatureMock->expects(static::once())
                ->method('id')
                ->willReturn($signatureIdMock);
            $signaturesArrayMock[] = $signatureMock;
        }

        $signaturesMock->expects(static::once())
            ->method('getArrayCopy')
            ->willReturn($signaturesArrayMock);

        $sectionId = $this->createMock(SectionId::class);
        $sectionId->method('id')->willReturn('sectionId');

        $section = $this->createMock(Section::class);
        $section->method('id')->willReturn($sectionId);
        $section->method('name')->willReturn('SectionName');
        $section->method('siteId')->willReturn('siteId');
        $section->method('isSubdomainBlog')->willReturn(false);
        $section->method('getPath')->willReturn('section-path');

        $editorial = $this->createMock(Editorial::class);
        $editorial->expects(static::once())
            ->method('signatures')
            ->willReturn($signaturesMock);
        $tag = $this->createMock(Tag::class);

        $result = $this->transformer->write($editorial, $section, [$tag])->read();

        $this->assertArrayHasKey('signatures', $result);
        $this->assertSame($expected, $result['signatures']);
    }

    #[Test]
    public function transformerSectionShouldReturnCorrectSection(): void
    {
        $section = $this->createMock(Section::class);
        $sectionId = $this->createMock(SectionId::class);
        $section->method('id')->willReturn($sectionId);
        $section->method('name')->willReturn('Section Name');
        $section->method('getPath')->willReturn('section-path');
        $section->method('siteId')->willReturn('siteId');
        $section->method('isSubdomainBlog')->willReturn(false);
        $section->method('encodeName')->willReturn('espana');

        $editorial = $this->createMock(Editorial::class);

        $tag = $this->createMock(Tag::class);

        $this->transformer->write($editorial, $section, [$tag]);
        /** @var array{section: array{id: string, name: string, url: string, encodeName: string}} $result */
        $result = $this->transformer->read();

        $this->assertEquals($sectionId, $result['section']['id']);
        $this->assertEquals($section->name(), $result['section']['name']);
        $this->assertEquals('https://www.elconfidencial.dev/section-path', $result['section']['url']);
        static::assertSame($section->encodeName(), $result['section']['encodeName']);
    }

    #[Test]
    public function transformerTagsShouldReturnCorrectTags(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $tag = $this->createMock(Tag::class);
        $tagId = $this->createMock(TagId::class);
        $tagId->method('id')->willReturn('tagId');
        $tag->method('id')->willReturn($tagId);
        $tag->method('name')->willReturn('Tag Name');
        $type = $this->createMock(TagType::class);
        $type->method('name')->willReturn('Type Name');
        $tag->method('type')->willReturn($type);

        $this->transformer->write($editorial, $section, [$tag]);
        /** @var array{tags: array<int, array{id: string, name: string, url: string}>} $result */
        $result = $this->transformer->read();

        $this->assertEquals($tagId->id(), $result['tags'][0]['id']);
        $this->assertEquals($tag->name(), $result['tags'][0]['name']);
        $this->assertEquals(
            'https://www.elconfidencial.dev/tags/type-name/tag-name-tagId',
            $result['tags'][0]['url']
        );
    }

    #[Test]
    public function transformerShouldReturnEmptyTagsArrayWhenNoTagsAreProvided(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $tags = [];

        $this->transformer->write($editorial, $section, $tags);
        $result = $this->transformer->read();

        $this->assertArrayHasKey('tags', $result);
        $this->assertEmpty($result['tags']);
    }

    #[Test]
    public function transformerOptionsShouldReturnCorrectOptions(): void
    {
        $editorial = $this->createMock(Editorial::class);
        $section = $this->createMock(Section::class);
        $sectionId = $this->createMock(SectionId::class);
        $section->method('id')->willReturn($sectionId);
        $section->method('name')->willReturn('Section Name');
        $section->method('getPath')->willReturn('section-path');
        $section->method('siteId')->willReturn('siteId');
        $section->method('isSubdomainBlog')->willReturn(false);
        $section->method('encodeName')->willReturn('espana');

        $this->transformer->write($editorial, $section, []);
        /** @var array{
         *     adsOptions: array<int, array{id: string, name: string, url: string, encodeName: string}>,
         *     analiticsOptions: array<int, array{id: string, name: string, url: string, encodeName: string}>
         * } $result */
        $result = $this->transformer->read();

        static::assertEquals($sectionId, $result['adsOptions'][0]['id']);
        static::assertSame($section->name(), $result['adsOptions'][0]['name']);
        static::assertSame('https://www.elconfidencial.dev/section-path', $result['adsOptions'][0]['url']);
        static::assertSame('espana', $result['adsOptions'][0]['encodeName']);

        static::assertEquals($sectionId, $result['analiticsOptions'][0]['id']);
        static::assertSame($section->name(), $result['analiticsOptions'][0]['name']);
        static::assertSame('https://www.elconfidencial.dev/section-path', $result['analiticsOptions'][0]['url']);
        static::assertSame($section->encodeName(), $result['analiticsOptions'][0]['encodeName']);
    }
}
