<?php
namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class TellerAccountReg extends Common {
	public $GID;
	public $realdate;
	public $oper_name;
	public $ShopNO;
	public $TellarMoney;
	public $CashMoney;
	public $CashID_Delete = "";
	public $TellerType = "业绩销售";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->FrontDeskCash = model("FrontDeskCash");
		$this->SaleEnteredByFront = model("SaleEnteredByFront");
		$this->TellerAccountReg = model("TellerAccountReg");
		$this->FDTellerdetail = model("FDTellerDetail");
		$this->FDTeller = model("FDTeller");
		$this->input = input("post.");
		self::$FormMark = "";
	}

	public function TellarData_Load() {
		$this->ProcessChangeTeller($this->input["ShopNO"]);
		$detail = $this->FDTellerdetail->getByWhere(["GroupID" => $this->input["GroupID"]]);
		$total = $this->FDTellerdetail->sum(["GroupID" => $this->input["GroupID"]]);
		$RegionMoney = $this->FDTellerdetail->GetRegionMoney($this->input["ShopNO"], $this->input["Region"]);
		$banlance = $this->FDTeller->getByWhereOne(["ShopNO" => $this->input["ShopNO"]]);
		$shopreg = $this->TellerAccountReg->getByWhereOne(["regserviceno" => $this->input["ShopNO"]]);
		$data["detail"] = $detail;
		$data["total"] = $total;
		$data["banlance"] = $banlance;
		$data["shopreg"] = $shopreg;
		$data["RegionMoney"] = $RegionMoney;
		$data["TellerType"] = $this->GetTellerType();
		$data["input"] = $this->input;
		if ($data) {
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	private function GetTellerType() {
		if (self::$FormMark == "NewBorrow") {
			$TellerType = "借货押金";
		} else
		if (self::$FormMark == "UpdateReport") {
			$TellerType = "业绩销售";

		} else if (self::$FormMark == "UpdateBigSCReport") {
			$TellerType = "业绩销售";

		} else if (self::$FormMark == "Material") {
			$TellerType = "资料销售";

		} else {
			$TellerType = "业绩销售";

		}
		$this->TellerType = $TellerType;
		return $TellerType;
	}
	public function add() {
		$rule = [
			"GroupID" => "require",
			"Amount" => "require|between:1,100000000",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		if ($this->input['RegionMoney'] > $this->input['Amount']) {
			Db::startTrans();
			try {
				$datetype = "PswStatus";
				$Add_Tellar_Sql = "insert FD_TellerDetail(GroupID,TellerDetailID,ShopNo,deposit,used,[date],tellerid,用途,region,Oper,bank,所属期,RegPswStatus) values('"
				. $this->input['GroupID'] . "'," . $this->Get_New_TellerDetailID() . ",'" . $this->input['SC'] . "','0','" . $this->input['Amount'] . "','" . $this->input['Date'] . "'," . $this->Get_TellerID() . ",'" . $this->GetTellerType() . "','" . $this->input['Region'] . "','" . self::$realname . "','KEDI','" . $this->GetDate_AcheavieFrom() . "','" . $datetype . "')";
				$status = $this->Exc_Sql($Add_Tellar_Sql);
				if (is_numeric($status)) {
					$this->Update_FD_Teller(); //完成在无账号情况下添加账号的功能
					$this->Show_Total_Balance();
					$data['Get_Tellar_Info'] = $this->Get_Tellar_Info();
					$data['Show_TellarTotal'] = $this->Show_TellarTotal();
					$this->input["Amount"] = "";
					$this->ProcessChangeTeller($this->input["SC"]);
					$data['input'] = $this->input;
					Db::commit();
					return suc($data);
				}
			} catch (\Exception $ex) {
				Db::rollback();
				return err(9000, $ex->getMessage());
			}

		} else {
			return err(9000, "The Balance is not enough!\n 余额不足！");
		}

	}
	private function Get_New_TellerDetailID() {
		$Get_TellarID = 1;
		$sql = "select max(tellerdetailid)+1 as newid from FD_TellerDetail";
		$data = $this->GetStringData($sql);
		if ($data) {
			$Get_TellarID = $data;
		}
		return $Get_TellarID;
	}
	private function Get_TellerID() {
		$Get_TellarID = 0;
		$sql = "";
		try
		{
			$sql = "select tellerid from FD_Teller where shopno='" . $this->input['SC'] . "'";
			$Get_TellarID = $this->GetStringData($sql);
		} catch (\Exception $ee) {
			$Get_TellarID = $this->Get_New_TellerID();
			$Upadte_Teller_Sql = "insert FD_Teller(TellerID,Shopno,total,used,banlance) values('" . $this->Get_New_TellerID() . "','" . $this->input['SC'] . "','0','0','0')";
			$this->Exc_Sql($Upadte_Teller_Sql);
		}

		return $Get_TellarID;
	}
	private function Get_New_TellerID() {
		$Get_TellarID = 0;

		try
		{
			$sql = "select max(tellerid)+1 from FD_Teller ";
			$Get_TellarID = $this->GetStringData($sql);
		} catch (\Exception $ee) {
			$Get_TellarID = 1;
		}

		return $Get_TellarID;
	}
	private function GetDate_AcheavieFrom() {
		$Get_Date = "";
		$month = $this->input['Month'] + 1;
		if ($this->input['Month'] < 9) {
			$Get_Date = $this->input['Year'] . "0" . $month;
		} else {
			$Get_Date = $this->input['Year'] . $month;
		}

		return $Get_Date;

	} //业绩所属期
	private function Update_FD_Teller() {
		if ($this->Get_TellerID() < 1) {
			$Upadte_Teller_Sql = "insert FD_Teller(TellerID,Shopno,total,used,banlance) values('" . $this->Get_New_TellerID() . "','" . $this->input['SC'] . "','0','0','0')";
			$this->Exc_Sql($Upadte_Teller_Sql);
		}

	}
	private function Show_Total_Balance() {
		$TellarTotal = "0";

		try
		{
			$sql = "select banlance from FD_Teller where shopno='" . $this->input['SC'] . "'";
			$TellarTotal = (int) $this->GetStringData($sql);
			$this->input['Balance'] = $TellarTotal;
		} catch (\Exception $ee) {
			$Upadte_Teller_Sql = "insert FD_Teller(TellerID,Shopno,total,used,banlance) values('" . $this->Get_New_TellerID() . "','" . $this->input['SC'] . "','0','0','0')";
			$this->Exc_Sql($Upadte_Teller_Sql);
			$this->input['Balance'] = "0";

		}

	}
	private function Get_Tellar_Info() {
		$data = $this->FDTellerdetail->getByWhere(["GroupID" => $this->input["GroupID"]]);
		return $data;
	}
	private function Show_TellarTotal() {
		$sql = "select sum(used) from FD_TellerDetail where groupid='" . $this->input['GroupID'] . "'";
		$data = (int) $this->GetStringData($sql);
		return $data;
	}
	public function DELETE() {
		$rule = [
			"CashID_Delete" => "require",
			"Amount" => "require|between:1,100000000",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$this->Delete_Tellar();
			$data['Get_Tellar_Info'] = $this->Get_Tellar_Info();
			$data['Get_Tellar_Info'] = $this->Show_TellarTotal();
			$this->Show_Total_Balance();
			$data['input'] = $this->input;
			Db::commit();
			return suc($data);
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	private function Delete_Tellar() {
		$this->Exc_Sql("delete from FD_TellerDetail where TellerDetailID='" . $this->input['CashID_Delete'] . "'");
		$this->ProcessChangeTeller($this->input['SC']);
	}
}
