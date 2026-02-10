<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BodyTagPictureMembership',
    title: 'BodyTagPictureMembership',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['bodytagpicturemembership']
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
            property: 'orientation',
            type: 'string',
            enum: ['landscape', 'portrait', 'square']
        ),
    ],
    type: 'object',
    example: '{
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
               }'
)]
/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class BodyTagPictureMembership
{
}
