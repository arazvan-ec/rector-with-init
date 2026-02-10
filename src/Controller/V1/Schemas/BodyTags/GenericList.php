<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GenericList',
    title: 'Generic list',
    description: 'Generic list',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['numberedlist', 'unorderedlist']
        ),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(
                ref: new Model(type: Paragraph::class)
            )
        ),
    ],
    type: 'object'
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class GenericList
{
}
