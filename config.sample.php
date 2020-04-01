<?php

    $config  =
    [
        'data-dir'=>dirname(__DIR__).'/data',
        'api-key' => '',
        'api-secret' => '',
        'markets' =>
        [
            'eur_usd' =>
            [
                'pair' => 'EURUSD',

                'from-currency' => 'EUR',
                'to-currency' => 'USD',

                'start-currency' => 'USD',
                'start-amount'  => '1.11000',

                'trade-fee' => 0.2,

                'strategy' => '\trader\strategy\simple',

                'simple-strategy-config' =>
                [
                    'first-trade-rate' => '0.0060',

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
