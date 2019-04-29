<?php

namespace app\admin\validate;
use think\Validate;

class User extends Validate
{
    protected $rule = [
        'username'  =>  'require|length:1,100',
        'userpassword'  =>  'require|length:1,100',
    ];
    protected $message  =   [
    ];
    protected $scene = [
    ];
}