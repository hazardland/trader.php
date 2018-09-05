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
    public $active = false;

    private $trader;

    public $name;
    public $pair;

    public $trade_fee;

    public $from_currency;
    public $from_balance = 0;
    public $from_balance_last = 0;

    public $to_currency;
    public $to_balance = 0;
    public $to_balance_last = 0;

    public $buy_rate;
    public $buy_pending;

    public $sell_rate;
    public $sell_pending;

    public $strategy;

    public function __construct (trader $trader, array $config)
    {
        //check config
        if (!is_array($config))
        {
            $this->log ('config','no config provided',\console\RED);
            return;
        }

        $this->trader = $trader;
        $this->name = $config['name'];

        //check pair something like USDT_DASH or USDT/DASH or DASHUSDT depends on exchange
        if (!isset($config['pair']))
        {
            $this->log ('config','pair not set',\console\RED);
            return;
        }
        $this->pair = $config['pair'];


        //market from currency, with what you buy altcoins like BTC, USDT
        if (!isset($config['from-currency']))
        {
            $this->log ('config','from-currency not set',\console\RED);
            return;
        }
        $this->from_currency = $config['from-currency'];

        //check for to currency, what you buy like XRP, DASH, SC
        if (!isset($config['to-currency']))
        {
            $this->log ('config','to-currency not set',\console\RED);
            return;
        }
        $this->to_currency = $config['to-currency'];

        //check first trade rate
        if (!isset($config['trade-fee']))
        {
            $this->log ('config','trade-fee not set',\console\RED);
            return;
        }
        $this->trade_fee = $config['trade-fee']/100;

        if ($this->min_total($this->from_currency)===false)
        {
            $this->log ('client','no min trade total defined for '.$this->from_currency.' defined',\console\RED);
            return;
        }

        if (!isset($config['strategy']))
        {
            $this->log ('config','strategy not set',\console\RED);
            return;
        }

        if (!class_exists($config['strategy']))
        {
            $this->log ('config', 'class '.$config['strategy'].' not exists', \console\RED);
            return;
        }

        if (!is_array($config[$config['strategy']::key()]))
        {
            $this->log ('config', $config['strategy']::key().' does not exist in $config', \console\RED);
            return;
        }

        $this->active = true;

        $this->load ();

        if ($this->active)
        {
            $this->strategy = new $config['strategy']($this->trader, $this, $config);
            $this->active = $this->strategy->active();
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
            $this->active = false;
            return false;
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
    public function buy_available ()
    {
        return $this->from_balance>=$this->min_total();
    }
    public function buy ()
    {
        $result = $this->client->buy ($this->pair, $this->buy_rate, $this->buy_amount());

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
    public function sell_available ()
    {
        return $this->sell_total()>=$this->min_total();
    }
    public function sell ()
    {
        $result = $this->client->sell ($this->pair(), $this->sell_rate, $this->to_balance);

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
    public function active()
    {
        return $this->active && $this->strategy->active();
    }
    public function fetch ()
    {
        return true;
    }
    public function trade ()
    {
        if (!$this->active())
        {
            if (!$this->active)
            {
                $this->log ('disabled','market is disabled',\console\RED);
            }
            if (!$this->strategy->active())
            {
                $this->log ('disabled','market strategy is disabled',\console\RED);
            }
            return;
        }

        if (!$this->fetch())
        {
            return;
        }

        if ($this->buy_available() && $this->sell_available())
        {
            $this->log ('trade','both buy and sell are avaiable what to do? inspect what is going',\console\RED);
            return;
        }

        $this->log ('refresh','refreshing market',\console\GREEN);

        if ($this->buy_available())
        {
            if ($this->strategy->buy_profitable())
            {

            }
        }
        else if ($this->sell_available())
        {
            if ($this->strategy->sell_profitable())
            {

            }
        }
    }
}
