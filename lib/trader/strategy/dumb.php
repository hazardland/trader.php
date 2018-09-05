<?php

namespace trader\strategy;

class dumb
{
    const name = 'dumb';
    private $trader;
    private $market;
    public function __construct ($trader, $market, $config)
    {
        $this->trader = $trader;
        $this->market = $market;

        //what percent you want to win on sell
        if (!isset($config[self::name.'-strategy-config']['buy-win-percent']))
        {
            $this->market->log ('config','buy-win-percent not set',\console\RED);
            exit;
        }
        $this->buy_win_percent = $config[self::name.'-strategy-config']['buy-win-percent']/100;


        //what percent you want to win on sell
        if (!isset($config[self::name.'-strategy-config']['sell-win-percent']))
        {
            $this->market->log ('config','sell-win-percent not set',\console\RED);
            exit;
        }
        $this->sell_win_percent = $config[self::name.'-strategy-config']['sell-win-percent']/100;

    }
}
