Clone with command
```
git clone --recursive https://github.com/hazardland/trader.php.git ./trader
```
Checkout all submodules to master branch with command inside ./trader dir
```
git submodule foreach --recursive git checkout master
```
Runing on Android with Termux

<div>
<img src="./doc/images/xrp_sell.png" width="400" style='float:left'/>
<img src="./doc/images/xrp_buy.png" width="400" style='float:left'/>
</div>

Usage:

1. Create your profile dir inside ./profile
2. Create a config file based on sample located in ./profile/sample/usdt_xrp.php inside your profile dir
3. In bot php include your config file before creating trader object
4. Run php ./bot.php

Config directives:

*profile* - Your profile name, same as your pofile dir under ./profile folder. Working files {currency}.first and {currency}.last will be stored in your profile folder also trade.log. You can have as many currency pair configs in one folder as you wish they do not come in conflict with each other.

*pair* - Your poloniex trading pair like USTD_XRP or BTC_ETC

*win-percent* - Minumum much percent you want to win on your next trade. For example if you bought 100 XRP with 10 USDT if you set win-percent to 1% trader will wait until it can afford 10.01 USDT + trading fees.
