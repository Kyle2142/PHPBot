<?php

class PHPBot
{
    private $api, $webhook_reply_used = false, $BOTID;

    public function __construct($token)
    {
        if(preg_match('/^(\d+):[\w-]{30,}$/', $token, $matches) === 0){
            throw new InvalidArgumentException('The supplied token does not look correct...');
        }
        $this->api = "https://api.telegram.org/bot$token/";
        $this->BOTID = $matches[0];
    }

    /**
     * Magic method to call botAPI methods that are not defined in this class
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function __call(string $method, array $args) : stdClass
    {
        return $this->action($method, $args[0]);
    }

    /**
     * Template function to make API calls using method name and array of parameters
     * @param string $method The method name from https://core.telegram.org/bots/api
     * @param array  $params The arguments of the method, as an array
     * @return stdClass
     */
    public function action(string $method, array $params): stdClass
    {
        $ch = curl_init($this->api . $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch); //not always needed, json encoded result from Telegram
        curl_close($ch);
        return json_decode($result);
    }

    /**
     * Executes a botAPI method as response to the webhook
     * which is faster than using action() but can only be used once per webhook call, and cannot retrieve result
     * @param string $method The Telegram botAPI method to call
     * @param array  $params Array of parameters needed for the method
     */
    public function quickaction($method, array $params = [])
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
    public function sendmessage($chat_id, $text, array $extras = []): stdClass
    {
        return $this->action('sendmessage', array_merge(['chat_id' => $chat_id, 'text' => $text], $extras)); //merges necessary params with extras, if any, and calls template
    }

    /**
     * Edits $msg_id from $chat_id to become $text, with optional $extras
     * @param       $chat_id
     * @param       $msg_id
     * @param       $text
     * @param array $extras see https://core.telegram.org/bots/api#editmessagetext
     * @return stdClass
     */
    public function editmessagetext($chat_id, $msg_id, $text, array $extras = []): stdClass
    {
        return $this->action('editmessagetext', array_merge(['chat_id' => $chat_id, 'message_id' => $msg_id, 'text' => $text], $extras));
    }

    /**
     * Edits only reply_markup of $msg_id from $chat_id
     * @param        $chat_id
     * @param        $msg_id
     * @param string $reply_markup The new reply_markup
     * @return stdClass
     */
    public function editmarkup($chat_id, $msg_id, $reply_markup = ''): stdClass
    {
        return $this->action('editmessagereplymarkup', ['chat_id' => $chat_id, 'message_id' => $msg_id, 'reply_markup' => $reply_markup]);
    }

    /**
     * Checks what admin privileges the bot has inside $chat_id
     * @param $chat_id
     * @return stdClass
     */
    public function checkadminprivs($chat_id): stdClass
    {
        $result = $this->action('getchatmember', ['chat_id' => $chat_id, 'user_id' => $this->BOTID])->result;
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
    public function deletemessage($chat_id, $msg_id): stdClass
    {
        return $this->action('deletemessage', ['chat_id' => $chat_id, 'message_id' => $msg_id]);
    }

    /**
     * Promotes user with necessary permissions for our groups
     * @param $user_id
     * @param $chat_id
     * @return stdClass
     */
    public function promote($user_id, $chat_id): stdClass
    {
        return $this->editadmin($user_id, $chat_id,
            [
                'can_delete_messages' => 1,
                'can_invite_users' => 1,
                'can_restrict_members' => 1,
                'can_pin_messages' => 1,
                'can_promote_members' => 1
            ]
        );
    }

    /**
     * Template function to edit/give $perms to $user_id in $chat_id
     * @param       $user_id
     * @param       $chat_id
     * @param array $perms
     * @return stdClass
     */
    public function editadmin($user_id, $chat_id, array $perms = []): stdClass
    {
        $perms = array_replace([ //overwrites defaults with values from $perms
            'can_change_info' => 0,
            'can_delete_messages' => 0,
            'can_invite_users' => 0,
            'can_restrict_members' => 0,
            'can_pin_messages' => 0,
            'can_promote_members' => 0
        ], $perms);
        return $this->action(
            'promotechatmember',
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
     * Gives {delete, invite, pin} permissions to $user_id in $chat_id
     * @param $user_id
     * @param $chat_id
     * @return stdClass
     */
    public function mod($user_id, $chat_id): stdClass
    {
        return $this->editadmin($user_id, $chat_id,
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
        return $this->editadmin($user_id, $chat_id); //no args means no perms
    }

    /**
     * Bans $user_id from $chat_id
     * @param $user_id
     * @param $chat_id
     * @return stdClass
     */
    public function ban($user_id, $chat_id): stdClass
    {
        return $this->action('kickchatmember', ['chat_id' => $chat_id, 'user_id' => $user_id]);
    }

    /**
     * Unbans $user_id from $chat_id (does not add them back)
     * @param $user_id
     * @param $chat_id
     * @return stdClass
     */
    public function unban($user_id, $chat_id): stdClass
    {
        return $this->action('unbanchatmember', ['chat_id' => $chat_id, 'user_id' => $user_id]);
    }

    /**
     * Edits info of group, using any info given
     * @param       $chat_id
     * @param array $info At least one of {title, description, photo path} must be in this array
     */
    public function editinfo($chat_id, array $info)
    {
        if(count($info) < 1){
            return;
        }
        if(isset($info['title'])){
            $this->action('setchattitle', ['chat_id' => $chat_id, 'title' => $info['title']]);
        }
        if(isset($info['description'])){
            $this->action('setchatdescription', ['chat_id' => $chat_id, 'description' => $info['description']]);
        }
        if(isset($info['photo']) && file_exists($info['photo'])){
            $post_fields = [
                'chat_id' => $chat_id,
                'photo' => new CURLFile(realpath($info['photo']))
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->api . "setchatphoto?chat_id=$chat_id");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type:multipart/form-data'
            ]);
            curl_exec($ch);
            unlink($info['photo']);
        }
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
     * @return mixed
     */
    public function getBOTID()
    {
        return $this->BOTID;
    }
}
