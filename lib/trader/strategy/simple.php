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
    const UP = 1;
    const DOWN = -1;

    private $trader;
    private $market;

    public $first_trade_rate;
    public $first_trade_margin;

    public $buy_win_percent;
    public $sell_win_percent;

    public $rate_trend_round = 4;

    public $sell_rate_trend_last;
    public $sell_rate_trend_changed = false;
    public $sell_rate_trend;

    public $buy_rate_trend_last;
    public $buy_rate_trend_changed = false;
    public $buy_rate_trend;


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

        if (isset($config[self::key()]['first-trade-margin']))
        {
            $this->first_trade_margin = $config[self::key()]['first-trade-margin'];
        }

        //check first trade rate
        if ((!isset($config[self::key()]['first-trade-rate']) || $config[self::key()]['first-trade-rate']<=0) && $this->first_trade_margin===null)
        {
            $this->market->log ('config','first-trade-rate or first-trade-margin is not set',\console\RED);
            return;
        }
        if (isset($config[self::key()]['first-trade-rate']))
        {
            $this->first_trade_rate = $config[self::key()]['first-trade-rate'];
        }

        if ($this->first_trade_rate===null && $this->first_trade_margin!==null && !$this->market->fetch())
        {
            $this->market->log ('config','market fetch failded for first-trade-margin',\console\RED);
            return;
        }

        if ($this->market->start_currency==$this->market->from_currency)
        {
            if ($this->first_trade_rate===null)
            {
                $this->first_trade_rate = self::number($this->market->low_rate+(($this->market->high_rate-$this->market->low_rate)/100)*$this->first_trade_margin);
            }
            $this->market->to_balance_last = self::number(($this->market->from_balance/$this->first_trade_rate)*(1-$this->buy_win_percent-$this->market->trade_fee));

        }
        else if ($this->market->start_currency==$this->market->to_currency)
        {
            if ($this->first_trade_rate===null)
            {
                $this->first_trade_rate = self::number($this->market->low_rate+(($this->market->high_rate-$this->market->low_rate)/100)*(100-$this->first_trade_margin));
            }
            $this->market->from_balance_last = self::number(($this->market->to_balance*$this->first_trade_rate)*(1-$this->sell_win_percent-$this->market->trade_fee));
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
    public function buy_now ()
    {
        if ($this->buy_profitable() && $this->buy_rate_trend!==self::DOWN)
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
    public function sell_now ()
    {
        if ($this->sell_profitable() && $this->sell_rate_trend!==self::UP)
        {
            return true;
        }
        return false;
    }
    public function buy_rate_trend_save ()
    {
        $buy_rate = self::number ($this->market->buy_rate, $this->rate_trend_round);
        if ($this->buy_rate_trend_last===null)
        {
            $this->buy_rate_trend_last = $buy_rate;
        }
        $this->buy_rate_trend_changed = false;
        if ($this->buy_rate_trend_last!=$buy_rate)
        {
            if ($buy_rate>$this->buy_rate_trend_last)
            {
                $this->buy_rate_trend = self::UP;
            }
            else
            {
                $this->buy_rate_trend = self::DOWN;
            }
            $this->buy_rate_trend_changed = true;
            $this->buy_rate_trend_last = $buy_rate;
        }
    }
    public function buy_rate_trend ()
    {
        if ($this->buy_rate_trend==self::UP)
        {
            return "UP";
        }
        else if ($this->buy_rate_trend==self::DOWN)
        {
            return "DOWN";
        }
        return "";
    }
    public function sell_rate_trend_save ()
    {
        $sell_rate = self::number ($this->market->sell_rate, $this->rate_trend_round);
        if ($this->sell_rate_trend_last===null)
        {
            $this->sell_rate_trend_last = $sell_rate;
        }
        $this->sell_rate_trend_changed = false;
        if ($this->sell_rate_trend_last!=$sell_rate)
        {
            if ($sell_rate>$this->sell_rate_trend_last)
            {
                $this->sell_rate_trend = self::UP;
            }
            else
            {
                $this->sell_rate_trend = self::DOWN;
            }
            $this->sell_rate_trend_changed = true;
            $this->sell_rate_trend_last = $sell_rate;
        }
    }
    public function sell_rate_trend ()
    {
        if ($this->sell_rate_trend==self::UP)
        {
            return "UP";
        }
        else if ($this->sell_rate_trend==self::DOWN)
        {
            return "DOWN";
        }
        return "";
    }
    public function buy_rate_next ()
    {
        return self::number($this->market->from_balance/($this->market->to_balance_last*(1+$this->buy_win_percent+$this->market->trade_fee)));
    }
    public function sell_rate_next ()
    {
        return self::number (($this->market->from_balance_last*(1+$this->sell_win_percent+$this->market->trade_fee))/$this->market->to_balance);
    }
    public function active()
    {
        return $this->active;
    }
    public function fetch ()
    {
        $this->sell_rate_trend_save();
        $this->buy_rate_trend_save();
    }
    public function buy_log ($color)
    {
echo
\console\color(
"[BUY ".$this->market->to_currency."] ".$this->trader->time()."\n".
"Buy profitable by ",$color).\console\color(self::number($this->market->buy_amount(true)-$this->market->to_balance_last),\console\GRAY)." ".\console\color($this->market->to_currency,$color)." ".\console\color($this->buy_rate_trend(),$this->buy_rate_trend_changed?\console\WHITE:$color).\console\color("\n".
"1 ".$this->market->to_currency.' = '.$this->market->buy_rate." >> ".$this->buy_rate_next()." ".$this->market->from_currency."\n".
"Rate needs to change by ", $color).\console\color(self::number($this->buy_rate_next()-$this->market->buy_rate),\console\RED).\console\color(" ".$this->market->from_currency."\n".
"Balance ".$this->market->from_balance." ".$this->market->from_currency."\n".
"Goal ".$this->market->buy_amount(true)." / ".(self::number($this->market->to_balance_last*(1+$this->buy_win_percent))+0)." ".$this->market->to_currency."\n".
"Next min profit ".(self::number($this->market->to_balance_last*$this->buy_win_percent)+0)." ".$this->market->to_currency." ~".($this->buy_win_percent*100)."%\n".
"Total profited ".self::number($this->market->from_balance-$this->market->from_balance_first)." ".$this->market->from_currency,$color)."\n";

    echo \console\progress (
            str_pad("B".$this->buy_rate_next(),15)." ".str_pad("C".$this->market->buy_rate,15,' ',STR_PAD_LEFT)." ".str_pad("H".$this->market->high_rate,15,' ',STR_PAD_LEFT),
            $this->buy_rate_next(), //this is what rate we need to buy
            $this->market->buy_rate, //this is current rate
            $this->market->high_rate
        )."\n";

    }
    public function sell_log ($color)
    {
echo
\console\color(
"[SELL ".$this->market->to_currency."] ".$this->trader->time()."\n".
"Sell profitable by ",$color).\console\color(self::number($this->market->sell_total(true)-$this->market->from_balance_last),\console\GRAY)." ".\console\color($this->market->from_currency,$color)." ".\console\color($this->sell_rate_trend(),$this->sell_rate_trend_changed?\console\WHITE:$color).\console\color("\n".
"1 ".$this->market->to_currency.' = '.$this->market->sell_rate." >> ".$this->sell_rate_next()." ".$this->market->from_currency."\n".
"Rate needs to change by ", $color).\console\color(self::number($this->sell_rate_next()-$this->market->sell_rate),\console\RED).\console\color(" ".$this->market->from_currency."\n".
"Balance ".$this->market->to_balance." ".$this->market->to_currency."\n".
"Goal ".$this->market->sell_total(true)." / ".(self::number($this->market->from_balance_last*(1+$this->sell_win_percent))+0)." ".$this->market->from_currency."\n".
"Next min profit ".(self::number($this->market->from_balance_last*$this->sell_win_percent)+0)." ".$this->market->from_currency." ~".($this->sell_win_percent*100)."%\n".
"Total profited ".self::number($this->market->to_balance-$this->market->to_balance_first)." ".$this->market->to_currency,$color)."\n";

    echo \console\progress (
            str_pad("L".$this->market->low_rate,15)." ".str_pad("C".$this->market->sell_rate,15,' ',STR_PAD_LEFT)." ".str_pad("S".$this->sell_rate_next(),15,' ',STR_PAD_LEFT),
            $this->market->low_rate,
            $this->market->sell_rate, //this is current rate
            $this->sell_rate_next() //this is what rate we need to sell
        )."\n";

    }
}
