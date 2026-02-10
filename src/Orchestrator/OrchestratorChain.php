<?php

/**
 * @copyright
 */

namespace App\Orchestrator;

use App\Orchestrator\Chain\EditorialOrchestratorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
interface OrchestratorChain
{
    /**
     * @return array<string, mixed>
     */
    public function handler(string $contentType, Request $request): array;

    public function addOrchestrator(EditorialOrchestratorInterface $orchestratorChain): OrchestratorChain;
}
