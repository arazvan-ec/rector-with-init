<?php

/**
 * @copyright
 */

namespace App\Orchestrator\Chain\Multimedia;

use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class MultimediaEmbedVideoOrchestrator implements MultimediaOrchestratorInterface
{
    private const OPENING = 'opening';

    public function canOrchestrate(): string
    {
        return MultimediaEmbedVideo::TYPE;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function execute(Multimedia $multimedia): array
    {
        return [
            $multimedia->id()->id() => [
                self::OPENING => $multimedia,
            ],
        ];
    }
}
