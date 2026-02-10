<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas;

use App\Controller\V1\Schemas\BodyTags\BodyTagHtml;
use App\Controller\V1\Schemas\BodyTags\BodyTagInsertedNews;
use App\Controller\V1\Schemas\BodyTags\BodyTagMembershipCard;
use App\Controller\V1\Schemas\BodyTags\BodyTagPicture;
use App\Controller\V1\Schemas\BodyTags\BodyTagPictureMembership;
use App\Controller\V1\Schemas\BodyTags\BodyTagVideoYoutube;
use App\Controller\V1\Schemas\BodyTags\GenericList;
use App\Controller\V1\Schemas\BodyTags\Paragraph;
use App\Controller\V1\Schemas\BodyTags\SubHead;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Body',
    description: 'Cuerpo del directo (entradas)',
    properties: [
        new OA\Property(
            property: 'type',
            description: 'Tipo del cuerpo del directo (entradas)',
            type: 'string',
            enum: ['normal']
        ),
        new OA\Property(
            property: 'elements',
            type: 'array',
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(
                        ref: new Model(type: Paragraph::class)
                    ),
                    new OA\Schema(
                        ref: new Model(type: GenericList::class)
                    ),
                    new OA\Schema(
                        ref: new Model(type: BodyTagVideoYoutube::class)
                    ),
                    new OA\Schema(
                        ref: new Model(type: BodyTagHtml::class)
                    ),
                    new OA\Schema(
                        ref: new Model(type: BodyTagPicture::class)
                    ),
                    new OA\Schema(
                        ref: new Model(type: BodyTagPictureMembership::class)
                    ),
                    new OA\Schema(
                        ref: new Model(type: BodyTagMembershipCard::class)
                    ),
                    new OA\Schema(
                        ref: new Model(type: BodyTagInsertedNews::class)
                    ),
                    new OA\Schema(
                        ref: new Model(type: SubHead::class)
                    ),
                ]
            )
        ),
    ],
    type: 'object'
)]
/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 *
 * @codeCoverageIgnore
 */
class Body
{
}
