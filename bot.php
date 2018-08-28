<?php

	/*
		Run with command:
		php bot.php my_config
	*/

	include __DIR__.'/lib/poloniex/poloniex.php';
	include __DIR__.'/lib/debug/debug.php';
	include __DIR__.'/lib/console/console.php';
	include __DIR__.'/lib/termux/termux.php';

	include __DIR__.'/lib/poloniex/market.php';

	if (!is_array($argv) || !isset($argv[1]) || !file_exists(__DIR__.'/'.$argv[1].'.php'))
	{
		echo "[ERROR] Market config not found\n";
		exit;
	}

	include __DIR__.'/'.$argv[1].'.php';

	market::$data_dir = __DIR__.'/data';

    $market = new market ($config);

	while (true)
	{
		$market->trade();
		sleep (10);
	}
