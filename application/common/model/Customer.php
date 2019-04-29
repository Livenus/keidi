<?php

namespace app\common\model;

use app\common\model\Base;
use app\common\model\Enterapp_user;
use think\Validate;

class Customer extends Base {

    protected $table = 'tb_Customer';
    protected $pk = 'CustomerID';
    protected $insert = [
        'AddMode' => 1
    ];

    public function add($data) {
        $rule = [
            'CustomerNO' => 'require|^\w{2}\d{6}$',
        ];
        $msg = [
            'CustomerNO' => 'Member Code 格式错误!',
        ];
        $validate = new Validate($rule, $msg);
        $result = $validate->check($data);

        if (!$result) {
            return err(0,$validate->getError());
        }
        $res = $this->_validate($data);
        if ($res['stat'] == '0') {
            return $res;
        }
       $data['Status'] = 1;
       $data['BranchID'] = 1;
       $data['Grade'] = 0;
       $data['Job_Grade'] = 0;
       $data['C_Grade'] = 0;
       $data['ApplyGrade'] = 1;
       $para["CustomerNO"]=$data["CustomerNO"];
       $para["Status"]=$data["Status"];
       $para["BranchID"]=$data["BranchID"];
       $para["RegDate"]=$data["RegDate"];
       $para["TurnDate"]="";
       $para["ApplyGrade"]=1;
       $para["Grade"]=0;
       $para["Job_Grade"]=0;
       $para["C_Grade"]=0;
       $para["ParentNO"]=$data["ParentNO"];
       $para["ParentName"]=$data["ParentName"];
       $para["Depth"]=$data["Depth"];
       $para["RecommendNO"]=$data["RecommendNO"];
       $para["RecommendName"]=$data["RecommendName"];
       $para["Depth_r"]=$data["Depth_r"];
       $para["NetType"]=2;
       $para["CustomerName"]=$data["CustomerName"];
       $para["Sex"]=$data["Sex"];
       $para["CardType"]=1;
       $para["CardNO"]="";
       $para["BankName"]=$data["BankName"];
       $para["BankCard"]=$data["BankCard"];
       $para["Nation"]="";
       $para["Province"]=$data["Province"];
       $para["Address"]=$data["Address"];
       $para["Address2"]=$data["Address2"];
       $para["PostCode"]=0;
       $para["Mobile"]=$data["Mobile"];
       $para["Phone"]="";
       $para["EditFlag"]=0;
       $para["ShopNO"]=$data["ShopNO"];
       $stat=$this->Procedure->sp_Import_Customer($para);
       $new=$this->where(array('CustomerNO' => $data['CustomerNO']))->find();
       if($stat["stat"]==1&&$new){
           return suc($new["CustomerID"]);
       }else{
           return err(3000,"插入错误".$stat['errmsg']);
       }
    }

    private function _validate(&$data) {
        $field = 'CustomerID,CustomerNO,ParentID,ParentNO,RecommendID,RecommendNO,Depth,Depth_r';
        //CustomerNO是否已经存在
        $Customer = $this->where(array('CustomerNO' => $data['CustomerNO']))->count();
        if ($Customer > 0) {
            return err(0,'CustomerNO 系统已经存在，不能重复添加');
        }
        $Recommend = $this->getByWhere(array('CustomerNO' => $data['RecommendNO']), $field, 1);
        if (empty($Recommend)) {
            return err(0,'Recommend Code系统不存在');
        }
        $Recommend = $Recommend[0];
        $Sponsor = $this->getByWhere(array('CustomerNO' => $data['ParentNO']), $field, 1);
        if (empty($Sponsor)) {
            return err(0,'Sponsor Code系统不存在');
        }
        $temlow = $Sponsor = $Sponsor[0];
        $Sponsor_info= model("CustomerInfo")->getBywhereOne(["CustomerID"=>$Sponsor["CustomerID"]]);
        $Recommend_info= model("CustomerInfo")->getBywhereOne(["CustomerID"=>$Recommend["CustomerID"]]);
        while (!empty($temlow)) {
            if ($temlow['ParentID'] == $Recommend['CustomerID']||$temlow['ParentID'] == $Recommend['ParentID']) {
                $data['ParentID'] = $Sponsor['CustomerID'];
                $data['RecommendID'] = $Recommend['CustomerID'];
                $data['ParentName'] = $Sponsor_info['CustomerName'];
                $data['RecommendName'] = isset($Recommend_info['CustomerName'])? $Recommend_info['CustomerName']:"";               
                $data['Depth'] = $Sponsor['Depth'] + 1;
                $data['Depth_r'] = $Recommend['Depth_r'] + 1;
                $data['RegDate'] = date('Y-m-d H:i:s');
                $data['AddDate'] = date('Y-m-d H:i:s');
                return suc();
            } else {
                $temlow = $this->getByWhereOne(array('CustomerID' => $temlow['ParentID']), $field);
                //$temlow = $temlow[0];
            }
        }
        return err(0,'Sponsor Code 不是 PlaceMent Code 下线，无法录入！');
    }
    public function editById($data,$id){
        $res=$this->_validateedit($data);
        if ($res['stat'] == '0') {
            return $res;
        }
        $res = parent::editById($data, $id);
        return $res;
        
    }
    private function _validateedit(&$data) {
        if(isset($data["RecommendNO"])&&isset($data["RecommendNO"])){
            
        }else{
            return true;
        }
        $field = 'CustomerID,CustomerNO,ParentID,ParentNO,RecommendID,RecommendNO,Depth,Depth_r';
        $Recommend = $this->getByWhere(array('CustomerNO' => $data['RecommendNO']), $field, 1);
        if (empty($Recommend)) {
            return err(0,'Recommend Code系统不存在');
        }
        $Recommend = $Recommend[0];
        $Sponsor = $this->getByWhere(array('CustomerNO' => $data['ParentNO']), $field, 1);
        if (empty($Sponsor)) {
            return err(0,'Sponsor Code系统不存在');
        }
        $temlow = $Sponsor = $Sponsor[0];
        $Sponsor_info= model("CustomerInfo")->getBywhereOne(["CustomerID"=>$Sponsor["CustomerID"]]);
        $Recommend_info= model("CustomerInfo")->getBywhereOne(["CustomerID"=>$Recommend["CustomerID"]]);
        while (!empty($temlow)) {
            if ($temlow['ParentID'] == $Recommend['CustomerID']||$temlow['CustomerID'] == $Recommend['CustomerID']) {
                $data['ParentID'] = $Sponsor['CustomerID'];
                $data['RecommendID'] = $Recommend['CustomerID'];
                $data['ParentName'] = $Sponsor_info['CustomerName'];
                $data['RecommendName'] =  isset($Recommend_info['CustomerName'])? $Recommend_info['CustomerName']:""; 
                $data['Depth'] = $Sponsor['Depth'] + 1;
                $data['Depth_r'] = $Recommend['Depth_r'] + 1;
                $data['LastEditTime'] = date('Y-m-d H:i:s');
                return suc();
            } else {
                $temlow = $this->getByWhereOne(array('CustomerID' => $temlow['ParentID']), $field);
            }
        }
        return err(0,'Sponsor Code 不是 PlaceMent Code 下线，无法录入！');
    }

}
