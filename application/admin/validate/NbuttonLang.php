<?php

namespace app\admin\validate;
use think\Validate;

class NbuttonLang extends Validate
{
    protected $rule = [
        'page'  =>  'require',
        'menu_key'  =>  'require|unique:NbuttonLang',
        'eng'  =>  'require',
        'zh'  =>  'require',
    ];
    protected $message  =   [
    ];
    protected $scene = [
        'edit'  =>  ['page','eng','zh'],
        'add'  =>  ['page','menu_key','eng','zh'],
    ];
}