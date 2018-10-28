<?php
/* https://github.com/bibz0r/cryptoBot */

require __DIR__.'/bittrex/Client.php';

$API_key = 'YOUR_API_KEY';  // YOUR_API_KEY OVBIOUSLY NEEDS TO BE REPLACED
$API_secret = 'YOUR_API_SECRET'; // YOUR_API_SECRET NEEDS TO BE REPLACED ALSO, otherwise the bot won't work since it can't access your account!

$tradingMarket = 'BTC-NEO';
$tradingCurrency = substr($tradingMarket, strpos($tradingMarket, "-") + 1);

$API_Client = new Client ($API_key, $API_secret);
$API_Results =  $API_Client->getTicker ($tradingMarket);

$sell_percentage = '10'; // e.g sell after 10% price increase
$buy_percentage = '-10'; // e.g sell after 10% price fall


/* STOP EDITING AFTER THIS LINE IF YOU DON'T KNOW WHAT YOU'RE DOING! */

$get_object = $API_Client->getOrderHistory ($tradingMarket);
$getOrderHistory =  json_decode(json_encode($get_object),true);
$get_keys_buy = findKeys($getOrderHistory,'OrderType','==','LIMIT_BUY');
$last_buy_key = $get_keys_buy[0];


$oldPrice_buy = $getOrderHistory[$last_buy_key]['PricePerUnit'];
$priceNow = $API_Results->Ask;
$percentChange_sell = (1 - $oldPrice_buy / $priceNow) * 100;
$priceChange = number_format($percentChange_sell, 2);


/* Checking if the last ACTION in getOrderHistory was to BUY. If it was, then we check if the price increased and we made some profit, so we can sell */
if($getOrderHistory[0]['OrderType'] == 'LIMIT_BUY'){
	echo("Last action was to buy (bought ". number_format($getOrderHistory[0]['Quantity'],0,'.','')." $tradingCurrency for ".  number_format($getOrderHistory[0]['PricePerUnit'],8,'.','')." but the price is now at $priceNow) \n");
	shell_exec("logger \"cryptobot: Last action was to buy at ".number_format($getOrderHistory[0]['PricePerUnit'],8,'.','').", price is now at $priceNow, thats a change of $priceChange!\"");
	if($priceChange > $sell_percentage) {
        	echo("Since I've bought, the price increased for $priceChange! (I bought at $oldPrice_buy and the price is now $priceNow) \n");
		echo("I will try to sell now, since we've made profit \n");
		$getOpenOrders = $API_Client->getOpenOrders($tradingMarket);
		var_dump($getOpenOrders);
		if(empty($getOpenOrders)) {
			$getbalance=json_decode(json_encode($API_Client->getbalance($tradingCurrency)),true);
			echo("I have ".$getbalance['Available']." to sell! \n");
			// market, quantity, rate
			$API_Results = $API_Client->sellLimit($tradingMarket, $getbalance['Available'], $priceNow);
		} else {
                        echo("Seems like there is an open order!\n"); // later add check if there are more than 1 orders!
                        echo("Order has been placed at: ". $getOpenOrders[0]->Opened);
                        $opened = strtotime($getOpenOrders[0]->Opened);
                        $now = strtotime("now");

                        if((abs($now - $opened) / 60) > '2'){
                                echo(" which is more than 2 minutes ago! Will cancel the order now. \n");
                                $result_cancel = $API_Client->cancel($getOpenOrders[0]->OrderUuid);
                        }

		}
	}
}



/* Checking if the last ACTION in getOrderHistory was to SELL. If it was, then we check if the price fell. If it did, we're buying back in */
if($getOrderHistory[0]['OrderType'] == 'LIMIT_SELL'){ 
	echo("Last action was to sell.");
	echo("(sold ". number_format($getOrderHistory[0]['Quantity'],0,'.','')." $tradingCurrency for ".  number_format($getOrderHistory[0]['PricePerUnit'],8,'.','')." each, price is now at $priceNow, thats a change of $priceChange) \n");

	$get_keys_sell = findKeys($getOrderHistory,'OrderType','==','LIMIT_SELL');
	$last_sell_key = $get_keys_sell[0];


	$oldPrice_sell = $getOrderHistory[$last_sell_key]['PricePerUnit'];
	$priceNow = $API_Results->Ask;
	$percentChange_sell = (1 - $oldPrice_sell / $priceNow) * 100;
	$priceChange_sell = number_format($percentChange_sell, 2);

	shell_exec("logger \"cryptobot: Last action was to sell at ".number_format($getOrderHistory[0]['PricePerUnit'],8,'.','').", price is now at $priceNow, thats a change of $priceChange_sell!\"");

	echo("Waiting for price to fall...\n");
	if($priceChange < $buy_percentage){
		echo("Since I've sold, the price fell for $percentChange_sell (sold at $oldPrice_sell, price is now: $priceNow)\n\n");
		echo("Going to buy in now! \n");
		$getOpenOrders = $API_Client->getOpenOrders($tradingMarket);
		if(empty($getOpenOrders)) {
		        echo("No open orders, ready to set a buy order! \n");
			$getbalance=json_decode(json_encode($API_Client->getbalance('BTC')),true);
			echo("I have ".$getbalance['Available']." BTC to spend \n");
			$API_ClientuyAmount = ($getbalance['Available'] / $priceNow);
			echo("I can buy $API_ClientuyAmount $tradingCurrency for this \n");
			$setBuyOrder = json_decode(json_encode($API_Client->buyLimit($tradingMarket, $API_ClientuyAmount, $priceNow)));
			if(!(empty($setBuyOrder->uuid))){
				echo("\nLooking good, seems like the buyLimit order has been set! \n");
			}
		} else {
			echo("Seems like there is an open order!\n"); // later add check if there are more than 1 orders!
			echo("Order has been placed at: ". $getOpenOrders[0]->Opened);
			$opened = strtotime($getOpenOrders[0]->Opened);
			$now = strtotime("now");

			if((abs($now - $opened) / 60) > '2'){
				echo(" which is more than 2 minutes ago! Will cancel the order now. \n");
				$result_cancel = $API_Client->cancel($getOpenOrders[0]->OrderUuid);
			}

		}
	}
}

function findKeys($array,$field,$condition,$value) { 
foreach ($array as $key=>$info) { 
eval('if ($info[$field] '.$condition.' $value) { 
$matches[] = $key; 
}'); 
} 
return $matches; 
} 

?>
