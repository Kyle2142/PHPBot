<?php

namespace kyle2142;
use InvalidArgumentException, LogicException, stdClass, CURLFile;

class PHPBot
{
    private $webhook_reply_used = false, $BOTID;
    public $api;

    /**
     * PHPBot constructor.
     * @param string $token The botAPI token provided by t.me/Botfather
     */
    public function __construct(string $token)
    {
        $this->api = new api($token);
    }

    /**
     * Executes a botAPI method as response to the webhook
     * which is faster than using action() but can only be used once per webhook call, and cannot retrieve result
     * @param string $method The Telegram botAPI method to call
     * @param array  $params Array of parameters needed for the method
     */
    public function quickAction($method, array $params = [])
    {
        if($this->webhook_reply_used){
            throw new LogicException('This function may only be called once per webhook call');
        }
        header_remove();
        http_response_code(200);
        header('Content-Type: application/json');
        header('Status: 200 OK');
        $params['method'] = $method;
        echo json_encode($params);
        $this->webhook_reply_used = true;
    }

    /**
     * Send $text to $chat_id with optional extras such as reply_markup, see https://core.telegram.org/bots/api#sendmessage
     * @param       $chat_id
     * @param       $text
     * @param array $extras
     * @return stdClass
     */
    public function sendMessage($chat_id, $text, array $extras = []): stdClass
    {
        return $this->api->sendMessage(array_merge(['chat_id' => $chat_id, 'text' => $text], $extras)); //merges necessary params with extras, if any, and calls template
    }

    /**
     * Edits $msg_id from $chat_id to become $text, with optional $extras
     * @param       $chat_id
     * @param       $msg_id
     * @param       $text
     * @param array $extras see https://core.telegram.org/bots/api#editmessagetext
     * @return stdClass
     */
    public function editMessage($chat_id, $msg_id, $text, array $extras = []): stdClass
    {
        return $this->api->editMessageText(array_merge(['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => $text], $extras));
    }

    /**
     * Edits only reply_markup of $msg_id from $chat_id
     * @param        $chat_id
     * @param        $msg_id
     * @param string $reply_markup The new reply_markup
     * @return stdClass
     */
    public function editMarkup($chat_id, $msg_id, $reply_markup = ''): stdClass
    {
        return $this->api->editMessageReplyMarkup(['chat_id' => $chat_id, 'message_id' => $msg_id, 'reply_markup' => $reply_markup]);
    }

    /**
     * Checks what admin privileges the bot has inside $chat_id
     * @param $chat_id
     * @return stdClass
     */
    public function getAdminPrivs($chat_id): stdClass
    {
        $result = $this->api->getChatMember(['chat_id' => $chat_id, 'user_id' => $this->BOTID])->result;
        if($result->status !== 'administrator')
        {
            $perms = [
                'can_change_info',
                'can_delete_messages',
                'can_invite_users',
                'can_restrict_members',
                'can_pin_messages',
                'can_promote_members'
            ];
            foreach($perms as $perm){
                $result->$perm = 0;
            }
        }
        return $result;
    }

    /**
     * Deletes $msg_id from $chat_id
     * @param $chat_id
     * @param $msg_id
     * @return stdClass
     */
    public function deleteMessage($chat_id, $msg_id): stdClass
    {
        return $this->api->deleteMessage(['chat_id' => $chat_id, 'message_id' => $msg_id]);
    }

    /**
     * Promotes user to full admin by default
     * @param       $user_id
     * @param       $chat_id
     * @param array $perms
     * @return stdClass
     */
    public function promoteUser($user_id, $chat_id, array $perms = [
        'can_change_info' => 1,
        'can_delete_messages' => 1,
        'can_invite_users' => 1,
        'can_restrict_members' => 1,
        'can_pin_messages' => 1,
        'can_promote_members' => 1
    ]): stdClass
    {
        return $this->editAdmin($user_id, $chat_id, $perms);
    }

    /**
     * Template function to edit/give $perms to $user_id in $chat_id
     * @param       $user_id
     * @param       $chat_id
     * @param array $perms
     * @return stdClass
     */
    public function editAdmin($user_id, $chat_id, array $perms = []): stdClass
    {
        return $this->api->promotechatmember(
            array_merge(
                [
                    'user_id' => $user_id,
                    'chat_id' => $chat_id
                ],
                $perms
            )
        );
    }

    /**
     * Gives {delete/pin messages, invite users} permissions to $user_id in $chat_id
     * @param $user_id
     * @param $chat_id
     * @return stdClass
     */
    public function makeModerator($user_id, $chat_id): stdClass
    {
        return $this->editAdmin($user_id, $chat_id,
            [
                'can_delete_messages' => 1,
                'can_invite_users' => 1,
                'can_pin_messages' => 1
            ]
        );
    }

    /**
     * Removes all admin permissions of $user_id in $chat_id
     * @param $user_id
     * @param $chat_id
     * @return stdClass
     */
    public function demote($user_id, $chat_id): stdClass
    {
        return $this->editAdmin($user_id, $chat_id); //no args means no perms
    }

    /**
     * Edits info of group, using any info given
     * @param       $chat_id
     * @param array $info At least one of {title, description, photo path} must be in this array
     * @return array
     */
    public function editInfo($chat_id, array $info): array
    {
        if(\count($info) < 1){
            return [NULL];
        }
        $results = [];
        if(isset($info['title'])){
            $results[] = $this->api->setChatTitle(['chat_id' => $chat_id, 'title' => $info['title']]);
        }
        if(isset($info['description'])){
            $results[] = $this->api->setChatDescription(['chat_id' => $chat_id, 'description' => $info['description']]);
        }
        if(isset($info['photo']) && file_exists($info['photo'])){
            $data = [
                'chat_id' => $chat_id,
                'photo' => new CURLFile(realpath($info['photo']))
            ];
            $results[] = $this->api->setChatPhoto($data);
        }
        return $results;
    }

    /**
     * Takes a list of entities and restores markdown in botAPI format
     * Example usage: get message, pass to this function and get back original message before sending,
     * so it can be resent.
     * @param string $msg
     * @param array  $entities
     * @return string
     */
    public function createMarkdownFromEntities(string $msg, array $entities): string
    {
        usort($entities, function ($a, $b) {
            if($a['offset'] < $b['offset']){
                return 1;
            }
            if($a['offset'] > $b['offset']){
                return -1;
            }
            return 0; //equal
        }); //sorts entities by their offset, descending

        foreach($entities as $e){ //entities may not overlap, so this will not edit the same part of a message twice
            switch($e['type']){
                case 'bold':
                    $char = '*';
                    break;
                case 'italic':
                    $char = '_';
                    break;
                case 'code':
                    $char = '`';
                    break;
                case 'pre':
                    $char = '```';
                    break;
                default:
                    $char = '';
            } //sets $char based on entity tyep
            if($char !== ''){
                $msg = substr_replace($msg, $char, $e['offset'], 0); //0 means "insert". Insert char at offset
                $msg = substr_replace($msg, $char, $e['offset'] + $e['length'] + 1, 0);
            }
        }
        return $msg;
    }

    /**
     * @return int
     */
    public function getBotID(): int
    {
        return $this->api->getBotID();
    }
}

class api{
    private $api_url, $BOTID, $curl;

    public function __construct(string $token){
        if(preg_match('/^(\d+):[\w-]{30,}$/', $token, $matches) === 0){
            throw new InvalidArgumentException('The supplied token does not look correct...');
        }
        $this->BOTID = $matches[0];
        $this->api_url = "https://api.telegram.org/bot$token/";

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, ['Content-Type:multipart/form-data']);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }
    
    /**
     * Template function to make API calls using method name and array of parameters
     * @param string $method The method name from https://core.telegram.org/bots/api
     * @param array  $params The arguments of the method, as an array
     * @return stdClass
     */
    public function __call(string $method, array $params = [[]]): stdClass
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->api_url . $method);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params[0]);
        $result = curl_exec($this->curl); //not always needed, json encoded result from Telegram
        $response = json_decode($result);
        if($response->ok) return $response->result;
        else return $response;
    }

    /**
     * @return int
     */
    public function getBotID(): int
    {
        return $this->BOTID;
    }
}