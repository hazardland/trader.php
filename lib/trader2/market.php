<?php

namespace trader;

class market
{
    public $trader;

    public $first_trade_currency;
    public $first_trade_amount;
    public $first_trade_rate;

    public $from_currency;
    public $from_balance;
    public $from_balance_last;

    public $to_currency;
    public $to_balance;
    public $to_balance_last;

    public $buy_rate;
    public $buy_win_percent;

    public $sell_rate;
    public $sell_win_percent;

    public function __construct (trader $trader, array $config)
    {

        if ($config['first-trade-amount'] )
    }

    public function buy ()
    {

    }
}
