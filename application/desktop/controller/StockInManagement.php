<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class StockInManagement extends Common {

    private $products;     //当前页码

    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
    }

    public function load() {
        $data['status'] = $this->status;
        $data['use_status'] = $this->use_status;
        $data['lock_status'] = $this->lock_status;
        $data['effective_date'] = $this->effective_date;
        return suc($data);
    }

    public function save() {
        if ($this->CheckIfStockClose($this->input["Date"], "kedistock")) {
            return err(9000, "The date you operate is already completed!");
        }
        $product = json_decode($this->input["products"],true);
        if (empty($product)) {
            return err(9000, "您没有选择产品！");
        }
        $this->products = $product;
        $rule = [
            "From" => "require",
            "GroupID" => "require",
           "StockInID" => "require",
              "StockName" => "require",
                  "StockType" => "require",
        ];
        $msg = [
            "From" => "You didn't put StockFrom,未写货源地！"
        ];
        $check = $this->validate($this->input, $rule, $msg);
        if ($check !== true) {
            return err(9001, $check);
        }
        Db::starttrans();
        try {
			if ($this->CheckIfInsert($this->input["GroupID"])) {
				$data["UpdateStockIn"] = $this->UpdateStockIn();
			} else {
				$data["InsertStockIn"] = $this->InsertStockIn();
			}
			$data["GetSearchStock"] = $this->GetSearchStock();
			Db::commit();
        return suc($data);
        } catch (Exception $exc) {
            Db::rollback();
            echo $exc->getTraceAsString();
        }


    }

    private function CheckIfInsert($Groupid) {

        $sql = "select count(*) from stockin where groupid='" . $Groupid . "'";
        if ($this->GetStringData($sql) == "0")
            return false;
        else
            return true;
    }

    private function UpdateStockIn() {
        if ($this->RemoveStockIndetail()) {
            $this->UpdateInstockName();
            return $this->InsertStockInDetail();
        } else
            exception("更新数据保存失败！");
    }

    private function RemoveStockIndetail() {
        $rm = "delete from stockindetail where stockinid='" . $this->input['StockInID'] . "'";
        if ($this->Exc_Sql($rm) > 0 || $this->GetStringData("select count(*) from stockindetail where StockInID='" . $this->input['StockInID'] . "'") == "0")
            return true;
        else
            return false;
    }

    private function UpdateInstockName() {
        $updatesql = "update stockin set indate='" . $this->inputp['Date'] . "', instockname='" . $this->input['StockName'] . "',instocknameid=" . $this->GetInstockNameID($this->input['StockName']) . " where groupid='" . $this->input['GroupID'] . "'";
        $this->Exc_Sql($updatesql);
    }

    private function GetInstockNameID($stockshow) {
        $sql = "select stockid from stocklist where stockshow='" . $stockshow . "'";
        $stocknameid = $this->GetStringData($sql);
        return $stocknameid;
    }

    private function InsertStockInDetail() {

        try {
            foreach ($this->products as $k => $v) {
                $memo = "";
                $pid = 0;
                $amount = 0;
                $detailid = 0;
                $pid = $v["ProductID"];
                $amount = $v["num"];
                $memo = $v["memo"];
                $detailid = $this->GetNewDetailID();
                $InsertSql = "insert stockindetail(stockindetailid,stockinid,productid,amount,memo) values(" . $detailid . "," . $this->input['StockInID'] . "," . $pid . "," . $amount . ",'" . $memo . "')";
                $this->Exc_Sql($InsertSql);
            }
            return $this->InsertStockInInfo();
        } catch (\Exception $ee) {

            exception("Insert Table StockInDetail unsuccessfully! ");
        }
    }

    private function GetNewDetailID() {
        $StockInDetailID = $this->GetStringData("select isnull(max(StockInDetailID),0)+1 as newid from StockInDetail");
        return $StockInDetailID;
    }

    private function InsertStockInInfo() {
        $InsertSql = "insert stockininfo(stockinid,sendperson,fromaddress,phone,email,country,city,postcode) " .
                "values('" . $this->input['StockInID'] . "','" . $this->input['textBox2'] . "','" . $this->input['textBox1'] . "','" . $this->input['textBox9'] . "','" . $this->input['textBox3'] . "','" . $this->GetCountry() . "','" . $this->input['From'] . "','" . $this->input['textBox4'] . "')";
        if ($this->Exc_Sql($InsertSql) <= 0)
            exception("Insert Table StockInInfo unsuccessfully! ");
        else
            return suc("保存成功，Saved successfully!");
    }

    //保存进货单分为2部分，一部分新录的进货单保存，一部分为反审核（反审核时已经从总数据库去除该单子的数据）后的保存！
    private function InsertStockIn() {
        $InsertSql = "insert stockin(stockinid,insertperson,lastedittime,groupid,status,indate,Fromplace,memo,fromcountry,InStockName,stocktype,overflowfromgroupid,instocknameid) " .
                "values(" . $this->GetStockInID() . ",'" . self::$realname . "','" . date("Y-m-d H:i:s") . "','" . $this->input['GroupID'] . "','0','"
                . $this->input['Date'] . "','" . $this->input['From'] . "','" . $this->input['Stock_Memo'] . "','" . $this->GetCountry() . "','"
                . $this->input['StockName'] . "','" . $this->input['StockType'] . "','" . $this->input['textBox11'] . "','" . $this->GetInstockNameID($this->input['StockName']) . "')";
        if ($this->Exc_Sql($InsertSql) > 0)
            return $this->InsertStockInDetail();
        else
            exception("进货单保存失败！\nInsert Table StockIn unsuccessfully! ");
    }

    private function GetCountry() {
        $sql = "select fromcountry from stockfrom where fromid=(select fromid from stockfromdetail where fromplace='" . $this->input['From'] . "')";
        $country = $this->GetStringData($sql);
        return $country;
    }

    private function GetStockInID() {

        $StockInID = $this->GetStringData("select isnull(max(stockinid),0)+1 as newid from stockin");
        $this->input["StockInID"] = $StockInID;
        return $StockInID;
    }

    private function GetSearchStock() {
        $sql = "select ProductNo,[Name],Amount as StockQTY,stockindetail.memo as 说明Memo  from stockin,stockindetail,stockproduct_material where groupid='" . $this->input['GroupID'] . "' and stockindetail.stockinid=stockin.stockinid and stockproduct_material.productid=stockindetail.productid";
        $data = $this->query_Sql($sql);
        $sql = "select memo from stockin where groupid='" . $this->input['GroupID'] . "'";
        $this->input["Stock_Memo"] = $this->GetStringData($sql);
        $data=utf_8($data);
        return $data;
    }

    public function check() {
        $rule = [
            "From" => "require",
            "GroupID" => "require",
           "StockInID" => "require",
              "StockName" => "require",
                  "StockType" => "require",
        ];
        $msg = [
            "From" => "You didn't put StockFrom,未写货源地！"
        ];
        $check = $this->validate($this->input, $rule, $msg);
        if ($check !== true) {
            return err(9001, $check);
        }
         if($this->input['button5_Text']!='审核Check'&&$this->input['button5_Text']!='反审核UnCheck'){
             return err(9001, "请输入操作名");
         }                    
        if (strtoupper($this->input['button5_Text']) == "审核CHECK") {
            $data["ActiveStockIn"]=$this->ActiveStockIn();
             $data["input"] = $this->input;
             return suc($data);
        } else {
            if ($this->CheckIfStockClose($this->input["Date"], "kedistock"))
                return err(9000, "The date you operate is already completed!");
            else {
                try {
                    if ($this->CheckIfTheStockCanRevert()) {
                        $data["UnActiveStockIn"] = $this->UnActiveStockIn();
                        $data["input"] = $this->input;
                        return suc($data);
                    } else
                        exception("入库数据已经使用，不能反审核！");
                } catch (\Exception $ex) {
                    return err(9001, $ex->getMessage());
                }
            }
        }
    }

    //region  审核反审核用到的函数
    private function ActiveStockIn() {
        $InsertSql = "update stockin set status='1' where groupid='" . $this->input['GroupID'] . "'";
        $status=$this->Exc_Sql($InsertSql);
        if ($status > 0) {
            return $this->InsertKediStock();
        } else {
            exception("激活入库信息失败，联系肖胜伟，Unable to Active StockIn,Pls contact with Eric!");
        }
    }

    private function InsertKediStock() {
        $effectno = 0;
        $InsertSql = "update " . $this->GetStockNameFromSQL() . " set Inamount=isnull(inamount,0)+(select amount from stockindetail where stockinid=" .
                "(select stockinid from stockin where groupid='" . $this->input['GroupID'] . "') and productid=" . $this->GetStockNameFromSQL() . ".productid) where productid in(select productid from stockindetail where "
                . "stockinid=(select stockinid from stockin where status='1'and groupid='" . $this->input['GroupID'] . "')) ";
        $effectno = $this->Exc_Sql($InsertSql);
        if ($effectno > 0) {
            if ($effectno == $this->GetProductCatogery($this->input['GroupID'])) {
                
                $this->UpdateBalanceOfStock(); //入库后更新库的余额

                $this->Fresh();
                return suc("审核入库成功！");
            } else
                exception("审核入库，数量不正确，请核对！Sql" . $InsertSql);
        }
        else {
            $InsertSql = "update stockin set status='0' where groupid='" . $this->input['GroupID'] . "'";
            $this->Exc_Sql($InsertSql);
            exception("审核入库失败,即将回滚系统,请重新审核！Unable to Check,Pls contact with Eric!");
            $this->Rollback("0");
        }
    }

    private function GetStockNameFromSQL() {
        $sql = "select stockname from stocklist where stockid=( select instocknameid from stockin where groupid='" . $this->input['GroupID'] . "')";
        $stockname = $this->GetStringData($sql);
        return $stockname;
    }

    private function GetProductCatogery($GID) {
        $sql = "select count(*) from stockindetail where stockinid in(select stockinid from stockin where groupid='" . $GID . "') ";
        $number = $this->GetStringData($sql);
        return $number;
    }

    private function UpdateBalanceOfStock() {
        $UpdateBalance = "update " . $this->GetStockNameFromSQL() . " set amount=isnull(inamount,0)-isnull(outamount,0) ";
        if ($this->Exc_Sql($UpdateBalance) < 1)
            exception("库存真正余额更新出现错误！");
        else {
            $UpdateBalance = "update " . $this->GetStockNameFromSQL() . " set realamount=isnull(amount,0)-isnull(backorderamount,0) ";
            if ($this->Exc_Sql($UpdateBalance) < 1)
                exception("库存理论余额更新出现错误！");
            else {
                $this->SetAfterCheckButtonStatus();
            }
        }
    }
	//状态为1时添加编辑反审核按钮
    private function SetAfterCheckButtonStatus() {
        $this->input['button3_Text'] = "编辑Edit";
        $this->input['button5_Text'] = "反审核UnCheck";
    }
//判断数据在stockin表中的状态是0还是1
    private function Fresh() {
        $sql = "select status from stockin where groupid='" . $this->input['GroupID'] . "'";
        $status=$this->GetStringData($sql);
        if ($status== "1") {
        } else {
            $this->SetAfterSaveButtonStatus();
        }

        $this->GetStockNamePlaceType();
    }
	//状态为0添加编辑审核按钮
    private function SetAfterSaveButtonStatus() {
        $this->input['button3_Text'] = "编辑Edit";
        $this->input['button5_Text'] = "审核Check";
    }
	
    private function GetStockNamePlaceType() {
        $sql = " select stockshow from stocklist where stockid=( select instocknameid from stockin where groupid='" . $this->input['GroupID'] . "')";
        $this->input["StockName"] = $this->GetStringData($sql);
        $sql = "select stocktype from stockin where groupid='" . $this->input['GroupID'] . "'";
        $this->input["StockType"] = $this->GetStringData($sql);
        $sql = "select fromplace from stockin where groupid='" . $this->input['GroupID'] . "'";
        $this->input["StockFromPlace"] = $this->GetStringData($sql);
    }

    private function Rollback($check) {
        $InsertSql = "update stockin set status='" . $check . "' where groupid='" . $this->input['GroupID'] . "'";
        if ($this->Exc_Sql($InsertSql) < 1)
            exception("回滚失败，请联系Eric！");
    }

    private function CheckIfTheStockCanRevert() {
        $status = true;
        try {
            $sql = "select productid,amount from stockindetail where stockinid=(select stockinid from stockin where groupid='" . $this->input['GroupID'] . "') order by productid";
            $InStockDs = $this->query_Sql($sql);
            $sql = "select productid,amount from " . $this->GetStockName() . " where productid in(select productid from stockindetail where stockinid=(select stockinid from stockin where groupid='" . $this->input['GroupID'] . "')) order by productid";
            $StockBalance = $this->query_Sql($sql);
            foreach ($InStockDs as $k => $v) {
                $InstockAmount = 0;
                $StockBalnceAmount = 0;
                $InstockAmount = $v["amount"];
                $StockBalnceAmount = $StockBalance[$k]['amount'];
                if ($InstockAmount > $StockBalnceAmount) {
                    $status = false;
                    break;
                }
            }
        } catch (\Exception $ee) {
            $status = false;
            exception($ee->getMessage());
        }
        return $status;
    }

    private function UnActiveStockIn() {
        $InsertSql = "update stockin set status='0' where groupid='" . $this->input['GroupID'] . "'";
        if ($this->Exc_Sql($InsertSql) > 0) {

             $data=$this->RemoveKediStock();
            $this->UpdateBalanceOfStock();
            $this->Fresh();
            return $data;
        } else {
            exception("反审核失败，联系肖胜伟，Unable to Active StockIn,Pls contact with Eric!");
        }
    }

    private function RemoveKediStock() {
        $InsertSql = "update " . $this->GetStockNameFromSQL() . " set Inamount=isnull(inamount,0)-(select amount from stockindetail where stockinid=" .
                "(select stockinid from stockin where groupid='" . $this->input['GroupID'] . "') and productid=" . $this->GetStockNameFromSQL() . ".productid) where productid in(select productid from stockindetail where "
                . "stockinid=(select stockinid from stockin where status='0'and groupid='" . $this->input['GroupID'] . "')) ";
        if ($this->Exc_Sql($InsertSql) > 0)
            return suc("反审核出库成功！");
        else {
            exception("反审核出库失败,即将回滚系统,请重新反审核！Unable to UnCheck,Pls contact with Eric!");
            $this->LRollback("1");
        }
    }
    public function add()
        {
            $this->GetTotalNoDate();//加此函数为了前后查找单据值标志位
            $this->AddNewForm();
            $this->LoadInfo();
            $data["input"]=$this->input;
            return suc($data);
        }
       private function AddNewForm()
        {
            $this->input['StockInID'] =$this->GetStringData("select isnull(max(stockinid),0)+1 from stockin");
            $this->input['GroupID']  =date("YmdHis");
        }
        private function LoadInfo()
        {
            $sql = "select [name]+':'+productno from stockproduct_material ";
            $ds = $this->query_Sql($sql);
            return suc($ds);

        }
		//得到stockin表中数据的总个数
        private function GetTotalNoDate() 
        {
            $sql = "select count(*) from stockin "; //where stocktype//not like'移库%'";
            $Total =$this->GetStringData($sql);
            return $Total;
        }
        private function GetStockName() 
        {
            $sql = "select stockname from stocklist where stockshow='" .$this->input['StockName']. "'";
          return  $this->GetStringData($sql);
     
        }
        //region 删除模块
        public function  UnSubmit()
        {
            $rule=[
                "GroupID"=>"require"
            ];
            $check=$this->validate($this->input, $rule);
            if($check!==true){
                return err(9000,$check);
            }
            $DeleteSql = "delete from stockin where groupid='" .$this->input['GroupID']. "' and status='0'";
            if ($this->Exc_Sql($DeleteSql) > 0)
            {
                return suc("Ok");
            }
            else return err(9000," 删除失败，您要删除的单据没有反审核！Unsuccessfully! ");
        }
		//前单
        public function get_last()
        {
            if ($this->input['StockInID']== "")
            {
                $Total=$this->GetTotalNoDate();
                $data["detail"]=$this->GetTopWhoNoDate($Total);
                $this->Set();
                $data["input"]=$this->input;
                return suc($data);
            }
            else
            {
              return  $this->GetLast();
			   
			}  
        }
        public function GetLast()
        {
			try {
				$sql = "select max(stockinid) from stockin where stockinid<" .$this->input['StockInID']. "";
				$this->input['StockInID']= $this->GetStringData($sql);
				$this->input['GroupID'] = $this->GetStringData("select groupid from stockin where stockinid=" .$this->input['StockInID']);
				$this->input['Date'] = $this->GetStringData("select indate from stockin where stockinid=" .$this->input['StockInID']);
				$ql = "select stockinid from stockin where stockinid='" .$this->input['GroupID']. "'";
			}
			catch (\Exception $ee) { }
            $data["detail"]=$this->GetSearchStock();
			
            $this->Fresh();
            $data["input"]=$this->input; 
            return suc($data);

         }
        private function GetTopWhoNoDate($top)
        {

            try { 

            $sql = "select top 1 stockinid from stockin where stockinid in" . "(select top " . $top. " stockinid from stockin order by stockinid) order by stockinid desc";
            $this->input['StockInID']= $this->GetStringData($sql);
            $this->input['GroupID']= $this->GetStringData("select groupid from stockin where stockinid=".$this->input['StockInID']);
            $this->input['Date']=$this->GetStringData("select indate from stockin where stockinid=" .$this->input['StockInID']);
            $sql = "select stockinid from stockin where stockinid='" .$this->input['GroupID']. "'";
            }
            catch (\Exception $ee) { }
            $data=$this->GetSearchStock();
            
            $this->Fresh();
            return $data;
        }
        private function Set()
        {
            $sql = "select status from stockin where groupid='" .$this->input['GroupID']. "'";
            $check = $this->GetStringData($sql);
            if ($check== "1")
            {
                $this->SetAfterCheckButtonStatus();
            }
            else if ($check== "0")
            {
                $this->SetAfterSaveButtonStatus();
            }
            $this->GetStockNamePlaceType();

        }
		//后单
        public function GetNext()
        {
            if(empty($this->input["StockInID"])){
                return err(9000,"当前单据是最后一个单据！");
            }
            try
            {
                $sql = "select min(stockinid) from stockin where stockinid>".$this->input["StockInID"]."";
                $this->input["StockInID"] = $this->GetStringData($sql);
                $this->input["GroupID"] = $this->GetStringData("select groupid from stockin where stockinid=" .$this->input["StockInID"]);
                $this->input["Date"] = $this->GetStringData("select indate from stockin where stockinid=" .$this->input["StockInID"]);
                $sql = "select stockinid from stockin where stockinid='" .$this->input["GroupID"]. "'";
            }
            catch (\Exception $ee) { }
            $data["detail"]=$this->GetSearchStock();

            $this->Fresh();
            $data["input"]=$this->input;
            return suc($data);
        }
}
