<?php

namespace app\desktop\controller;
use app\desktop\controller\Common;

/**
 * @title 现金付款
 */
class FrontDeskCash extends Common {
	public $GID;
	public $realdate;
	public $oper_name;
	public $ShopNO;
	public $TellarMoney;
	public $CashMoney;
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->FrontDeskCash = model("FrontDeskCash");
		$this->SaleEnteredByFront = model("SaleEnteredByFront");
		$this->input = input("post.");
		$this->oper_name = self::$user['realname'];
	}

	public function CashData() {
		$rule = [
			"GroupID" => "require|\d+.*\d$",
			"ShopNO" => "require|\d+$",
			"RealDate" => "require|\d{4}.*\d$",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$data_cash = $this->FrontDeskCash->Get_Cash_Info($this->input["GroupID"]);
		$CashTotal = $this->FrontDeskCash->sum_amount($this->input["GroupID"]);
		$data["Total"] = $CashTotal;
		$data["list"] = $data_cash;
		if ($data["list"]) {
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	public function Add_Cash() {
		$check = $this->validate($this->input, "FrontDeskCash.add");
		if ($check !== true) {
			return err(9001, $check);
		}
		$data = $this->FrontDeskCash->Add_Cash($this->input["GroupID"], $this->input["ShopNO"], $this->input["Amount"], $this->input["RealDate"], $this->oper_name, "CashType");
		if ($data["stat"] == 1) {
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	public function Delete_Cash() {
		$rule = [
			"CashID" => "require|\d+$",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$status = $this->FrontDeskCash->Delete_Cash($this->input["CashID"]);
		if ($status) {
			return suc("删除成功");
		}
		return err(9000, "删除失败");
	}
}
