<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class MaterialSearch extends Common {


        public  $mark = "0";
        private $PrintMark = 0;
        private $RowHeight = 20;
        private $PrintIndex;     //当前页码
        private $dataIndex;      //打印开始的数据行
        private $Count = 0;
        private $Count_AddColor = 0;
        public $CommissionID="";
         public $dataGridView1="";
         public $Name='MaterialSearch';
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        
    }
     public  function button2_Click()
        {
            $rule=[

                "Date"=>"require",
                
            ];
            $check=$this->validate($this->input, $rule);
            if($check!==true){
                return err(9000,$check);
            }
            try {
            $this->GetDailyCash_Teller($this->input['Date']);
            $data['GetMaterialReportDaily1']=$this->GetMaterialReportDaily1($this->input['Date']);
            $data['input']=$this->input;
            return suc($data);   
            } catch (\Exception $exc) {
                return err(9000,$exc->getMessage());
            }



        }
        private function GetDailyCash_Teller($SDate) 
        {

            $sql = "select sum(cash)  as cash from bonuscheck.dbo.materialform where status='1' and date='".$SDate."'";
            $this->input['Cash']=$this->GetStringData($sql);
            $sql = "select sum(teller) as teller from bonuscheck.dbo.materialform where status='1' and date='" .$SDate. "'";
            $this->input['Cash']= $this->GetStringData($sql);
        }
        private function GetMaterialReportDaily1($SDate)
        {
                $sql = "select ProductNo as Item,Name as Products,str(memberprice) as [U/Price NR],Qty,str(money) as [Total NR] from(select MaterialID,sum(Qty) as Qty,sum(Money) as Money from bonuscheck.dbo.materialFormDetail where formid in"
                   . "(select formid from bonuscheck.dbo.materialForm where date='" .$SDate. "') group by materialid) a,stockProduct_material b where a.MaterialID=b.productid";
                $data=$this->query_Sql($sql);
                 return $data;

        }
        public function button4_Click()
        {
            $rule=[
                "GID"=>"require",
            ];
            $check=$this->validate($this->input, $rule);
            if($check!==true){
                return err(9000,$check);
            }
            if ($this->CheckIfGotoWarehouse($this->input['GID']))
                return err(9000,"无法撤销资料销售，已经进入仓库，请先撤出仓库！");
            Db::startTrans();
            try {
                $this->CancelMaterial($this->input['GID']);
                Db::commit();
            } catch (\Exception $exc) {
                Db::rollback();
                return err(9000,$exc->getMessage());
            }

            
        }
        private function CheckIfGotoWarehouse($Groupid) 
        {
            $sql = "select warehousemark from bonuscheck.dbo.materialform where groupid='".$Groupid."'";
            $warehouseMark = $this->GetStringData ($sql);
            if ((int)$warehouseMark== 2)
                return true;
            else return false;
        }
        private function CancelMaterial($Groupid) 
        {
            $sql = "delete from frontdesk_cash where groupid='".$Groupid."' and memo=' 资料销售'";
            $status=$this->Exc_Sql($sql);
            if (!is_numeric($status))
                throw  new \Exception("Error sql:".$sql);
            else
            {
                $sql = "delete from fd_tellerDetail where 用途='资料销售' and groupid='".$Groupid."'";
                $status=$this->Exc_Sql($sql);
                if (!is_numeric($status))
                    throw  new \Exception("Error sql:" .$sql);
                else
                {
                    $this->ProcessChangeTeller($this->GetStringData ("select buyer from bonuscheck.dbo.materialForm where groupid='".$Groupid."'"));
                    if ($this->inpput['D'])
                    {
                        $sql = "delete from bonuscheck.dbo.materialform where groupid='" .$Groupid. "'";
                        $status=$this->Exc_Sql($sql);
                         if (!is_numeric($status))
                           throw  new \Exception("Error sql:" .$sql);
                        return suc("已经删除！");
                    }
                    else
                    {
                        $sql = "update bonuscheck.dbo.materialform set cash=0,teller=0,status=0 where groupid='" .$Groupid."'";
                        $status=$this->Exc_Sql($sql);
                        if (!is_numeric($status))
                            throw  new \Exception("Error sql:" .$sql);
                        return suc("已经撤销，可以重新登陆进行操作！");
                    }
                }
            }
        }
        public function button6_Click()
        {
            Db::startTrans();
            try {
               $this->GotoSTockOut($this->input['GID']);
                Db::commit();
            } catch (\Exception $exc) {
                Db::rollback();
                return err(9000,$exc->getMessage());
            }

        }
        public function GotoSTockOut($Groupid)
        {
            if (!$this->CheckIfWenttoStock($Groupid))
            {

                    $this->GotoWarehouse($Groupid, self::$realname, "资料销售", "0", "0", $this->input['SC_CARD']);
                    if ($this->CheckIfWentStockIsCorrect($Groupid))
                    {
                        $this->SetManuelStockOut("2",$Groupid );
                    }
                    else                        throw new Exception("已经进入库存提货系统,核对不正确，联系Eric！");

                
            }
            else
            {
                if ($this->CheckIfWentStockIsCorrect($Groupid))
                {
                    $this->SetManuelStockOut("2", $Groupid);
                }
                else throw new Exception("已经进入库存提货系统,核对不正确，联系Eric！");
            }
        }
        private function SetManuelStockOut($mark,$Groupid)
        {
            $sql = "update bonuscheck.dbo.materialform set warehousemark='" .$mark. "' where groupid='" .$Groupid. "'";
            $status=$this->Exc_Sql($sql);
            if (!is_numeric($status))
                throw new \Exception("标志位失败！Sql:" .$sql);
        }
        private function CheckIfWentStockIsCorrect($GroupID)
        {
            $result = true; 
            try
            {
                $sql = "select materialid,qty from bonuscheck.dbo.materialformdetail where formid=(select formid from bonuscheck.dbo.materialform where groupid='".$GroupID. "') order by materialid desc";
                $Sale_DeductDS = $this->query_Sql($sql);
                $sql = "select Productid,Amount from stockout,stockoutdetail where stockoutdetail.stockoutid=stockout.stockoutid and groupid='" .$GroupID. "' order by productid desc";
                $StockDS = $this->query_Sql($sql);
               foreach($Sale_DeductDS as $k=>$v)
                {
                    if ($v['qty'] != $StockDS[$k]['Amount'])
                    {
                        $result = false;
                    }

                }
            }
            catch (\Exception $ee) { }
            return $result;


        }
        #region 检查已进入库存系统数据是否正确
        private function CheckIfWenttoStock($GroupID)//检查是否进入
        {
           $result=true;
            $sql = "select count(*) from stockoutdetail where stockoutid in(select stockoutid from stockout where groupid='" .$GroupID. "') ";
            $StockInfo = $this->GetStringData($sql);
            if ($StockInfo == "0")
                $result = false;
            return $result;


        }  
        private function GotoWarehouse($groupid, $Oper, $SaleType, $SN, $BN, $Shopno)
        {

                $sql = "";$NewStockOutId = "";
                $NewStockOutId = $this->GetNewStockOutID();
                $sql = "  insert stockout(stockoutid,lastedittime,groupid,status,toplace,insertperson,insertdate,saletype,saledate,shopno,memo,snylon,bnylon)"
                    . " values(" . $NewStockOutId . ",'" .$this->input['Date']. "','" .$groupid. "','1','" .$this->GetRecieveInfo($this->input['GID'], "2") . "','" .self::$realname. "','" .$this->input['Date']. "','" .$SaleType. "','" .$this->input['Date']. "','" .$Shopno. "','资料销售'," .$SN. "," .$SN. ")";
                        $status=$this->Exc_Sql($sql);
                if (is_numeric($status))
                {

                    $sql = "insert stockoutinfo(stockoutid,sendperson,sendmac,sendip,sendsoftware,sendfunction,customerid,customerno,recieveman)"
                        ." values(" .$NewStockOutId.",'" .self::$realname."','" .$this->Local_Mac(). "','" .$this->Local_IP() . "','前台系统','". $this->Name . "'," .$this-> GetRecieveInfo($this->input['GID'], "0") + ",'" .$this->GetRecieveInfo($Shopno, "1") + "','" .$this->GetRecieveInfo($Shopno, "2") . "')";
                    $status=$this->Exc_Sql($sql);
                    if (is_numeric($status))
                    {

                        $pid = ""; $amount = "";
                        $sql = "select materialid,qty as amount from bonuscheck.dbo.materialformdetail where formid=(select formid from bonuscheck.dbo.materialform where groupid='" .$groupid."') order by materialid";
                        $ds = $this->query_Sql($sql);
                        foreach($ds as $v)
                        {
                            $pid = $v["materialid"];
                            $amount =$v["amount"];
                            $sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)"
                             . " values(" .$this-> GetNewStockOutDetailID() . "," .$NewStockOutId . "," .$pid. "," .$amount . ",'" .$SaleType . "') ";
                                                $status=$this->Exc_Sql($sql);
                            if (!is_numeric($status))
                            {
                            throw  new \Exception("向表stockoutdetail中插入数据失败！Sql:" .$sql);

                                break;
                            }

                        }

                        //ProcessNylonIntoDetail(NewStockOutId);
                    }
                    else
                    {
                        throw  new \Exception("向表stockoutinfo中插入数据失败！Sql:" .$sql);
                    }
                }
                else throw  new \Exception("向表stockout中插入数据失败！Sql:" .$sql);

        }
        private function GetNewStockOutID()
        {
            $sql = "select isnull(max(stockoutid),0)+1 from stockout";
            $outid =$this->GetStringData($sql);
            return $outid;

        }
        private function GetRecieveInfo($customerno, $mark)
        {
            if ($customerno== "")
                return "";
            else
            {
                if (strlen($customerno)!= 8)
                {
                    if ($mark == "0")
                        return "0";
                    else
                        return $customerno;
                }
                else
                {
                    if ($mark == "1")
                        return $customerno;
                    else if ($mark == "0")
                        return $this->GetCustomerID($customerno);
                    else return $this->GetCustomerName($customerno);
                }
            }
        }
        private function GetCustomerName($No)
        {
            $sql = "select customername from tb_customerinfo where customerid=(select customerid from tb_customer where customerno='" .$No. "')";
            $cname =$this->GetStringData($sql);
            return $this->ProccessNull($cname);
        }
        private function ProccessNull($str)
        {
            if ($str== "")
                return "Nothing";
            else return $str;

        }
        private function GetCustomerID($No)
        {
            $sql = "select customerid from tb_customer where customerno='" .$No. "'";
            $cid =$this->GetStringData($sql);
            return (int)$cid;
        }
        private function GetNewStockOutDetailID()
        {
            $sql = "select isnull(max(stockoutdetailid),0)+1 from stockoutdetail";
            $outid =$this->GetStringData($sql);
            return $outid;

        }
        public function button5_Click()
        {
           
            Db::startTrans();
            try {
                $data=$this->BackFromStockOut($this->input['GID']);
                Db::commit();
                return $data;
            } catch (\Exception $exc) {
                Db::rollback();
                return err(9000,$exc->getMessage());
            }
        }
        private function BackFromStockOut($groupid)
        {
            if ($this->CheckIfStockedOut("资料销售", $groupid))
            {
                throw new \Exception("该编号订单已经发货，无法撤销！\n The Sale with the GroupID is already sent to Distributor!");

            }
            else
            {
               return  $this->RemoveToStock($this->input['GID'], "资料销售");



            }
        }
        private function CheckIfStockedOut($saletype, $groupid)
        {
            $stockoutstatus = false;
            $sql = "select count(*) from stockout where status='2' and saletype='" .$saletype. "'and groupid='" .$groupid. "'";
            $count=$this->GetStringData($sql);
            if ($count> 0)
                $stockoutstatus = true;
            return $stockoutstatus;
        }
        private function RemoveToStock($groupid, $saletype)
        {
            
            $sql = "delete from stockoutdetail where stockoutid in(select stockoutid from stockout where saletype='" .$saletype. "'and groupid='" .$groupid. "')";
            $status=$this->Exc_Sql($sql);
            if (!is_numeric($status))

                throw new \Exception("表stockoutdetail记录撤销失败！Sql:" .$sql);
            else
            {
                $sql = "delete from stockoutinfo where stockoutid in(select stockoutid from stockout where saletype='" .$saletype. "'and groupid='" .$groupid. "')";
                 $status=$this->Exc_Sql($sql);
                if (!is_numeric($status))
                     throw new \Exception("表stockoutinfo记录撤销失败！Sql:" .$sql);
                else
                {
                    $sql = "delete from stockout where saletype='".$saletype. "'and groupid='" .$groupid. "'";
                    $status=$this->Exc_Sql($sql);
                    if (!is_numeric($status))
                         throw new \Exception("表stockoutinfo记录撤销失败！Sql:" .$sql);
                    else
                    {
                        return suc("撤销扣货成功！");
                        $this->SetManuelStockOut("1",$groupid );
                    }
                }
            }


        }
        public function toolStripMenuItem1_Click()
        {
            
            try {
           $this->GetLast($this->input['GID']);
           $data["input"]=$this->input;
           $data["url"]="S";
            $this->CheckStatus($this->input['GID']);
             return suc($data);
            } catch (\Exception $exc) {
                return err(9000,$exc->getMessage());
            }
        }
        public function toolStripMenuItem2_Click()
        {
           
            try {
           $this->GetNext($this->input['GID']);
           $data["input"]=$this->input;
           $data["url"]="S";
             return suc($data);
            } catch (\Exception $exc) {
                return err(9000,$exc->getMessage());
            }
        }
        private function GetNext($Groupid)
        {
            if ($this->CheckRank($Groupid) == "Last")
                exception("已经是最后一个单据了");
            else
            {
                $sql = "";$Gid = "";
                if (trim($Groupid) == "")
                {
                    $sql = "select groupid from bonuscheck.dbo.materialform where formid=(select max(formid) from bonuscheck.dbo.materialform where status='1' and date='" .$this->input['Date']. "')";
                    $Gid = $this->GetStringData($sql);
                    if (trim($Gid) != "")
                        $this->input["GID"]=$Gid;
                    else exception("It is the Last form on the date :"  .$this->input['Date']);
           
                }
                else
                {
                    $sql = "select groupid from bonuscheck.dbo.materialform where formid=(select min(formid) from bonuscheck.dbo.materialform where  date='" .$this->input['Date'].  "'and formid>(select formid from bonuscheck.dbo.materialform where groupid='" .$Groupid. "'))";
                    $Gid = $this->GetStringData($sql);
                    if (trim($Gid) != "")
                        $this->input["GID"]=$Gid;
                    else exception("It is the Last form on the date :" .$this->input['Date']);
           
                }
            }

        }
        private function GetLast($Groupid) 
        {
            $sql="";$Gid="";
        if ($this->CheckRank($Groupid) == "First")
            return err(9000,"已经是第一个单据了");
        else
        {
            if ($Groupid == "")
            {
                $sql = "select groupid from bonuscheck.dbo.materialform where formid=(select max(formid) from bonuscheck.dbo.materialform where status='1' and date='".$this->input['Date']."')";
                $Gid=$this->GetStringData($sql);
                if ($Groupid!= "")
                    $this->input["GID"]= $Gid;
                else  return err(9000,"It is the first form on the date :".$this->input['Date']);
            }
            else
            {
                $sql = "select groupid from bonuscheck.dbo.materialform where formid=(select max(formid) from bonuscheck.dbo.materialform where  date='" .$this->input['Date']. "' and formid<(select formid from bonuscheck.dbo.materialform where groupid='" .$Groupid. "'))";
                $Gid = $this->GetStringData($sql);
                if ($Gid != "")
                    $this->input["GID"]= $Gid;
                else return err(9000,"It is the first form on the date :".$this->input['Date']);
           
            }
        }

        }
        public function button1_Click()
        {
            try {
            $data=$this->Search();
           $data["input"]=$this->input;
            $this->CheckStatus($this->input['GID']);
             return $data;
            } catch (\Exception $exc) {
                return err(9000,$exc->getMessage());
            }

        }
        private function Search()
        {

            $this->input['SC_CARD']= $this->GetStringData("select buyer from bonuscheck.dbo.materialForm where groupid='" .$this->input['GID']. "'");
            $this->input['Cash']= $this->GetStringData("select cash from bonuscheck.dbo.materialForm where groupid='"  .$this->input['GID'].  "'");
           $this->input['Teller']=  $this->GetStringData("select teller from bonuscheck.dbo.materialForm where groupid='"  .$this->input['GID'].  "'");
            $this->input['Date']= $this->GetStringData("select date from bonuscheck.dbo.materialForm where groupid='" .$this->input['GID']."'");
            $this->input['Oper'] = $this->GetStringData("select operater from bonuscheck.dbo.materialForm where groupid='" .$this->input['GID']. "'");
         
            $data['GetMaterialReport1']=$this->GetMaterialReport1($this->input['GID']);
             
             return suc($data);
        }
        private function CheckStatus($Groupid)
        {
            if (!$this->CheckIfWenttoStock($Groupid))
            { 
                $this->SetButtonAfterSave(); 
            }
            else
            {
                if ($this->CheckIfWentStockIsCorrect($Groupid))
                {
                    $this->SetButtonAfterGotoStockOut();
                }
                else                    exception("已经进入库存提货系统,核对不正确，联系Eric！");
            }
        }
        private function SetButtonAfterSave()
        {
                return false;
        }
        private function SetButtonAfterGotoStockOut()
        {
                return false;
        }
        private function GetMaterialReport1($GID)
        {

                $sql = "select ProductNo as Item,Name as Products,str(memberprice) as [U/Price NR],Qty,str(money) as [Total NR] from(select MaterialID,Qty,Money from bonuscheck.dbo.materialFormDetail where formid="
                    ."(select formid from bonuscheck.dbo.materialForm where groupid='".$GID."')) a,stockProduct_material b where a.MaterialID=b.productid";
               $data=$this->query_Sql($sql);
               return $data;

        }
        private function  CheckRank($groupid) 
        {
            $sql = "";$MinGid = "";$MaxGid = ""; $NowGid = "";$result = "Middle";
            $sql = "select formid from bonuscheck.dbo.materialform where groupid='".$groupid."'";
            $NowGid = $this->GetStringData($sql);
            $sql = "select min(formid) from bonuscheck.dbo.materialform where status='1'";
            $MinGid = $this->GetStringData($sql);
           $sql = "select max(formid) from bonuscheck.dbo.materialform where status='1'";
            $MaxGid =$this->GetStringData($sql);
            if ($NowGid == $MinGid)
               $result = "First";
            if ($NowGid == $MaxGid)
                $result = "Last";
            return $result;


        }
}
