<?php

/**
 * @copyright
 */

namespace App\Orchestrator\Chain\Multimedia;

use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaWidget;
use Ec\Widget\Domain\Model\EveryWidget;
use Ec\Widget\Domain\Model\QueryWidgetClient;
use Psr\Log\LoggerInterface;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class MultimediaWidgetOrchestrator implements MultimediaOrchestratorInterface
{
    private const OPENING = 'opening';
    private const RESOURCE = 'resource';

    public function __construct(
        private readonly QueryWidgetClient $queryWidgetClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function canOrchestrate(): string
    {
        return MultimediaWidget::TYPE;
    }

    /** @param MultimediaWidget $multimedia */
    public function execute(Multimedia $multimedia): array
    {
        try {
            /**
             * @var EveryWidget $widget
             */
            $widget = $this->queryWidgetClient->findWidgetById($multimedia->resourceId()->id());

            return [
                $multimedia->id()->id() => [
                    self::OPENING => $multimedia,
                    self::RESOURCE => $widget,
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error(\sprintf(
                'Failed to retrieve widget with ID %s: %s',
                $multimedia->resourceId()->id(),
                $e->getMessage()
            ));

            return [];
        }
    }
}
