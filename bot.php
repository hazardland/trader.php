<?php

	include './lib/poloniex/poloniex.php';
	include './lib/debug/debug.php';
	include './lib/console/console.php';
	include './lib/termux/termux.php';
	include './lib/poloniex/trader.php';

	include './profile/biohazard/usdt_xrp.php';

    $trader = new trader ($config);

	while (true)
	{
		$trader->trade();
		sleep (10);
	}

