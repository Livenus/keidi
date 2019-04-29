<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;
class FDTellerDetail extends Base {

    protected $table = 'FD_TellerDetail';
    protected $pk = 'TellerDetailID';
    
    
    public function sum($map){
        
        $sum=$this->where($map)->sum("used");
        return $sum+0;
    }
    public  function GetRegionMoney($shopno,$region){
                $CommandText = "select isnull(sum(deposit)-sum(used),0) as amount  from fd_tellerdetail where shopno='".$shopno."' and region='".$region."' and isnull(freezeMark,0)<>'1'";
                $data=$this->query($CommandText);
                return $data[0]["amount"];
    }
    public function getByWhere($where, $field = '*', $limit = "0,100", $order = "",$fitter=""){
        $data= parent::getByWhere($where);
        foreach($data as $k=>$v){
            $vvv=[];
             foreach($v as $kk=>$vv){
                 $kk= iconv("GBK", "UTF-8", $kk);      
                 $vvv[$kk]=$vv;
             }
             $data[$k]=$vvv;
          
        }
        return $data;
    }
}
