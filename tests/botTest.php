<?php
/**
 * Created by PhpStorm.
 * User: Kyle
 * Date: 2018/06/13
 * Time: 08:54
 */

use \PHPUnit\Framework\TestCase;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/config.php';

final class botTest extends TestCase
{
    private $bot;

    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->bot = new kyle2142\PHPBot(config::TOKEN);

    }

    public function testGetChat()
    {
        $result = $this->bot->api->getChat(['chat_id' => config::OWNER['id']]);

        $this->assertAttributeEquals(config::OWNER['id'], 'id', $result);
        $this->assertAttributeEquals(config::OWNER['first_name'], 'first_name', $result);
        if(isset($result->last_name)){
            $this->assertAttributeEquals(config::OWNER['last_name'], 'last_name', $result);
        }
        if(isset($result->username)){
            $this->assertAttributeEquals(config::OWNER['username'], 'username', $result);
        }
    }

    public function testMessaging()
    {
        $text = 'test'; $edited = 'beep';

        $msg = $this->bot->sendMessage(config::OWNER['id'], $text);
        $this->assertAttributeEquals($text, 'text', $msg);

        $edited_msg = $this->bot->editMessageText($msg->chat->id, $msg->message_id, $edited);
        $this->assertAttributeEquals($edited, 'text', $edited_msg);

        $deleted = $this->bot->deleteMessage($edited_msg->chat->id, $edited_msg->message_id);
        $this->assertTrue($deleted);
    }

    public function testPermissions()
    {
        $perms = $this->bot->getPermissions(config::GROUP_WITH_ADMIN);

        $this->assertEquals('administrator', $perms->status);

        $has_all_admin = $perms->can_change_info
            and $perms->can_delete_messages
            and $perms->can_invite_users
            and $perms->can_restrict_members
            and $perms->can_pin_messages
            and $perms->can_promote_members;
        $has_all_normal_perms = $perms->can_send_messages
            and $perms->can_send_messages
            and $perms->can_send_media_messages
            and $perms->can_send_other_messages
            and $perms->can_add_web_page_previews;

        $this->assertTrue($has_all_admin and $has_all_normal_perms);
    }

}
