<?php

namespace app\desktop\controller;
use app\desktop\controller\Common;
use think\Db;
class TbCustomer extends Common {

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->Customer = model("Customer");
        $this->CustomerInfo = model("CustomerInfo");
    }

    public function edit() {
        $allow = ["CustomerNO", "ShopNO", "CustomerName", "ParentNO", "RecommendNO","Mobile"];
        $input = input("post.");
        $check = $this->validate($input, "Customer.edit");
        if ($check !== true) {
            return err(9000, $check);
        }
        $customer["CustomerNO"] = $input["CustomerNO"];
        $customer["ShopNO"] = $input["ShopNO"];
        $customer["ParentNO"] = $input["ParentNO"];
        $customer["RecommendNO"] = $input["RecommendNO"];
        foreach ($customer as $k => $v) {
            if (empty($v)) {
                unset($customer[$k]);
            }
        }

        if (isset($customer["ParentNO"])) {
            $parent = $this->Customer->getByWhereOne(["CustomerNO" => $customer["ParentNO"]]);
            $parent_info = $this->CustomerInfo->getByWhereOne(["CustomerID" => $parent["CustomerID"]]);
            $customer["ParentID"] = $parent["CustomerID"];
            $customer["ParentName"] = $parent_info["CustomerName"];
        }
        if (isset($customer["RecommendNO"])) {
            $parent = $this->Customer->getByWhereOne(["CustomerNO" => $customer["RecommendNO"]]);
            $parent_info = $this->CustomerInfo->getByWhereOne(["CustomerID" => $parent["CustomerID"]]);
            $customer["RecommendID"] = $parent["CustomerID"];
            $customer["RecommendName"] = $parent_info["CustomerName"];
        }
                
        if (isset($input["CustomerName"]) && !empty($input["CustomerName"])) {
            $info["CustomerName"] = $input["CustomerName"];
             $info["Mobile"] = $input["Mobile"];            



            $status = $this->CustomerInfo->editById($info, $input["CustomerID"]);
        }
        if(!empty($customer)){
                    $status = $this->Customer->editById($customer, $input["CustomerID"]);
        }else if(empty($customer)&&empty($input["CustomerName"])){
        return err(9000, "编辑错误没有信息更新" );
        }

        if ($status["stat"] == 1) {
            actionlog(self::$realname."编辑用户",$input["CustomerID"]);
            return suc("修改成功");
        }
        return err(9000, "编辑错误" . $status['errmsg']);
    }

    public function get_list($order = "CustomerID asc", $limit = "0,100") {
        $input = input("post.");
        $map = [];
        if (isset($input["CustomerNO"]) && !empty($input["CustomerNO"])) {
            $map["CustomerNO"] = $input["CustomerNO"];
        }
        if (isset($input["CustomerName"]) && !empty($input["CustomerName"])) {
            $mapi["CustomerName"] = $input["CustomerName"];
            $user = $this->CustomerInfo->getByWhereOne($mapi);
            if ($user) {
                $map["CustomerID"] = $user["CustomerID"];
            } else {
                $map["CustomerID"] = 0;
            }
        }

        if (isset($input["ShopNO"]) && !empty($input["ShopNO"])) {
            $map["ShopNO"] = $input["ShopNO"];
        }
        if (isset($input["RecommendName"]) && !empty($input["RecommendName"])) {
            $map["RecommendName"] = $input["RecommendName"];
        }
        if (isset($input["ParentName"]) && !empty($input["ParentName"])) {
            $map["ParentName"] = $input["ParentName"];
        }
        if (isset($input["ParentNO"]) && !empty($input["ParentNO"])) {
            $map["ParentNO"] = $input["ParentNO"];
        }
        if (isset($input["RecommendNO"]) && !empty($input["RecommendNO"])) {
            $map["RecommendNO"] = $input["RecommendNO"];
        }
        if (isset($input["Grade"]) && !empty($input["Grade"])) {
            $map["Grade"] = $input["Grade"];
        }
        if (isset($input["Start"]) && !empty($input["End"])) {
            $map["RegDate"] = ["between","{$input['Start']},{$input['End']}"];
        }
        $data = $this->Customer->getByWhere($map, "*", $limit, $order);
        foreach($data as $k=>$v){
            $user=$this->CustomerInfo->getByWhereOne(["CustomerID"=>$v["CustomerID"]], "CustomerName,Mobile");
            if($user){
            $data[$k]["CustomerName"]=$user["CustomerName"];
            $data[$k]["Mobile"]=$user["Mobile"];   
            }else{
            $data[$k]["CustomerName"]="";
            $data[$k]["Mobile"]="";    
            }

        }
        $count = $this->Customer->getCount($map);
        if (!empty($data)) {
            $res["count"] = $count;
            $res["list"] = $data;
            return suc($res);
        }
        return err(9000, "没有数据");
    }

    public function customer_create() {

        $user_data = [
            'CustomerNO' => input('post.CustomerNO'),
            'ParentNO' => input('post.ParentNO'),
            'RecommendNO' => input('post.RecommendNO'),
            'ShopNO' => input('post.ShopNO'),
            'CustomerName' => input('post.CustomerName'),
            'Sex' => input('post.Sex'),
            'Mobile' =>input('post.Mobile'),
            'BankName' =>input('post.BankName'),
            'BankCard' =>input('post.BankCard'),
            'Province' =>input('post.State'),
            'Address2' => self::$realname,
            'Address' => input('post.Adress')
        ];
        $info_data = [
            'CustomerName' => input('post.CustomerName'),
            'Sex' => input('post.Sex'),
            'Mobile' =>input('post.Mobile'),
            'BankName' =>input('post.BankName'),
            'BankCard' =>input('post.BankCard'),
            'Province' =>input('post.State'),
            'Address2' => self::$realname,
            'Address' => input('post.Adress')
        ];
        try {
            $Customer = model("Customer");
           $check=$this->validate($user_data, "Customer.add");
           if($check!==true){
            throw new \Exception($check."3");
           }
           $check=$this->validate($user_data, "CustomerInfo.add");

           if($check!==true){
            throw new \Exception($check."1");
           }
            $res = $Customer->add($user_data);
            if ($res['stat'] ===0) {
                throw new \Exception($res['errmsg']."2");
            }
			var_dump($res);
            actionlog(self::$realname."创建用户",$res['data']);
            return suc($res['data']);
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

}
