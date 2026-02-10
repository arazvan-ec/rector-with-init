<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Assert\Assertion;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Paragraph;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class ParagraphDataTransformer extends ElementContentWithLinksDataTransformer
{
    /** @var Paragraph */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        $message = 'BodyElement should be instance of '.Paragraph::class;
        Assertion::isInstanceOf($this->bodyElement, Paragraph::class, $message);

        return parent::read();
    }

    public function canTransform(): string
    {
        return Paragraph::class;
    }
}
