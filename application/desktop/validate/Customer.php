<?php

namespace app\desktop\validate;
use think\Validate;

class Customer extends Validate
{
    protected $rule = [
        'CustomerID'  =>  'require',
        'CustomerNO'  =>  '[a-zA-Z]{2}\d{6}|is_in',
        'ShopNO'  =>  '^\d{3}$|is_shop',
        'ParentNo'  =>  'is_parent',
        'RecommendNO'  =>  'is_recommend',
    ];
    protected $message  =   [
        'CustomerNO.is_in' => '编号已存在',
        'ShopNO.is_shop' => '专卖店不存在或已禁用',
        'ParentNo.is_parent' => '编号父编号不存在',
        'RecommendNO.is_recommend' => '区域推荐人不存在',
    ];
    protected $scene = [
        'edit'  =>  ['CustomerID','CustomerNO','ShopNO','ParentNo','RecommendNO'],
        'add'  =>  ['CustomerNO','ShopNO','ParentNo','RecommendNO'],
    ];
    protected function is_shop($value,$rule,$data){
         $maketshop=model("MarketShop");
         $count=$maketshop->getCount(["Shopno"=>$value,"Status"=>1]);
          if(empty($value)||$count){
              return true;
          }
        return false;
    }
    protected function is_parent($value,$rule,$data){
         $maketshop=model("Customer");
          if(!empty($value)&&$maketshop->getCount(["CustomerNO"=>$value])){
              return true;
          }
        return false;
    }
    protected function is_recommend($value,$rule,$data){
         $maketshop=model("Customer");
         $count=$maketshop->getCount(["CustomerNO"=>$value]);
          if(!empty($value)&&$count){
              return true;
          }
        return false;
    }
    protected function is_in($value,$rule,$data){
         $maketshop=model("Customer");
         $map["CustomerNO"]=$value;
         if(isset($data["CustomerID"])){
          $map["CustomerID"]=["neq",$data["CustomerID"]]; 
         }
          $count=$maketshop->getCount($map);
          if(empty($value)||$count==0){
              return true;
          }
        return false;
    }
}