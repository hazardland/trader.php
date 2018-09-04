<?php

interface client
{
    public function get_balances();

    /*
        GET FEES
        ---------------------------------
        return false - on general error
        return ['error'=>'error message'] - on api error
        return
        [
            'maker' => xx.xx,
            'taker' => xx.xx
        ]

    */
    public function get_fees();

    /*
        GET RATES
        ---------------------------------
        return false - on general error
        return ['error'=>'error message'] - on api error
        return
        [
            'CUR1' =>
            [
                'sell' => xx.xx,
                'buy' => xx.xx,
                'high' => xx.xx,
                'low' => xx.xx
            ],
        ]
    */
    public function get_rates();

    /*
        GET ORDERS
        ---------------------------------
        return false - on general error
        return ['error'=>'error message'] - on api error
        return
        [
            'CUR1_CUR2' =>
            [
                0 =>
                [
                    'id' => xx,
                    'rate' => xx.xx,
                    'amount' => xx.xx, // what amount you buy or for what amount you sold,
                    'total' => xx.xx // with what amount you buy or what amount you sold,
                    'fee' => xx.xx // trade fee included in amount (amount-fee=gained amount)
                ],
            ],
        ] - on success
    */
    public function get_orders();

    /*
        GET TRADES
        ---------------------------------
        id - buy or sell id
        ---------------------------------
        return false - on general error
        return ['error'=>'error message'] - on api error
        return
        [
            0 =>
            [
                'rate' => xx.xx,
                'amount' => xx.xx, // what amount you buy or for what amount you sold,
                'total' => xx.xx // with what amount you buy or what amount you sold,
                'fee' => xx.xx // trade fee included in amount (amount-fee=gained amount)
            ],
        ] - on success
    */
    public function get_trades($id);


    /*
        BUY
        ---------------------------------
        return false - on general error
        return ['error'=>'error message'] - on api error
        return
        [
            'id'=>buy_id,
            'trades' =>
            [
                0=>
                [
                    'rate' => 'xx.xxx', //buy rate
                    'amount' => x.xx, // what amount you buy
                    'total' => x.xx, // with what amount you buy
                    'date' => 'xx-xx-xx xx:xx:xx' //buy date
                ],
            ]
        ] - on success
    */
    public function buy ($pair, $rate, $amount);


    /*
        SELL
        ---------------------------------
        return false - on general error
        return ['error'=>'error message'] - on api error
        return
        [
            'id'=>sell_id,
            'trades' =>
            [
                0=>
                [
                    'rate' => xx.xx, // sell rate
                    'amount' => x.xx, // what amount you gained after sell
                    'total' => x.xx, // what you sell
                    'date' => 'xx-xx-xx xx:xx:xx' // sell date
                ],
            ]
        ] - on success
    */
    public function sell ($pair, $rate, $amount);
}
