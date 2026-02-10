<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\SubHead;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class SubHeadDataTransformer extends ElementContentWithLinksDataTransformer
{
    /** @var SubHead */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        $message = 'BodyElement should be instance of '.SubHead::class;
        Assertion::isInstanceOf($this->bodyElement, SubHead::class, $message);

        return parent::read();
    }

    public function canTransform(): string
    {
        return SubHead::class;
    }
}
