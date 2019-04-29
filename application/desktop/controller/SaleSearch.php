<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class SaleSearch extends Common {

       public  $UpdateMark = 0;
       public $saleno = "";

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input=input('post.');
    }

    public function button2_Click()
        {
            $this->UpdateMark = 1;
            $data=$this->SearchReport();
            return suc($data);
        }
        private function SearchReport()
        {
            $searchsql = "select * from frontdesk_report where realdate>='" .$this->input['From']. "'and realdate<='" .$this->input['To'].  "' and  groupid like'%" .$this->input['GroupID'].  "'";
            $data=$this->query_Sql($searchsql);
            return $data;
        }
        public function button5_Click()
        {
            $group=$this->input['GroupID2'];
            if ($this->CheckIfBigSC($this->input['GroupID2']) > 0)
            {
                Db::startTrans();
                try {
                $data=$this->CancelBigSC();
                $this->ProcessChangeTeller(substr($group, -3,3));
                Db::commit();
                return $data;
                } catch (\Exception $exc) {
                    Db::rollback();
                   return err(9000,$exc->getMessage());
                }


                
            
            }
            else return err(9000,"您选中的专卖店ID不是合并后的ID!");
        }
        private function CheckIfBigSC($GId)
        {
            $count = 0;

                $CommandText = "select count(*) as count from frontdesk_report where belongedsc='" .$GId. "'";
                $count  = $this->GetStringData($CommandText);
            return $count;
        }
        private function CancelBigSC() {
        $sql = "delete from frontdesk_report where groupid='".$this->input['GroupID2']."'";
        $status=$this->Exc_Sql($sql);
        if (is_numeric($status))
        {
            $sql = "update frontdesk_report set reporttype='SC',belongedsc='None' where groupid in(select groupid from frontdesk_report where belongedsc='".$this->input['GroupID2']."')";
            $status=$this->Exc_Sql($sql);
            if (is_numeric($status))
            {
                return suc("撤销成功！");
                
            }

        }else 
            throw  new \Exception ("撤销失败！");
    }
    public function button3_Click()
        {
            if ($this->CheckIfRegionLock($this->input['GroupID']))
                return err(9000,"The GroupID " .$this->input['GroupID']." is already locked by Miss Alice!");
            else
            {
                Db::startTrans();
                try {
                $this->UpdateReport();
                Db::commit();
                return suc('Updated Successfully!');
                } catch (\Exception $exc) {
                    Db::rollback();
                   return err(9000,$exc->getMessage());
                }
            }
            
        }
        private function UpdateReport()
        {

            $UpdateSql = "update frontdesk_report set Area='" .$this->input['Region']. "',BelongedSC='"  .$this->input['BelongSC'].  "',kits='"  .$this->input['Kits'].  "',ReportType='"  .$this->input['Type'].   "' where Groupid='"  .$this->input['GroupID'].    "'";
            $this->Exc_Sql($UpdateSql);
            $UpdateSql = "update frontdesk_report set totalmoney=".self::$KitsValue."*kits+scnaira where Groupid='".$this->input['GroupID']. "'";
            $this->Exc_Sql($UpdateSql);
        }
        public function button1_Click()
        {


                Db::startTrans();
                try {
               $data=$this->Update1();
               Db::commit();
                return $data;
                } catch (\Exception $exc) {
                    Db::rollback();
                   return err(9000,$exc->getMessage());
                }

        }
        private function Update1() 
        { 
            $UpdateSql = "update TB_SALE_ENTERED_BYFRONTDESK set groupid='" .$this->input['GroupID4'].  "',current_status='1' where saleID=".$this->input['SaleID']. "";
            $status=  $this->Exc_Sql($UpdateSql);
            if (is_numeric($status))
            {
                if ($this->input['Status']== "3")
                {
                    $UpdateSql = "DELETE  FROM CALC_ERROR_DATA where saleno=" .$this->input['saleno']. "";
                    $status=  $this->Exc_Sql($UpdateSql);
                    if (is_numeric($status))
                        return suc("OK");
                }
                else if ($this->input['Status']== "2")
                {
                   
                    $UpdateSql = "DELETE  FROM Tb_sale where saleID="  .$this->input['SaleID'].  "";
                    $status=  $this->Exc_Sql($UpdateSql);
                    if (is_numeric($status))
                        return suc("OK");
                }
            }
        }
        public function toolStripMenuItem2_Click()
        {
            $this->UpdateMark = 0;
            $data=$this->Search();
            return suc($data);
        }
        private function Search() 
        {
            if ($this->GetDateFromGID($this->input['GroupID'])>="2016-07-27")
            $searchsql = "select SaleID,SaleNo,CustomerNo AS Code,a.ShopNo as SC,SaleDate,RealDate,Total_BV as BV,Total_PV as PV,Total_NAIRA as Naira,GroupID,Current_Status as Status,Oper_name as FD,Calc_Oper_Name as Calc from tb_sale_entered_byfrontdesk a where   realdate>='" .$this->input['From']. "'and realdate<='" .$this->input['To'].  "'  and groupid like'%" .$this->input['GroupID']. "' order by groupid,saleno";
           else
                $searchsql = "select SaleID,SaleNo,b.CustomerNo AS Code,a.ShopNo as SC,SaleDate,RealDate,Total_BV as BV,Total_PV as PV,Total_NAIRA as Naira,GroupID,Current_Status as Status,Oper_name as FD,Calc_Oper_Name as Calc from tb_sale_entered_byfrontdesk a,tb_customer b where a.customerno=b.customerid and  realdate>='" .$this->input['From']. "'and realdate<='" .$this->input['To'].  "'  and groupid like'%"  .$this->input['GroupID'].  "' order by groupid,saleno";
        
                   
            return $this->query_Sql($searchsql);
        }
        private function GetDateFromGID($GID)
        {

            $sql = "select realdate from frontdesk_report where groupid='".$GID."'";
            $date=$this->GetStringData($sql);
            if ($GID== "")
                $result = $this->input['To'];
            else 
            $result = $date;
            return $result;
        }
        public function button4_Click()
        {
            
                Db::startTrans();
                try {
               $data=$this->DeleteSale();
                 Db::commit();
                return $data;
                } catch (\Exception $exc) {
                    Db::rollback();
                   return err(9000,$exc->getMessage());
                }
        }
        private function DeleteSale()
        {
            
            $UpdateSql = "delete from TB_SALE_ENTERED_BYFRONTDESK where saleID='" .$this->input['SaleID']. "'";
             $status=  $this->Exc_Sql($UpdateSql);
            if (is_numeric($status))
            {
                $UpdateSql = "update frontdesk_report set scbv='" .$this->GetTotalBV() . "',scpv='" .$this->GetTotalPV() . "',scnaira='" .$this-> GetTotalNAIRA(). "' where Groupid='" .$this->input['GroupID4'].  "'";
              $status=  $this->Exc_Sql($UpdateSql);
                if (is_numeric($status))
                {
                    $UpdateSql = "update frontdesk_report set totalmoney=scnaira+kits*" .self::$KitsValue. " where Groupid='" .$this->input['GroupID4'].  "'";
                      $status=  $this->Exc_Sql($UpdateSql);
                    if (is_numeric($status))
                    {
                        $UpdateSql = "DELETE  FROM CALC_ERROR_DATA where saleno='" .$this->input['saleno'].  "'";
                        $status=  $this->Exc_Sql($UpdateSql);
                        $UpdateSql = "DELETE  FROM Tb_sale where saleid='" .$this->input['SaleID'].  "'";
                        $status=  $this->Exc_Sql($UpdateSql);
                       return suc("Delete it successfully and update the report!");
                    }
                }
            }
            else err(9000,"Already deleted before！");
        }
        private function GetTotalBV()
        {
            $sql = "select sum(Total_BV) as Total_BV from tb_sale_entered_byfrontdesk where groupid='" .$this->input['GroupID4']. "'";
            $BV = $this->GetStringData($sql);

            return $BV;
        }
        private function GetTotalPV()
        {
                $sql= "select sum(Total_PV) as Total_PV from tb_sale_entered_byfrontdesk where groupid='" .$this->input['GroupID4']. "'";
               $PV = $this->GetStringData($sql);


            return $PV;
        }
        private function GetTotalNAIRA()
        {

                $sql = "select sum(Total_NAIRA) from tb_sale_entered_byfrontdesk where groupid='" .$this->input['GroupID4']. "'";
               $NAIRA = $this->GetStringData($sql);
              return $NAIRA;
        }
}
