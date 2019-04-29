<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;
class StockOut extends Base {

    protected $table = 'StockOut';
    protected $pk = 'StockOutID';
    public function add($data){
        $data["StockOutID"]=$this->GetNewStockOutID();
         return parent::addItem($data);
    }
    public function GetRecieveInfo($CustomerNo, $mark){
            if (empty($CustomerNo))
                return " ";
            else
            {
                if (strlen($CustomerNo) != 8)
                {
                    if ($mark == "0")
                        return "0";
                    else
                        return $CustomerNo;
                }
                else
                {
                    if ($mark == "1")
                        return $CustomerNo;
                    else if ($mark == "0")
                        return $this->GetCustomerID($CustomerNo);
                    else return $this->GetCustomerName($CustomerNo);
                }
            }
        
    }
    public function GetCustomerID($CustomerNo){
        
        $cust=model("Customer")->getByWhereOne(["CustomerNO"=>$CustomerNo]);
        if(empty($cust)){
            return 0;
        }
        return $cust["CustomerID"];
    }
        public function GetCustomerName($CustomerNo){
        $id=$this->GetCustomerID($CustomerNo);
        $cust=model("CustomerInfo")->getByWhereOne(["CustomerID"=>$id]);
        if(empty($cust)){
            return "Nothing";
        }
        return $cust["CustomerName"];
    }
    public function GetNewStockOutID(){
        
        $sql = "select isnull(max(stockoutid),0)+1 as newid from stockout";
        $data=$this->query($sql);
        return $data[0]["newid"];
    }
    public function  SetMarkStandforInto($groupid){
        $map["GroupID"]=$groupid;
        $map["Status"]=["lt",2];
        $data["Status"]=1;
        return $this->editByMap($data,$map);
        
        
    }

}
