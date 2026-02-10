<?php

namespace App\Orchestrator\Chain\Multimedia;

use App\Orchestrator\Exceptions\DuplicateChainInOrchestratorHandlerException;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;

class MultimediaOrchestratorHandler implements MultimediaOrchestratorChain
{
    /**
     * @var MultimediaOrchestratorInterface[]
     */
    private array $orchestrators = [];

    /**
     * @return array<string, mixed>
     *
     * @throws OrchestratorTypeNotExistException
     */
    public function handler(Multimedia $multimedia): array
    {
        if (!\array_key_exists($multimedia->type(), $this->orchestrators)) {
            $message = \sprintf('Orchestrator %s not exist', $multimedia->type());
            throw new OrchestratorTypeNotExistException($message);
        }

        return $this->orchestrators[$multimedia->type()]->execute($multimedia);
    }

    public function addOrchestrator(MultimediaOrchestratorInterface $orchestrator): MultimediaOrchestratorHandler
    {
        $key = $orchestrator->canOrchestrate();
        if (isset($this->orchestrators[$key])) {
            throw new DuplicateChainInOrchestratorHandlerException("$key orchestrator duplicate.");
        }
        $this->orchestrators[$key] = $orchestrator;

        return $this;
    }
}
