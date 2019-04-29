<?php

namespace app\desktop\controller;
use app\desktop\controller\Common;
use think\Db;
class KediCommission extends Common {
    public $GID;
    public $realdate;
    public $oper_name;
    public $ShopNO;
    public $TellarMoney;
    public $RegionMoney=0;
    public $DeductSc=0;
    public $Debt=0;
    public $Commission=0;
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->FrontDeskCash = model("FrontDeskCash");
        $this->KediCommission = model("KediCommission");
    }


    public function UpdateCommission() {
        $input = input("post.");
        $data_cash=$this->KediCommission->Get_count($input["date_year"],$input["date_month"],$input["region"]);
        if($data_cash){
            
        }else{
            $data["Region"]=$input["region"];
            $data["TotalMoney"]=1;
            $data["DeductMoney"]=1;
            $data["Debit"]=1;
            $data["Commission"]=1;
            $data["Date_Year"]=1;
            $data["Date_Month"]=1;
            $data["Memo"]=1;
            $this->KediCommission->add($data);
        }
        return err(9000, "没有数据");
    }
    public  function GetCommission(){
                      $input = input("post.");
                      $data=$this->SaleEnteredByFront->search($input["from"],$input["to"],$input["area"]);
                      foreach($data as $v){
                          $this->RegionMoney+=$v["Achievement"];
                          $this->DeductSc+=$v["Achievement"];
                          $this->Debt+=$v["Debit"];
                          $this->Commission+=($v["Achievement"]-$v["Debit"])*0.01;
                      }
        
    }
    //搜索全部佣金
    public  function search(){
        $input = input("post.");
        $data=$this->KediCommission->search($input["date_year"],$input["date_month"]);
        return suc($data);
    }
    //打印
    public  function print_excel(){
        
    }
    //打印
    public  function print_excel_out(){
        
    }
}
