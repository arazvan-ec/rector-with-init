<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\DataProvider;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailsAppsDataProvider
{
    /**
     * @return array<string, mixed>
     */
    public function getJournalists(): array
    {
        return [
            'one-journalist' => [
                [
                    'aliasId' => [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                ],
                ['aliasId'],
                [
                    [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                ],
            ],
            'no-journalist' => [
                [
                    'aliasId' => [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                ],
                [],
                [],
            ],
            'two-journalist-and-one-result' => [
                [
                    'aliasId1' => [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId1',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                    'aliasId2' => [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId2',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                ],
                ['aliasId2'],
                [
                    [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId2',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                ],
            ],
            'two-journalist-and-two-result' => [
                [
                    'aliasId1' => [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId1',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                    'aliasId2' => [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId2',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                ],
                ['aliasId1', 'aliasId2'],
                [
                    [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId1',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                    [
                        'journalistId' => 'journalistId',
                        'aliasId' => 'aliasId2',
                        'name' => 'name',
                        'url' => 'url',
                        'departments' => [
                            [
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        'photo' => 'photo',
                    ],
                ],
            ],
        ];
    }
}
