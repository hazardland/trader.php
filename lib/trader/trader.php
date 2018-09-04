<?php

namespace trader;

class trader
{
    public $client;
    public $data_dir;
    public $markets;
    public function __construct ($client, $config)
    {
        $this->client = $client;
        if (!is_array($config))
        {
            $this->log ('config','config not provided',\console\RED);
            exit;
        }
        if (!is_array($config['markets']))
        {
            $this->log ('config','markets not configured',\console\RED);
            exit;
        }
        if (!isset($config['data-dir']))
        {
            $this->log ('config','data dir not set',\console\RED);
            exit;
        }
        $this->data_dir = $config['data-dir'];
        if (!is_dir($this->data_dir()))
        {
            mkdir($this->data_dir(), 0777, true);
        }
        if (!is_dir($this->data_dir('log')))
        {
            mkdir($this->data_dir('log'), 0777, true);
        }
        foreach ($config['markets'] as $key => $value)
        {
            $this->markets[$key] = new \trader\market ($this,['name'=>$key]+$value);
        }
    }
    public function time ()
    {
        return @date("Y-m-d H:i:s");
    }
    public function data_dir ($path='')
    {
        if ($path)
        {
            $path = '/'.$path;
        }
        return $this->data_dir.$path;
    }
    public function log ($title, $message, $color=null, $market=null)
    {
        $message = (is_object($market)?'['.strtoupper($market->name).']':'[]').'['.strtoupper($title).'] '.$this->time()." ".$message;
        if ($color!==null)
        {
            echo \console\color($message, $color)."\n";
        }
        else
        {
            echo $message."\n";
        }
    }
}
