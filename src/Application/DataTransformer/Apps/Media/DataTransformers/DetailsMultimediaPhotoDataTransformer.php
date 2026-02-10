<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Media\DataTransformers;

use App\Application\DataTransformer\Apps\Media\MediaDataTransformer;
use App\Infrastructure\Service\Thumbor;
use App\Infrastructure\Trait\MultimediaTrait;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class DetailsMultimediaPhotoDataTransformer implements MediaDataTransformer
{
    use MultimediaTrait;

    /** @var string */
    private const WIDTH = 'width';

    /** @var string */
    private const HEIGHT = 'height';

    /** @var string */
    private const ASPECT_RATIO_16_9 = '16:9';

    /** @var string */
    private const ASPECT_RATIO_3_4 = '3:4';

    /** @var string */
    private const ASPECT_RATIO_4_3 = '4:3';

    /** @var string */
    private const ASPECT_RATIO_3_2 = '3:2';

    /** @var string */
    private const ASPECT_RATIO_2_3 = '2:3';

    /** @var array<string, array<string, array<string, string> > > */
    private const SIZES_RELATIONS = [
        self::ASPECT_RATIO_4_3 => [
            // High density
            '1440w' => [
                self::WIDTH => '1440',
                self::HEIGHT => '1080',
            ],
            '1200w' => [
                self::WIDTH => '1200',
                self::HEIGHT => '900',
            ],
            '996w' => [
                self::WIDTH => '996',
                self::HEIGHT => '747',
            ],
            // Desktop
            '557w' => [
                self::WIDTH => '557',
                self::HEIGHT => '418',
            ],
            // Tablet
            '381w' => [
                self::WIDTH => '381',
                self::HEIGHT => '286',
            ],
            // Mobile
            '600w' => [
                self::WIDTH => '600',
                self::HEIGHT => '450',
            ],
            '414w' => [
                self::WIDTH => '414',
                self::HEIGHT => '311',
            ],
            '375w' => [
                self::WIDTH => '375',
                self::HEIGHT => '281',
            ],
            '360w' => [
                self::WIDTH => '360',
                self::HEIGHT => '270',
            ],
            // landscapePhotoFull
            '767w' => [
                self::WIDTH => '767',
                self::HEIGHT => '575',
            ],
        ],
        self::ASPECT_RATIO_16_9 => [
            // High density
            '1440w' => [
                self::WIDTH => '1440',
                self::HEIGHT => '810',
            ],
            '1200w' => [
                self::WIDTH => '1200',
                self::HEIGHT => '675',
            ],
            // Desktop
            '972w' => [
                self::WIDTH => '972',
                self::HEIGHT => '547',
            ],
            // Tablet
            '720w' => [
                self::WIDTH => '720',
                self::HEIGHT => '405',
            ],
            // Mobile
            '600w' => [
                self::WIDTH => '600',
                self::HEIGHT => '338',
            ],
            '414w' => [
                self::WIDTH => '414',
                self::HEIGHT => '233',
            ],
            '375w' => [
                self::WIDTH => '375',
                self::HEIGHT => '211',
            ],
            '360w' => [
                self::WIDTH => '360',
                self::HEIGHT => '203',
            ],
        ],
        self::ASPECT_RATIO_3_4 => [
            // High density
            '1440w' => [
                self::WIDTH => '1440',
                self::HEIGHT => '1920',
            ],
            '1200w' => [
                self::WIDTH => '1200',
                self::HEIGHT => '1600',
            ],
            '996w' => [
                self::WIDTH => '996',
                self::HEIGHT => '1328',
            ],
            // Desktop
            '391w' => [
                self::WIDTH => '391',
                self::HEIGHT => '521',
            ],
            // Tablet
            '300w' => [
                self::WIDTH => '300',
                self::HEIGHT => '400',
            ],
            // Mobile
            '600w' => [
                self::WIDTH => '600',
                self::HEIGHT => '800',
            ],
            '414w' => [
                self::WIDTH => '414',
                self::HEIGHT => '552',
            ],
            '375w' => [
                self::WIDTH => '375',
                self::HEIGHT => '500',
            ],
            '360w' => [
                self::WIDTH => '360',
                self::HEIGHT => '480',
            ],
        ],
        self::ASPECT_RATIO_3_2 => [
            // High density
            '1440w' => [
                self::WIDTH => '1440',
                self::HEIGHT => '960',
            ],
            '1200w' => [
                self::WIDTH => '1200',
                self::HEIGHT => '800',
            ],
            '996w' => [
                self::WIDTH => '996',
                self::HEIGHT => '664',
            ],
            // Desktop
            '557w' => [
                self::WIDTH => '557',
                self::HEIGHT => '371',
            ],
            // Tablet
            '381w' => [
                self::WIDTH => '381',
                self::HEIGHT => '254',
            ],
            // Mobile
            '600w' => [
                self::WIDTH => '600',
                self::HEIGHT => '400',
            ],
            '414w' => [
                self::WIDTH => '414',
                self::HEIGHT => '276',
            ],
            '375w' => [
                self::WIDTH => '375',
                self::HEIGHT => '250',
            ],
            '360w' => [
                self::WIDTH => '360',
                self::HEIGHT => '240',
            ],
            // landscapePhotoFull
            '767w' => [
                self::WIDTH => '767',
                self::HEIGHT => '511',
            ],
            // Low Quality Placeholder
            'lo-res' => [
                self::WIDTH => '48',
                self::HEIGHT => '32',
            ],
        ],
        self::ASPECT_RATIO_2_3 => [
            // High density
            '1440w' => [
                self::WIDTH => '1440',
                self::HEIGHT => '2160',
            ],
            '1200w' => [
                self::WIDTH => '1200',
                self::HEIGHT => '1800',
            ],
            '996w' => [
                self::WIDTH => '996',
                self::HEIGHT => '1494',
            ],
            // Desktop
            '557w' => [
                self::WIDTH => '557',
                self::HEIGHT => '835',
            ],
            // Tablet
            '381w' => [
                self::WIDTH => '381',
                self::HEIGHT => '571',
            ],
            // Mobile
            '600w' => [
                self::WIDTH => '600',
                self::HEIGHT => '900',
            ],
            '414w' => [
                self::WIDTH => '414',
                self::HEIGHT => '621',
            ],
            '375w' => [
                self::WIDTH => '375',
                self::HEIGHT => '562',
            ],
            '360w' => [
                self::WIDTH => '360',
                self::HEIGHT => '540',
            ],
            // landscapePhotoFull
            '767w' => [
                self::WIDTH => '767',
                self::HEIGHT => '1150',
            ],
            // Low Quality Placeholder
            'lo-res' => [
                self::WIDTH => '48',
                self::HEIGHT => '72',
            ],
        ],
    ];

    /**
     * @var array{array{opening: MultimediaPhoto, resource: Photo}}|array{}
     */
    private array $arrayMultimedia;
    private Opening $openingMultimedia;

    public function __construct(private readonly Thumbor $thumborService)
    {
    }

    /**
     * @param array{array{opening: MultimediaPhoto, resource: Photo}}|array{} $arrayMultimedia
     */
    public function write(array $arrayMultimedia, Opening $openingMultimedia): self
    {
        $this->arrayMultimedia = $arrayMultimedia;
        $this->openingMultimedia = $openingMultimedia;

        return $this;
    }

    /**
     * @return array<string, \stdClass|string>|array{}
     */
    public function read(): array
    {
        $multimediaId = $this->openingMultimedia->multimediaId();

        if (!$multimediaId || empty($this->arrayMultimedia[$multimediaId])) {
            return [];
        }

        /** @var MultimediaPhoto $multimedia */
        $multimedia = $this->arrayMultimedia[$multimediaId]['opening'];
        /** @var Photo $resource */
        $resource = $this->arrayMultimedia[$multimediaId]['resource'];
        $clippings = $multimedia->clippings();

        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_MULTIMEDIA_BIG);

        $allShots = [];
        foreach (self::SIZES_RELATIONS as $aspectRatio => $sizes) {
            $shots = array_map(function ($size) use ($clipping, $resource) {
                return $this->thumborService->retriveCropBodyTagPicture(
                    $resource->file(),
                    $size[self::WIDTH],
                    $size[self::HEIGHT],
                    $clipping->topLeftX(),
                    $clipping->topLeftY(),
                    $clipping->bottomRightX(),
                    $clipping->bottomRightY()
                );
            }, $sizes);

            $allShots[$aspectRatio] = $shots;
        }

        return [
            'id' => $multimediaId,
            'type' => 'photo',
            'caption' => $multimedia->caption(),
            'shots' => (object) $allShots,
            'photo' => current($allShots[self::ASPECT_RATIO_16_9]),
        ];
    }

    public function canTransform(): string
    {
        return MultimediaPhoto::class;
    }
}
