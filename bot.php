<?php

	include __DIR__.'/lib/poloniex/poloniex.php';
	include __DIR__.'/lib/debug/debug.php';
	include __DIR__.'/lib/console/console.php';
	include __DIR__.'/lib/termux/termux.php';

	include __DIR__.'/lib/poloniex/market.php';

	//include __DIR__.'/config.php'; # Uncomment this line
	include __DIR__.'/data/usdt_xrp.php'; # Remove this line

	market::$data_dir = __DIR__.'/data';

    $market = new market ($config);

	while (true)
	{
		$market->trade();
		sleep (10);
	}
