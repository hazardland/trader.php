<?php

	/*
		Run with command:
		php bot.php config.sample
	*/

    include __DIR__.'/lib/debug/debug.php';
    include __DIR__.'/lib/console/console.php';
    include __DIR__.'/lib/termux/termux.php';

    include __DIR__.'/lib/trader/trader.php';
    include __DIR__.'/lib/trader/market.php';
    include __DIR__.'/lib/trader/strategy/simple.php';

	include __DIR__.'/lib/poloniex/poloniex.php';

	if (!is_array($argv) || !isset($argv[1]) || !file_exists(__DIR__.'/'.$argv[1].'.php'))
	{
		echo "[][ERROR] Market config not found\n";
		exit;
	}

	include __DIR__.'/'.$argv[1].'.php';

    $trader = new \trader\trader
    (
    	new poloniex
    	(
    		$config['api-key'],
    		$config['api-secret']
    	),
    	$config

    );

    debug ($trader);
	while (true)
	{
		$trader->trade();
        sleep (10);
	}
