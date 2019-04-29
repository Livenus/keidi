<?php

namespace app\desktop\validate;
use think\Validate;

class EnterAppUser extends Validate
{
    protected $rule = [
        'old_password'  =>  'require|max:25',
        'new_password' =>  'require|max:25',
    ];

}