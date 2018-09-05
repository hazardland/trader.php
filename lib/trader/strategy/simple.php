<?php

namespace trader\strategy;

/*
    todo:
    . think on if should have sell and buy strategy separate maybe not because of coordination is needed between them ?
    . finish buy pending
    . finish sell pending
    . trader:fetch
    . market:fetch
    . trader.trade
    . market.trade
*/

class simple
{
    public $active = false;
    const name = 'simple';
    private $trader;
    private $market;

    public $first_trade_currency;
    public $first_trade_amount;
    public $first_trade_rate;

    public $buy_win_percent;
    public $sell_win_percent;

    public function __construct ($trader, $market, $config)
    {
        $this->trader = $trader;
        $this->market = $market;

        //what percent you want to win on sell
        if (!isset($config[self::key()]['buy-win-percent']))
        {
            $this->market->log ('config','buy-win-percent not set',\console\RED);
            return;
        }
        $this->buy_win_percent = $config[self::key()]['buy-win-percent']/100;


        //what percent you want to win on sell
        if (!isset($config[self::key()]['sell-win-percent']))
        {
            $this->market->log ('config','sell-win-percent not set',\console\RED);
            return;
        }
        $this->sell_win_percent = $config[self::key()]['sell-win-percent']/100;

        //check first trade currency, you buy or sell with this amount first
        if (!isset($config[self::key()]['first-trade-currency']))
        {
            $this->market->log ('config','first-trade-currency not set',\console\RED);
            return;
        }
        $this->first_trade_currency = $config[self::key()]['first-trade-currency'];

        //check first trade amount
        if (!isset($config[self::key()]['first-trade-amount']) || $config[self::key()]['first-trade-amount']<=0)
        {
            $this->market->log ('config','first-trade-amount not set',\console\RED);
            return;
        }
        $this->first_trade_amount = $config[self::key()]['first-trade-amount'];

        //check first trade rate
        if (!isset($config[self::key()]['first-trade-rate']) || $config[self::key()]['first-trade-rate']<=0)
        {
            $this->market->log ('config','first-trade-rate not set',\console\RED);
            return;
        }
        $this->first_trade_rate = $config[self::key()]['first-trade-rate'];

        if ($this->market->from_balance===0 && $this->market->to_balance===0)
        {
            if ($this->first_trade_currency==$this->market->from_currency)
            {
                $this->market->from_balance = self::number($this->first_trade_amount);
                $this->market->to_balance_last = self::number(($this->market->from_balance/$this->first_trade_rate)*(1-$this->buy_win_percent-$this->market->trade_fee));
            }
            else if ($this->first_trade_currency==$this->market->to_currency)
            {

                    $this->market->to_balance = self::number($this->first_trade_amount);
                    $this->market->from_balance_last = self::number(($this->market->to_balance*$this->first_trade_rate)*(1-$this->sell_win_percent-$this->trade_fee));
            }
            else
            {
                $this->market->log ('config','unknown first trade currency',\console\RED);
                return;
            }
        }
        $this->active = true;
    }
    public static function key ()
    {
        return self::name.'-strategy-config';
    }
    public static function number ($amount, $decimals=8)
    {
        return number_format ($amount, $decimals, '.', '');
    }
    public function buy_profitable ()
    {
        if ($this->market->buy_amount(true)>=($this->market->to_balance_last*(1+$this->buy_win_percent)))
        {
            return true;
        }
        return false;
    }
    public function sell_profitable()
    {
        if ($this->market->sell_total(true)>=($this->market->from_balance_last*(1+$this->sell_win_percent)))
        {
            return true;
        }
        return false;
    }
    public function active()
    {
        return $this->active;
    }
}
