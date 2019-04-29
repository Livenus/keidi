<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class TellerSearch extends Common {

    public $PrintContain;
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
    public function RegionBalanceUnderShop()
        {
             $rule=[
                 "account"=>"require"
                 
             ];
            $check=$this->validate($this->input, $rule);
           if($check!==true){
               return err(9000,$check);
               
           }
          $AccountWithdrawalSearch = "select Region,sum(deposit)-sum(used) as 'SC" .$this->input['account']. "' from fd_tellerdetail where  shopno='"  .$this->input['account']. "' group by region order by region";

           $data=$this->query_Sql($AccountWithdrawalSearch);
           return suc($data);
        }
        public function button9_Click()
        {
             $rule=[
                 "account"=>"require",
               "From"=>"require",
               "To"=>"require",
             ];
            $check=$this->validate($this->input, $rule);
           if($check!==true){
               return err(9000,$check);
               
           }
            $sql = "select ShopNO,Deposit,Pay,''as Balance,Bank,Region,[Date],Oper,Note,用途,所属期,GroupID,ID,isnull(充值系统ID,' ') as 充值系统ID from(select shopno as 'ShopNO',deposit as 'Deposit',"
                . "0 as Pay,Bank,Region,[Date],Oper,memo1 as Note,用途,所属期,GroupID,tellerdetailid as 'ID',systemtellerID as '充值系统ID'  from fd_tellerDetail " .
"where deposit<>0 and [date]>='".$this->input['From']."' and [date]<='".$this->input['To']."' and shopno='".$this->input['account']."' ".
"union all"
. " select shopno as 'ShopNO',0 as Deposit,used as 'Pay',Bank,Region,[Date],Oper,memo1 as Note,用途,所属期,GroupID,tellerdetailid as 'ID',systemtellerID as '充值系统ID'  from fd_tellerDetail" .
" where used<>0 and [date]>='" .$this->input['From']. "' and [date]<='" .$this->input['To']. "' and shopno='".$this->input['account']."') a order by [date],ID ";
           $data=$this->query_Sql($sql);
           $data= utf_8($data);
           return suc($data);
        }
        public function button3_Click()
        {
             $rule=[
                 "account"=>"require"
                 
             ];
            $check=$this->validate($this->input, $rule);
           if($check!==true){
               return err(9000,$check);
               
           }
           Db::startTrans();
           try {
                $this->ProcessChangeTeller($this->input['account']);
                $this->PrintContain = "AccountDetail";
                $this->GetSearchResult();
                $data=$this->LoadononeShopViewfromTellerUsedInfo(); 
                Db::commit();
                return suc($data);
           } catch (\Exception $exc) {
               Db::rollback();
               return err(9000,$exc->getMessage());
           }


        }
        private function GetSearchResult()
        {

            $sql = "delete from fd_tellerusedinfo";
            $status=$this->Exc_Sql($sql);
            $sql = "insert FD_TellerUsedInfo (账户) select distinct shopno from fd_tellerdetail where [date]>='" .$this->input['From']. "' and [date]<='" .$this->input['To'].  "'";
            $status=$this->Exc_Sql($sql);
            if (is_numeric($status))//获取该日期区间的专卖店号
            {
                $this->GetEachUsedData("个人提款", "个人提款");
                $this->GetEachUsedData("业绩销售", "业绩销售");
                $this->GetEachUsedData("资料销售", "资料销售");
                $this->GetEachUsedData("借货押金", "借货押金");
                $this->GetEachUsedData("铺货押金", "铺货押金");
                $this->GetEachUsedData("开店押金", "开店押金");
                $this->GetEachUsedData("业绩还款", "业绩还款");
                $this->GetEachUsedData("其他还款", "其他还款");
                $this->GetEachUsedData("个人提款手续", "个人提款手续");
                $this->GetEachUsedData("现金存款手续", "现金存款手续");
                $this->GetFreezeMoney();         //获取冻结资金
                $this->GetDepositData();         //存款
                $this->UpdateFD_TellerUsedInfo();//获取各个值填充到表里
                $this->GetTempBanlance();
                $this->GetRealBanlance();
               
            }
        }
        private function GetEachUsedData($viewname,$itemname)//获取花销数据通用模板
        {
             $sql = "drop view " .$viewname. "";
            $status=$this->Exc_Sql($sql);
            $sql = "create view ".$viewname." as select shopno, sum(used) as a from (select * from fd_tellerdetail where 用途='" .$itemname. "' and [date]>='" .$this->input['From']. "' and [date]<='"  .$this->input['To']. "') a group by a.shopno ";
            $status=$this->Exc_Sql($sql);
           
        }
        private function GetFreezeMoney()
        {
             $sql ="drop view 冻结资金";
            $status=$this->Exc_Sql($sql);
            $sql = "create view 冻结资金 as select shopno, sum(Deposit) as a from (select * from fd_tellerdetail where isnull(freezemark,0)='1' and [date]>='"  .$this->input['From'].  "' and [date]<='"  .$this->input['To'].  "') a group by a.shopno ";
            $status=$this->Exc_Sql($sql);
        }
        private function GetDepositData()                                //获取存款数据模板
        {
             $sql ="drop view 存款 ";
             $status=$this->Exc_Sql($sql);
             $sql = "create view 存款 as select shopno, sum(deposit) as a from fd_tellerdetail where deposit<>0 and [date]>='"  .$this->input['From'].  "' and [date]<='"  .$this->input['To'].  "' group by shopno ";
            $status=$this->Exc_Sql($sql);
           
        }
        private function UpdateFD_TellerUsedInfo() 
        {
            $sql = "update FD_TellerUsedInfo  set "
                      .$this->SetSql("冻结资金")
                . "," .$this->SetSql("存款")
                . "," .$this->SetSql("个人提款")
                . "," .$this->SetSql("业绩销售")
                . "," .$this->SetSql("资料销售")
                . "," .$this->SetSql("借货押金")
                . "," .$this->SetSql("铺货押金")
                . "," .$this->SetSql("开店押金")
                . ",". $this->SetSql("业绩还款")
                . "," .$this->SetSql("个人提款手续")
                . "," .$this->SetSql("现金存款手续")
                . "," .$this->SetSql("其他还款");
              $status=$this->Exc_Sql($sql);
        }
        private function SetSql($itemname) 
        {
            $setsql = $itemname ."=(select a from ".$itemname." tt where tt.shopno=FD_TellerUsedInfo.账户)";
            return $setsql;
        }
        private function GetTempBanlance()
        {
            $sql = "update fd_tellerusedinfo set 余额=isnull(存款,0)-isnull(冻结资金,0)-isnull(业绩销售,0)-isnull(资料销售,0)" .
                  "-isnull(个人提款,0)-isnull(借货押金,0)-isnull(铺货押金,0)-isnull(开店押金,0)-isnull(业绩还款,0)-isnull(个人提款手续,0)-isnull(现金存款手续,0)-isnull(其他还款,0)";
            $status=$this->Exc_Sql($sql);
        }
        private function GetRealBanlance() 
        {
            $sql = " update fd_tellerusedinfo set 真正余额=(select banlance from fd_teller where fd_teller.shopno=fd_tellerusedinfo.账户)";
            $status=$this->Exc_Sql($sql);
        }
        private function LoadononeShopViewfromTellerUsedInfo()
        {
            $sql = "select 账户 ,isnull(存款,0) as 存款,isnull(冻结资金,0) as 冻结资金,isnull(业绩销售,0) as 业绩销售,isnull(资料销售,0) as " .
                   "资料销售,isnull(个人提款,0) as 个人提款,isnull(借货押金,0) as 借货押金," .
                   "isnull(铺货押金,0) as 铺货押金,isnull(开店押金,0) as 开店押金,isnull(业绩还款,0) as 业绩还款,isnull(个人提款手续,0) as 个人提款手续,isnull(现金存款手续,0) as 现金存款手续,isnull(其他还款,0) as 其他还款,isnull(余额,0) as 余额,真正余额  from fd_tellerusedinfo where 账户='" .$this->input['account']. "'";
    
            $data=$this->query_Sql($sql);
            return utf_8($data);
        }
        public function button4_Click()
        {
            $this->PrintContain = "";
            Db::startTrans();
            try {
            $this->GetSearchResult();
            $data["LoadonViewfromTellerUsedInfo"]=$this->LoadonViewfromTellerUsedInfo();//载入datagridview
            $this->Get_Total();
            $data['input']=$this->input;
            Db::commit();
            return suc($data);
            } catch (Exception $exc) {
                Db::rollback();
                return err(9000,$exc->getMessage());
            }



        }
        private function LoadonViewfromTellerUsedInfo()
        {
            $sql = "select 账户,isnull(存款,0) as 存款,isnull(冻结资金,0) as 冻结资金,isnull(业绩销售,0) as 业绩销售,isnull(资料销售,0) as " .
                  "资料销售,isnull(个人提款,0) as 个人提款,isnull(借货押金,0) as 借货押金," .
                  "isnull(铺货押金,0) as 铺货押金,isnull(开店押金,0) as 开店押金,isnull(业绩还款,0) as 业绩还款,isnull(个人提款手续,0) as 个人提款手续,isnull(现金存款手续,0) as 现金存款手续,isnull(其他还款,0) as 其他还款,isnull(余额,0) as 余额,真正余额 from fd_tellerusedinfo order by 账户";
            $data=$this->query_Sql($sql);
            return utf_8($data);
        }
        private function  Get_Total()
        {
          

                $sql= "select sum(isnull(存款,0)) as 存款,sum(isnull(业绩销售,0)) as 业绩销售,sum(isnull(资料销售,0)) as 资料销售,".
                                     "sum(isnull(个人提款,0)) as 个人提款,sum(isnull(借货押金,0)) as 借货押金,sum(isnull(铺货押金,0)) as 铺货押金,".
                                     "sum(isnull(开店押金,0)) as 开店押金,sum(isnull(业绩还款,0)) as 业绩还款,sum(isnull(其他还款,0)) as 其他还款,sum(isnull(余额,0)) as 余额,sum(真正余额) as 真正余额,sum(isnull(冻结资金,0)) as 冻结资金,sum(isnull(个人提款手续,0)) as 个人提款手续,sum(isnull(现金存款手续,0)) as 现金存款手续 from fd_tellerusedinfo";
                $data=$this->query_Sql($sql);
                $data=utf_8($data);
                $data=$data[0];
                $this->input['current_deposit'] = $data["存款"];
                $this->input['Sales_Performance'] = $data["业绩销售"];
                $this->input['sale_information'] = $data["资料销售"];
                $this->input['Personal_withdrawals'] = $data["个人提款"];
                $this->input['Lending_deposit'] = $data["借货押金"];
                $this->input['Shop_deposit'] = $data["铺货押金"];
                $this->input['Open_deposit'] = $data["开店押金"];
                $this->input['reimbursement'] = $data["业绩还款"];
                $this->input['reimbursement_other'] = $data["其他还款"];
                $this->input['Current_Balance'] = $data["余额"];
                $this->input['Real_balance'] = $data["真正余额"];
                $this->input['total_frozen'] = $data["冻结资金"];
                $this->input['total_withdrawals'] = $data["个人提款手续"];
                $this->input['withdrawal_fee'] = $data["现金存款手续"];


           
        } 
        public function button1_Click()
        {
            $this->input['toolStripMenuItem4.Enabled'] = false ;
            if ($this->input['item'] == "冻结资金")
                $data['SearchFreeze']=$this->SearchFreeze();
            else 
            $data['Search']=$this->Search();
          return suc($data);
        }
        private function SearchFreeze()
        {


            if ($this->input['account']== "")
            {
                if ($this->input['amount'] == "")
                    $sqlorder = "select TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 使用,[date] as [日期(Date)],'资金冻结'as 用途,memo1 as 备注,所属期,GroupID,Oper as 操作人  from fd_tellerdetail where isnull(freezemark,0)='1' and [date]>='" .$this->input['From']. "' and [date]<='" .$this->input['To'].  "'";
                else
                    $sqlorder = "select TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 使用,[date] as [日期(Date)],'资金冻结'as 用途,memo1 as 备注,所属期,GroupID,Oper as 操作人  from fd_tellerdetail where deposit=".$this->input['amount'].  " and isnull(freezemark,0)='1' and [date]>='".$this->input['From']."' and [date]<='" .$this->input['To']. "'";

            }
            else
            {
                if ($this->input['amount']  == "")

                    $sqlorder = "select TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 使用,[date] as [日期(Date)],'资金冻结'as 用途,memo1 as 备注,所属期,GroupID,Oper as 操作人  from fd_tellerdetail where shopno='" .$this->input['account'].   "' and isnull(freezemark,0)='1' and [date]>='" .$this->input['From']."' and [date]<='" .$this->input['To']. "'";
                else
                    $sqlorder = "select TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 使用,[date] as [日期(Date)],'资金冻结'as 用途,memo1 as 备注,所属期,GroupID,Oper as 操作人  from fd_tellerdetail where shopno='"  .$this->input['account'].  "' and deposit=" .$this->input['amount'].  " and isnull(freezemark,0)='1' and [date]>='"  .$this->input['From'].  "' and [date]<='"  .$this->input['To'].  "'";

            }
            mlog($sqlorder);
            $data=$this->query_Sql($sqlorder);
            return utf_8($data);
        }
        private function Search()
        {

           
            if ($this->input['account'] == "")
            {
                if ($this->input['amount'] == "")
                    $sqlorder = "select TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.used as 使用,[date] as [日期(Date)],用途,memo1 as 备注,所属期,GroupID,Oper as 操作人  from fd_tellerdetail where 用途='" .$this->input['account'] . "'and [date]>='" .$this->input['From'] . "' and [date]<='" .$this->input['To'] . "'";
                else
                    $sqlorder = "select TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.used as 使用,[date] as [日期(Date)],用途,memo1 as 备注,所属期,GroupID,Oper as 操作人  from fd_tellerdetail where used=" .$this->input['amount'] . " and 用途='"  .$this->input['account'] . "'and [date]>='" .$this->input['From'] .  "' and [date]<='" .$this->input['To'] .  "'";

            }
            else
            {
                if ($this->input['amount'] == "")

                    $sqlorder = "select TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.used as 使用,[date] as [日期(Date)],用途,memo1 as 备注,所属期,GroupID,Oper as 操作人  from fd_tellerdetail where shopno='"  .$this->input['account'] . "' and 用途='"  .$this->input['account'] . "'and [date]>='" .$this->input['From'] .  "' and [date]<='" .$this->input['To'] .  "'";
                else
                    $sqlorder = "select TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.used as 使用,[date] as [日期(Date)],用途,memo1 as 备注,所属期,GroupID,Oper as 操作人  from fd_tellerdetail where shopno='"  .$this->input['account'] . "' and used="  .$this->input['amount'] . " and 用途='" .$this->input['account'] .  "'and [date]>='" .$this->input['From'] .  "' and [date]<='" .$this->input['To'] . "'";

            }
            $sql=$this->ProcessDaily($sqlorder);
            $data=$this->query_Sql($sqlorder);
            return utf_8($data);
        }
        private function ProcessDaily($sql)
        {
            if ($this->input['Day'])
                return "select [日期(Date)],sum(使用) as 使用,'".$this->input['Day']."' as 用途 from (".$sql.") s group by [日期(Date)]";
            else return $sql;
        }
        public function button2_Click()
        {
            Db::startTrans();
            try {
            $this->PrintContain = "OnedayDeposit";
            $data['AccountDepositSearch']=$this->AccountDepositSearch();
            $this->GetDepositFromGridview($data['AccountDepositSearch']);
            $data['input']=$this->input;
            Db::commit();
            return suc($data);
            } catch (Exception $exc) {
                Db::rollback();
                return err(9000,$exc->getMessage());
            }
        }
        private function  AccountDepositSearch()
        {
            $sql = "drop table TempDepositTable";
             $status=$this->Exc_Sql($sql);
            if ($this->input['KEDI'])
            {
                if ($this->input['account'] == "")
                    $sql = "select shopno as '账户',deposit as '存款',Bank as '银行',region as 区域,[date] as '确认日期',Oper as '确认人',memo1 as 备注,tellerdetailid as '存款ID',memo2 as '付款方式','1900-01-01'as '存款日期',systemtellerid,用途 into TempDepositTable from fd_tellerDetail where deposit<>0 and isnull(bank,'')='KEDI' and [date]>='" .$this->input['From'] .  "' and [date]<='" .$this->input['To'] . "' order by shopno, tellerdetailid";
                else
                    $sql = "select shopno as '账户',deposit as '存款',Bank as '银行',region as 区域,[date] as '确认日期',Oper as '确认人',memo1 as 备注,tellerdetailid as '存款ID',memo2 as '付款方式','1900-01-01'as '存款日期',systemtellerid,用途 into TempDepositTable  from fd_tellerDetail where deposit<>0 and isnull(bank,'')='KEDI' and [date]>='" .$this->input['From'] . "' and [date]<='" .$this->input['To'] .  "' and shopno='" .$this->input['account'] . "' order by tellerdetailid";
            }
            else
            {
                if ($this->input['account'] == "")
                    $sql = "select shopno as '账户',deposit as '存款',Bank as '银行',region as 区域,[date] as '确认日期',Oper as '确认人',memo1 as 备注,tellerdetailid as '存款ID',memo2 as '付款方式','1900-01-01'as '存款日期',systemtellerid,用途 into TempDepositTable  from fd_tellerDetail where deposit<>0 and isnull(bank,'')<>'KEDI' and [date]>='" .$this->input['From'] . "' and [date]<='" .$this->input['To'] . "' order by shopno, tellerdetailid";
                else
                    $sql = "select shopno as '账户',deposit as '存款',Bank as '银行',region as 区域,[date] as '确认日期',Oper as '确认人',memo1 as 备注,tellerdetailid as '存款ID',memo2 as '付款方式','1900-01-01'as '存款日期',systemtellerid,用途 into TempDepositTable  from fd_tellerDetail where deposit<>0 and isnull(bank,'')='KEDI' and [date]>='" .$this->input['From'] . "' and [date]<='" .$this->input['To'] .  "' and shopno='" .$this->input['account'] . "' order by tellerdetailid";
            }
           $status=$this->Exc_Sql($sql);
            $sql = "update TempDepositTable set 付款方式='Teller转账' where isnull(付款方式,'0')<>'POS'";
             $status=$this->Exc_Sql($sql);
            $sql = "update TempDepositTable set 存款日期=(select isnull(effective_date,'1900-01-01') from tb_systemteller where tellerid=TempDepositTable.systemtellerid) where 用途='系统'";
             $status=$this->Exc_Sql($sql);
            $sql = "update TempDepositTable set 存款日期=(select isnull(effective_date,'1900-01-01') from tb_tempteller where tellerid=TempDepositTable.systemtellerid) where 用途='临时'";
              $status=$this->Exc_Sql($sql);
            $AccountDepositSearch = "select 账户,存款,银行,区域,确认日期,确认人,备注,存款ID,付款方式,存款日期 from TempDepositTable where [账户]='454' and [存款]='500000'";

            $data=$this->query_Sql($AccountDepositSearch);
            return utf_8($data);
        }
        private function GetDepositFromGridview($data)
        {
            $nairasum = 0;
            foreach ($data as $v)
            {
                   $nairasum +=$v['存款'];
                
            }
            $this->input['current_deposit']= $nairasum;
        }
        public function button5_Click()
        {
            $this->PrintContain = "";
            $data=$this->AccountWithdrawalSearch();
            return suc($data);
        }
        private function AccountWithdrawalSearch()
        {
            if ($this->input['account'] == "")
                $AccountWithdrawalSearch = "select shopno as '账户',used as '取款',Bank as '银行',[date] as '日期',Oper as '确认人',memo1 as 备注,用途,所属期,region as '区域',GroupID,tellerdetailid as '取款ID' from fd_tellerDetail where used<>0 and [date]>='" .$this->input['From']."' and [date]<='" .$this->input['To']. "' order by shopno, tellerdetailid";
            else
                $AccountWithdrawalSearch = "select shopno as '账户',used as '取款',Bank as '银行',[date] as '日期',Oper as '确认人',memo1 as 备注,用途,所属期,region as '区域',GroupID,tellerdetailid as '取款ID' from fd_tellerDetail where used<>0 and [date]>='" .$this->input['From']. "' and [date]<='" .$this->input['To']. "' and shopno='"  .$this->input['account'].  "' order by tellerdetailid";

            $data=$this->query_Sql($AccountWithdrawalSearch);
            return utf_8($data);
        }
        public function button8_Click()
        {
            $data=$this->ShopBalanceUnderRegion();
            return suc($data);
        }
        private function ShopBalanceUnderRegion()
        {
           $AccountWithdrawalSearch = "select ShopNo,sum(deposit) as 存款,sum(used) as 取款,sum(deposit)-sum(used) as " .$this->input['Region']. " from fd_tellerdetail where  region='"  .$this->input['Region']. "' group by shopno order by shopno";

            $data=$this->query_Sql($AccountWithdrawalSearch);
            return utf_8($data);
        }


}
