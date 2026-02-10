<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use App\Controller\V1\Schemas\LinkCollection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Paragraph',
    title: 'Paragraph',
    description: 'Paragraph',
    properties: [
        new OA\Property(
            property: 'type',
            description: 'Paragraph',
            type: 'string',
            enum: ['paragraph']
        ),
        new OA\Property(
            property: 'content',
            description: 'Paragraph content',
            type: 'string',
        ),
        new OA\Property(
            property: 'links',
            description: 'Links',
            ref: new Model(type: LinkCollection::class)
        ),
    ],
    type: 'object',
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class Paragraph
{
}
