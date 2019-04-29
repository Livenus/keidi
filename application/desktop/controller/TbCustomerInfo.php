<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class TbCustomerInfo extends Common {

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->CustomerInfo=model("CustomerInfo");
    }

    public function edit() {
        $allow=["CustomerName","NickName","Birthday","CardType","CardNO","BankName","Email","Mobile","Phone","Fax","PostCode","Address","Memo","QQ","BankUser","Address2",
            "PayType",
             "Nation",
             "Province",
             "City",
             "District",
             "BankBranch"
            ];
        $input = input("post.");
        $allow_data=[];
        foreach ($allow as $v){
            if(isset($input[$v])&&!empty($input[$v])){
            $allow_data[$v]=$input[$v];
            }
        }
        if(empty($allow_data)||empty($input["CustomerID"])){
             return err("提交数据为空或CustomerID为空");
        }
        $user=self::$user ;
        $check = $this->validate($input, "CustomerInfo.edit");
        if ($check !== true) {
              return err($check);
        }
        $status=$this->CustomerInfo->editById($allow_data, $input["CustomerID"]);
        if($status["code"]==1){

            return suc("修改成功");
        }
        return err("修改失败");
    }
    
}
