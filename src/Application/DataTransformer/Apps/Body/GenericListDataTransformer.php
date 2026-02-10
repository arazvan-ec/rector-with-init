<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\Trait\LinksDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\GenericList;
use Ec\Editorial\Domain\Model\Body\ListItem;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
abstract class GenericListDataTransformer extends ElementTypeDataTransformer
{
    use LinksDataTransformer;

    /** @var GenericList */
    protected BodyElement $bodyElement;

    public function read(): array
    {
        $elementArray = parent::read();
        $elementArray['items'] = [];

        /** @var ListItem $item */
        foreach ($this->bodyElement as $item) {
            $links = $this->readLinks($item);

            $listItemArray = [];
            $listItemArray['type'] = $item->type();
            $listItemArray['content'] = $item->content();
            $listItemArray['links'] = $links ?: null;
            $elementArray['items'][] = $listItemArray;
        }

        return $elementArray;
    }
}
