<?php

namespace app\desktop\validate;
use think\Validate;

class FrontDeskCash extends Validate
{
    protected $rule = [
        'Amount'  =>  'require|between:0.1,10000000',
            "GroupID"=>"require|\d+.*\d$",
            "ShopNO"=>"require|\d+$",
            "RealDate"=>"require|\d{4}.*\d$",
    ];
    protected $message=[
        "Amount.require"=>"付款金额必填"
        
        
    ];
    protected $scene = [
          "add"=>["GroupID","Amount","ShopNO","RealDate"]
    ];
}