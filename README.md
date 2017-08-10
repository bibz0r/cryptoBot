What is CryptoBot?
=======

CryptoBot is a very simple automated PHP-based cryptocurrency trading bot for the Bittrex exchange.
It uses the wrapper class from Edson Medina (https://github.com/edsonmedina/bittrex)

The trading bot works on a conditional model (if this then that), and does NOT include any kind of trading analysis.
It simply sells after a desired procentual price increase and buys after the price falls again.

Requirements
=======
* 2-FA enabled on your bittrex account, so you can add API-access to your profile
* Any kind of webserver, where the script can be run (PHP is obviously required)

Usage 
=======

* Edit the bot.php
1) Change the values of the API_key and API_secret variables with the ones you've got from your bittrex account
2) Change the values of sell_percentage and buy_percentage. Those are per default "10", that means the bot will buy after the price fell for 10% and sell after it has 10% profit from last buy)

* Create a cronjob
Either create a cronjob with "crontab -e", and let the bot run every 1 minute or 5 minutes or
Use a free service that calls your script every X minutes


