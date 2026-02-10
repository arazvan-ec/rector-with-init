<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagInsertedNewsDataTransformer;
use App\Infrastructure\Service\Thumbor;
use App\Tests\Application\DataTransformer\Apps\Body\DataProvider\BodyTagInsertedNewsDataProvider;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\EditorialTitles;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use Ec\Multimedia\Domain\Model\Clipping;
use Ec\Multimedia\Domain\Model\Clippings;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class BodyTagInsertedNewsDataTransformerTest extends TestCase
{
    private BodyTagInsertedNewsDataTransformer $transformer;
    private MockObject $thumbor;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->transformer = new BodyTagInsertedNewsDataTransformer(
            $this->thumbor,
            'dev'
        );
    }

    /**
     * @param array<string, mixed>                             $data
     * @param array{signaturesWithIndexId: array<int, string>} $allSignatures
     * @param array<string, mixed>                             $expected
     */
    #[DataProviderExternal(BodyTagInsertedNewsDataProvider::class, 'getData')]
    #[Test]
    public function transformBodyTagInsertedNewsWithSignatures(array $data, array $allSignatures, array $expected): void
    {
        $resolveData = [];
        $id = 'editorial_id';
        $title = 'title body tag inserted news';
        $multimediaId = '1';

        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);

        $editorialIdBodyTagMock = $this->createMock(EditorialId::class);
        $editorialIdBodyTagMock->expects(static::once())
            ->method('id')
            ->willReturn($id);

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->expects(static::exactly(2))
            ->method('id')
            ->willReturn($id);

        $bodyElementMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyElementMock->expects(static::once())
            ->method('editorialId')
            ->willReturn($editorialIdBodyTagMock);

        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytaginsertednews');

        $editorialMock->expects(static::exactly(2))
            ->method('id')
            ->willReturn($editorialIdMock);

        $editorialTitlesMock = $this->createMock(EditorialTitles::class);
        $editorialTitlesMock->expects(static::once())
            ->method('title')
            ->willReturn($title);

        $editorialMock->expects(static::exactly(2))
            ->method('editorialTitles')
            ->willReturn($editorialTitlesMock);

        $clippingsMock = $this->createMock(Clippings::class);
        $multimediaMock = $this->createMock(\Ec\Multimedia\Domain\Model\Multimedia::class);
        $multimediaMock->expects(static::once())
            ->method('clippings')
            ->willReturn($clippingsMock);

        $clippingMock = $this->createMock(Clipping::class);

        $clippingsMock->expects(static::once())
            ->method('clippingByType')
            ->with(ClippingTypes::SIZE_ARTICLE_4_3)
            ->willReturn($clippingMock);

        $fileMock = $multimediaMock->method('file')
            ->willReturn($data['file']);

        $thumborPhoto = $data['photo'];

        $this->thumbor->method('retriveCropBodyTagPicture')
            ->willReturnCallback(function (string $fileImage, string $width, string $height, int $topY, int $bottomX, int $bottomY) use (
                &$callArguments,
                $thumborPhoto
            ) {
                $callArguments[] = [
                    $fileImage,
                    $width,
                    $height,
                    $topY,
                    $bottomX,
                    $bottomY,
                ];

                return $thumborPhoto;
            });

        $resolveData['insertedNews'] = [
            $id => [
                'editorial' => $editorialMock,
                'signatures' => $data['signaturesIndexes'],
                'section' => $sectionMock,
                'multimediaId' => $multimediaId,
            ],
        ];

        $resolveData['shots'] = $data['shots'];
        $resolveData['signatures'] = $allSignatures['signaturesWithIndexId'];

        $resolveData['multimedia'] = [];
        $resolveData['multimedia'][$multimediaId] = $multimediaMock;

        $result = $this->transformer->write($bodyElementMock, $resolveData)->read();

        $this->assertSame($expected, $result);
    }

    /**
     * @param array<string, mixed>                             $data
     * @param array{signaturesWithIndexId: array<int, string>} $allSignatures
     * @param array<string, mixed>                             $expected
     */
    #[DataProviderExternal(BodyTagInsertedNewsDataProvider::class, 'getData')]
    #[Test]
    public function transformBodyTagInsertedNewsWithMultimediaOpening(array $data, array $allSignatures, array $expected): void
    {
        $resolveData = [];
        $id = 'editorial_id';
        $title = 'title body tag inserted news';
        $multimediaId = '1';

        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);

        $editorialIdBodyTagMock = $this->createMock(EditorialId::class);
        $editorialIdBodyTagMock->expects(static::once())
            ->method('id')
            ->willReturn($id);

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->expects(static::exactly(2))
            ->method('id')
            ->willReturn($id);

        $bodyElementMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyElementMock->expects(static::once())
            ->method('editorialId')
            ->willReturn($editorialIdBodyTagMock);

        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytaginsertednews');

        $editorialMock->expects(static::exactly(2))
            ->method('id')
            ->willReturn($editorialIdMock);

        $editorialTitlesMock = $this->createMock(EditorialTitles::class);
        $editorialTitlesMock->expects(static::once())
            ->method('title')
            ->willReturn($title);

        $editorialMock->expects(static::exactly(2))
            ->method('editorialTitles')
            ->willReturn($editorialTitlesMock);

        $clippingsMock = $this->createMock(\Ec\Multimedia\Domain\Model\Multimedia\Clippings::class);
        $multimediaMock = $this->createMock(MultimediaPhoto::class);
        $multimediaMock
            ->method('clippings')
            ->willReturn($clippingsMock);

        $clippingMock = $this->createMock(\Ec\Multimedia\Domain\Model\Multimedia\Clipping::class);

        $clippingsMock
            ->method('clippingByType')
            ->with(\Ec\Multimedia\Domain\Model\Multimedia\ClippingTypes::SIZE_ARTICLE_4_3)
            ->willReturn($clippingMock);

        $thumborPhoto = $data['photo'];

        $this->thumbor->method('retriveCropBodyTagPicture')
            ->willReturnCallback(function (string $fileImage, string $width, string $height, int $topY, int $bottomX, int $bottomY) use (
                &$callArguments,
                $thumborPhoto
            ) {
                $callArguments[] = [
                    $fileImage,
                    $width,
                    $height,
                    $topY,
                    $bottomX,
                    $bottomY,
                ];

                return $thumborPhoto;
            });

        $resolveData['insertedNews'] = [
            $id => [
                'editorial' => $editorialMock,
                'signatures' => $data['signaturesIndexes'],
                'section' => $sectionMock,
                'multimediaId' => $multimediaId,
            ],
        ];

        $resolveData['shots'] = $data['shots'];
        $resolveData['signatures'] = $allSignatures['signaturesWithIndexId'];

        $resolveData['multimediaOpening'] = [];
        $resolveData['multimediaOpening'][$multimediaId]['opening'] = $multimediaMock;
        $resolveData['multimediaOpening'][$multimediaId]['resource'] = $this->createMock(Photo::class);

        $result = $this->transformer->write($bodyElementMock, $resolveData)->read();

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function canTransformShouldReturnBodyTagInsertedNewsString(): void
    {
        static::assertSame(BodyTagInsertedNews::class, $this->transformer->canTransform());
    }

    #[Test]
    public function readShouldThrowExceptionWhenEditorialIdNotFoundInInsertedNews(): void
    {
        $editorialId = 'non_existent_editorial_id';

        $bodyElementMock = $this->createMock(BodyTagInsertedNews::class);
        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytaginsertednews');

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->expects(static::once())
            ->method('id')
            ->willReturn($editorialId);

        $bodyElementMock->expects(static::once())
            ->method('editorialId')
            ->willReturn($editorialIdMock);

        $resolveData = [
            'insertedNews' => [
                'different_editorial_id' => [
                    'editorial' => $this->createMock(Editorial::class),
                    'signatures' => [],
                    'section' => $this->createMock(Section::class),
                    'multimediaId' => '1',
                ],
            ],
            'multimedia' => [],
            'signatures' => [],
        ];

        $this->expectException(BodyDataTransformerNotFoundException::class);
        $this->expectExceptionMessage('Inserted news: editorial not found for id: '.$editorialId);

        $this->transformer->write($bodyElementMock, $resolveData)->read();
    }
}
