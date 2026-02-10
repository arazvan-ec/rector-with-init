<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use App\Controller\V1\Schemas\LinkCollection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubHead',
    title: 'SubHead',
    description: 'SubHead',
    properties: [
        new OA\Property(
            property: 'type',
            description: 'SubHead',
            type: 'string',
            enum: ['subhead']
        ),
        new OA\Property(
            property: 'content',
            description: 'SubHead content',
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
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class SubHead
{
}
