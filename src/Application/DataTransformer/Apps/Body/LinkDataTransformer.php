<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Link;

/**
 * @author Jose Guillermo Moreu Peso <jgmoreu@ext.elconfidencial.com>
 */
class LinkDataTransformer extends ElementContentDataTransformer
{
    /** @var Link */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        $message = 'BodyElement should be instance of '.Link::class;
        Assertion::isInstanceOf($this->bodyElement, Link::class, $message);

        return parent::read();
    }

    public function canTransform(): string
    {
        return Link::class;
    }
}
