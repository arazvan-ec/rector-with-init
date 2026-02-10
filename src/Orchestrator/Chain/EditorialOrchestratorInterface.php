<?php

/**
 * @copyright
 */

namespace App\Orchestrator\Chain;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
interface EditorialOrchestratorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array;

    public function canOrchestrate(): string;
}
