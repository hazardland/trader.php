<?php

    date_default_timezone_set ('Asia/Tbilisi');

    $config =  [
            //will be used for file prefix
            //same as profile folder name
            'profile' => 'sample',
            //you buy XRP or you sell XRP with USD
            'pair' => 'USDT_XRP',
            //bot will wait until rate changes till it will be possible to buy 101 XRP with your whole USDT balance
            'first-trade-currency' => 'XRP',
            'first-trade-amount' => 100,
            'win-percent'=> 1,
            'poloniex-key' => '{poloniex-key}',
            'poloniex-secret' => '{poloniex-secret},
        ];
