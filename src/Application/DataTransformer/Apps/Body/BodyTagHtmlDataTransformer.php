<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagHtml;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class BodyTagHtmlDataTransformer extends ElementContentDataTransformer
{
    /** @var BodyTagHtml */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        $message = 'BodyElement should be instance of '.BodyTagHtml::class;
        Assertion::isInstanceOf($this->bodyElement, BodyTagHtml::class, $message);

        return parent::read();
    }

    public function canTransform(): string
    {
        return BodyTagHtml::class;
    }
}
