<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\BodyDataTransformerInterface;
use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagExplanatorySummary;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class BodyTagExplanatorySummaryDataTransformer extends ElementContentDataTransformer
{
    /**
     * @param BodyTagExplanatorySummary $bodyElement
     */
    protected BodyElement $bodyElement;

    public function __construct(
        private readonly BodyDataTransformerInterface $bodyElementDataTransformer,
    ) {
    }

    public function read(): array
    {
        $message = 'BodyElement should be instance of '.BodyTagExplanatorySummary::class;
        Assertion::isInstanceOf($this->bodyElement, BodyTagExplanatorySummary::class, $message);

        $body = $this->bodyElementDataTransformer->execute($this->bodyElement->body(), []);

        return [
            'type' => $this->bodyElement->type(),
            'title' => $this->bodyElement->title(),
            'items' => $body['elements'],
        ];
    }

    public function canTransform(): string
    {
        return BodyTagExplanatorySummary::class;
    }
}
