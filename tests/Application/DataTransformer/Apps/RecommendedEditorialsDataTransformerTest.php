<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps;

use App\Application\DataTransformer\Apps\RecommendedEditorialsDataTransformer;
use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\EditorialNews;
use Ec\Editorial\Domain\Model\EditorialTitles;
use Ec\Multimedia\Domain\Model\Clipping;
use Ec\Multimedia\Domain\Model\Clippings;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\exactly;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class RecommendedEditorialsDataTransformerTest extends TestCase
{
    private RecommendedEditorialsDataTransformer $transformer;

    private MockObject $thumbor;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->transformer = new RecommendedEditorialsDataTransformer('dev', $this->thumbor);
    }

    #[Test]
    public function testWriteShouldAssignedValueAndReturnSelf(): void
    {
        $editorialMock1 = $this->createMock(Editorial::class);
        $editorialMock2 = $this->createMock(Editorial::class);

        $editorials = [$editorialMock1, $editorialMock2];

        $result = $this->transformer->write($editorials, ['test1' => ['test2' => ['test3' => 'value']]]);

        self::assertEquals($this->transformer, $result);
    }

    #[Test]
    public function testReadShouldReturnCorrectArrayData(): void
    {
        $recommendedEditorialId = ['7422', '7423'];
        $multimediaId = '2532';
        $membershipLink = 'https://snaapy.elconfidencial.dev/editorials/editorial_id';
        $title = ['title recommended editorial', 'title recommended editorial 2'];

        $editorialMock = $this->createMock(EditorialNews::class);
        $sectionMock = $this->createMock(Section::class);
        $multimediaMock = $this->createMock(Multimedia::class);
        $photoMock = $this->createMock(Photo::class);

        $editorialId1 = $this->createMock(EditorialId::class);
        $editorialId1->expects(self::never())
            ->method('id')
            ->willReturn($recommendedEditorialId[0]);
        $editorialMock->expects(self::never())
            ->method('id')
            ->willReturn($editorialId1);

        $editorialId2 = $this->createMock(EditorialId::class);
        $editorialId2->expects(self::never())
            ->method('id')
            ->willReturn($recommendedEditorialId[1]);
        $editorialMock->expects(self::never())
            ->method('id')
            ->willReturn($editorialId2);
        /** @var array<string, (array<int|string, (array<string, array<int, string>|MockObject|string>)|MockObject|string>)|string|null> $resolveData */
        $resolveData = [
            'multimedia' => [
                '2532' => $multimediaMock,
                '2506' => $multimediaMock,
            ],
            'recommendedEditorials' => [
                $recommendedEditorialId[0] => [
                    'editorial' => $editorialMock,
                    'section' => $sectionMock,
                    'signatures' => [
                        '20116' => 'signature',
                    ],
                    'multimediaId' => $multimediaId,
                ],
                $recommendedEditorialId[1] => [
                    'editorial' => $editorialMock,
                    'section' => $sectionMock,
                    'signatures' => [
                        '20116' => 'signature',
                    ],
                    'multimediaId' => $multimediaId,
                ],
            ],
            'insertedNews' => [
                '7422' => [
                    'editorial' => $editorialMock,
                    'section' => $sectionMock,
                    'signatures' => [
                        '20116',
                        '20117',
                    ],
                    'multimediaId' => $multimediaId,
                ],
            ],
            'photoFromBodyTags' => [
                '707' => $photoMock,
            ],
            'membershipLinkCombine' => [
                $membershipLink => $membershipLink,
            ],
            'shots' => [
                '202w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                '144w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                '128w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
            ],
            'photo' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
        ];

        $editorialMock1 = $this->createMock(Editorial::class);
        $editorialMock2 = $this->createMock(Editorial::class);

        $editorialId1 = $this->createMock(EditorialId::class);
        $editorialId1->expects(exactly(2))
            ->method('id')
            ->willReturn('7422');

        $editorialId2 = $this->createMock(EditorialId::class);
        $editorialId2->expects(exactly(2))
            ->method('id')
            ->willReturn('7423');
        $editorialMock1->expects(exactly(2))
            ->method('id')
            ->willReturn($editorialId1);
        $editorialMock2->expects(exactly(2))
            ->method('id')
            ->willReturn($editorialId2);

        $editorialTitlesMock1 = $this->createMock(EditorialTitles::class);
        $editorialTitlesMock1->expects(static::once())
            ->method('title')
            ->willReturn($title[0]);

        $editorialMock1->expects(exactly(2))
            ->method('editorialTitles')
            ->willReturn($editorialTitlesMock1);

        $editorialTitlesMock2 = $this->createMock(EditorialTitles::class);
        $editorialTitlesMock2->expects(static::once())
            ->method('title')
            ->willReturn($title[1]);

        $editorialMock2->expects(exactly(2))
            ->method('editorialTitles')
            ->willReturn($editorialTitlesMock2);

        $editorials = [$editorialMock1, $editorialMock2];

        $clippingsMock = $this->createMock(Clippings::class);
        $multimediaMock->expects(exactly(2))
            ->method('clippings')
            ->willReturn($clippingsMock);

        $clippingMock = $this->createMock(Clipping::class);

        $clippingsMock->expects(exactly(2))
            ->method('clippingByType')
            ->with(ClippingTypes::SIZE_ARTICLE_4_3)
            ->willReturn($clippingMock);

        $thumborPhoto = $resolveData['photo'];

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

        /** @var array<string, array<string, array<string, mixed>>> $resolveData */
        $result = $this->transformer->write($editorials, $resolveData)->read();

        $expected = [
            0 => [
                'type' => 'recommendededitorial',
                'editorialId' => $recommendedEditorialId[0],
                'signatures' => [
                    '20116' => 'signature',
                ],
                'editorial' => 'https://www.elconfidencial.dev/_'.$recommendedEditorialId[0],
                'title' => 'title recommended editorial',
                'shots' => [
                    '202w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    '144w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    '128w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                ],
                'photo' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
            ],
            1 => [
                'type' => 'recommendededitorial',
                'editorialId' => $recommendedEditorialId[1],
                'signatures' => [
                    '20116' => 'signature',
                ],
                'editorial' => 'https://www.elconfidencial.dev/_'.$recommendedEditorialId[1],
                'title' => 'title recommended editorial 2',
                'shots' => [
                    '202w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    '144w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    '128w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                ],
                'photo' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
            ],
        ];

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function testReadShouldReturnCorrectArrayDataWithEmptyShots(): void
    {
        $recommendedEditorialId = ['7422', '7423'];
        $multimediaId = '2532';
        $membershipLink = 'https://snaapy.elconfidencial.dev/editorials/editorial_id';
        $title = ['title recommended editorial', 'title recommended editorial 2'];

        $editorialMock = $this->createMock(EditorialNews::class);
        $sectionMock = $this->createMock(Section::class);
        $multimediaMock = $this->createMock(Multimedia::class);
        $photoMock = $this->createMock(Photo::class);

        $editorialId1 = $this->createMock(EditorialId::class);
        $editorialId1->expects(self::never())
            ->method('id')
            ->willReturn($recommendedEditorialId[0]);
        $editorialMock->expects(self::never())
            ->method('id')
            ->willReturn($editorialId1);

        $editorialId2 = $this->createMock(EditorialId::class);
        $editorialId2->expects(self::never())
            ->method('id')
            ->willReturn($recommendedEditorialId[1]);
        $editorialMock->expects(self::never())
            ->method('id')
            ->willReturn($editorialId2);

        /** @var array<string, (array<int|string, (array<string, array<int, string>|MockObject|string>)|MockObject|string>)|string|null> $resolveData */
        $resolveData = [
            'multimedia' => null,
            'recommendedEditorials' => [
                $recommendedEditorialId[0] => [
                    'editorial' => $editorialMock,
                    'section' => $sectionMock,
                    'signatures' => [
                        '20116' => 'signature',
                    ],
                    'multimediaId' => $multimediaId,
                ],
                $recommendedEditorialId[1] => [
                    'editorial' => $editorialMock,
                    'section' => $sectionMock,
                    'signatures' => [
                        '20116' => 'signature',
                    ],
                    'multimediaId' => $multimediaId,
                ],
            ],
            'insertedNews' => [
                '7422' => [
                    'editorial' => $editorialMock,
                    'section' => $sectionMock,
                    'signatures' => [
                        '20116',
                        '20117',
                    ],
                    'multimediaId' => $multimediaId,
                ],
            ],
            'photoFromBodyTags' => [
                '707' => $photoMock,
            ],
            'membershipLinkCombine' => [
                $membershipLink => $membershipLink,
            ],
            'shots' => '',
            'photo' => '',
        ];

        $editorialMock1 = $this->createMock(Editorial::class);
        $editorialMock2 = $this->createMock(Editorial::class);

        $editorialId1 = $this->createMock(EditorialId::class);
        $editorialId1->expects(exactly(2))
            ->method('id')
            ->willReturn('7422');

        $editorialId2 = $this->createMock(EditorialId::class);
        $editorialId2->expects(exactly(2))
            ->method('id')
            ->willReturn('7423');
        $editorialMock1->expects(exactly(2))
            ->method('id')
            ->willReturn($editorialId1);
        $editorialMock2->expects(exactly(2))
            ->method('id')
            ->willReturn($editorialId2);

        $editorialTitlesMock1 = $this->createMock(EditorialTitles::class);
        $editorialTitlesMock1->expects(static::once())
            ->method('title')
            ->willReturn($title[0]);

        $editorialMock1->expects(exactly(2))
            ->method('editorialTitles')
            ->willReturn($editorialTitlesMock1);

        $editorialTitlesMock2 = $this->createMock(EditorialTitles::class);
        $editorialTitlesMock2->expects(static::once())
            ->method('title')
            ->willReturn($title[1]);

        $editorialMock2->expects(exactly(2))
            ->method('editorialTitles')
            ->willReturn($editorialTitlesMock2);

        $editorials = [$editorialMock1, $editorialMock2];

        $clippingsMock = $this->createMock(Clippings::class);
        $multimediaMock->expects(static::never())
            ->method('clippings')
            ->willReturn($clippingsMock);

        $clippingMock = $this->createMock(Clipping::class);

        $clippingsMock->expects(static::never())
            ->method('clippingByType')
            ->with(ClippingTypes::SIZE_ARTICLE_4_3)
            ->willReturn($clippingMock);

        $thumborPhoto = $resolveData['photo'];

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

        /** @var array<string, array<string, array<string, mixed>>> $resolveData */
        $result = $this->transformer->write($editorials, $resolveData)->read();

        $expected = [
            0 => [
                'type' => 'recommendededitorial',
                'editorialId' => $recommendedEditorialId[0],
                'signatures' => [
                    '20116' => 'signature',
                ],
                'editorial' => 'https://www.elconfidencial.dev/_'.$recommendedEditorialId[0],
                'title' => 'title recommended editorial',
                'shots' => [],
                'photo' => '',
            ],
            1 => [
                'type' => 'recommendededitorial',
                'editorialId' => $recommendedEditorialId[1],
                'signatures' => [
                    '20116' => 'signature',
                ],
                'editorial' => 'https://www.elconfidencial.dev/_'.$recommendedEditorialId[1],
                'title' => 'title recommended editorial 2',
                'shots' => [],
                'photo' => '',
            ],
        ];

        self::assertEquals($expected, $result);
    }
}
