<?php

/**
 * @copyright
 */

namespace App\Controller\V1\Schemas\BodyTags;

use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BodyTagInsertedNews',
    title: 'BodyTagInsertedNews',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['bodytaginsertednews']
        ),
        new OA\Property(
            property: 'editorialId',
            description: 'Identidicador de contenido editorial',
            type: 'string',
        ),
        new OA\Property(
            property: 'title',
            description: 'Titulo para una noticia insertada',
            type: 'string'
        ),
        new OA\Property(
            property: 'signatures',
            description: 'Conjunto de firmas',
            type: 'object'
        ),
        new OA\Property(
            property: 'editorial',
            description: 'URL de la noticia insertada',
            type: 'object'
        ),
        new OA\Property(
            property: 'photo',
            ref: new Model(type: \App\Controller\V1\Schemas\Photo::class),
            description: 'Foto - thumbnail de la noticia insertada'
        ),
    ],
    type: 'object',
    example: '{
      "type": "bodytaginsertednews",
      "editorialId": "4794",
      "title": "Body tag inserted news",
      "signatures": [
        {
          "journalistId": "1111",
          "aliasId": "1212",
          "name": "Ken",
          "url": "https://www.elconfidencial.dev/autores/ken-1111/",
          "photo": "https://images.ecestaticos.dev/KQd3KewvyU4hXr3XI2Av4gVNORI=/dev.f.elconfidencial.com/journalist/447/60e/9cd/44760e9cd772578bcac515980dee96a7.png",
          "departments": [
            {
              "id": "1",
              "name": "Técnico"
            }
          ]
        }
      ],
      "editorial": "https://www.elconfidencial.dev/espana/2024-11-17/body-tag-inserted-news-4794",
      "photo": {
        "id": "2208",
        "type": "photo",
        "caption": "caption!",
        "shots": {
          "202w": "https://images.ecestaticos.dev/ln0e0EQDSKs2I9zx-36g8uev2D4=/0x0:800x450/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/238/f7a/18e/238f7a18eec0117162977cae575ced6e.jpg",
          "144w": "https://images.ecestaticos.dev/2Ksg8cEvX38lUfYvc46L1Te7dQI=/0x0:800x450/144x108/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/238/f7a/18e/238f7a18eec0117162977cae575ced6e.jpg",
          "128w": "https://images.ecestaticos.dev/YuvZo3dJn-9V4tiK4yXPlUaVnZ0=/0x0:800x450/128x96/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/238/f7a/18e/238f7a18eec0117162977cae575ced6e.jpg"
        },
        "photo": "https://images.ecestaticos.dev/ln0e0EQDSKs2I9zx-36g8uev2D4=/0x0:800x450/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/238/f7a/18e/238f7a18eec0117162977cae575ced6e.jpg"
      }
    }'
)]
/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class BodyTagInsertedNews
{
}
