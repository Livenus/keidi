<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;
class SaleDetailByFront extends Base {

    protected $table = 'tb_SaleDetail_ByFrontDesk';
    protected $pk = 'SaleDetailID';

    public function add($data) {
        $data["SaleDetailID"]=$this->_setSaleDetailID();
        $res = Db::table($this->table)->insert($data);
        if($res){
           return suc($data); 
        }
        return err("出错了".$res);
    }
    public function  _setSaleDetailID(){
        $last=$this->order("SaleDetailID desc")->find();
        return $last["SaleDetailID"]+1;
    }

}
