<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;
class Region extends Base {

    protected $table = 'RegionInfo';
    protected $pk = 'RegionID';

    public function add($data) {
        $data["SaleDetailID"]=$this->_setSaleDetailID();
        $res = Db::table($this->table)->insert($data);
        if($res){
           return suc($data); 
        }
        return err("出错了".$res);
    }
    public function  GetRegion(){
        $sqlcmd="select region as result from regioninfo";
        $data=$this->query($sqlcmd);
        return $data;
    }

}
