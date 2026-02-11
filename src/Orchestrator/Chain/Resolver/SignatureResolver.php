<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain\Resolver;

use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use Ec\Editorial\Domain\Model\Signatures;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use Ec\Section\Domain\Model\Section;
use Psr\Log\LoggerInterface;

final readonly class SignatureResolver
{
    public function __construct(
        private QueryJournalistClient $queryJournalistClient,
        private JournalistFactory $journalistFactory,
        private JournalistsDataTransformer $journalistsDataTransformer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolveSignatures(Signatures $signatures, Section $section, bool $hasTwitter = false): array
    {
        $result = [];
        foreach ($signatures->getArrayCopy() as $signature) {
            $resolved = $this->resolveAlias($signature->id()->id(), $section, $hasTwitter);
            if (!empty($resolved)) {
                $result[] = $resolved;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveAlias(string $aliasId, Section $section, bool $hasTwitter = false): array
    {
        $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);

        try {
            /** @var Journalist $journalist */
            $journalist = $this->queryJournalistClient->findJournalistByAliasId($aliasIdModel);

            return $this->journalistsDataTransformer
                ->write($aliasId, $journalist, $section, $hasTwitter)
                ->read();
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }

        return [];
    }
}
