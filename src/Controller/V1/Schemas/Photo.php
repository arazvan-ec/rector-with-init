<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Photo',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            example: 'photo'
        ),
        new OA\Property(
            property: 'id',
            type: 'string',
            example: '3498'
        ),
        new OA\Property(
            property: 'caption',
            type: 'string',
            example: 'photo caption'
        ),
        new OA\Property(
            property: 'shots',
            type: 'object',
            example: '{
                "202w": "https://images.ecestaticos.dev/ln0e0EQDSKs2I9zx-36g8uev2D4=/0x0:800x450/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/238/f7a/18e/238f7a18eec0117162977cae575ced6e.jpg",
                "144w": "https://images.ecestaticos.dev/2Ksg8cEvX38lUfYvc46L1Te7dQI=/0x0:800x450/144x108/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/238/f7a/18e/238f7a18eec0117162977cae575ced6e.jpg",
                "128w": "https://images.ecestaticos.dev/YuvZo3dJn-9V4tiK4yXPlUaVnZ0=/0x0:800x450/128x96/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/238/f7a/18e/238f7a18eec0117162977cae575ced6e.jpg"
            }'
        ),
        new OA\Property(
            property: 'photo',
            type: 'string',
            example: 'https://images.ecestaticos.dev/.../original/238/f7a/18e/238f7a18eec0117162977cae575ced6e.jpg'
        ),
    ],
    type: 'object'
)]
/**
 * @author Ken Serikawa <kserikawa@eext.elconfidencial.com>
 *
 * @codeCoverageIgnore
 */
class Photo
{
}
