<?php

namespace App\Infrastructure\Service;

use Ec\Editorial\Domain\Model\Body\AbstractPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureDefault;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class PictureShots
{
    private const WIDTH = 'width';

    /** @var string */
    private const HEIGHT = 'height';

    private const ASPECT_RATIO_16_9 = '16:9';

    /** @var string */
    private const ASPECT_RATIO_3_4 = '3:4';

    /** @var string */
    private const ASPECT_RATIO_4_3 = '4:3';

    /** @var string */
    private const ASPECT_RATIO_1_1 = '1:1';

    private const ASPECT_RATIO_3_2 = '3:2';

    private const ASPECT_RATIO_2_3 = '2:3';

    public const SIZES_RELATIONS = [
        self::ASPECT_RATIO_16_9 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '810'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '675'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '560'],
            '640w' => [self::WIDTH => '640', self::HEIGHT => '360'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '219'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '320'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '215'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '185'],
        ],
        self::ASPECT_RATIO_3_4 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1920'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1600'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '1328'],
            '560w' => [self::WIDTH => '560', self::HEIGHT => '747'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '520'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '757'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '509'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '437'],
        ],
        self::ASPECT_RATIO_1_1 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1440'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1200'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '996'],
            '560w' => [self::WIDTH => '560', self::HEIGHT => '560'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '390'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '568'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '382'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '328'],
        ],
        self::ASPECT_RATIO_4_3 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1080'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '900'],
            '996w' => [self::WIDTH => '996',  self::HEIGHT => '747'],
            '560w' => [self::WIDTH => '560',  self::HEIGHT => '420'],
            '390w' => [self::WIDTH => '390',  self::HEIGHT => '292'],
            '568w' => [self::WIDTH => '568',  self::HEIGHT => '426'],
            '382w' => [self::WIDTH => '382',  self::HEIGHT => '286'],
            '328w' => [self::WIDTH => '328',  self::HEIGHT => '246'],
        ],
        self::ASPECT_RATIO_3_2 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '960'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '800'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '664'],
            '640w' => [self::WIDTH => '640', self::HEIGHT => '427'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '260'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '379'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '254'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '219'],
        ],
        self::ASPECT_RATIO_2_3 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '2160'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1800'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '1494'],
            '560w' => [self::WIDTH => '560', self::HEIGHT => '840'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '585'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '852'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '573'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '492'],
        ],
    ];

    public function __construct(
        private Thumbor $thumbor,
    ) {
    }

    private function retrieveAspectRatio(string $orientation): string
    {
        $result = self::ASPECT_RATIO_16_9;

        if (AbstractPicture::ORIENTATION_SQUARE === $orientation) {
            $result = self::ASPECT_RATIO_1_1;
        } elseif (AbstractPicture::ORIENTATION_PORTRAIT === $orientation) {
            $result = self::ASPECT_RATIO_3_4;
        } elseif (AbstractPicture::ORIENTATION_LANDSCAPE === $orientation) {
            $result = self::ASPECT_RATIO_4_3;
        } elseif (AbstractPicture::ORIENTATION_LANDSCAPE_3_2 === $orientation) {
            $result = self::ASPECT_RATIO_3_2;
        } elseif (AbstractPicture::ORIENTATION_PORTRAIT_2_3 === $orientation) {
            $result = self::ASPECT_RATIO_2_3;
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function retrieveAllShotsByAspectRatio(string $fileName, BodyTagPictureDefault $bodytag): array
    {
        $shots = [];
        $aspectRatio = $this->retrieveAspectRatio($bodytag->orientation());
        foreach (self::SIZES_RELATIONS[$aspectRatio] as $viewport => $sizeValues) {
            $shots[$viewport] = $this->thumbor->retriveCropBodyTagPicture(
                $fileName,
                $sizeValues[self::WIDTH],
                $sizeValues[self::HEIGHT],
                $bodytag->topX(),
                $bodytag->topY(),
                $bodytag->bottomX(),
                $bodytag->bottomY()
            );
        }

        return $shots;
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array|string[]
     */
    public function retrieveShotsByPhotoId(array $resolveData, BodyTagPictureDefault $bodyTagPicture): array
    {
        $photoFile = $this->retrievePhotoFile($resolveData, $bodyTagPicture);
        if ($photoFile) {
            return $this->retrieveAllShotsByAspectRatio($photoFile, $bodyTagPicture);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $resolveData
     */
    private function retrievePhotoFile(array $resolveData, BodyTagPictureDefault $bodyTagPicture): string
    {
        $photoFile = '';

        if (!isset($resolveData['photoFromBodyTags'])) {
            return $photoFile;
        }
        /** @var array<string, string> $photoFromBodyTags */
        $photoFromBodyTags = $resolveData['photoFromBodyTags'];

        if (isset($photoFromBodyTags[$bodyTagPicture->id()->id()])) {
            return $photoFromBodyTags[$bodyTagPicture->id()->id()]->file(); // @phpstan-ignore method.nonObject
        }

        return $photoFile;
    }
}
