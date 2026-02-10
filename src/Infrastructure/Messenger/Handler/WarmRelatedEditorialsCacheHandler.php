<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Infrastructure\Messenger\Message\WarmRelatedEditorialsCache;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class WarmRelatedEditorialsCacheHandler
{
    public function __construct(
        private QueryEditorialClient $queryEditorialClient,
        private QueryMultimediaClient $queryMultimediaClient,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(WarmRelatedEditorialsCache $message): void
    {
        foreach ($message->editorialIds as $editorialId) {
            try {
                $editorial = $this->queryEditorialClient->findEditorialById($editorialId);

                if (!empty($editorial->multimedia()->id()->id())) {
                    $this->queryMultimediaClient->findMultimediaById(
                        $editorial->multimedia()->id()
                    );
                }
            } catch (\Throwable $throwable) {
                $this->logger->warning(
                    'Failed to warm cache for editorial {id}: {message}',
                    ['id' => $editorialId, 'message' => $throwable->getMessage()]
                );
            }
        }
    }
}
