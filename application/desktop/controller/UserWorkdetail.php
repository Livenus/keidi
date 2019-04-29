<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class UserWorkdetail extends Common {


        public $Bank =["ACCESS", "NEWFIRST",
            "OLDFIRST",
            "SKYE",
            "UNION",
            "ZENITH",
            "ALL"];

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        
    }

    public function UserWorkdetail_Load() {
            $data['Bank']= $this->Bank;
            $data['Name']=$this->LoadUser();
            return suc($data);
    }
        private function LoadUser()
        {
            $sql = "select ltrim(rtrim(realname)) as realname from enterapp_user where userid in(select userid from enterapp_userconnectrole where roleid in(select roleid from enterapp_role where rolename='前台出纳' or rolename='前台收银员'or rolename='前台主管'))";
            $data=$this->query_Sql($sql);
            return $data;
        }
    public function button1_Click()
        {
            $data['Get_ReportDetail_Mem_Info']=$this->Get_ReportDetail_Mem_Info();
            $data['Get_ReportDetail_ForUserWork']=$this->Get_ReportDetail_ForUserWork();
            return suc($data);
        }
        public function button2_Click()
        {
            $data=$this->GetTeller();
            return suc($data);
        }//从数据库得到各个汇总的cash,tellar信息
      private function GetTeller()
        {
            try
            {
                $TellerSql = "";
                $sqlorder = "";
                if ($this->input['Name']== "ALL")
                {
                    if ($this->input['Bank']== "ALL")
                    {
                        $sqlorder = "select frontdesk_tellar.groupid as GroupID,TellarId as Teller,Bank,Amount,frontdesk_tellar.Shopno as SC,frontdesk_tellar.realdate as [Date] from FrontDesk_Report,frontdesk_tellar where FrontDesk_Report.groupid=frontdesk_tellar.groupid and FrontDesk_Report.realdate>='" .$this->input['OldDate']. "' and FrontDesk_Report.realdate<='" .$this->input['NewDate'].  "'order by bank";
                        $TellerSql = "select sum(Amount) as Amount from FrontDesk_Report,frontdesk_tellar where FrontDesk_Report.groupid=frontdesk_tellar.groupid and FrontDesk_Report.realdate>='" .$this->input['OldDate'].  "' and FrontDesk_Report.realdate<='" .$this->input['NewDate'].  "'";

                    }
                    else
                    {
                        $sqlorder = "select frontdesk_tellar.groupid as GroupID,TellarId as Teller,Bank,Amount,frontdesk_tellar.Shopno as SC,frontdesk_tellar.realdate as [Date] from FrontDesk_Report,frontdesk_tellar where FrontDesk_Report.groupid=frontdesk_tellar.groupid and bank='"  .$this->input['Bank'].  "' and FrontDesk_Report.realdate>='" .$this->input['OldDate'].  "' and FrontDesk_Report.realdate<='" .$this->input['NewDate']. "'order by bank";
                        $TellerSql = "select sum(Amount) as Amount from FrontDesk_Report,frontdesk_tellar where FrontDesk_Report.groupid=frontdesk_tellar.groupid and bank='" .$this->input['Bank']. "' and FrontDesk_Report.realdate>='" .$this->input['OldDate'].  "' and FrontDesk_Report.realdate<='" .$this->input['NewDate']. "'";
                  
                    }
                }
                else
                {
                    if ($this->input['Bank'] == "ALL")
                    {
                        $sqlorder = "select frontdesk_tellar.groupid as GroupID,TellarId as Teller,Bank,Amount,frontdesk_tellar.Shopno as SC,frontdesk_tellar.realdate as [Date] from FrontDesk_Report,frontdesk_tellar where FrontDesk_Report.groupid=frontdesk_tellar.groupid and Oper_Name='" .$this->input['Name'].  "' and FrontDesk_Report.realdate>='" .$this->input['OldDate']. "' and FrontDesk_Report.realdate<='" .$this->input['NewDate'].  "'order by bank";
                        $TellerSql = "select sum(Amount) as Amount from FrontDesk_Report,frontdesk_tellar where FrontDesk_Report.groupid=frontdesk_tellar.groupid and Oper_Name='".$this->input['Name']. "' and FrontDesk_Report.realdate>='" .$this->input['OldDate'].  "' and FrontDesk_Report.realdate<='"  .$this->input['NewDate'].  "'";
                 
                    }
                    else
                    {
                        $sqlorder = "select frontdesk_tellar.groupid as GroupID,TellarId as Teller,Bank,Amount,frontdesk_tellar.Shopno as SC,frontdesk_tellar.realdate as [Date] from FrontDesk_Report,frontdesk_tellar where FrontDesk_Report.groupid=frontdesk_tellar.groupid and bank='"  .$this->input['Bank'].  "' and Oper_Name='" .$this->input['Name'].  "' and FrontDesk_Report.realdate>='" .$this->input['OldDate']. "' and FrontDesk_Report.realdate<='"  .$this->input['NewDate'].  "'order by bank";
                        $TellerSql = "select sum(Amount) as Amount  from FrontDesk_Report,frontdesk_tellar where FrontDesk_Report.groupid=frontdesk_tellar.groupid and bank='" .$this->input['Bank'].  "' and Oper_Name='" .$this->input['Name'].  "' and FrontDesk_Report.realdate>='" .$this->input['OldDate'].  "' and FrontDesk_Report.realdate<='"  .$this->input['NewDate']. "'";
                 
                    }
                }
                $data['frontdesk_tellar']=$this->query_Sql($TellerSql);
                $data['Amount']=$this->query_Sql($sqlorder);
                return $data;
            }
            catch (\Exception $ee) { }
        }
        public function button3_Click()
        {
            $data=$this->GetCash();
            return suc($data);
        }
        private function GetCash()
        {
            try
            {

                $sqlorder = "select frontdesk_cash.groupid as GroupID,cashId as Cash,Amount,frontdesk_cash.Shopno as SC,frontdesk_cash.realdate as [Date] from FrontDesk_Report,frontdesk_cash where FrontDesk_Report.groupid=frontdesk_cash.groupid and Oper_Name='"  .$this->input['Name'].  "' and FrontDesk_Report.realdate>='"  .$this->input['OldDate']. "' and FrontDesk_Report.realdate<='" .$this->input['NewDate'].  "'";
                $CashSql = "select sum(Amount) as Amount from FrontDesk_Report,frontdesk_cash where FrontDesk_Report.groupid=frontdesk_cash.groupid and Oper_Name='" .$this->input['Name']. "' and FrontDesk_Report.realdate>='" .$this->input['OldDate'].  "' and FrontDesk_Report.realdate<='" .$this->input['NewDate'].  "'";
         
                $data['frontdesk_cash']=$this->query_Sql($sqlorder);
                $data['Amount']=$this->query_Sql($CashSql);
                return $data;
            }
            catch (\Exception $ee) { }
        }
    private function Get_ReportDetail_ForUserWork()
        {
                if ($this->input['Name']=="ALL")
                     $sqlorder= "select sum(Kits) as Kits,sum(CashMoney) as CashMoney,sum(TellarMoney) as TellarMoney,sum(debitmoney) as debitmoney,sum(disc_trsfer) as disc_trsfer," .
                    "sum(cash_USD) as cash_USD,sum(TotalMoney) as TotalMoney,sum(scpv) as scpv,sum(scnaira) as scnaira from FrontDesk_Report where reporttype='SC' and realdate>='" .$this->input['OldDate']. "'and realdate<='" .$this->input['NewDate'].  "'";
                else 
                     $sqlorder= "select sum(Kits) as Kits,sum(CashMoney) as CashMoney,sum(TellarMoney) as TellarMoney,sum(debitmoney) as debitmoney,sum(disc_trsfer) as disc_trsfer," .
                    "sum(cash_USD) as cash_USD,sum(TotalMoney) as TotalMoney,sum(scpv) as scpv,sum(scnaira) as scnaira from FrontDesk_Report where reporttype='SC' and Oper_name='" .$this->input['Name']. "' "
                                                                               . " and  realdate>='" .$this->input['OldDate']. "'and realdate<='" .$this->input['NewDate']. "'";
             
               $data=$this->query_Sql($sqlorder);
               return $data;
        }
        private function Get_ReportDetail_Mem_Info()
        {
                if ($this->input['Name']== "ALL")              
                      $sqlorder = "select * from FrontDesk_Report where realdate>='" .$this->input['OldDate']. "' and realdate<='"  .$this->input['NewDate']. "'";               
                else $sqlorder = "select * from FrontDesk_Report where Oper_Name='" .$this->input['Name']. "' and realdate>='".$this->input['OldDate']. "' and realdate<='" .$this->input['NewDate'].  "'";
             
               $data=$this->query_Sql($sqlorder);
               return $data;
        }
  
}
