<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;
class KediCommission extends Base {

    protected $table = 'Kedi_Commission';
    protected $pk = 'ComID';

    public function add($data) {
        $data["ComID"]=$this->GetNewCID();
       
        return parent::addItem($data);
    }
    public function  sum_amount($groupid){
        $sqlcmd="select sum(amount) as amount from FrontDesk_Cash where groupid='{$groupid}'";
        $data=$this->query($sqlcmd);
        return $data[0]["amount"]+0;
    }
    public  function Get_count($date_year,$date_month,$region){
           $sql = "select count(*) as c from kedi_commission where date_year=".$date_year." and date_month=".$date_month." and region='".$region."'";
           $data=$this->query($sql);
            return $data[0]["c"];
    }
    public function GetNewCID(){
        $sql = "select isnull(max(comID),0)+1 as newid from kedi_commission";
        $data=$this->query($sql);
        return $data[0]["newid"];
    }
    public function search($date_year,$date_month){
                        $sql = "select region as 'region','nairas'as 'nairas',commission as date_year,LastCommission as date_month,Rate as 'growth' from kedi_commission where date_year=" .$date_year. " and date_month=".$date_month;
           return $this->query($sql);
                        
    }

}
