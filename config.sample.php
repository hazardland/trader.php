<?php

    $config  =
    [
        'data-dir'=>dirname(__DIR__).'/data',
        'api-key' => '',
        'api-secret' => '',
        'markets' =>
        [
            'usdt_doge' =>
            [
                'pair' => 'USDT_DOGE',

                'from-currency' => 'USDT',
                'to-currency' => 'DOGE',

                'start-currency' => 'DOGE',
                'start-amount'  => '5117.08568500',

                'trade-fee' => 0.2,

                'strategy' => '\trader\strategy\simple',

                'simple-strategy-config' =>
                [
                    'first-trade-rate' => '0.0057',

                    'buy-win-percent' => 1,
                    'sell-win-percent' => 1
                ]
            ],
            'usdt_xrp' =>
            [
                'pair' => 'USDT_XRP',

                'from-currency' => 'USDT',
                'to-currency' => 'XRP',

                'start-currency' => 'USDT',
                'start-amount'  => '29.13252102',

                'trade-fee' => 0.2,

                'strategy' => '\trader\strategy\simple',

                'simple-strategy-config' =>
                [
                    'first-trade-rate' => '0.27',

                    'buy-win-percent' => 1,
                    'sell-win-percent' => 1
                ]
            ],
            'usdt_dash' =>
            [
                'pair' => 'USDT_DASH',

                'from-currency' => 'USDT',
                'to-currency' => 'DASH',

                'start-currency' => 'DASH',
                'start-amount'  => '0.999',

                'trade-fee' => 0.2,

                'strategy' => '\trader\strategy\simple',

                'simple-strategy-config' =>
                [
                    'first-trade-rate' => '198.2',

                    'buy-win-percent' => 1,
                    'sell-win-percent' => 1
                ]
            ]
        ]
    ];
