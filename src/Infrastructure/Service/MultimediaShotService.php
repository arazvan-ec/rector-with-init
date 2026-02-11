<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Infrastructure\Enum\AspectRatioEnum;
use App\Infrastructure\ValueObject\ImageSizeCollection;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Clippings;
use Ec\Multimedia\Domain\Model\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;

final readonly class MultimediaShotService
{
    public function __construct(
        private Thumbor $thumbor,
    ) {
    }

    /**
     * Generate landscape thumbnail shots (3 sizes, 4:3 ratio).
     * Replaces MultimediaTrait::getShotsLandscape().
     *
     * @return array<string, string> Map of size label => Thumbor URL
     */
    public function generateLandscapeShots(Multimedia $multimedia): array
    {
        $clippings = $multimedia->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_ARTICLE_4_3);

        return $this->generateShotsFromClipping(
            $multimedia->file(),
            $clipping,
            ImageSizeCollection::landscape(),
        );
    }

    /**
     * Generate landscape thumbnail shots from media opening data.
     * Replaces MultimediaTrait::getShotsLandscapeFromMedia().
     *
     * @param array{opening: MultimediaPhoto, resource: Photo} $multimediaOpening
     *
     * @return array<string, string> Map of size label => Thumbor URL
     */
    public function generateLandscapeShotsFromMedia(array $multimediaOpening): array
    {
        $clippings = $multimediaOpening['opening']->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_ARTICLE_4_3);

        return $this->generateShotsFromClipping(
            $multimediaOpening['resource']->file(),
            $clipping,
            ImageSizeCollection::landscape(),
        );
    }

    /**
     * Generate responsive shots for all aspect ratios (opening multimedia).
     * Replaces DetailsMultimediaPhotoDataTransformer::SIZES_RELATIONS loop.
     *
     * @return array<string, array<string, string>> Map of aspectRatio => (size => URL)
     */
    public function generateResponsiveShots(Photo $resource, Clippings $clippings): array
    {
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_MULTIMEDIA_BIG);
        $allShots = [];

        foreach (AspectRatioEnum::cases() as $ratio) {
            $sizes = ImageSizeCollection::forAspectRatio($ratio);
            $allShots[$ratio->value] = $this->generateShotsFromClipping(
                $resource->file(),
                $clipping,
                $sizes,
            );
        }

        return $allShots;
    }

    /**
     * Generate journalist photo URL.
     * Replaces JournalistsDataTransformer::photoUrl().
     */
    public function generateJournalistPhoto(string $blogPhoto, string $photo): string
    {
        if (!empty($blogPhoto)) {
            return $this->thumbor->createJournalistImage($blogPhoto);
        }

        if (!empty($photo)) {
            return $this->thumbor->createJournalistImage($photo);
        }

        return '';
    }

    /**
     * @return array<string, string>
     */
    private function generateShotsFromClipping(
        string $file,
        object $clipping,
        ImageSizeCollection $sizes,
    ): array {
        $shots = [];

        foreach ($sizes->sizes() as $size) {
            $shots[$size->label] = $this->thumbor->retriveCropBodyTagPicture(
                $file,
                (string) $size->width,
                (string) $size->height,
                $clipping->topLeftX(),
                $clipping->topLeftY(),
                $clipping->bottomRightX(),
                $clipping->bottomRightY(),
            );
        }

        return $shots;
    }
}
