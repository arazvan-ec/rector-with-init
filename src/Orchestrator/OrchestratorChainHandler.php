<?php

/**
 * @copyright
 */

namespace App\Orchestrator;

use App\Orchestrator\Chain\EditorialOrchestratorInterface;
use App\Orchestrator\Exceptions\DuplicateChainInOrchestratorHandlerException;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class OrchestratorChainHandler implements OrchestratorChain
{
    /** @var EditorialOrchestratorInterface[] */
    private array $orchestratorChain = [];

    /**
     * @throws OrchestratorTypeNotExistException
     */
    public function handler(string $contentType, Request $request): array
    {
        if (!\array_key_exists($contentType, $this->orchestratorChain)) {
            throw new OrchestratorTypeNotExistException('Orchestrator '.$contentType.' not exist');
        }

        return $this->orchestratorChain[$contentType]->execute($request);
    }

    /**
     * @throws DuplicateChainInOrchestratorHandlerException
     */
    public function addOrchestrator(EditorialOrchestratorInterface $orchestratorChain): OrchestratorChain
    {
        $key = $orchestratorChain->canOrchestrate();
        if (isset($this->orchestratorChain[$key])) {
            throw new DuplicateChainInOrchestratorHandlerException("$key orchestrator duplicate.");
        }
        $this->orchestratorChain[$key] = $orchestratorChain;

        return $this;
    }
}
