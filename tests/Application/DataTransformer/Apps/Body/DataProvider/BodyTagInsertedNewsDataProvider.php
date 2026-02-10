<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body\DataProvider;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class BodyTagInsertedNewsDataProvider
{
    /**
     * @return array<string, mixed>
     */
    public static function getData(): array
    {
        return [
            'inserted-news-with-one-signature' => [
                [
                    'signatures' => [
                        [
                            'journalistId' => '5164',
                            'aliasId' => '20116',
                            'name' => 'jmoreu',
                            'url' => 'https://www.elconfidencial.dev/autores/jose-guillermo-moreu-peso-5164/',
                            'photo' => 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png',
                            'departments' => [
                                [
                                    'id' => '1',
                                    'name' => 'Técnico',
                                ],
                            ],
                        ],
                    ],
                    'title' => 'title body tag inserted news',
                    'editorial' => 'https://www.elconfidencial.dev/_editorial-456',
                    'shots' => [
                        '202w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                        '144w' => 'https://images.ecestaticos.dev/5D2vSIceX7HBoCSheXq_CJZfziE=/0x0:458x344/144x108/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                        '128w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    ],
                    'photo' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    'file' => '8b294d3142a5c28c1c7467da78c13481.jpg',
                    'signaturesIndexes' => [
                        '20116',
                    ],
                    'sizes' => [
                        [
                            'width' => '202',
                            'height' => '152',
                        ],
                        [
                            'width' => '144',
                            'height' => '108',
                        ],
                        [
                            'width' => '128',
                            'height' => '96',
                        ],
                    ],
                ],
                [
                    'signaturesWithIndexId' => [
                        '20116' => [
                            'journalistId' => '5164',
                            'aliasId' => '20116',
                            'name' => 'jmoreu',
                            'url' => 'https://www.elconfidencial.dev/autores/jose-guillermo-moreu-peso-5164/',
                            'photo' => 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png',
                            'departments' => [
                                [
                                    'id' => '1',
                                    'name' => 'Técnico',
                                ],
                            ],
                        ],
                    ],
                ],
                // expected
                [
                    'type' => 'bodytaginsertednews',
                    'editorialId' => 'editorial_id',
                    'title' => 'title body tag inserted news',
                    'signatures' => [
                        '20116',
                    ],
                    'editorial' => 'https://www.elconfidencial.dev/_editorial_id',
                    'shots' => [
                        '202w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                        '144w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                        '128w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    ],
                    'photo' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                ],
            ],
            'inserted-news-with-two-signature' => [
                [
                    'signatures' => [
                        [
                            'journalistId' => '5164',
                            'aliasId' => '20116',
                            'name' => 'jmoreu',
                            'url' => 'https://www.elconfidencial.dev/autores/jose-guillermo-moreu-peso-5164/',
                            'photo' => 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png',
                            'departments' => [
                                [
                                    'id' => '1',
                                    'name' => 'Técnico',
                                ],
                            ],
                        ],
                        [
                            'journalistId' => '5165',
                            'aliasId' => '20117',
                            'name' => 'another-author',
                            'url' => 'https://www.elconfidencial.dev/autores/another-author-5165/',
                            'photo' => 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png',
                            'departments' => [
                                [
                                    'id' => '1',
                                    'name' => 'Técnico',
                                ],
                            ],
                        ],
                    ],
                    'title' => 'title body tag inserted news',
                    'editorial' => 'https://www.elconfidencial.dev/_editorial-456',
                    'shots' => [
                        '202w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                        '144w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                        '128w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    ],
                    'photo' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    'signaturesIndexes' => [
                        '20116',
                        '20117',
                    ],
                    'sizes' => [
                        [
                            'width' => '202',
                            'height' => '152',
                        ],
                        [
                            'width' => '144',
                            'height' => '108',
                        ],
                        [
                            'width' => '128',
                            'height' => '96',
                        ],
                    ],
                    'file' => '8b294d3142a5c28c1c7467da78c13481.jpg',
                ],
                [
                    'signaturesWithIndexId' => [
                        '20116' => [
                            'journalistId' => '5164',
                            'aliasId' => '20116',
                            'name' => 'jmoreu',
                            'url' => 'https://www.elconfidencial.dev/autores/jose-guillermo-moreu-peso-5164/',
                            'photo' => 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png',
                            'departments' => [
                                [
                                    'id' => '1',
                                    'name' => 'Técnico',
                                ],
                            ],
                        ],
                        '20117' => [
                            'journalistId' => '5165',
                            'aliasId' => '20117',
                            'name' => 'another-author',
                            'url' => 'https://www.elconfidencial.dev/autores/another-author-5165/',
                            'photo' => 'https://images.ecestaticos.dev/FGsmLp_UG1BtJpvlkXA8tzDqltY=/dev.f.elconfidencial.com/journalist/953/855/f9d/953855f9d072b9cd509c3f6c5f9dc77f.png',
                            'departments' => [
                                [
                                    'id' => '1',
                                    'name' => 'Técnico',
                                ],
                            ],
                        ],
                    ],
                ],
                // expected
                [
                    'type' => 'bodytaginsertednews',
                    'editorialId' => 'editorial_id',
                    'title' => 'title body tag inserted news',
                    'signatures' => [
                        '20116',
                        '20117',
                    ],
                    'editorial' => 'https://www.elconfidencial.dev/_editorial_id',
                    'shots' => [
                        '202w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                        '144w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                        '128w' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                    ],
                    'photo' => 'https://images.ecestaticos.dev/jGiDDrUXxOoBrAXspxCAxfgkPog=/0x0:458x344/202x152/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/8b2/94d/314/8b294d3142a5c28c1c7467da78c13481.jpg',
                ],
            ],
        ];
    }
}
