<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LinkCollection',
    title: 'Link Collection',
    description: 'Collection of links or null',
    oneOf: [
        new OA\Schema(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(
                        property: 'replace{num#}',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'type', type: 'string', enum: ['link']),
                            new OA\Property(property: 'content', type: 'string'),
                            new OA\Property(property: 'url', type: 'string'),
                            new OA\Property(property: 'target', type: 'string'),
                        ]
                    ),
                ]
            )
        ),
        new OA\Schema(type: 'null'),
    ]
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 *
 * @codeCoverageIgnore
 */
class LinkCollection
{
}
