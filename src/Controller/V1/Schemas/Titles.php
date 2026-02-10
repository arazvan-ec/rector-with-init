<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Titles',
    type: 'object',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Title'),
        new OA\Property(property: 'preTitle', type: 'string', example: 'Pre title'),
        new OA\Property(property: 'urlTitle', type: 'string', example: 'url-title'),
        new OA\Property(property: 'mobileTitle', type: 'string', example: 'Mobile title'),
    ]
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 *
 * @codeCoverageIgnore
 */
class Titles
{
}
