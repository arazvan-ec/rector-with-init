<?php

/**
 * @copyright
 */

namespace App\Infrastructure\Trait;

use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\Multimedia\Video;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia as MultimediaModel;
use Ec\Multimedia\Domain\Model\Photo\Photo;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
trait MultimediaTrait
{
    private Thumbor $thumbor;

    /**
     * @var array<string, array<string, string>>
     */
    private array $sizes = [
        '202w' => [
            'width' => '202',
            'height' => '152',
        ],
        '144w' => [
            'width' => '144',
            'height' => '108',
        ],
        '128w' => [
            'width' => '128',
            'height' => '96',
        ],
    ];

    public function thumbor(): Thumbor
    {
        return $this->thumbor;
    }

    private function setThumbor(Thumbor $thumbor): void
    {
        $this->thumbor = $thumbor;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function sizes(): array
    {
        return $this->sizes;
    }

    private function getMultimediaId(Multimedia $multimedia): ?MultimediaId
    {
        $multimediaId = null;
        if ($multimedia instanceof PhotoExist) {
            $multimediaId = $multimedia->id();
        }

        if (
            ($multimedia instanceof Video || $multimedia instanceof Widget)
            && ($multimedia->photo() instanceof PhotoExist)
        ) {
            $multimediaId = $multimedia->photo()->id();
        }

        return $multimediaId;
    }

    /**
     * @return array<string, string>
     */
    private function getShotsLandscape(MultimediaModel $multimedia): array
    {
        $shots = [];
        $clippings = $multimedia->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_ARTICLE_4_3);

        foreach ($this->sizes() as $type => $size) {
            $shots[$type] = $this->thumbor->retriveCropBodyTagPicture(
                $multimedia->file(),
                $size['width'],
                $size['height'],
                $clipping->topLeftX(),
                $clipping->topLeftY(),
                $clipping->bottomRightX(),
                $clipping->bottomRightY()
            );
        }

        return $shots;
    }

    /**
     * @param array{opening: MultimediaModel\MultimediaPhoto, resource: Photo} $multimediaOpening
     *
     * @return array<string, string>
     */
    private function getShotsLandscapeFromMedia(array $multimediaOpening): array
    {
        $shots = [];
        $clippings = $multimediaOpening['opening']->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_ARTICLE_4_3);

        foreach ($this->sizes() as $type => $size) {
            $shots[$type] = $this->thumbor->retriveCropBodyTagPicture(
                $multimediaOpening['resource']->file(),
                $size['width'],
                $size['height'],
                $clipping->topLeftX(),
                $clipping->topLeftY(),
                $clipping->bottomRightX(),
                $clipping->bottomRightY()
            );
        }

        return $shots;
    }
}
