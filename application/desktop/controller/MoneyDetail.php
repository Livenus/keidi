<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class MoneyDetail extends Common {

    public $user_admin=[
                    "funmi",
            "peter",
            "李云"
    ];
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        
    }
    public function load(){
        $data['user']=$this->user_admin;
        return suc($data);
    }

    public function Search() 
        {
        $rule=[
            "Date"=>"require",
            "user"=>"require"
            
        ];
        $check=$this->validate($this->input, $rule);
        if($check!==true){
            return err(9000,$check);
        }
            $sql = "SELECT Sc_code as Collector,PayMoney,createtime as Date,OperMan as Oper,GroupID,IP,Mac from dpbv_products where createtime='".$this->input['Date']."' and operman='".$this->input['user']."'";
            $data=$this->query_Sql($sql);
            $this->GetTotal($data);
            $rep["list"]=$data;
            $rep["input"]=$this->input;
            return suc($rep);
        }
       private function GetTotal($data)
        {
            $total = 0;
            foreach ($data as $v)
            {
                $total += $v['PayMoney'];
                
            }
            $this->input['sum']=$total;
        }
}
