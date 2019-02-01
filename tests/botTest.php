<?php

use \PHPUnit\Framework\TestCase;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';

final class botTest extends TestCase
{
    private $bot;

    public function __construct(string $name = null, array $data = [], string $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->bot = new kyle2142\PHPBot(config::TOKEN);
    }

    public function testGetChat() {
        $result = $this->bot->api->getChat(['chat_id' => config::OWNER['id']]);

        $this->assertAttributeEquals(config::OWNER['id'], 'id', $result);
        $this->assertAttributeEquals(config::OWNER['first_name'], 'first_name', $result);
        if (isset($result->last_name)) {
            $this->assertAttributeEquals(config::OWNER['last_name'], 'last_name', $result);
        }
        if (isset($result->username)) {
            $this->assertAttributeEquals(config::OWNER['username'], 'username', $result);
        }
    }

    public function testMessaging() {
        $text = 'test';
        $edited = 'beep';

        $msg = $this->bot->sendMessage(config::OWNER['id'], $text);
        $this->assertAttributeEquals($text, 'text', $msg);

        $edited_msg = $this->bot->editMessageText($msg->chat->id, $msg->message_id, $edited);
        $this->assertAttributeEquals($edited, 'text', $edited_msg);

        $deleted = $this->bot->deleteMessage($edited_msg->chat->id, $edited_msg->message_id);
        $this->assertTrue($deleted);
    }

    public function testPermissions() {
        $has_all_admin =
            function ($perms) {
                return $perms->can_change_info
                    and $perms->can_delete_messages
                    and $perms->can_invite_users
                    and $perms->can_restrict_members
                    and $perms->can_pin_messages
                    and $perms->can_promote_members;
            };
        $has_all_normal =
            function ($perms) {
                return $perms->can_send_messages
                    and $perms->can_send_media_messages
                    and $perms->can_send_other_messages
                    and $perms->can_add_web_page_previews;
            };

        $admin_group_perms = $this->bot->getPermissions(config::GROUP_ADMIN);
        $this->assertEquals('administrator', $admin_group_perms->status);
        $this->assertTrue($has_all_admin($admin_group_perms));
        $this->assertTrue($has_all_normal($admin_group_perms));

        $banned_group_perms = $this->bot->getPermissions(config::GROUP_BANNED);
        $this->assertEquals('banned', $banned_group_perms->status);
        $this->assertFalse($has_all_admin($banned_group_perms));
        $this->assertFalse($has_all_normal($banned_group_perms));

        $invalid_group_perms = $this->bot->getPermissions(config::GROUP_INVALID);
        $this->assertEquals('invalid', $invalid_group_perms->status);
        $this->assertFalse($has_all_admin($invalid_group_perms));
        $this->assertFalse($has_all_normal($invalid_group_perms));

        $left_group_perms = $this->bot->getPermissions(config::GROUP_LEFT);
        $this->assertEquals('left', $left_group_perms->status);
        $this->assertFalse($has_all_admin($left_group_perms));
        $this->assertFalse($has_all_normal($left_group_perms));

        $restricted_group_perms = $this->bot->getPermissions(config::GROUP_RESTRICTED);
        $this->assertEquals('restricted', $restricted_group_perms->status);
        $this->assertFalse($has_all_admin($restricted_group_perms));
        $this->assertFalse($has_all_normal($restricted_group_perms));

        $normal_group_perms = $this->bot->getPermissions(config::GROUP_NORMAL);
        $this->assertEquals('member', $normal_group_perms->status);
        $this->assertFalse($has_all_admin($normal_group_perms));
        $this->assertTrue($has_all_normal($normal_group_perms));
    }

    public function testException() {
        $thrown = false;
        try {
            $this->bot->sendMessage(config::GROUP_BANNED, 'this should fail');
        } catch (\kyle2142\TelegramException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testBadAccessing() {
        $thrown = false;
        try {
            $this->bot->api->getChatMember(['chat_id' => config::GROUP_BANNED, 'user_id' => $this->bot->getBotID()])->result;
        } catch (\kyle2142\TelegramException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }
}
