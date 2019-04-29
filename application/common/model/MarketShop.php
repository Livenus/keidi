<?php

namespace app\common\model;
use app\common\model\Base;
class MarketShop extends Base {

    protected $table = 'MarketShop';
    protected $pk = 'Shopno';

    public function addItem($data, $mod=""){
        
        $customer=model("Customer")->getById($data["KediID"]);
        $customer_info=model("CustomerInfo")->getById($data["KediID"]);
        if(empty($customer_info)){
            return err(3000,"Customer信息不存在");
        }
        $data["OwnerName"]=$customer_info["CustomerName"];
        $data["Phone"]=$customer_info["Phone"];
        $data["Address"]=$customer_info["Address"];
        return parent::addItem($data);
    }
}
