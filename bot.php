<?php

	include './lib/poloniex/poloniex.php';
	include './lib/debug/debug.php';
	include './lib/console/console.php';
	include './lib/termux/termux.php';

	include './lib/poloniex/market.php';

	//include './config.php'; # Uncomment this line
	include './data/usdt_xrp.php'; # Remove this line

    $market = new market ($config);

	while (true)
	{
		$market->trade();
		sleep (10);
	}
