<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Section',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', example: '318'),
        new OA\Property(property: 'name', type: 'string', example: 'Madrid'),
        new OA\Property(property: 'url', type: 'string', example: 'https://www.elconfidencial.dev/espana/madrid'),
    ]
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 *
 * @codeCoverageIgnore
 */
class Section
{
}
