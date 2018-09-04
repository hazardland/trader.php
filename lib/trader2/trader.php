<?php

namespace trader;

class trader
{
    public $client;
    public $markets;
    public function __construct ($config)
    {
        if (is_array($config))
        {
            foreach ($config as $key => $value)
            {
                $this->markets[$key] = new market ($this,['name'=>$key]+$value);
            }
        }
    }
}
