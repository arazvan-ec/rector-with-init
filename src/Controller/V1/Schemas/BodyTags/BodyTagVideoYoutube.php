<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BodyTagVideoYoutube',
    title: 'BodyTagVideoYoutube',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['bodytagvideoyoutube']
        ),
        new OA\Property(
            property: 'id',
            type: 'string'
        ),
        new OA\Property(
            property: 'width',
            type: 'int'
        ),
        new OA\Property(
            property: 'height',
            type: 'int'
        ),
        new OA\Property(
            property: 'caption',
            type: 'string'
        ),
        new OA\Property(
            property: 'start',
            type: 'int'
        ),
        new OA\Property(
            property: 'video',
            type: 'string'
        ),
    ],
    type: 'object',
    example: '{"type":"bodytagvideoyoutube","id":"L5lXyh98Itg","width":1280,"height":720,"caption":"Youtube video","start":0,"video":"https://player.elconfidencial.dev/embed/video/L5lXyh98Itg/1280/720/0/"}'
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class BodyTagVideoYoutube
{
}
