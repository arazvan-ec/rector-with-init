<?php

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Body\Trait;

use Ec\Editorial\Domain\Model\Body\ElementContentWithLinks;
use Ec\Editorial\Domain\Model\Body\Link;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
trait LinksDataTransformer
{
    /**
     * @return array<string, array<string, string>>
     */
    private function readLinks(ElementContentWithLinks $elementContentWithLinks): array
    {
        $result = [];
        /** @var Link $element */
        foreach ($elementContentWithLinks->links() as $position => $element) {
            $result[$position] = [
                'type' => $element->type(),
                'content' => $element->content(),
                'url' => $element->url(),
                'target' => $element->target(),
            ];
        }

        return $result;
    }
}
