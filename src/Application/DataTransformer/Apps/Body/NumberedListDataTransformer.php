<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\NumberedList;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class NumberedListDataTransformer extends GenericListDataTransformer
{
    /** @var NumberedList */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        Assertion::isInstanceOf($this->bodyElement, NumberedList::class);

        return parent::read();
    }

    public function canTransform(): string
    {
        return NumberedList::class;
    }
}
