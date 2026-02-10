<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagSummary;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class BodyTagSummaryDataTransformer extends ElementContentDataTransformer
{
    /** @var BodyTagSummary */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        $message = 'BodyElement should be instance of '.BodyTagSummary::class;
        Assertion::isInstanceOf($this->bodyElement, BodyTagSummary::class, $message);

        return parent::read();
    }

    public function canTransform(): string
    {
        return BodyTagSummary::class;
    }
}
