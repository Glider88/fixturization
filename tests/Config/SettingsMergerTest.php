<?php declare(strict_types=1);

namespace Tests\Glider88\Fixturization\Config;

use Glider88\Fixturization\Config\SettingsMerger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SettingsMergerTest extends TestCase
{
    #[DataProvider('provideSettings')]
    public function testEnrichSettings(array $config, array $expected): void
    {
        $merger = new SettingsMerger(['t1']);
        $this->assertSame($expected, $merger->enrichSettings($config));
    }

    public static function provideSettings(): iterable
    {
        yield 'empty config' => [
            'config' => [],
            'expected' => [],
        ];

        yield 'start from entrypoint over base' => [
            'config' => [
                'base_settings' => ['start' => 't1'],
                'entrypoints' => [['start' => 't2']],
            ],
            'expected' => [
                ['start' => 't2'],
            ],
        ];

        yield 'start from base' => [
            'config' => [
                'base_settings' => ['start' => 't1'],
                'entrypoints' => [[]],
            ],
            'expected' => [
                ['start' => 't1'],
            ],
        ];

        yield 'start from entrypoint' => [
            'config' => [
                'base_settings' => [],
                'entrypoints' => [['start' => 't1']],
            ],
            'expected' => [
                ['start' => 't1'],
            ],
        ];

        yield 'empty entrypoint' => [
            'config' => [
                'base_settings' => [
                    'start' => 't1',
                    't1' => [
                        'exclude_columns' => ['c1', 'c2'],
                        'filter' => 'id > 1',
                        'columns' => ['c1', 'c2'],
                        'count' => 1,
                        'transformers' => ['c1' => ['t1']],
                        'tree' => 2,
                    ],
                ],
                'entrypoints' => [[
                    't1' => [],
                ]],
            ],
            'expected' => [
                [
                    'start' => 't1',
                    't1' => [
                        'exclude_columns' => ['c1', 'c2'],
                        'columns' => ['c1', 'c2'],
                        'filter' => 'id > 1',
                        'count' => 1,
                        'transformers' => ['c1' => ['t1']],
                        'tree' => 2,
                    ],
                ],
            ],
        ];

        yield 'empty base settings' => [
            'config' => [
                'base_settings' => [
                    't1' => [],
                ],
                'entrypoints' => [[
                    'start' => 't1',
                    't1' => [
                        'exclude_columns' => ['c2', 'c3'],
                        'filter' => 'id > 2',
                        'columns' => ['c2', 'c3'],
                        'count' => 3,
                        'transformers' => ['c2' => ['t2']],
                        'tree' => 4,
                    ],
                ]],
            ],
            'expected' => [
                [
                    't1' => [
                        'exclude_columns' => ['c2', 'c3'],
                        'columns' => ['c2', 'c3'],
                        'filter' => 'id > 2',
                        'count' => 3,
                        'transformers' => ['c2' => ['t2']],
                        'tree' => 4,
                    ],
                    'start' => 't1',
                ],
            ],
        ];

        yield 'base settings and entrypoint' => [
            'config' => [
                'base_settings' => [
                    'start' => 't1',
                    't1' => [
                        'exclude_columns' => ['c1', 'c2'],
                        'filter' => 'id > 1',
                        'columns' => ['c1', 'c2'],
                        'count' => 1,
                        'transformers' => ['c1' => ['t1']],
                        'tree' => 2,
                    ],
                ],
                'entrypoints' => [[
                    't1' => [
                        'exclude_columns' => ['c2', 'c3'],
                        'filter' => 'id > 2',
                        'columns' => ['c2', 'c3'],
                        'count' => 3,
                        'transformers' => ['c2' => ['t2']],
                        'tree' => 4,
                    ],
                ]],
            ],
            'expected' => [
                [
                    'start' => 't1',
                    't1' => [
                        'exclude_columns' => ['c1', 'c2', 'c3'],
                        'columns' => ['c2', 'c3'],
                        'filter' => 'id > 2',
                        'count' => 3,
                        'transformers' => ['c2' => ['t2']],
                        'tree' => 4,
                    ],
                ],
            ],
        ];

        yield 'different base and entrypoint route settings' => [
            'config' => [
                'base_settings' => [
                    'start' => 't1',
                    'route-settings' => [[
                        'route' => ['t1', 't2'],
                        'exclude_columns' => ['c1', 'c2'],
                        'filter' => 'id > 1',
                        'columns' => ['c2', 'c3'],
                        'count' => 1,
                        'transformers' => ['c1' => ['t1']],
                        'tree' => 2,
                    ]]
                ],
                'entrypoints' => [[
                    'route-settings' => [[
                        'route' => ['t2', 't3'],
                        'exclude_columns' => ['c2', 'c3'],
                        'filter' => 'id > 2',
                        'columns' => ['c3', 'c4'],
                        'count' => 3,
                        'transformers' => ['c4' => ['t2']],
                        'tree' => 4,
                    ]]
                ]],
            ],
            'expected' => [
                [
                    'start' => 't1',
                    'route-settings' => [
                        [
                            'route' => ['t1', 't2'],
                            'exclude_columns' => ['c1', 'c2'],
                            'filter' => 'id > 1',
                            'columns' => ['c2', 'c3'],
                            'count' => 1,
                            'transformers' => ['c1' => ['t1']],
                            'tree' => 2,
                        ],
                        [
                            'route' => ['t2', 't3'],
                            'exclude_columns' => ['c2', 'c3'],
                            'filter' => 'id > 2',
                            'columns' => ['c3', 'c4'],
                            'count' => 3,
                            'transformers' => ['c4' => ['t2']],
                            'tree' => 4,
                        ]
                    ]
                ],
            ],
        ];

        yield 'same base and entrypoint route settings' => [
            'config' => [
                'base_settings' => [
                    'start' => 't1',
                    'route-settings' => [[
                        'route' => ['t1', 't2'],
                        'exclude_columns' => ['c1', 'c2'],
                        'filter' => 'id > 1',
                        'columns' => ['c2', 'c3'],
                        'count' => 1,
                        'transformers' => ['c1' => ['t1']],
                        'tree' => 2,
                    ]]
                ],
                'entrypoints' => [[
                    'route-settings' => [[
                        'route' => ['t1', 't2'],
                        'exclude_columns' => ['c2', 'c3'],
                        'filter' => 'id > 2',
                        'columns' => ['c3', 'c4'],
                        'count' => 3,
                        'transformers' => ['c4' => ['t2']],
                        'tree' => 4,
                    ]]
                ]],
            ],
            'expected' => [
                [
                    'start' => 't1',
                    'route-settings' => [
                        [
                            'route' => ['t1', 't2'],
                            'exclude_columns' => ['c2', 'c3'],
                            'filter' => 'id > 2',
                            'columns' => ['c3', 'c4'],
                            'count' => 3,
                            'transformers' => ['c4' => ['t2']],
                            'tree' => 4,
                        ]
                    ]
                ],
            ],
        ];
    }
}
