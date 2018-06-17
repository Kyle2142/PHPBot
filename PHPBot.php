<?php

namespace kyle2142;

use InvalidArgumentException, LogicException, stdClass, CURLFile;

class PHPBot
{
    private $webhook_reply_used = false;
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
     * @param        $chat_id
     * @param string $text
     * @param array  $extras
     * @return stdClass
     */
    public function sendMessage($chat_id, string $text, array $extras = []): stdClass
    {
        return $this->api->sendMessage(array_merge(['chat_id' => $chat_id, 'text' => $text], $extras)); //merges necessary params with extras, if any, and calls template
    }

    /**
     * Edits $msg_id from $chat_id to become $text, with optional $extras
     * @param        $chat_id
     * @param int    $msg_id
     * @param string $text
     * @param array  $extras see https://core.telegram.org/bots/api#editmessagetext
     * @return stdClass
     */
    public function editMessageText($chat_id, int $msg_id, string $text, array $extras = []): stdClass
    {
        return $this->api->editMessageText(array_merge(['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => $text], $extras));
    }

    /**
     * Edits only reply_markup of $msg_id from $chat_id
     * @param        $chat_id
     * @param int    $msg_id
     * @param array  $reply_markup The new reply_markup
     * @return mixed
     */
    public function editMarkup($chat_id, int $msg_id, array $reply_markup = [])
    {
        return $this->api->editMessageReplyMarkup(['chat_id' => $chat_id, 'message_id' => $msg_id, 'reply_markup' => $reply_markup]);
    }

    /**
     * Checks what privileges the bot has inside $chat_id
     * @param $chat_id
     * @return stdClass
     */
    public function getPermissions($chat_id): stdClass
    {
        $result = $this->api->getChatMember(['chat_id' => $chat_id, 'user_id' => $this->getBotID()]);
        if(($result->error_code ?? 0) === 403){
            //403 == forbidden: bot was kicked, left the group, or was never participant
            $result->status = 'left';
        }
        if($result->status !== 'administrator'){
            $admin_perms = [
                'can_change_info',
                'can_delete_messages',
                'can_invite_users',
                'can_restrict_members',
                'can_pin_messages',
                'can_promote_members'
            ];
            foreach($admin_perms as $perm){
                $result->$perm = false;
            }
        }
        if($result->status !== 'restricted'){
            $restricted_perms = [
                'can_send_messages',
                'can_send_media_messages',
                'can_send_other_messages',
                'can_add_web_page_previews'
            ];
            foreach($restricted_perms as $perm){
                //true if the bot is admin, false if the bot isn't in the chat:
                $result->$perm = ($result->status !== 'left');
            }
        }
        return $result;
    }

    /**
     * Deletes $msg_id from $chat_id
     * @param     $chat_id
     * @param int $msg_id
     * @return bool
     */
    public function deleteMessage($chat_id, int $msg_id): bool
    {
        return $this->api->deleteMessage(['chat_id' => $chat_id, 'message_id' => $msg_id]);
    }

    /**
     * Promotes user to full admin by default
     * @param int   $user_id
     * @param       $chat_id
     * @param array $perms
     * @return bool
     */
    public function promoteUser(int $user_id, $chat_id, array $perms = [
        'can_change_info' => 1,
        'can_delete_messages' => 1,
        'can_invite_users' => 1,
        'can_restrict_members' => 1,
        'can_pin_messages' => 1,
        'can_promote_members' => 1
    ]): bool
    {
        return $this->editAdmin($user_id, $chat_id, $perms);
    }

    /**
     * Restricts user (forever) to be only able to read messages
     * @param int $user_id
     * @param     $chat_id
     * @param int $until
     * @return bool
     */
    public function muteUser(int $user_id, $chat_id, int $until = 0): bool
    {
        return $this->api->restrictChatMember([
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'can_send_messages' => false,
            'until_date' => $until
        ]);
    }

    /**
     * Template function to edit/give $perms to $user_id in $chat_id
     * @param int   $user_id
     * @param       $chat_id
     * @param array $perms
     * @return bool
     */
    public function editAdmin(int $user_id, $chat_id, array $perms = []): bool
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
     * @param int $user_id
     * @param     $chat_id
     * @return bool
     */
    public function makeModerator(int $user_id, $chat_id): bool
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
     * @param int $user_id
     * @param     $chat_id
     * @return bool
     */
    public function demote(int $user_id, $chat_id): bool
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
            return [null];
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

class api
{
    private $api_url, $BOTID, $curl;

    public function __construct(string $token)
    {
        if(preg_match('/^(\d+):[\w-]{30,}$/', $token, $matches) === 0){
            throw new InvalidArgumentException('The supplied token does not look correct...');
        }
        $this->BOTID = (int)$matches[0];
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
     * @return stdClass/bool
     */
    public function __call(string $method, array $params = [[]])
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