<?php

namespace App\Tests\Orchestrator\Chain\DataProvider;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
class EditorialOrchestratorDataProvider
{
    /**
     * @return array<string, mixed>
     */
    public static function getData(): array
    {
        /** @var array<1|2|3|7|6|9, array{
         *     journalistId: string,
         *     aliasId: string,
         *     name: string,
         *     url: string,
         *     photo: string,
         *     departments: array<int, array{id: string, name: string}>
         * }> $allJournalist */
        $allJournalist = [
            '1' => [
                'journalistId' => '1',
                'aliasId' => '1',
                'name' => 'Javier Bocanegra 1',
                'url' => 'https://www.elconfidencial.dev/autores/Javier+Bocanegra-2338/',
                'photo' => 'https://images.ecestaticos.dev/K0FFtVTsHaYc4Yd0feIi_Oiu6O4=/dev.f.elconfidencial.com/journalist/1b2/c5e/4ff/1b2c5e4fff467ca4e86b6aa3d3ded248.jpg',
                'departments' => [
                    [
                        'id' => '11',
                        'name' => 'Fin de semana',
                    ],
                ],
            ],
            '2' => [
                'journalistId' => '2',
                'aliasId' => '2',
                'name' => 'Javier Bocanegra 1',
                'url' => 'https://www.elconfidencial.dev/autores/Javier+Bocanegra-2338/',
                'photo' => 'https://images.ecestaticos.dev/K0FFtVTsHaYc4Yd0feIi_Oiu6O4=/dev.f.elconfidencial.com/journalist/1b2/c5e/4ff/1b2c5e4fff467ca4e86b6aa3d3ded248.jpg',
                'departments' => [
                    [
                        'id' => '11',
                        'name' => 'Fin de semana',
                    ],
                ],
            ],
            '5' => [
                'journalistId' => '5',
                'aliasId' => '5',
                'name' => 'Javier Bocanegra 1',
                'url' => 'https://www.elconfidencial.dev/autores/Javier+Bocanegra-2338/',
                'photo' => 'https://images.ecestaticos.dev/K0FFtVTsHaYc4Yd0feIi_Oiu6O4=/dev.f.elconfidencial.com/journalist/1b2/c5e/4ff/1b2c5e4fff467ca4e86b6aa3d3ded248.jpg',
                'departments' => [
                    [
                        'id' => '11',
                        'name' => 'Fin de semana',
                    ],
                ],
            ],
            '6' => [
                'journalistId' => '6',
                'aliasId' => '6',
                'name' => 'Javier Bocanegra 1',
                'url' => 'https://www.elconfidencial.dev/autores/Javier+Bocanegra-2338/',
                'photo' => 'https://images.ecestaticos.dev/K0FFtVTsHaYc4Yd0feIi_Oiu6O4=/dev.f.elconfidencial.com/journalist/1b2/c5e/4ff/1b2c5e4fff467ca4e86b6aa3d3ded248.jpg',
                'departments' => [
                    [
                        'id' => '11',
                        'name' => 'Fin de semana',
                    ],
                ],
            ],
            '7' => [
                'journalistId' => '7',
                'aliasId' => '7',
                'name' => 'Javier Bocanegra 1',
                'url' => 'https://www.elconfidencial.dev/autores/Javier+Bocanegra-2338/',
                'photo' => 'https://images.ecestaticos.dev/K0FFtVTsHaYc4Yd0feIi_Oiu6O4=/dev.f.elconfidencial.com/journalist/1b2/c5e/4ff/1b2c5e4fff467ca4e86b6aa3d3ded248.jpg',
                'departments' => [
                    [
                        'id' => '11',
                        'name' => 'Fin de semana',
                    ],
                ],
            ],
            '9' => [
                'journalistId' => '9',
                'aliasId' => '9',
                'name' => 'Javier Bocanegra 1',
                'url' => 'https://www.elconfidencial.dev/autores/Javier+Bocanegra-2338/',
                'photo' => 'https://images.ecestaticos.dev/K0FFtVTsHaYc4Yd0feIi_Oiu6O4=/dev.f.elconfidencial.com/journalist/1b2/c5e/4ff/1b2c5e4fff467ca4e86b6aa3d3ded248.jpg',
                'departments' => [
                    [
                        'id' => '11',
                        'name' => 'Fin de semana',
                    ],
                ],
            ],
        ];

        $multimediaTypes = [
            'null' => null,
        ];

        return [
            'case-empty' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1'],
                    'insertedNews' => [
                    ],
                    'recommender' => [
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                    ],
                ],
                $allJournalist,
                [$allJournalist['1']],
                [],
                [$allJournalist['1']],
                [],
                $multimediaTypes['null'],
            ],
            'case-empty-with-membership-and-standfirst' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1'],
                    'insertedNews' => [
                    ],
                    'recommender' => [
                    ],
                    'membershipCards' => [
                        [
                            'btns' => [
                                [
                                    'urlMembership' => 'https://www.amazon.es/url1/dp/B0BJQPQVHP1',
                                    'url' => 'https://www.amazon.es/url2/dp/B0BJQPQVHP2',
                                ],
                            ],
                        ],
                    ],
                    'bodyExpected' => [
                    ],
                    'standfirstExpected' => [
                        'type' => 'unorderedlist',
                        'items' => [
                            [
                                'type' => 'listitem',
                                'content' => 'un bolillo',
                                'links' => [],
                            ],
                            [
                                'type' => 'listitem',
                                'content' => '#replace0#',
                                'links' => [
                                    '#replace0#' => [
                                        'type' => 'link',
                                        'content' => 'dos bolillos',
                                        'url' => 'http://www.google.com',
                                        'target' => '_self',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'recommenderExpected' => [
                    ],
                ],
                $allJournalist,
                [$allJournalist['1']],
                [
                    'https://www.amazon.es/url1/dp/B0BJQPQVHP1' => 'https://www.amazon.es/url1/dp/B0BJQPQVHP1?tag=cacatuaMan',
                    'https://www.amazon.es/url2/dp/B0BJQPQVHP2' => 'https://www.amazon.es/url2/dp/B0BJQPQVHP2?tag=cacatuaMan',
                ],
                [$allJournalist['1']],
                ['' => null],
                $multimediaTypes['null'],
            ],
            'case-two-journalist' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1', '2'],
                    'insertedNews' => [
                    ],
                    'recommender' => [
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                    ],
                ],
                $allJournalist,
                [$allJournalist['1'], $allJournalist['2']],
                [],
                [
                    $allJournalist['1'],
                    $allJournalist['2'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-two-journalist-with-recommended' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1'],
                    'insertedNews' => [
                    ],
                    'recommender' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                        [
                            'type' => 'recommendededitorial',
                            'editorialId' => '3',
                            'title' => 'Noticia para recomendar',
                            'signatures' => [
                                $allJournalist['7'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                ],
                $allJournalist,
                [$allJournalist['1']],
                [],
                [
                    $allJournalist['7'],
                    $allJournalist['1'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-two-journalist-with-body-tag-inserted-news' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['1'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['1'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                    ],
                ],
                $allJournalist,
                [$allJournalist['1']],
                [],
                [
                    $allJournalist['1'],
                    $allJournalist['1'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-three-journalist' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1', '2', '7'],
                    'insertedNews' => [
                    ],
                    'recommender' => [
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                    ],
                ],
                $allJournalist,
                [$allJournalist['1'], $allJournalist['2'], $allJournalist['7']],
                [],
                [
                    $allJournalist['1'],
                    $allJournalist['2'],
                    $allJournalist['7'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-three-journalist-with-inserted-and-recommended' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                        [
                            'id' => '4',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['2'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['7'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                        [
                            'type' => 'recommendededitorial',
                            'editorialId' => '4',
                            'title' => 'Noticia para recomendar',
                            'signatures' => [
                                $allJournalist['2'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                ],
                $allJournalist,
                [$allJournalist['1']],
                [],
                [
                    $allJournalist['7'],
                    $allJournalist['2'],
                    $allJournalist['1'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-three-journalist-with-inserted' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1', '2'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['7'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                    ],
                ],
                $allJournalist,
                [$allJournalist['1'], $allJournalist['2']],
                [],
                [
                    $allJournalist['7'],
                    $allJournalist['1'],
                    $allJournalist['2'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-three-journalist-with-inserted-two' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['2', '7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['2'],
                                $allJournalist['7'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                    ],
                ],
                $allJournalist,
                [$allJournalist['1']],
                [],
                [
                    $allJournalist['2'],
                    $allJournalist['7'],
                    $allJournalist['1'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-four-journalist-with-inserted-and-recommended' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1', '6'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                        [
                            'id' => '4',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['2'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['7'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                        [
                            'type' => 'recommendededitorial',
                            'editorialId' => '4',
                            'title' => 'Noticia para recomendar',
                            'signatures' => [
                                $allJournalist['2'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                ],
                $allJournalist,
                [$allJournalist['1'], $allJournalist['6']],
                [],
                [
                    $allJournalist['7'],
                    $allJournalist['2'],
                    $allJournalist['1'],
                    $allJournalist['6'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-with-deactive-journalist-onto-editorial' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1', '6', '9'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                        [
                            'id' => '4',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['2'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['7'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                        [
                            'type' => 'recommendededitorial',
                            'editorialId' => '4',
                            'title' => 'Noticia para recomendar',
                            'signatures' => [
                                $allJournalist['2'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                ],
                $allJournalist,
                [$allJournalist['1'], $allJournalist['6']],
                [],
                [
                    $allJournalist['7'],
                    $allJournalist['2'],
                    $allJournalist['1'],
                    $allJournalist['6'],
                    $allJournalist['9'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-with-deactive-journalist-onto-inserted' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1', '6'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                        [
                            'id' => '4',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['2'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['7'],
                                $allJournalist['9'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                        [
                            'type' => 'recommendededitorial',
                            'editorialId' => '4',
                            'title' => 'Noticia para recomendar',
                            'signatures' => [
                                $allJournalist['2'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                ],
                $allJournalist,
                [$allJournalist['1'], $allJournalist['6']],
                [],
                [
                    $allJournalist['7'],
                    $allJournalist['2'],
                    $allJournalist['1'],
                    $allJournalist['6'],
                    $allJournalist['9'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-with-deactive-journalist-onto-recommender' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1', '6'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                        [
                            'id' => '4',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['2'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['7'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                        [
                            'type' => 'recommendededitorial',
                            'editorialId' => '4',
                            'title' => 'Noticia para recomendar',
                            'signatures' => [
                                $allJournalist['2'],
                                $allJournalist['9'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                ],
                $allJournalist,
                [$allJournalist['1'], $allJournalist['6']],
                [],
                [
                    $allJournalist['7'],
                    $allJournalist['2'],
                    $allJournalist['1'],
                    $allJournalist['6'],
                    $allJournalist['9'],
                ],
                [],
                $multimediaTypes['null'],
            ],
            'case-with-not-found-journalist' => [
                [
                    'id' => 'editorialId',
                    'sectionId' => 'editorialSectionId',
                    'signatures' => ['1', '6', '10'],
                    'insertedNews' => [
                        [
                            'id' => '3',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['7'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'recommender' => [
                        [
                            'id' => '4',
                            'sectionId' => 'sectionId3',
                            'signatures' => ['2'],
                            'multimediaId' => '56',
                        ],
                    ],
                    'membershipCards' => [
                    ],
                    'bodyExpected' => [
                        [
                            'type' => 'bodytaginsertednews',
                            'editorialId' => '3',
                            'title' => 'Noticia para insertar',
                            'signatures' => [
                                $allJournalist['7'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                    'standfirstExpected' => [
                    ],
                    'recommenderExpected' => [
                        [
                            'type' => 'recommendededitorial',
                            'editorialId' => '4',
                            'title' => 'Noticia para recomendar',
                            'signatures' => [
                                $allJournalist['2'],
                            ],
                            'editorial' => 'XXX',
                            'shots' => [
                                '202w' => 'XXX',
                                '144w' => 'XXX',
                                '128w' => 'XXX',
                            ],
                            'photo' => 'XXX',
                        ],
                    ],
                ],
                $allJournalist,
                [$allJournalist['1'], $allJournalist['6']],
                [],
                [
                    $allJournalist['7'],
                    $allJournalist['2'],
                    $allJournalist['1'],
                    $allJournalist['6'],
                    new \Exception('Journalist not found'),
                ],
                [],
                $multimediaTypes['null'],
            ],
        ];
    }
}
