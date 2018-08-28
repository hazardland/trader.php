<?php

    /*
        Define your timezone
    */
    date_default_timezone_set ('Asia/Tbilisi');

    /*
        Warning 1: This values ar just samples! Modify with tour currencies
        Warning 2: Bot trades with full balances (Like in case if you want to buy XRP with USDT balance bot will use all USDT amount)
    */

    $config =  [
            'poloniex-key' => '',
            'poloniex-secret' => '',

            /*
                Valid poloniex market pair like USDT_XRP
            */
            'pair' => 'USDT_XRP',

            /*
                Float or integer value
                1% is recomended
            */
            'buy-win-percent'=> 1,
            'sell-win-percent'=> 1,

            /*
                If you have on balance 100 XRP wich you bought with 10 USD
                And your win-percent=1
                Specify:
                    'first-trade-currency' => 'USDT',
                    'first-trade-amount' => 10,
                So bot will asume that it should buy at least 10 USDT + 0.01 USDT (1%)
            */
            'first-trade-currency' => 'USDT',
            'first-trade-amount' => 10,
        ];
