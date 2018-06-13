# PHPBot

At some point I decided I wanted to make a Telegram bot.
I saw the API and thought "*hey, I might as well make my own lib for this*"
This lib doesn't have any groundbreaking features, but it allowed me to develop my own bots and be able to edit the underlying code as I needed.

## Getting Started

```php
require 'PHPBot.php';
$bot = new kyle2142\PHPBot('12345678:ABCDEF123456890abcdef'); //replace with your token
$me = 98765432; //replace with your ID
$bot->sendMessage($me, "Hello World!");
echo print_r($bot->api->getMe(), true);
```

### Prerequisites

* PHP \>= 7.0
* cURL extension correctly installed and enabled

### Installing

* composer.json:
```json
"require": {
    "kyle2142/PHPBot"
}
```
* `composer require kyle2142/PHPBot`
* Download [PHPBot.php](https://raw.githubusercontent.com/Kyle2142/PHPBot/master/PHPBot.php) and `require` it

## Usage

### Receiving updates

Firstly, I highly suggest you set up a webhook (requires SSL), so you can use this amazingly simple code to process updates:

```php
$content = file_get_contents('php://input');
$update = json_decode($content, true);
//do stuff with $update
```

### Calling methods

This library contains some convenience functions, as well as access to the normal botAPI functions.

For the most part, you can refer to Telegram's [BotAPI](https://core.telegram.org/bots/api) for method names and parameters.
```php
$params = array('chat_id'=>511048636, 'from_chat_id'=>'@durov', 'message_id'=>79);
$bot->api->forwardMessage($params); //direct api method

$bot->editMessage(/*chat id*/ 511048646, /*msg id*/ 21, "New text!");
```
If you really want to, you can use PHP's `...` operator with convenience functions to unpack arrays:
```php
$params = [511048646, 21, "New text!"];
$bot->editMessage(...$params);
```
Check the examples and documentation of the PHPBot class for details

### Return values

<details><summary>While calling any raw API method or convenience function, you will get a result in the form of an object:</summary><p>
    
```php
php > var_dump($bot->editMessage(343859930, 172, "New text!"));

object(stdClass)#3 (2) {
  ["ok"]=>
  bool(true)
  ["result"]=>
  object(stdClass)#4 (6) {
    ["message_id"]=>
    int(172)
    ["from"]=>
    object(stdClass)#5 (4) {
      ["id"]=>
      int(511048636)
      ["is_bot"]=>
      bool(true)
      ["first_name"]=>
      string(15) "Kyle's test bot"
      ["username"]=>
      string(14) "kyle_s_testbot"
    }
    ["chat"]=>
    object(stdClass)#6 (4) {
      ["id"]=>
      int(343859930)
      ["first_name"]=>
      string(4) "Kyle"
      ["username"]=>
      string(6) "Kyle_S"
      ["type"]=>
      string(7) "private"
    }
    ["date"]=>
    int(1528881693)
    ["edit_date"]=>
    int(1528881742)
    ["text"]=>
    string(9) "New text!"
  }
}
```
</p></details>
This means you can access return values as such:

```php
$result = $bot->sendmessage('@mychannel', "New stuff at example.com!");

$params = ['chat_id'=>343859930, 'from_chat_id' => $result->chat->id, 'message_id' => $result->message_id];
$bot->api->forwardMessage($params); //put params as variable due to line length
```

In the case of an error, you will receive the whole reply from Telegram:
* ok = `bool`:  Always `false`
* error_code = `int`:   HTTP error code, like `400` or `404`
* description = `string`:   Small description of the error

## Have Questions?
Please do not open an issue for small questions etc, its there for *issues*

* Telegram: [@Kyle_S](https://t.me/kyle_s) (preferred)
* [Email](mailto:kyle-2142@outlook.com) (not preferred)

## Contributing

If you want to contribute, please make sure you follow the current style and the changes do not break anything!

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments <3

* [Lonami](https://github.com/LonamiWebs) who inspired me to develop using Telegram
* [Telegram](https://telegram.org) for the chat platform with an open API
