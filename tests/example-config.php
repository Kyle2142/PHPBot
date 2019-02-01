<?php

class config{
    const
        TOKEN = '987654321:ABC123abc123ABC123',
        //owner info
        OWNER = ['id'=>1234, 'first_name'=>'Example', 'last_name'=>'Optional','username'=>'Optional'],
        //permissions testing
        GROUP_ADMIN = -100123456789, //A group the bot is admin in
        GROUP_NORMAL = -100123456789, //normal member
        GROUP_RESTRICTED = -100123456789,
        GROUP_BANNED = -100123456789, //must be recently banned
        GROUP_LEFT = -100123456789, //left group or was banned/unbanned
        GROUP_INVALID = -100123456789 //nonexistent group
    ;
}