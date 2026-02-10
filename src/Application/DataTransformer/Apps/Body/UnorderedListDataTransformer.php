<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\UnorderedList;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class UnorderedListDataTransformer extends GenericListDataTransformer
{
    /** @var UnorderedList */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        Assertion::isInstanceOf($this->bodyElement, UnorderedList::class);

        return parent::read();
    }

    public function canTransform(): string
    {
        return UnorderedList::class;
    }
}
