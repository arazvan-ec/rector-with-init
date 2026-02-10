<?php

namespace App\Application\DataTransformer;

use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class BodyDataTransformer implements BodyDataTransformerInterface
{
    public function __construct(
        private readonly BodyElementDataTransformerHandler $bodyElementDataTransformerHandler,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $resolveData
     *
     * @return array<string, mixed>
     */
    public function execute(Body $body, array $resolveData): array
    {
        $parsedBody = [
            'type' => $body->type(),
            'elements' => [],
        ];

        /** @var BodyElement $bodyElement */
        foreach ($body->getArrayCopy() as $bodyElement) {
            try {
                $parsedBody['elements'][] = $this->bodyElementDataTransformerHandler->execute(
                    $bodyElement,
                    $resolveData
                );
            } catch (BodyDataTransformerNotFoundException $exception) {
                $this->logger->info($exception->getMessage());
                continue;
            }
        }

        return $parsedBody;
    }
}
