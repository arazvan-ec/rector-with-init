<?php

/**
 * @copyright
 */

namespace App\Orchestrator\Chain\Multimedia;

use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient;
use Psr\Log\LoggerInterface;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class MultimediaPhotoOrchestrator implements MultimediaOrchestratorInterface
{
    private const OPENING = 'opening';
    private const RESOURCE = 'resource';

    public function __construct(
        private readonly QueryMultimediaClient $queryMultimediaClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function canOrchestrate(): string
    {
        return MultimediaPhoto::TYPE;
    }

    /**
     * @param MultimediaPhoto $multimedia
     */
    public function execute(Multimedia $multimedia): array
    {
        try {
            $photo = $this->queryMultimediaClient->findPhotoById($multimedia->resourceId()->id());

            return [
                $multimedia->id()->id() => [
                    self::OPENING => $multimedia,
                    self::RESOURCE => $photo,
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error(\sprintf(
                'Failed to retrieve photo with ID %s: %s',
                $multimedia->resourceId()->id(),
                $e->getMessage()
            ));

            return [];
        }
    }
}
