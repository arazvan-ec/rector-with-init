<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BodyTagMembershipCard',
    title: 'BodyTagMembershipCard',
    properties: [
        new OA\Property(
            property: 'type',
            description: 'Tipo de elemento bodytagmembershipcard',
            type: 'string',
            enum: ['bodytagmembershipcard']
        ),
        new OA\Property(
            property: 'title',
            description: 'Titulo para presentación del producto',
            type: 'string'
        ),
        new OA\Property(
            property: 'buttons',
            description: 'Botones relacionados al producto de afiliación',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(
                        property: 'url',
                        description: 'URL del producto',
                        type: 'string'
                    ),
                    new OA\Property(
                        property: 'urlMembership',
                        description: 'URL relacionada al producto de afiliación',
                        type: 'string'
                    ),
                    new OA\Property(
                        property: 'text',
                        description: 'Texto del botón',
                        type: 'string'
                    ),
                ],
                type: 'object'
            )
        ),
        new OA\Property(
            property: 'titleBanner',
            description: 'Titulo del banner destacado',
            type: 'string'
        ),
        new OA\Property(
            property: 'classBanner',
            description: 'Clase del banner',
            type: 'string'
        ),
        new OA\Property(
            property: 'picture',
            description: 'Objecto de imagen para el producto de afiliación',
            ref: new Model(type: BodyTagPictureMembership::class)
        ),
    ],
    type: 'object',
    example: '{
                "type": "bodytagmembershipcard",
                "title": "titulo del producto",
                "buttons": [
                    {
                        "url": "https://www.amazon.es/Cecotec-Multifunci%C3%B3n-Funciones-Antiadherente-Accesorios/dp/B0BJQPQVHP?ref=dlx_deals_dg_dcl_B0BJQPQVHP_dt_sl14_33&th=1&tag=elconfidencial-21",
                        "urlMembership": "https://www.amazon.es/Cecotec-Multifunci%C3%B3n-Funciones-Antiadherente-Accesorios/dp/B0BJQPQVHP?ref=dlx_deals_dg_dcl_B0BJQPQVHP_dt_sl14_33&th=1&tag=elconfidencial-21",
                        "text": "texto boton1"
                    },
                    {
                        "url": "https://www.amazon.es/Cecotec-Multifunci%C3%B3n-Funciones-Antiadherente-Accesorios/dp/B0BJQPQVHP?ref=dlx_deals_dg_dcl_B0BJQPQVHP_dt_sl14_33&th=1",
                        "urlMembership": "https://www.amazon.es/Cecotec-Multifunci%C3%B3n-Funciones-Antiadherente-Accesorios/dp/B0BJQPQVHP?ref=dlx_deals_dg_dcl_B0BJQPQVHP_dt_sl14_33&th=1",
                        "text": "texto boton 2"
                    }
                ],
                "titleBanner": "soy un banner destacado",
                "classBanner": "",
                "picture":
                {
                    "type": "bodytagpicturemembership",
                    "shots":{
                        "1440w": "https://images.ecestaticos.dev/nzgYsFk9qmYojWEP_yCYOyskYTk=/95x115:350x454/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg",
                        "1200w": "https://images.ecestaticos.dev/ZWevS17Sact--pDW-N-7_qDCZRg=/95x115:350x454/1200x1600/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg",
                        "996w": "https://images.ecestaticos.dev/3NiH0epL2B7WTT5spwtTHlZ3aFY=/95x115:350x454/996x1328/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg",
                        "560w": "https://images.ecestaticos.dev/itQXCEsAHrGRGHnDSGlKlxY84TI=/95x115:350x454/560x747/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg",
                        "390w": "https://images.ecestaticos.dev/7FxxRC2DcSGN_Bjx2Ok9p0hjrp8=/95x115:350x454/390x520/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg",
                        "568w": "https://images.ecestaticos.dev/bSKBNEB621ZGzxmSeFnk0DsGPpA=/95x115:350x454/568x757/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg",
                        "382w": "https://images.ecestaticos.dev/zQlmwVQyBPI70VJbJ8Xl0CbLJh0=/95x115:350x454/382x509/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg",
                        "328w": "https://images.ecestaticos.dev/gQlH0-sL63ts8UlWyBUXMhNedC8=/95x115:350x454/328x437/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg"
                    },
                    "url": "https://images.ecestaticos.dev/nzgYsFk9qmYojWEP_yCYOyskYTk=/95x115:350x454/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg",
                    "orientation": "portrait"
                }
            }'
)]
/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class BodyTagMembershipCard
{
}
