<?php

namespace app\desktop\validate;
use think\Validate;

class SaleEnteredByFront extends Validate
{
    protected $rule = [
        'ProductNO'  =>  'require|^\[.*\]$',
        'Shopno'  =>  'require|\d+',
        'RealDate'  =>  'require|\d{4}-\d{1,2}-\d{1,2}$',
    ];
    protected $scene = [
    ];
}