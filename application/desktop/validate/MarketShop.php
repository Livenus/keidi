<?php

namespace app\desktop\validate;
use think\Validate;

class MarketShop extends Validate
{
    protected $rule = [
        'Shopno'  =>  'require|/\d{3}$/|is_in',
        'RegionPerson'  =>  'require',
        'KediID'  =>  '\d{1,}$|is_has',
        'State'  =>  'require',
        'OpenDate'  =>  'require',
        'Preparedby'  =>  'require',
        'Region'  =>  'require',
        'Status'  =>  'require',
        'apply_status'  =>  'require',
    ];
    protected $message  =   [
        'Shopno.is_in'  =>  '店铺号已经存在',
        'KediID.is_has'  =>  'KediID已经存在',
    ];
    protected $scene = [
        'edit'  =>  ["Status"],
        'update'  =>  ["apply_status"],
        'add'  =>  ['Shopno','KediID','State','Region'],
    ];
    protected  function is_in($value,$rule,$data){
        if(model("MarketShop")->getCount(["Shopno"=>$value])){
            return false;
        }
        return true;
    }
    protected  function is_has($value,$rule,$data){
        if(model("MarketShop")->getCount(["KediID"=>$value,"Status"=>["in","0,1"]])){
            return false;
        }
        return true;
    }
}