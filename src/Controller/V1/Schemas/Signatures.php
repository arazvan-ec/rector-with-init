<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Signatures',
    type: 'object',
    properties: [
        new OA\Property(property: 'journalistId', type: 'string', example: '2103'),
        new OA\Property(property: 'aliasId', type: 'string', example: '6277'),
        new OA\Property(property: 'name', type: 'string', example: 'Alias name'),
        new OA\Property(property: 'url', type: 'string', example: 'https://www.elconfidencial.dev/autores/journalist-encode-name-2103/'),
        new OA\Property(
            property: 'departments',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string', example: '1'),
                    new OA\Property(property: 'name', type: 'string', example: 'Department name'),
                ]
            )
        ),
        new OA\Property(property: 'photo', type: 'string', example: 'https://images.ecestaticos.test/photo.png'),
    ]
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 *
 * @codeCoverageIgnore
 */
class Signatures
{
}
