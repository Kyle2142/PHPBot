# PHPBot

At some point I decided I wanted to make a Telegram bot.
I saw the API and thought 
> hey, I might as well make my own lib for this

This lib doesn't have any groundbreaking features, but it allowed me to develop my own bots and be able to edit the underlying code as I needed.

**Please read the wiki!**

## Getting Started

```php
require 'PHPBot.php';
$bot = new kyle2142\PHPBot('12345678:ABCDEF123456890abcdef'); //replace with your token
print_r($bot->api->getMe()); //dump bot info
$me = 98765432; //replace with your ID
$bot->sendMessage($me, "Hello World!");
```

### Prerequisites

* PHP \>= 7.0
* cURL extension correctly installed and enabled

### Installing
You may use any of the following:

* Download [PHPBot.php](https://raw.githubusercontent.com/Kyle2142/PHPBot/master/PHPBot.php) and 
```php
require __DIR__.'/PHPBot.php';`
```
* composer.json:
```
"require": {
    "kyle2142/phpbot": "dev-master"
}
```
* CLI: `composer require kyle2142/phpbot=dev-master`
  
with both of the composer methods, add this to your main file:
```php
require __DIR__.'/vendor/autoload.php';
```

## Usage

### Receiving updates

I highly suggest you set up a webhook (requires SSL), so you can use this amazingly simple code to process updates:

```php
$content = file_get_contents('php://input');
$update = json_decode($content, true);
//do stuff with $update:
if(isset($update['message']['text']) and $update['message']['text'] === "Hello!"){
    $msg_id = $update['message']['message_id'];
    $chat_id = $update['message']['chat']['id'];
    $name = $update['message']['from']['first_name'];
    $bot->sendMessage($chat_id, "Hello $name!", ['reply_to_message_id'=>$msg_id]);
}
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

#### Success

While calling any raw API method or convenience function, you will get a result in the form of an object or a boolean:
<details><summary>View dump</summary><p>
    
```php
php > var_dump($bot->editMessage(343859930, 172, "New text!"));

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

php > var_dump($bot->deleteMessage(343859930, 172));

bool(true)
```
</p></details>

This means you can access return values as such:

```php
$result = $bot->sendmessage('@mychannel', "New stuff at example.com!");

$params = ['chat_id'=>343859930, 'from_chat_id' => $result->chat->id, 'message_id' => $result->message_id];
$bot->api->forwardMessage($params); //put params as variable due to line length
```
#### Error
In the case of an error, `kyle2142\TelegramException` (or one of its subclasses) will be thrown.

As an example, let us try delete a message that does not exist:

(Note that we already deleted message 172 [here](#return-values), so trying to delete it again will throw an error)
```php
php > var_dump($bot->deleteMessage(343859930, 172));

PHP Warning:  Uncaught TelegramException: 400 (Bad Request: message to delete not found)
Trace:
//trace omitted
```
When you catch the error, you can get the error code and description (returned by Telegram) as such:
```php
try{
    $bot->deleteMessage(343859930, 172);
}catch(\kyle2142\TelegramExeption $exception){
    echo "Error code was {$exception->getCode()}\n
    Telegram says '{$exception->getMessage()}' ";
}

Error code was 400
Telegram says 'Bad Request: message to delete not found'
```
If your bot makes a lot of requests, you might run into `429` "FloodWait" errors, which will tell you how long until you can make that request again:
```php
try{
    $bot->editMessage(343859930, 173, "beep");
}catch(\kyle2142\TelegramFloodWait $exception){
    echo "We need to wait {$exception->getRetryAfter()} seconds";
}
```

## Have Questions?
Please do not open an issue for small questions etc, it's there for *issues*

* Telegram: [@Kyle_S](https://t.me/kyle_s) (preferred)
* [Email](mailto:kyle-2142@outlook.com) (not preferred)

## Contributing

If you want to contribute, please make sure you follow the current style and the changes do not break anything!

## Tests
To run tests, you need to rename/copy `tests/example-config.php` to `tests/config.php` and update the values.

**Make sure to have the correct testing environment!**

 E.g.`GROUP_WITH_ADMIN` needs to be the ID of a group where the bot is an admin, or skip the tests that require them.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Acknowledgments <3

* [Lonami](https://github.com/LonamiWebs) who inspired me to develop using Telegram
* [Telegram](https://telegram.org) for the chat platform with an open API
