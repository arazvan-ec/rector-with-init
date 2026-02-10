<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BodyTagPicture',
    title: 'BodyTagPicture',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['bodytagpicture']
        ),
        new OA\Property(
            property: 'shots',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'string',
                format: 'url'
            )
        ),
        new OA\Property(
            property: 'url',
            type: 'string'
        ),
        new OA\Property(
            property: 'caption',
            type: 'string'
        ),
        new OA\Property(
            property: 'alternate',
            type: 'string'
        ),
        new OA\Property(
            property: 'orientation',
            type: 'string',
            enum: ['landscape', 'portrait', 'square']
        ),
    ],
    type: 'object',
    example: '{
        "type": "bodytagpicture",
        "shots": {
            "1440w": "https://images.ecestaticos.dev/B26-5pH9vylfOiapiBjXanvO7Ho=/615x99:827x381/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg",
            "1200w": "https://images.ecestaticos.dev/gN2tLeVBCOcV5AKBmZeJhGYztTk=/615x99:827x381/1200x1600/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg",
            "996w": "https://images.ecestaticos.dev/YRLxy6ChIKjekgdg_BN1DirWtJ8=/615x99:827x381/996x1328/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg",
            "560w": "https://images.ecestaticos.dev/WByyZwZDIXdsAikGvHjMd3wOiUI=/615x99:827x381/560x747/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg",
            "390w": "https://images.ecestaticos.dev/6LRdLT09KxKdAIaRQV6gbHtiZSQ=/615x99:827x381/390x520/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg",
            "568w": "https://images.ecestaticos.dev/m70h5OCBdQyGjYRqai5qmRVZoUQ=/615x99:827x381/568x757/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg",
            "382w": "https://images.ecestaticos.dev/ws_0oo3JORfvWxI_XKyluvDeGRI=/615x99:827x381/382x509/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg",
            "328w": "https://images.ecestaticos.dev/YsYE5tLIS_WX3BU6agIfeikYUl8=/615x99:827x381/328x437/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg"
        },
        "url": "https://images.ecestaticos.dev/B26-5pH9vylfOiapiBjXanvO7Ho=/615x99:827x381/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg",
        "caption": "",
        "alternate": "Prueba con pepe Astros",
        "orientation": "portrait"
    }'
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class BodyTagPicture
{
}
