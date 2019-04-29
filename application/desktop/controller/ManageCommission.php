<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class ManageCommission extends Common {
    public $Bank =["ACCESS", "NEWFIRST",
            "OLDFIRST",
            "SKYE",
            "UNION",
            "ZENITH",
            "ALL"];
        public $CommissionID="";
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        
    }

    public function button1_Click()
        {
                    $searchsql = "select distinct tb_sale_entered_byfrontdesk.realdate from tb_sale_entered_byfrontdesk,frontdesk_report where tb_sale_entered_byfrontdesk.groupid=frontdesk_report.groupid and tb_sale_entered_byfrontdesk.realdate>='" .$this->input['From']. "' and tb_sale_entered_byfrontdesk.realdate<='" .$this->input['To'].  "' and area='" .$this->input['Regional'].  "' order by tb_sale_entered_byfrontdesk.realdate";
                   $data=$this->query_Sql($searchsql);
                   return suc($data);
        }
        public function dataGridView1_CellClick()
        {

          $data=$this->GetShopCommission($this->input['realdate']);

                return suc($data);
        }
        private function GetShopCommission($DateforRegion)
        {

                $sql= "select ShopNo,totalmoney,Area,Realdate from frontdesk_report where area='".$this->input['Regional']."' AND realdate>='".$DateforRegion."'and realdate<='".$DateforRegion."' and reporttype='SC'";

             $data=$this->query_Sql($sql);
             return $data;
        } 

}
