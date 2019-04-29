<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class ManageStockOutType extends Common {
//初始化
public function _initialize() {
parent::_initialize();
$this->input = input("post.");
$this->StockType=model("StockType");

}
public function load(){
$data['Table'] = $this->Table;
$data['field'] = $this->field;
$data['bank'] = $this->bank;
return suc($data);
}
public  function Search()
        {
    $map=[];
    if(isset($this->input["TypeName"])&&!empty($this->input["TypeName"])){
            $map["TypeName"]=["like","%{$this->input['TypeName']}%"];
        
    }
   if(isset($this->input["Memo"])&&!empty($this->input["Memo"])){
           $map["Memo"]=$this->input["Memo"];
   }

    $data=$this->StockType->getByWhere($map);
return suc($data);
}
public  function StartUse()
        {
                $rule=[
                "Status"=>"require|between:0,1",
                "StockTypeID"=>"require|length:1,100"
            ];
            $check=$this->validate($this->input, $rule);
            if($check!==true){
                
                return err(9000,$check);
            }
          $data["Status"]=$this->input["Status"];
          $data["TypeName"]=$this->input["TypeName"];
          $data["TypeName_E"]=$this->input["TypeName_E"];
          $data["Memo"]=$this->input["Memo"];
          $data["Dept"]=$this->input["Dept"];       
         $old=$this->StockType->getByWhereOne(["StockTypeID"=>$this->input["StockTypeID"]]);
         if($old){
                       $data["LastEditDate"]=date("Y-m-d H:i:s");
                                   $data=setarray($data);
                       $status=$this->StockType->editById($data,$old["StockTypeID"]);
                       
                       return suc("修改".$status["data"]);
         }
        }
        public function AddStockOutType()
        {
            $rule=[
                "TypeName"=>"require|length:1,100",
                "Memo"=>"require|length:1,100",
                "Dept"=>"require|length:1,100",
            ];
            $check=$this->validate($this->input, $rule);
            if($check!==true){
                
                return err(9000,$check);
            }
          $data["TypeName"]=$this->input["TypeName"];
          $data["TypeName_E"]=$this->input["TypeName_E"];
          $data["Memo"]=$this->input["Memo"];
          $data["Dept"]=$this->input["Dept"];

          
         $old=$this->StockType->getByWhereOne(["TypeName"=>$this->input["TypeName"]]);
         if($old){
                       $data["LastEditDate"]=date("Y-m-d H:i:s");
                       $status=$this->StockType->editById($data,$old["StockTypeID"]);
                       return suc("修改".$status["data"]);
         }else{
                      $data["Oper"]=self::$realname;
                      $data["StockTypeID"]=$this->GetNewTypeID();
                      $data["CreateTime"]=date("Y-m-d H:i:s");
                      $data["Status"]=0;
                       $status=$this->StockType->addItem($data);
         }

          if ($status["stat"])
               return suc("添加成功！");
            else return err(9000,"Error:Sql:".$status["errmsg"] );

        }
        private function GetNewTypeID() 
        {
            $sql = "select isnull(max(stocktypeid),0)+1 as NewID from stocktype";
            $NewID = $this->GetStringData($sql );
            return $NewID;
        }
}
