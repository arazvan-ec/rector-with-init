<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\ElementContent;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
abstract class ElementContentDataTransformer extends ElementTypeDataTransformer
{
    /** @var ElementContent */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        $elementArray = parent::read();
        $elementArray['content'] = $this->bodyElement->content();

        return $elementArray;
    }
}
