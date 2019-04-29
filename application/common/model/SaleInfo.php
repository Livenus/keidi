<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;
class SaleInfo extends Base {

    protected $table = 'tb_SaleInfo';
    protected $pk = 'SaleID';

    public function add($data) {
            return parent::addItem($data);
    }
    public function  Get_Order_Info($groupid){
        $sqlorder = "select saleno as 报单编号,returnman as 会员编号,a.shopno as 专卖店,a.totalpv as 总BV,a.totalretail as 总PV,a.Totalmember as 总金额,buydate as 购买日期,SaleID from tb_sale a where  a.status='0' and saleid in(select saleid from tb_sale_entered_Byfrontdesk where groupid='" .$groupid. "') order by saleid desc";
        $data=$this->query($sqlorder);
        if(empty($data)){
            return false;
        }
        $data1=$data[0];
        foreach($data1 as $k=>$v){
            $kk= iconv("GBK", "UTF-8", $k);
            $data[$kk]=$v;
        }
        return $data1;
    }

}
