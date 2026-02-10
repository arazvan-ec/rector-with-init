<?php

/**
 * @copyright
 */

namespace App\Orchestrator\Chain\Multimedia;

use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
interface MultimediaOrchestratorChain
{
    /**
     * @return array<string, mixed>
     */
    public function handler(Multimedia $multimedia): array;

    public function addOrchestrator(MultimediaOrchestratorInterface $orchestrator): self;
}
