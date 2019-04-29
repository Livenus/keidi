<?php

namespace app\desktop\controller;
use app\desktop\controller\Common;
use think\Db;
class StockOutCancel extends Common {
        public  $ReportNO = ""; 
        public  $TransferValue = "";
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
    }

    public function Search()
        {
         $rule=[
             "From"=>"require",
             "To"=>"require",
         ];
         $check=$this->validate($this->input, $rule);
         if($check!==true){
             return err(9100,$check);
         }
            $list=$this->GetRegionNO();
            $data['count']=count($list);
            $data['list']=$list;
            return suc($data);
        }
        public function GetRegionNO()
        {
            $sql = "select distinct Groupid,OutDate,SaleType,ShopNo from stockout where status='2' and saletype<>'移库' and outdate<='" .$this->input['To']. "' and outdate>='"  .$this->input['From']. "' order by OutDate desc,saletype";
            return $this->query_Sql($sql);
        }
        public function SearchRegion()
        {
         $rule=[
             "RegionReportNo"=>"require",
         ];
         $msg=[
             "RegionReportNo"=>"请输入汇总编号！",
         ];
         $check=$this->validate($this->input, $rule,$msg);
         if($check!==true){
             return err(9100,$check);
         }
                if ($this->CheckIfFromRegionTogether())
                {
                  return err(9000,"该编号属于区域店提货，所属区域店编号：" .$this->GetStringData("select regiontogether from stockout where groupid='" .$this->input['RegionReportNo'].  "'"));
                }
                else
                {
                    if ($this->CheckIfAllRegionSCisTheSameStatus())
                    {
                        $ReportNO =$this->input['RegionReportNo'];
                        $data=$this->LoadStockOutInfo();
                        return suc($data);
                    }
                }
            

        }
        private function CheckIfFromRegionTogether()
        {
            $sql = "select isnull(regiontogether,'') from stockout where groupid='" .$this->input['RegionReportNo']. "'";
            $regiontogether = $this->GetStringData($sql);
            if ($regiontogether == "")
                return false;
            else return true;
        }
        private function CheckIfAllRegionSCisTheSameStatus()
        {
            $sql = "select distinct status from stockout where regiontogether='" .$this->input['RegionReportNo'].  "'";
            $data=$this->query_Sql($sql);
            if (count($data) > 1)
                return false;
            else return true;
        }
        private function LoadStockOutInfo()
        {
            $sql = "select ProductNO,[name] as PName,a.Amount as Sale,a.realamount as StockOut,a.backorderamount as BackOrder from (select productid,amount,realamount,backorderamount from stockoutdetailreal where groupid='".$this->input['RegionReportNo'] ."') a,StockProduct_Material where a.productid=StockProduct_Material.productid order by productno";
           return $this->query_Sql($sql);
        }
        public function Cancel()
        {
         $rule=[
             "RegionReportNo"=>"require",
         ];
         $msg=[
             "RegionReportNo"=>"请输入汇总编号！",
         ];
         $check=$this->validate($this->input, $rule,$msg);
         if($check!==true){
             return err(9100,$check);
         }  
                if ($this->CheckIfStockClose($this->input['From'], "kedistock"))
                    return err(9000,"The date you operate is already completed!");
                else
                {
                    if ($this->Cancel_r())
                    {
                        return suc("撤销成功！You already canceled it!");
                    }
                }
            

        }
        private function  Cancel_r() 
        {
            $result=true ;
        if ($this->RomoveFromKediStock())
        {

            $sql = "delete from stockoutdetailreal where groupid='" . $this->input['RegionReportNo']  . "'";
           if($this->Exc_Sql($sql)>0)
           {
               $sql = "  delete from kedi_backorder where groupid='" . $this->input['RegionReportNo'] . "'";
            if ($this->Exc_Sql($sql) > -1)
            {
                $sql = "  update stockout set status='1' where groupid='" . $this->input['RegionReportNo'] . "' or regiontogether='" . $this->input['RegionReportNo'] . "'";
                 if ($this->Exc_Sql($sql) > 0)
                     $result = true;
            }
           }
        }
        else $result = false;

        return result;
        }
        private function RomoveFromKediStock()
        {
            $result = true;
            $InsertSql = "update " . $this->GetStockNameByGID($this->input['RegionReportNo']) . " set Outamount=isnull(Outamount,0)-(select realamount from stockOutdetailreal where groupid ='" . $this->input['RegionReportNo'] . "' and productid=" .$this->GetStockNameByGID($this->input['RegionReportNo']) . ".productid) where productid in(select productid from stockOutdetailreal where "
               . "groupid='" . $this->input['RegionReportNo'] . "' and '2'=(select min(status) from stockout where  groupid='" . $this->input['RegionReportNo'] . "' or regiontogether='" . $this->input['RegionReportNo'] . "')) ";
            if ($this->Exc_Sql($InsertSql) < 1)
            {
                exception("撤销失败,即将回滚系统,请重新审核！Unable to Cancel,Pls contact with Eric!");
                $result = false;
            }
            else
            {
                $sql = "update kedistock set amount=isnull(inamount,0)-isnull(outamount,0) ";
                if ($this->Exc_Sql($sql) < 1)
                {
                    exception("Error:" . $sql);
                    $result = false;
                }
            }
            return $result;
        }
        private function GetStockNameByGID($GID)
        {
            $sql = "select stockname from stocklist where stockid=(select outstocknameid from stockout where status='2' and groupid='".$GID ."')";
            $stocnname = $this->GetStringData($sql);
            if (trim($stocnname)== "")
                exception("Error  Call Eric!Sql:".$sql );
            return $stocnname;
        }
}
