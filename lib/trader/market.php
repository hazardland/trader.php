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
            'usdt_xrp' =>
            [
                'pair' => 'USDT_XRP',

                'from-currency' => 'USDT',
                'to-currency' => 'XRP',

                'start-currency' => 'USDT',
                'start-amount'  => '234',

                'trade-fee' => 0.2,

                'strategy' => '\trader\strategy\simple',

                'simple-strategy-config' =>
                [
                    'first-trade-rate' => '0.33100000',

                    'buy-win-percent' => 1,
                    'sell-win-percent' => 1
                ]
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

    public $start_currency;
    public $start_amount;

    public $from_currency;
    public $from_balance = 0;
    public $from_balance_first;
    public $from_balance_last;

    public $to_currency;
    public $to_balance = 0;
    public $to_balance_first;
    public $to_balance_last;

    public $buy_pending;
    public $sell_pending;

    public $buy_rate;
    public $sell_rate;
    public $high_rate;
    public $low_rate;

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

        //check first trade currency, you buy or sell with this amount first
        if (!isset($config['start-currency']))
        {
            $this->log ('config','start-currency not set',\console\RED);
            return;
        }
        $this->start_currency = $config['start-currency'];

        //check first trade amount
        if (!isset($config['start-amount']) || $config['start-amount']<=0)
        {
            $this->log ('config','start-amount not set',\console\RED);
            return;
        }
        $this->start_amount = $config['start-amount'];

        if ($this->start_currency==$this->from_currency)
        {
            $this->from_balance = self::number($this->start_amount);
            $this->from_balance_first = $this->from_balance;
            $this->from_balance_last = $this->from_balance;
        }
        else if ($this->start_currency==$this->to_currency)
        {
            $this->to_balance = self::number($this->start_amount);
            $this->to_balance_first = $this->to_balance;
            $this->to_balance_last = $this->to_balance;
        }
        else
        {
            $this->log ('config','unknown start-currency',\console\RED);
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

        $this->strategy = new $config['strategy']($this->trader, $this, $config);
        $this->active = $this->strategy->active();

        if ($this->to_balance_first===null)
        {
            $this->to_balance_first = $this->to_balance_last;
        }
        if ($this->from_balance_first===null)
        {
            $this->from_balance_first = $this->from_balance_last;
        }

        $this->active = true;

        $this->load ();


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
        $this->from_balance_first = $data['from-balance-first'];
        $this->from_balance_last = $data['from-balance-last'];
        $this->buy_pending = $data['buy-pending'];
        $this->to_balance = $data['to-balance'];
        $this->to_balance_first = $data['to-balance-first'];
        $this->to_balance_last = $data['to-balance-last'];
        $this->sell_pending = $data['sell-pending'];
        return true;
    }

    public function save ()
    {
        if (
        file_put_contents ($this->file(), json_encode(
            [
                'from-balance'=>$this->from_balance,
                'from-balance-first'=>$this->from_balance_first,
                'from-balance-last'=>$this->from_balance_last,
                'buy-pending'=>$this->buy_pending,
                'to-balance'=>$this->to_balance,
                'to-balance-first'=>$this->to_balance_first,
                'to-balance-last'=>$this->to_balance_last,
                'sell-pending'=>$this->sell_pending
            ],
            JSON_PRETTY_PRINT
        )))
        {
            $this->log ('SAVE','saving into '.$this->file(),\console\GREEN);
        }
        else
        {
            $this->log ('SAVE','failed saving into '.$this->file(),\console\RED);
        }
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
        $result = $this->trader->client->buy ($this->pair, $this->buy_rate, $this->buy_amount());

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
        $result = $this->trader->client->sell ($this->pair, $this->sell_rate, $this->to_balance);

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
        //debug ($this->trader->orders[$this->pair]);
        //$this->buy_pending = "129019584389";
        //$this->buy_pending = "128926488578";
        if ($this->buy_pending)
        {
            if
            (
                isset($this->trader->orders[$this->pair][$this->buy_pending]) &&
                is_array($this->trader->orders[$this->pair][$this->buy_pending]) &&
                count($this->trader->orders[$this->pair][$this->buy_pending])
            )
            {
                $orders = '';
                foreach ($this->trader->orders[$this->pair][$this->buy_pending] as $order)
                {
                    $orders .= ' '.$order['type'].' '.$order['amount'].' '.$this->to_currency.' rate '.$order['rate'].' '.$this->from_currency;
                }
                $this->log ('skip','pending orders:'.$orders,\console\YELLOW);
                return false;
            }
            else
            {
                //debug ($this->trader->client->get_trades()['USDT_DASH']);
                //exit;
                $result = $this->trader->client->get_trades ($this->buy_pending);
                //debug ($result);
                if (!is_array($result) || (is_array($result) && isset($result['error'])))
                {
                    $this->log ('error',isset($result['error'])?$result['error']:'error retrieving trades for order '.$this->buy_pending,\console\RED);
                    return false;
                }
                else
                {
                    $data = [];
                    $data['type'] = 'buy';
                    $data['amount'] = 0;
                    $data['total'] = 0;
                    $data['rate'] = 0;
                    foreach ($result as $trade)
                    {
                        $data['amount'] = self::number($data['amount']+$trade['amount'] * (1-$trade['fee']));
                        $data['total'] = self::number($data['total']+$trade['total']);
                        $data['rate'] = $trade['rate'];
                    }
                    $data['profit'] = self::number($data['amount']-$this->to_balance_last);
                    debug ($data);

                    $this->from_balance_last = $this->from_balance;
                    $this->from_balance = self::number ($this->from_balance-$data['total']);
                    $this->from_balance_last = self::number ($this->from_balance_last-$this->from_balance);
                    $this->to_balance = $data['amount'];
                    $this->buy_pending = null;
                    $this->save();

                    file_put_contents($this->trader->data_dir($this->name.'.trade.csv'),'"'.implode('","',$data).'"'."\n",FILE_APPEND);

                }
            }
        }

        //debug ($this->trader->orders[$this->pair]);
        //$this->sell_pending = "129019584389";
        //$this->sell_pending = "128926488578";
        if ($this->sell_pending)
        {
            if
            (
                isset($this->trader->orders[$this->pair][$this->sell_pending]) &&
                is_array($this->trader->orders[$this->pair][$this->sell_pending]) &&
                count($this->trader->orders[$this->pair][$this->sell_pending])
            )
            {
                $orders = '';
                foreach ($this->trader->orders[$this->pair][$this->sell_pending] as $order)
                {
                    $orders .= ' '.$order['type'].' '.$order['amount'].' '.$this->to_currency.' rate '.$order['rate'].' '.$this->from_currency;
                }
                $this->log ('skip','pending orders:'.$orders,\console\YELLOW);
                return false;
            }
            else
            {
                //debug ($this->trader->client->get_trades()['USDT_DASH']);
                //exit;
                $result = $this->trader->client->get_trades ($this->sell_pending);
                //debug ($result);
                if (!is_array($result) || (is_array($result) && isset($result['error'])))
                {
                    $this->log ('error',isset($result['error'])?$result['error']:'error retrieving trades for order '.$this->sell_pending,\console\RED);
                    return false;
                }
                else
                {
                    $data = [];
                    $data['type'] = 'sell';
                    $data['amount'] = 0;
                    $data['total'] = 0;
                    $data['rate'] = 0;
                    foreach ($result as $trade)
                    {
                        $data['amount'] = self::number($data['amount']+$trade['amount']);
                        $data['total'] = self::number($data['total']+$trade['total'] * (1-$trade['fee']));
                        $data['rate'] = $trade['rate'];
                    }
                    $data['profit'] = self::number($data['total']-$this->from_balance_last);
                    debug ($data);

                    $this->to_balance_last = $this->to_balance;
                    $this->to_balance = self::number ($this->to_balance-$data['amount']);
                    $this->to_balance_last = self::number ($this->to_balance_last-$this->to_balance);
                    $this->from_balance = $data['total'];
                    $this->sell_pending = null;
                    $this->save();

                    file_put_contents($this->trader->data_dir($this->name.'.trade.csv'),'"'.implode('","',$data).'"'."\n",FILE_APPEND);

                }
            }
        }

        if
        (
            !isset($this->trader->rates[$this->pair]) ||
            !isset($this->trader->rates[$this->pair]['buy']) ||
            !isset($this->trader->rates[$this->pair]['sell']) ||
            !isset($this->trader->rates[$this->pair]['high']) ||
            !isset($this->trader->rates[$this->pair]['low'])
        )
        {
            $this->log ('fetch','rates not provided',\console\RED);
            return false;
        }

        $this->buy_rate = $this->trader->rates[$this->pair]['buy'];
        $this->sell_rate = $this->trader->rates[$this->pair]['sell'];
        $this->high_rate = $this->trader->rates[$this->pair]['high'];
        $this->low_rate = $this->trader->rates[$this->pair]['low'];

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

        $this->log ('fetch','fetching market',\console\GREEN);

        if (!$this->fetch())
        {
            $this->log ('fetch','fetch failed',\console\RED);
            return;
        }

        if (!$this->buy_rate || !$this->sell_rate)
        {
            $this->log ('fetch','rates not set',\console\MAROON);
            return;
        }

        if ($this->buy_available() && $this->sell_available())
        {
            $this->log ('trade','both buy and sell are avaiable what to do? inspect what is going',\console\RED);
            return;
        }


        if ($this->buy_available())
        {
            if ($this->strategy->should_buy())
            {
                $this->strategy->buy_log (\console\GREEN);
                //$this->buy();
            }
            else
            {
                $this->strategy->buy_log (\console\BLUE);
            }
        }
        else if ($this->sell_available())
        {
            if ($this->strategy->should_sell())
            {
                $this->strategy->sell_log(\console\GREEN);
                $this->sell();
            }
            else
            {
                $this->strategy->sell_log(\console\PINK);
            }
        }
    }
}
