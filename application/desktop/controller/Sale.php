<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class Sale extends Common {

	public $GID;
	public $realdate;
	public $oper_name;
	public $ShopNO;
	public $TellarMoney;
	public $CashMoney;
	public $SaleType = 1;
	public $NetType = 2;

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->Sale = model("Sale");
		$this->SaleInfo = model("SaleInfo");
		$this->SaleDetail = model("SaleDetail");
		$this->SaleEnteredByFront = model("SaleEnteredByFront");
		$this->Customer = model("Customer");
		$this->CustomerInfo = model("CustomerInfo");
	}

	public function InsertOrder() {
		$input = input("post.");
		$desk = $this->SaleEnteredByFront->getByWhereOne(["SaleID" => $input["SaleID"]]);
		$Customer = $this->Customer->getByWhereOne(["CustomerID" => $input["CustomerID"]]);
		$Customer_info = $this->CustomerInfo->getByWhereOne(["CustomerID" => $input["CustomerID"]]);
		$data["ReturnMan"] = $Customer["CustomerNO"];
		$data["SaleID"] = $input["SaleID"];
		$data["SaleNO"] = $desk["SaleNo"];
		$data["CustomerID"] = $Customer["CustomerID"];
		$data["BranchID"] = 0;
		$data["ShopNO"] = $desk["ShopNO"];
		$data["BuyDate"] = date("Y-m-d H:i:s");
		$data["SaleType"] = $this->SaleType;
		$data["TotalPV"] = $desk["Total_BV"];
		$data["TotalRetail"] = $desk["Total_PV"];
		$data["TotalMember"] = $desk["Total_NAIRA"];
		$data["NetType"] = $this->NetType;
		$data["Status"] = 0;
		$data["AccountMan"] = self::$realname;
		$data["ProcessStatus"] = 10;
		$data["ProcessFlag"] = '已购买';
		$data["AccountDate"] = $data["BuyDate"];
		$data["SaleDate"] = $data["BuyDate"];
		$data["BuyDate"] = $data["BuyDate"];
		$data["PayType"] = 1;
		$data["SendType"] = 1;
		$data["SendMoney"] = $desk["Total_NAIRA"];
		$data["TotalMoney"] = $desk["Total_NAIRA"];
		$data["ShopID"] = 0;
		Db::startTrans();
		try {
			$status = $this->Sale->add($data);
			if ($status["stat"] == 0) {
				throw new \Exception($status["errmsg"]);
			}
			$data_info["SaleID"] = $input["SaleID"];
			$data_info["Address"] = "";
			$data_info["Phone"] = "";
			$data_info["PostCode"] = "";
			$data_info["ReceiveMan"] = $Customer_info['CustomerName'];
			$status2 = $this->SaleInfo->add($data_info);
			if ($status2["stat"] == 0) {
				throw new \Exception($status2["errmsg"]);
			}
			$status3 = $this->SaleDetail->add($input["SaleID"]);
			if ($status3["stat"] == 0) {
				throw new \Exception($status3["errmsg"]);
			}
			$data_desk["CustomerNO"] = $Customer["CustomerNO"];
			$data_desk["Current_Status"] = 2;
			$data_desk["SaleDate"] = $data["BuyDate"];
			$data_desk["Calc_Oper_Name"] = self::$realname;
			$status4 = $this->SaleEnteredByFront->editById($data_desk, $input["SaleID"]);
			if ($status4["stat"] == 0) {
				throw new \Exception($status4["errmsg"]);
			}
			Db::commit();
			return suc($status4);
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}
	}

	public function fresh() {
		$input = input("post.");
		$desk = $this->SaleEnteredByFront->getByWhereOne(["SaleID" => $input["SaleID"]]);
		if (empty($desk)) {
			return err(9000, "无此订单");
		}
		$data = $this->SaleEnteredByFront->GetTotalResult($desk["GroupID"]);
		return suc($data);
	}

	public function Activate_Order() {
		$input = input("post.");
		$desk = $this->SaleEnteredByFront->getByWhereOne(["SaleID" => $input["SaleID"]]);
		if (empty($desk)) {
			return err(9000, "无此订单");
		}
		$data["Status"] = 1;
		$data["AccountMan"] = self::$realname;
		$data["ProcessStatus"] = 30;
		$data["ProcessFlag"] = '已到款';
		$status = $this->Sale->editById($data, $desk["SaleID"]);
		if ($status["stat"] == 1) {
			return suc($status["data"]);
		}
		return err(9000, $status["errmsg"]);
	}

	//清除
	public function RemoveWhoseMark3() {
		$input = input("post.");
		$status = $this->Sale->del($input["GroupID"]);
		if ($status["stat"] == 1) {
			$msg[] = suc("删除成功");
		} else {
			$msg[] = err(7000, $status["errmsg"]);
		}
		$msg[] = $this->ProcessMix();
		$msg[] = $this->ProcessCustomerIDlost();
		return err(8001, $msg);
	}

	//从临时表到正式表移动过程中总BV发生变化的数据
	private function ProcessMix() {
		$input = input("post.");
		$data = $this->SaleEnteredByFront->ProcessMix($input["GroupID"]);
		if ($data) {
			return err(7000, $data);
		}
		return true;
	}
	//进入正式表的时候部分订单丢失卡号
	private function ProcessCustomerIDlost() {
		$input = input("post.");
		$data = $this->Sale->ProcessCustomerIDlost($input["GroupID"]);
		return $data;

	}
	//错误订单
	public function Insert_Error_Form_Data() {
		$input = input("post.");
		$rule = [
			"SaleNO" => "require",
			"CustomerNO" => "require",
		];
		$check = $this->validate($input, $rule);
		if ($check !== true) {

			return err(9001, $check);
		}
		$desk = $this->SaleEnteredByFront->getByWhereOne(["SaleNO" => $input["SaleNO"]]);
		if (empty($desk)) {
			return err(9001, "没有此订单");
		}
		$CalcErrorData = model("CalcErrorData");
		$data["SaleNO"] = $input["SaleNO"];
		$data["Error_Form_KN"] = $input["CustomerNO"];
		$data["Error_Form_SC"] = $desk["ShopNO"];
		$data["ErrorType"] = $this->Get_ErrorType($input["CustomerNO"]);
		$data["ErrorDate"] = date("Y-m-d H:i:s");
		if ($CalcErrorData->getCount(["SaleNO" => $input["SaleNO"]])) {
			return err(9000, "订单已经移除");
		}
		$status = $CalcErrorData->add($data);
		$msg[] = $status;
		$data_sale["Current_Status"] = 3;
		$data_sale["SaleDate"] = date("Y-m-d H:i:s");
		$data_sale["Calc_Oper_Name"] = self::$realname;
		$status1 = $this->SaleEnteredByFront->editById($data_sale, $desk["SaleID"]);
		$msg[] = $status1;
		return suc($msg);
	}
	private function Get_ErrorType($CustomerNO) {
		if ($CustomerNO == "") {
			return "No Code on the form";
		} else if (!preg_match("/\d{6}/", $CustomerNO)) {
			return "Incorrect Code Number";
		} else if (preg_match("/\d{6}/", $CustomerNO)) {
			return "Diffrent Number";
		} else if ($CustomerNO == "MORE") {
			return "前台多录";
		} else {
			return "Unkown Error";
		}

	}

}
