<?php

namespace trader;

/*
    $config  =
    [
        'data-dir' => '/full/path/to/data/dir',
        'api-key' => '',
        'api-secret'=> '',
        'markets' =>
        [
            'my_market_1' =>
            [
                'pair' => 'USDT_XRP',

                'from-currency' => 'USDT',
                'to-currency' => 'XRP',

                'first-trade-currency' => 'USDT',
                'first-trade-amount'  => '10',
                'first-trade-rate' => '0.33100000',

                'trade-fee' => 0.2,
                'buy-win-percent' => 1,
                'sell-win-percent' => 1
            ]
        ]
    ]
*/

class market
{
    private $trader;

    public $name;
    public $pair;

    public $trade_fee;

    public $first_trade_currency;
    public $first_trade_amount;
    public $first_trade_rate;

    public $from_currency;
    public $from_balance = 0;
    public $from_balance_last = 0;
    public $buy_win_percent;

    public $to_currency;
    public $to_balance = 0;
    public $to_balance_last = 0;
    public $sell_win_percent;

    public $buy_rate;
    public $buy_pending;

    public $sell_rate;
    public $sell_pending;

    public function __construct (trader $trader, array $config)
    {
        //check config
        if (!is_array($config))
        {
            $this->log ('config','no config provided',\console\RED);
            exit;
        }

        $this->trader = $trader;
        $this->name = $config['name'];

        //check pair something like USDT_DASH or USDT/DASH or DASHUSDT depends on exchange
        if (!isset($config['pair']))
        {
            $this->log ('config','pair not set',\console\RED);
            exit;
        }
        $this->pair = $config['pair'];


        //market from currency, with what you buy altcoins like BTC, USDT
        if (!isset($config['from-currency']))
        {
            $this->log ('config','from-currency not set',\console\RED);
            exit;
        }
        $this->from_currency = $config['from-currency'];

        //check for to currency, what you buy like XRP, DASH, SC
        if (!isset($config['to-currency']))
        {
            $this->log ('config','to-currency not set',\console\RED);
            exit;
        }
        $this->to_currency = $config['to-currency'];


        //check first trade currency, you buy or sell with this amount first
        if (!isset($config['first-trade-currency']))
        {
            $this->log ('config','first-trade-currency not set',\console\RED);
            exit;
        }
        $this->first_trade_currency = $config['first-trade-currency'];

        //check first trade amount
        if (!isset($config['first-trade-amount']) || $config['first-trade-amount']<=0)
        {
            $this->log ('config','first-trade-amount not set',\console\RED);
            exit;
        }
        $this->first_trade_amount = $config['first-trade-amount'];

        //check first trade rate
        if (!isset($config['first-trade-rate']) || $config['first-trade-rate']<=0)
        {
            $this->log ('config','first-trade-rate not set',\console\RED);
            exit;
        }
        $this->first_trade_rate = $config['first-trade-rate'];

        //check first trade rate
        if (!isset($config['trade-fee']))
        {
            $this->log ('config','trade-fee not set',\console\RED);
            exit;
        }
        $this->trade_fee = $config['trade-fee']/100;

        //what percent you want to win on sell
        if (!isset($config['buy-win-percent']))
        {
            $this->log ('config','buy-win-percent not set',\console\RED);
            exit;
        }
        $this->buy_win_percent = $config['buy-win-percent']/100;


        //what percent you want to win on sell
        if (!isset($config['sell-win-percent']))
        {
            $this->log ('config','sell-win-percent not set',\console\RED);
            exit;
        }
        $this->sell_win_percent = $config['sell-win-percent']/100;

        if ($this->min_total($this->from_currency)===false)
        {
            $this->log ('client','no min trade total defined for '.$this->from_currency.' defined',\console\RED);
            exit;
        }

        /*
            $this->from_balance
            $this->from_balance_last
            $this->to_balance
            $this->to_balance_last
        */
        $this->load ();

        if ($this->from_balance===0 && $this->to_balance===0)
        {
            if ($this->first_trade_currency==$this->from_currency)
            {
                $this->from_balance = self::number($this->first_trade_amount);
                $this->to_balance_last = self::number(($this->from_balance/$this->first_trade_rate)*(1-$this->buy_win_percent-$this->trade_fee));
            }
            else if ($this->first_trade_currency==$this->to_currency)
            {

                    $this->to_balance = self::number($this->first_trade_amount);
                    $this->from_balance_last = self::number(($this->to_balance*$this->first_trade_rate)*(1-$this->sell_win_percent-$this->trade_fee));
            }
            else
            {
                $this->log ('config','unknown first trade currency',\console\RED);
                exit;
            }
        }
    }

    public static function number ($amount, $decimals=8)
    {
        return number_format ($amount, $decimals, '.', '');
    }

    public function file ($type='')
    {
        return $this->trader->data_dir ($this->name.($type?'.'.$type:'').'.json');
    }

    public function load ()
    {
        if (!file_exists($this->file()))
        {
            $this->log ('load',$this->file().' does not exists',\console\YELLOW);
            return false;
        }
        $data = json_decode(file_get_contents($this->file()),true);
        if (
            !isset($data['from-balance']) ||
            !isset($data['from-balance-last']) ||
            !isset($data['to-balance']) ||
            !isset($data['to-balance-last'])
        )
        {
            $this->log ('load',$this->file().' file lacks data',\console\RED);
            exit;
        }
        $this->from_balance = $data['from-balance'];
        $this->from_balance_last = $data['from-balance-last'];
        $this->buy_pending = $data['buy-pending'];
        $this->to_balance = $data['to-balance'];
        $this->to_balance_last = $data['to-balance-last'];
        $this->sell_pending = $data['sell-pending'];
        return true;
    }

    public function save ()
    {
        $this->log ('SAVE','saving into '.$this->file(),\console\GREEN);
        file_put_contents ($this->file(), json_encode(
            [
                'from-balance'=>$this->from_balance,
                'from-balance-last'=>$this->from_balance_last,
                'buy-pending'=>$this->buy_pending,
                'to-balance'=>$this->to_balance,
                'to-balance-last'=>$this->to_balance_last,
                'sell-pending'=>$this->sell_pending
            ],
            JSON_PRETTY_PRINT
        ));
    }

    public function log ($title, $message, $color=null)
    {
        $this->trader->log ($title, $message, $color, $this);
    }

    public function min_total ()
    {
        return $this->trader->client->min($this->from_currency);
    }

    public function max_total ()
    {
        return $this->trader->client->max($this->from_currency);
    }

    public function buy_amount ($fee=false)
    {
        if (!$fee)
        {
            return self::number($this->from_balance/$this->buy_rate);
        }
        return self::number($this->buy_amount()*(1-$this->trade_fee));
    }
    public function buy ()
    {
        if ($this->from_balance>=$this->min_total())
        {
            $result = $this->client->buy ($this->pair, $this->buy_rate, $this->buy_amount());
        }
        else
        {
            $result = null;
            $this->log ("buy", "Not buying total mast be at least ".$this->min_total(), \console\RED);
        }

        if (is_array($result) && !isset($result['error']))
        {
            $total = 0;
            $amount = 0;
            $rate = 0;
            foreach ($result['trades'] as $trade)
            {
                $total += $trade['total'];
                $amount += $trade['amount'];
                $rate = $trade['rate'];
            }
            $this->log
            (
                'buy',
                "\nBuying ".self::number($amount)." ".$this->to_currency." with ".self::number($total)." ".$this->from_currency.
                "\n1 ".$this->to_currency." = ".self::number($rate)." ".$this->from_currency,
                \console\YELLOW
            );
            $this->buy_pending = $result['id'];
            $this->save ();
            \termux\notification
            (
                "BUY ".self::number($amount)." ".$this->to_currency,
                "WITH ".self::number($total)." ".$this->from_currency." AT ".self::number($rate)." ".$this->from_currency,
                "FF00FF"
            );
        }
        else if ($result!==null)
        {
            if (!is_array($result))
            {
                $this->log ('buy '.$this->to_currency, 'buy failed', \console\RED);
            }
            else
            {
                $this->log ('buy '.$this->to_currency, 'buy failed: '.$result['error'], \console\RED);
            }

        }
    }

    public function sell_total ($fee=false)
    {
        if (!$fee)
        {
            return self::number($this->to_balance*$this->sell_rate);
        }
        return self::number($this->sell_total()*(1-$this->trade_fee));
    }
    public function sell ()
    {
        if ($this->sell_total()>=$this->min_total())
        {
            $result = $this->client->sell ($this->pair(), $this->sell_rate, $this->to_balance);
        }
        else
        {
            $result = null;
            $this->log ('sell', 'not selling total mast be at least '.$this->min_total(), \console\RED);
        }

        if (is_array($result) && !isset($result['error']))
        {
            $total = 0;
            $amount = 0;
            $rate = 0;
            foreach ($result['trades'] as $trade)
            {
                $total += $trade['total'];
                $amount += $trade['amount'];
                $rate = $trade['rate'];
            }
            $this->log
            (
                'sell',
                "\nSelling ".self::number($amount)." ".$this->to_currency." for ".self::number($total)." ".$this->from_currency.
                "\n1 ".$this->to_currency." = ".self::number($rate)." ".$this->from_currency,
                \console\YELLOW
            );
            $this->sell_pending = $result['id'];
            $this->save ();
            \termux\notification
            (
                "SELL ".self::number($amount)." ".$this->to_currency,
                "FOR ".self::number($total)." ".$this->from_currency." AT ".self::number($rate)." ".$this->from_currency,
                "FF00FF"
            );
        }
        else if ($result!==null)
        {
            if (!is_array($result))
            {
                $this->log ('sell '.$this->to_currency, 'sell failed', \console\RED);
            }
            else
            {
                $this->log ('sell '.$this->to_currency, 'sell failed: '.$result['error'], \console\RED);
            }
        }
    }
}
