<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body\DataProvider;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class BodyTagMembershipCardDataProvider
{
    /**
     * @return array<string, mixed>
     */
    public static function getData(): array
    {
        return [
            'two-btn-one-link-for-all' => [
                [
                    'btns' => [
                        [
                            'url' => 'https://google.com',
                            'urlMembership' => 'https://google.com',
                            'cta' => 'cta',
                        ],
                        [
                            'url' => 'https://google.com',
                            'urlMembership' => 'https://google.com',
                            'cta' => 'cta',
                        ],
                    ],
                    'title' => 'title',
                    'titleBanner' => 'titleBanner',
                    'classBanner' => 'classBanner',
                ],
                [
                    'membershipLinkCombine' => [
                        'https://google.com' => 'https://google.com?tag=ec-confi-21',
                    ],
                ],
                [
                    'type' => 'bodytagmembershipcard',
                    'title' => 'title',
                    'buttons' => [
                        [
                            'url' => 'https://google.com?tag=ec-confi-21',
                            'urlMembership' => 'https://google.com?tag=ec-confi-21',
                            'text' => 'cta',
                        ],
                        [
                            'url' => 'https://google.com?tag=ec-confi-21',
                            'urlMembership' => 'https://google.com?tag=ec-confi-21',
                            'text' => 'cta',
                        ],
                    ],
                    'titleBanner' => 'titleBanner',
                    'classBanner' => 'classBanner',
                    'picture' => [],
                ],
            ],
            'two-btn-one-link-for-one' => [
                [
                    'btns' => [
                        [
                            'url' => 'https://google.com',
                            'urlMembership' => 'https://google2.com',
                            'cta' => 'cta',
                        ],
                        [
                            'url' => 'https://google3.com',
                            'urlMembership' => 'https://google4.com',
                            'cta' => 'cta',
                        ],
                    ],
                    'title' => 'title',
                    'titleBanner' => 'titleBanner',
                    'classBanner' => 'classBanner',
                ],
                [
                    'membershipLinkCombine' => [
                        'https://google.com' => 'https://google.com?tag=ec-confi-21',
                    ],
                ],
                [
                    'type' => 'bodytagmembershipcard',
                    'title' => 'title',
                    'buttons' => [
                        [
                            'url' => 'https://google.com?tag=ec-confi-21',
                            'urlMembership' => 'https://google2.com',
                            'text' => 'cta',
                        ],
                        [
                            'url' => 'https://google3.com',
                            'urlMembership' => 'https://google4.com',
                            'text' => 'cta',
                        ],
                    ],
                    'titleBanner' => 'titleBanner',
                    'classBanner' => 'classBanner',
                    'picture' => [],
                ],
            ],
            'one-btn-second-link' => [
                [
                    'btns' => [
                        [
                            'url' => 'https://google.com',
                            'urlMembership' => 'https://google2.com',
                            'cta' => 'cta',
                        ],
                    ],
                    'title' => 'title',
                    'titleBanner' => 'titleBanner',
                    'classBanner' => 'classBanner',
                ],
                [
                    'membershipLinkCombine' => [
                        'https://google3.com' => 'https://google.com?tag=ec-confi-21',
                        'https://google.com' => 'https://google.com?tag=ec-confi-21',
                    ],
                ],
                [
                    'type' => 'bodytagmembershipcard',
                    'title' => 'title',
                    'buttons' => [
                        [
                            'url' => 'https://google.com?tag=ec-confi-21',
                            'urlMembership' => 'https://google2.com',
                            'text' => 'cta',
                        ],
                    ],
                    'titleBanner' => 'titleBanner',
                    'classBanner' => 'classBanner',
                    'picture' => [],
                ],
            ],
            'two-btn-any-link-for-all' => [
                [
                    'btns' => [
                        [
                            'url' => 'https://google.com',
                            'urlMembership' => 'https://google1.com',
                            'cta' => 'cta',
                        ],
                        [
                            'url' => 'https://google2.com',
                            'urlMembership' => 'https://google3.com',
                            'cta' => 'cta',
                        ],
                    ],
                    'title' => 'title',
                    'titleBanner' => 'titleBanner',
                    'classBanner' => 'classBanner',
                ],
                [
                    'membershipLinkCombine' => [
                        'https://google4.com' => 'https://google.com?tag=ec-confi-21',
                    ],
                ],
                [
                    'type' => 'bodytagmembershipcard',
                    'title' => 'title',
                    'buttons' => [
                        [
                            'url' => 'https://google.com',
                            'urlMembership' => 'https://google1.com',
                            'text' => 'cta',
                        ],
                        [
                            'url' => 'https://google2.com',
                            'urlMembership' => 'https://google3.com',
                            'text' => 'cta',
                        ],
                    ],
                    'titleBanner' => 'titleBanner',
                    'classBanner' => 'classBanner',
                    'picture' => [],
                ],
            ],
        ];
    }
}
