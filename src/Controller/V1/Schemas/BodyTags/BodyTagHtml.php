<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BodyTagHtml',
    title: 'BodyTagHtml',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['bodytaghtml']
        ),
        new OA\Property(
            property: 'content',
            type: 'string'
        ),
    ],
    type: 'object',
    example: '{"type":"bodytaghtml","content":"<iframe src=\"https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2Felconfidencial%2Fposts%2Fpfbid0mdjXYhdUdmEDtec4xPmmPxRa1xwdhQa36qUzgn9PZS5LrvakVs1E7p9ZstrUHdBzl&show_text=true&width=500\" width=\"500\" height=\"250\" style=\"border:none;overflow:hidden\" scrolling=\"no\" frameborder=\"0\" allowfullscreen=\"true\" allow=\"autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share\"></iframe>"}'
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
class BodyTagHtml
{
}
