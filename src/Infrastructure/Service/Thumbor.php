<?php

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

use Thumbor\Url\Builder;
use Thumbor\Url\BuilderFactory;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class Thumbor
{
    /** @var string */
    private const DEFAULT_EXTENSION = 'jpg';

    private string $awsBucket;
    private BuilderFactory $thumborFactory;

    public function __construct(string $thumborServerUrl, string $thumborSecret, string $awsBucket)
    {
        $this->awsBucket = $awsBucket;
        $this->thumborFactory = BuilderFactory::construct($thumborServerUrl, $thumborSecret);
    }

    public function createJournalistImage(string $fileImage): string
    {
        return $this->getOriginalUrl($fileImage, 'journalist');
    }

    public function retriveCropBodyTagPicture(
        string $fileImage,
        string $width,
        string $height,
        int $topX,
        int $topY,
        int $bottomX,
        int $bottomY,
    ): string {
        $pattern = '/^.*\.(?<extension>.*)$/m';
        preg_match($pattern, $fileImage, $matches);
        $extension = self::DEFAULT_EXTENSION;

        if (!empty($matches['extension'])) {
            $extension = $matches['extension'];
        }

        $photo = $this->getOriginalUrl($fileImage, 'original');
        $photo->resize($width, $height);
        $photo->crop($topX, $topY, $bottomX, $bottomY);
        $photo->addFilter('fill', 'white');
        $photo->addFilter('format', $extension);

        return $photo;
    }

    private function getOriginalUrl(string $fileName, string $directory): Builder
    {
        $path1 = substr($fileName, 0, 3);
        $path2 = substr($fileName, 3, 3);
        $path3 = substr($fileName, 6, 3);

        $path = $this->awsBucket."/{$directory}/{$path1}/{$path2}/{$path3}/{$fileName}";

        return $this->thumborFactory->url($path);
    }
}
