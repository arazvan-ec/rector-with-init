<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Media\DataTransformers;

use App\Application\DataTransformer\Apps\Media\MediaDataTransformer;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailsMultimediaEmbedVideoDataTransformer implements MediaDataTransformer
{
    /** @var string */
    private const EMBED_VIDEO_GENERIC = 'embedVideo';

    /** @var string */
    private const EMBED_VIDEO_DAILY_MOTION = 'embedVideoDailyMotion';

    /** @var string */
    private const REGEX_PATTERN = '/\/player\/([a-zA-Z0-9]+)\.html\?video=([a-zA-Z0-9]+)/';

    /** @var int */
    private const PLAYER_ID_POSITION = 1;

    /** @var int */
    private const VIDEO_ID_POSITION = 2;
    /**
     * @var array{array{opening: MultimediaEmbedVideo}}|array{}
     */
    private array $arrayMultimedia;
    private Opening $openingMultimedia;

    /**
     * @param array{array{opening: MultimediaEmbedVideo}}|array{} $arrayMultimedia
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

        /** @var MultimediaEmbedVideo $multimedia */
        $multimedia = $this->arrayMultimedia[$multimediaId]['opening'];

        return $this->isDailyMotionVideo($multimedia)
            ? $this->buildDailyMotionResponse($multimediaId, $multimedia)
            : $this->buildGenericResponse($multimediaId, $multimedia);
    }

    public function canTransform(): string
    {
        return MultimediaEmbedVideo::class;
    }

    private function isDailyMotionVideo(MultimediaEmbedVideo $multimedia): bool
    {
        return str_contains($multimedia->html(), 'dailymotion.com');
    }

    /**
     * @return array{id: string, type: string, caption: string, playerId: string, videoId: string}|array{}
     */
    private function buildDailyMotionResponse(string $multimediaId, MultimediaEmbedVideo $multimedia): array
    {
        $dailyMotionData = $this->extractDailyMotionData($multimedia);

        if (empty($dailyMotionData)) {
            return [];
        }

        return [
            'id' => $multimediaId,
            'type' => self::EMBED_VIDEO_DAILY_MOTION,
            'caption' => $multimedia->caption(),
            'playerId' => $dailyMotionData['playerId'],
            'videoId' => $dailyMotionData['videoId'],
        ];
    }

    /**
     * @return array{id: string, type: string, caption: string, html: string}
     */
    private function buildGenericResponse(string $multimediaId, MultimediaEmbedVideo $multimedia): array
    {
        return [
            'id' => $multimediaId,
            'type' => self::EMBED_VIDEO_GENERIC,
            'caption' => $multimedia->caption(),
            'html' => $multimedia->html(),
        ];
    }

    /**
     * @return array{playerId: string, videoId: string}|array{}
     */
    private function extractDailyMotionData(MultimediaEmbedVideo $multimedia): array
    {
        $htmlContent = $multimedia->html();

        $matches = [];

        @preg_match(self::REGEX_PATTERN, $htmlContent, $matches);

        if (!isset($matches[self::PLAYER_ID_POSITION], $matches[self::VIDEO_ID_POSITION])) {
            return [];
        }

        return [
            'playerId' => $matches[self::PLAYER_ID_POSITION],
            'videoId' => $matches[self::VIDEO_ID_POSITION],
        ];
    }
}
