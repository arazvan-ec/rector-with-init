<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps;

use App\Infrastructure\Service\Thumbor;
use App\Infrastructure\Trait\MultimediaTrait;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class DetailsMultimediaDataTransformer implements MultimediaDataTransformer
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
    ];

    /**
     * @var array<mixed>
     */
    private array $arrayMultimedia;
    private MultimediaEditorial $openingMultimedia;

    public function __construct(private readonly Thumbor $thumborService)
    {
    }

    /**
     * @param array<mixed> $arrayMultimedia
     */
    public function write(array $arrayMultimedia, MultimediaEditorial $openingMultimedia): MultimediaDataTransformer
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
        $multimediaId = $this->getMultimediaId($this->openingMultimedia);
        if (!$multimediaId || empty($this->arrayMultimedia[$multimediaId->id()])) {
            return [];
        }
        /** @var Multimedia $multimedia */
        $multimedia = $this->arrayMultimedia[$multimediaId->id()];
        $clippings = $multimedia->clippings();

        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_MULTIMEDIA_BIG);

        $allShots = [];
        foreach (self::SIZES_RELATIONS as $aspectRatio => $sizes) {
            $shots = array_map(function ($size) use ($clipping, $multimedia) {
                return $this->thumborService->retriveCropBodyTagPicture(
                    $multimedia->file(),
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
            'id' => $multimedia->id(),
            'type' => 'photo',
            'caption' => $multimedia->caption(),
            'shots' => (object) $allShots,
            'photo' => current($allShots[self::ASPECT_RATIO_16_9]),
        ];
    }
}
