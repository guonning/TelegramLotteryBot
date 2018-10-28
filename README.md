# AzukiLotteryBot
超级菜的 Telegram lottery bot developed for small-scale use.<br>
~~随缘项目，就算有bug，我懒一样不会修，嗯。~~

## Deploy
1. Deploy the web server and PHP (recommend PHP 7.2)
2. Download ZIP or Clone this repository.
3. Import LotteryBot.sql into your MySQL database
4. Copy config.php.example to config.php and follow the prompts to fill in the relevant information.
5. Set your webhook address at api.telegram.org like https://example.com/LotteryBot/webhook.php?key=YOUR_KEY_IN_CONFIG
6. Add rewrite rules: (Take Nginx as an example, Modify as needed)
```
rewrite /LotteryBot/details /LotteryBot/details.php last;
```

## Usage
/new - Start a new lottery<br>
/my - Manage your lottery<br>
/cancel - Cancel creating a lottery session<br>

## License
MIT
