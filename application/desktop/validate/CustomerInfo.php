<?php

namespace app\desktop\validate;
use think\Validate;

class CustomerInfo extends Validate
{
    protected $rule = [
        'CustomerID'  =>  'require',
        'BankName'  =>  'number',
    ];
    protected $scene = [
        'edit'  =>  ['CustomerID'],
        'add'  =>  ['CustomerName','BankName'],
    ];
}