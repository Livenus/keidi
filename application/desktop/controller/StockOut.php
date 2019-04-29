<?php

namespace app\desktop\controller;
use app\desktop\controller\Common;
use think\Db;
class StockOut extends Common {
    public $GID;
    public $realdate;
    public $oper_name;
    public $ShopNO;
    public $TellarMoney;
    public $CashMoney;
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->FrontDeskCash = model("FrontDeskCash");
        $this->SaleEnteredByFront = model("StockOut");
    }


    public function GoWareHouse() {
        $input = input("post.");
        $this->ProcessChangeTeller($input["ShopNO"]);
        $detail=$this->FDTellerdetail->getByWhere(["GroupID"=>$input["GroupID"]]);
        $total=$this->FDTellerdetail->sum(["GroupID"=>$input["GroupID"]]);
        $RegionMoney=$this->FDTellerdetail->GetRegionMoney($input["ShopNO"],$input["Region"]);
        $banlance=$this->FDTeller->getByWhereOne(["ShopNO"=>$input["ShopNO"]]);
        $shopreg=$this->TellerAccountReg->getByWhereOne(["regserviceno"=>$input["ShopNO"]]);
         $data["detail"]=$detail;
         $data["total"]=$total;
         $data["banlance"]=$banlance;
         $data["shopreg"]=$shopreg;
         $data["RegionMoney"]=$RegionMoney;
        $data["TellerType"]=$this->GetTellerType();
        $data["req"]=$input;
         if($data){
             return suc($data);
         }
        return err(9000, "没有数据");
    }
}
