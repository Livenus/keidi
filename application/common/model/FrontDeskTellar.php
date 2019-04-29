<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;
class FrontDeskTellar extends Base {

    protected $table = 'FrontDesk_Tellar';
    protected $pk = 'GroupID';

    public function add($data) {
        $data["SaleDetailID"]=$this->_setSaleDetailID();
        $res = Db::table($this->table)->insert($data);
        if($res){
           return suc($data); 
        }
        return err("出错了".$res);
    }
    public function  sum_amount($groupid){
        $sqlcmd="select sum(amount) as amount from FrontDesk_Tellar where groupid='{$groupid}'";
        $data=$this->query($sqlcmd);
        return $data[0]["amount"]+0;
    }

}
