<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Tags',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '21276'),
        new OA\Property(property: 'name', type: 'string', example: 'Toto'),
        new OA\Property(property: 'url', type: 'string', example: 'https://www.elconfidencial.dev/tags/otros/toto-21276'),
    ]
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 *
 * @codeCoverageIgnore
 */
class Tags
{
}
