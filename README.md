# PHPBot

At some point I decided I wanted to make a Telegram bot.
I saw the API and thought "*hey, I might as well make my own lib for this*"
This lib doesn't have any groundbreaking features, but it allowed me to develop my own bots and be able to edit the underlying code as I needed.

## Getting Started

```
require 'PHPBot.php';
$api = new PHPBot('12345678:ABCDEF123456890abcdef'); //replace with your token
$me = 98765432; //replace with your ID
$api->sendMessage($me, "Hello World!");
```

### Prerequisites

PHP \>= 7.0

### Installing

Download [PHPBot.php](https://raw.githubusercontent.com/Kyle2142/master/PHPBot.php) and place in your bot's folder

## Usage

Firstly, I highly suggest you set up a webhook, so you can use this amazingly simple code to process updates:
```
$content = file_get_contents('php://input');
$update = json_decode($content, true);
//do stuff with $update
```
For the most part, you can refer to Telegram's [BotAPI](https://core.telegram.org/bots/api) for method names and parameters.
Generally, you will use an array for the parameters:
```
$params = array('chat_id'=>511048636, 'from_chat_id'=>'@durov', 'message_id'=>79);
$api->forwardMessage($params);
```
However, a few popular methods have convenience functions.
Check the examples and documentation of the PHPBot class for details

## Contributing

If you want to contribute, please make sure you follow the current style and the changes do not break anything!

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* [Lonami](https://github.com/LonamiWebs) who inspired me to develop using Telegram
* [Telegram](https://telegram.org) for the chat platform with an open API

