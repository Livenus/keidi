<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class SaleEnteredByFront extends Common {

	public $GID;
	public $realdate;
	public $oper_name;
	public $ShopNO;
	public $TellarMoney = 0;
	public $CashMoney = 0;
	public $ScBV = "";
	public $ScPV = "";
	public $ScNaira = "";
	public $Delete_View = "";
	public $SaleGroupID = "";
	public $SaleGroupSc = "";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->SaleEnteredByFront = model("SaleEnteredByFront");
		$this->SaleDetailByFront = model("SaleDetailByFront");
		$this->Customer = model("Customer");
		$this->Sale = model("Sale");
		$this->Product = model("Product");
		$this->MarketShop = model("MarketShop");
		$this->oper_name = self::$user['realname'];
		$this->input = input("post.");
	}

	public function add() {
		$check = $this->validate($this->input, "SaleEnteredByFront");
		if ($check !== true) {
			return err(9000, $check);
		}
		$admin = self::$user;
		$this->input["ProductNO"] = json_decode($this->input["ProductNO"], true);

		$Total_BV = 0;
		$Total_PV = 0;
		$Total_NAIRA = 0;
		foreach ($this->input["ProductNO"] as $v) {
			$product = $this->Product->getBywhereOne(["ProductNO" => $v["ProductNO"], "Status" => 1]);
			if (empty($product)) {
				return err(9002, "获取商品出错");
			}
			$product["Amount"] = $v["Amount"];
			$Total_BV = $Total_BV + $product["BV"] * $v["Amount"];
			$Total_PV = $Total_PV + $product["PV"] * $v["Amount"];
			$Total_NAIRA = $Total_NAIRA + $v["Amount"] * $product["MemberPrice"];
			$products[] = $product;
		}
		$shop = $this->MarketShop->getBywhereOne(["Shopno" => $this->input["Shopno"]]);
		if (empty($shop)) {
			return err(9001, "专卖店不存在");
		}
		$order["CustomerNO"] = "KN999999";
		$order["BranchID"] = 0;
		$order["SaleDate"] = $this->input["RealDate"];
		$order["RealDate"] = $this->input["RealDate"];
		$order["ShopNO"] = $shop["Shopno"];
		$order["Total_BV"] = $Total_BV;
		$order["Total_PV"] = $Total_PV;
		$order["Total_NAIRA"] = $Total_NAIRA;
		$order["Oper_Name"] = $admin["realname"];
		Db::startTrans();
		$order_new = $this->SaleEnteredByFront->add($order);
		$order_new = $this->SaleEnteredByFront->getBywhereOne(["SaleNO" => $order_new["SaleNO"]]);
		foreach ($products as $v) {
			$order_detail = array();
			$order_detail["SaleID"] = $order_new["SaleID"];
			$order_detail["ProductID"] = $v["ProductID"];
			$order_detail["Amount"] = $v["Amount"];
			$order_detail["PV"] = $v["PV"];
			$order_detail["RetailPrice"] = $v["RetailPrice"];
			$order_detail["MemberPrice"] = $v["MemberPrice"];
			$status = $this->SaleDetailByFront->add($order_detail);
		}
		if ($status["stat"] == 1) {
			Db::commit();
			return suc($order_new);
		} else {
			Db::rollback();
		}
		return err(9000, "没有数据");
	}

	/*
		      订单详情
	*/

	public function detail($SaleID = "") {
		$rule = [
			'SaleID' => 'require',
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9001, $check);
		}
		$data = $order_SaleID = $this->SaleEnteredByFront->getById($SaleID);
		if ($data) {
			return suc($data);
		}
		return err(9000, "没有数据");
	}

	/*
		      未计算订单列表 search
	*/

	public function get_list() {
		$rule = [
			"RealDate" => "require|\d{4}.*\d$",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$admin = self::$user;
		$rep["last_date"] = $this->SetDateForFD();
		$data = $this->SaleEnteredByFront->select_reload($this->input["RealDate"], $admin["realname"], $this->input["Shopno"]);
		if ($data) {
			$total_bv = 0;
			$total_pv = 0;
			$total_naira = 0;
			foreach ($data as $v) {
				$total_bv += $v["Total_BV"];
				$total_pv += $v["Total_PV"];
				$total_naira += $v["Total_naira"];
			}
			$rep["Total_BV"] = $total_bv;
			$rep["Total_PV"] = $total_pv;
			$rep["Total_naira"] = $total_naira;
			$rep["list"] = $data;
			$rep["lastshopno"] = $this->SetShopno();
			$rep["input"] = $this->input;
			return suc($rep);
		}
		return err(9000, "没有数据");
	}

	/*
		      最近录入的专卖店号
	*/

	public function SetShopno() {
		$admin = self::$user;
		$map["RealDate"] = date("Y-m-d", strtotime($this->input["RealDate"]));
		$map["Oper_Name"] = $admin["realname"];
		$map["Current_Status"] = 0;
		$data = $this->SaleEnteredByFront->getBywhereOne($map);
		return $data;
	}

	/*
		      最获取当前系统里正在录入单子的专卖店店号
	*/

	public function SetDateForFD() {
		$admin = self::$user;
		$map["Oper_Name"] = $admin["realname"];
		$map["Current_Status"] = 0;
		$data = $this->SaleEnteredByFront->getBywhereOne($map, "*", "", "RealDate desc");
		if ($data) {
			$this->input["RealDate"] = $data["RealDate"];
		}
		return $data;
	}

	public function report() {
		set_time_limit(120);
		$admin = self::$user;
		$rule = [
			"Naira" => "require|number|between:0.1,1000000",
			"ShopNO" => "require|number",
			"RealDate" => "require|\d{4}.*\d$",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$this->GID = $this->SaleEnteredByFront->Get_UnActive_GroupID($this->input["ShopNO"], $this->input["RealDate"], $admin["realname"]);
		$this->realdate = $this->input["RealDate"];
		$this->ShopNO = $this->input["ShopNO"];
		if ($this->GID) {

		} else {
			$this->GID = $this->SaleEnteredByFront->_set_group_id($this->input["ShopNO"], $this->input);
		}
		$status = $this->Set_GroupID();
		if ($status['stat'] == 1) {
			return suc(["GroupID" => $this->GID]);
		}
		return err(9000, "插入报单信息错误" . $status['errmsg']);
	}

	public function get_data($GroupID) {
		$product_model = Model("Product");
		$product = $product_model->report();
		$sale = $this->SaleEnteredByFront->report_detail($GroupID);
		foreach ($product as $k => $v) {
			foreach ($sale as $vv) {
				if ($v["Code"] == $vv["productno"]) {
					$product[$k]["QTY"] = $vv["amount"];
					$product[$k]["Total BV"] = $vv["amount"] * $v["BV(USD)"];
					$product[$k]["Total PV"] = $vv["amount"] * $v["PV(USD)"];
					$product[$k]["Total NR"] = $vv["amount"] * $v["Naira PRICE"];
				}
			}
		}
		return $product;
	}
	public function report_list() {
		$this->GID = $this->input["GroupID"];
		if ($this->CheckIfRegionLock(trim($this->input["GroupID"]))) {
			return err(9000, "The region " . $this->input['Area'] . " on " . date('Y-m-d') . " is already locked by Miss Alice!");
		}
		$product = $this->get_data($this->input["GroupID"]);

		$res["input"] = $this->input;
		$res["TellarMoney"] = $this->Set_TellarTotal();
		$res["CashMoney"] = $this->Set_CashTotal();
		$total = $this->SaleEnteredByFront->Set_Total($this->input["ShopNO"], $this->input["RealDate"], $this->input["GroupID"]);
		$res["ScBV"] = $total["Total_BV"];
		$res["ScPV"] = $total["Total_PV"];
		$res["System Total"] = $total["Total_Naira"];
		$res["ScNaira"] = $total["Total_Naira"];
		if ($res["ScNaira"] == 0) {
			return err(9000, "未找到" . $this->input["GroupID"] . "相关的report");
		}
		$res["Grand Total"] = $res["TellarMoney"] + $res["CashMoney"];
		$res["Result"] = $res["Grand Total"] - $res["System Total"];
		$res["Status"] = $res["Result"] < 0 ? "NO" : "OK";
		$res["S_Nylon"] = floor($res["ScNaira"] / 10000);
		$res["B_Nylon"] = floor($res["S_Nylon"] / 4);
		$res["Aera"] = model("Region")->GetRegion();
		$res["Unit Price"] = self::$KitsValue;
		$res["DebitMoney"] = 0;
		$res["Disc_Trsfer"] = 0;
		$res["CheckHalf"] = $this->CheckHalf($product);
		$res["list"] = $product;
		return suc($res);
	}

	public function Set_GroupID() {

		$status = $this->SaleEnteredByFront->Set_GroupID($this->GID, $this->realdate, $this->oper_name, $this->ShopNO);
		return $status;
	}

	public function test() {
		$Procedure = model("Procedure");
		$re = $Procedure->sp_GetNewSaleNO();
		print_r($re);
	}

	public function Set_TellarTotal() {
		$this->GID = $this->input["GroupID"];
		return $this->TellarMoney = model("FrontDeskTellar")->sum_amount($this->GID);
	}

	public function Set_CashTotal() {
		$this->GID = $this->input["GroupID"];
		return $this->FrontDeskCash = model("FrontDeskCash")->sum_amount($this->GID);
	}

	public function search() {
		$data = $this->SaleEnteredByFront->search($this->input["from"], $this->input["to"], $this->input["area"]);
		return suc($data);
	}

	public function Sale_Delete() {
		$rule = [
			'SaleNo' => 'require|\d+$',
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9001, $check);
		}
		$where["SaleNo"] = $this->input["SaleNo"];
		$data = $this->SaleEnteredByFront->del($where);
		if ($data) {
			return suc($data);
		} else {
			return err(9000, "删除失败");
		}
	}

	//提货
	public function GoWareHouse($groupid, $Oper = "", $SaleType = "业绩销售", $SN, $BN, $Shopno, $LastEditTime, $CustomerNo = "KN000000", $insertdate = "", $memo = "", $SoftName = "前台系统", $ThisWindow = "Sale_Report", $kits = 0, $Area) {
		set_time_limit(120);
		$rule = [
			"kits" => "require",
			"Area" => "require",
			"ReportType" => "SC",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9003, $check);
		}
		if ($this->input["CheckHalf"] !== '1') {
			return err(9000, "half error" . $this->input["CheckHalf"]['errmsg']);
		}
		$admin = self::$user;
		Db::starttrans();
		try {
			if ($this->SaleEnteredByFront->getCount(['GroupID' => $groupid]) == 0) {
				throw new \Exception("订单号错误");
			}
			$StockOut = model("StockOut");
			if ($StockOut->getCount(["GroupID" => $groupid]) == 0 && $this->input['Result'] === '0') {

				$StockOutInfo = model("StockOutInfo");
				$StockOutDetail = model("StockOutDetail");
				$FrontDeskReport = model("FrontDeskReport");
				$stockout_data["LastEdittime"] = $LastEditTime;
				$stockout_data["GroupID"] = $groupid;
				$stockout_data["Status"] = 1;
				$stockout_data["ToPlace"] = $StockOut->GetRecieveInfo($CustomerNo, 2);
				$stockout_data["InsertPerson"] = $admin["realname"];
				$stockout_data["InsertDate"] = $LastEditTime;
				$stockout_data["SaleType"] = $SaleType;
				$stockout_data["SaleDate"] = $LastEditTime;
				$stockout_data["ShopNo"] = $Shopno;
				$stockout_data["Memo"] = $memo;
				$stockout_data["Snylon"] = floor($SN);
				$stockout_data["Bnylon"] = floor($BN);
				$stat = $StockOut->add($stockout_data);
				if ($stat["stat"] == 0) {
					throw new \Exception($stat["errmsg"] . "1");
				}
				$stockout_data_info["StockOutID"] = $stat["data"]["StockOutID"];
				$stockout_data_info["SendPerson"] = $admin["realname"];
				$stockout_data_info["SendMac"] = "";
				$stockout_data_info["SendIP"] = $this->Local_IP();
				$stockout_data_info["SendSoftware"] = $SoftName;
				$stockout_data_info["SendFunction"] = $ThisWindow;
				$stockout_data_info["CustomerID"] = $StockOut->GetRecieveInfo($CustomerNo, 0);
				$stockout_data_info["CustomerNo"] = $StockOut->GetRecieveInfo($Shopno, 1);
				$stockout_data_info["RecieveMan"] = $StockOut->GetRecieveInfo($Shopno, 2);
				$status = $StockOutInfo->add($stockout_data_info);
				if ($status["stat"] == 0) {
					throw new \Exception($status["errmsg"] . "2");
				}
				$sale_count = $this->SaleEnteredByFront->get_sale_out($groupid);
				foreach ($sale_count as $v) {
					$data_d["StockOutID"] = $stat["data"]["StockOutID"];
					$data_d["ProductID"] = $v["productid"];
					$data_d["Amount"] = $v["amount"];
					$data_d["Memo"] = $SaleType;
					$status = $StockOutDetail->add($data_d);
					if ($status["stat"] == 0) {
						throw new \Exception($status["errmsg"] . "3");
					}
				}
				$out = $this->ProcessNylon_kitsIntoDetail($stat["data"]["StockOutID"], floor($SN), $FrontDeskReport->GetKitsAfterdeudct($groupid, $kits), $FrontDeskReport->GetKitsLanguage($groupid, $kits), $SaleType);
				if ($out["stat"] == 0) {
					throw new \Exception($out["errmsg"] . "4");
				}
				$status = $FrontDeskReport->SetWareHouse(1, $groupid);
				if ($status["stat"] == 0) {
					throw new \Exception($status["errmsg"] . "5");
				}
				$status = $StockOut->SetMarkStandforInto($groupid);
				if ($status["stat"] == 0) {
					throw new \Exception($status["errmsg"] . "6");
				}
				$status = $this->AddSaleTypeID();
				if ($status["stat"] == 0) {
					throw new \Exception($status["errmsg"] . "7");
				}
				$status = $FrontDeskReport->Update_FrontDesk_Report($groupid, $this->input["status"], $this->input["CashMoney"], $admin["realname"], $this->input["TellarMoney"], $this->input["DebitMoney"], $this->input["Disc_Trsfer"], $this->input["Cash_USD"], $LastEditTime, $Shopno, $Area, $kits, $this->input["ScBV"], $this->input["ScPV"], $this->input["ScNaira"], self::$KitsValue, "Normal", $this->GetKitsLanguage());
				if ($status["stat"] == 0) {
					throw new \Exception($status["errmsg"] . "8");
				}
				$rep["msg"] = "更新";

			} else {
				$rep["msg"] = "展示";
			}
			DB::commit();
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

		$rep["sum"] = $this->SaleEnteredByFront->Set_Total($Shopno, $LastEditTime, $groupid);
		$rep["input"] = $this->input;
		if ($this->input["CheckHalf"]) {
			$rep["list"] = $this->Pre_Print();
			if (isset($this->input["CheckIfDataCorrect"])) {
				$right = $this->CheckIfDataCorrect($rep["list"]);
				if ($right) {
					$list = $this->Pre_Print_2();
					if (isset($list['stat'])) {
						return err(9000, "错误" . $list);
					}
					$rep["list"] = $list;
				} else {
					return err(9000, "CheckIfDataCorrect错误");
				}
			}
			return suc($rep);
		}
		return err(9000, "错误");
	}
	private function GetKitsLanguage() {
		if ($this->input['French']) {
			return "French";
		} else {
			return "English";
		}

	}
	public function ProcessNylon_kitsIntoDetail($NewStockOutId, $snylon, $kits, $kitslanguage, $SaleType) {
		$StockOutDetail = model("StockOutDetail");
		$data_d["StockOutID"] = $NewStockOutId;
		$data_d["ProductID"] = 65;
		$data_d["Amount"] = $snylon;
		$data_d["Memo"] = $SaleType;
		$status = $StockOutDetail->add($data_d);
		if ($status["stat"] == 0) {
			return err(9000, "65错误");
		}
		if ($status) {
			$data_d["StockOutID"] = $NewStockOutId;
			$data_d["ProductID"] = 66;
			$data_d["Amount"] = $snylon;
			$data_d["Memo"] = $SaleType;
			$status = $StockOutDetail->add($data_d);
		}
		if ($status["stat"] == 0) {
			return err(9000, "66错误");
		}
		if ($kits > 0) {
			$data_d["StockOutID"] = $NewStockOutId;
			$data_d["ProductID"] = 61;
			$data_d["Amount"] = $snylon;
			$data_d["Memo"] = $SaleType;
			$status = $StockOutDetail->add($data_d);
		}
		if ($status["stat"] == 0) {
			return err(9000, "61错误");
		}
		if ($status) {
			$data_d["StockOutID"] = $NewStockOutId;
			$data_d["ProductID"] = 63;
			$data_d["Amount"] = $snylon;
			$data_d["Memo"] = $SaleType;
			$status = $StockOutDetail->add($data_d);
		}
		if ($status["stat"] == 0) {
			return err(9000, "63错误");
		}
		if ($status) {
			$data_d["StockOutID"] = $NewStockOutId;
			$data_d["ProductID"] = 64;
			$data_d["Amount"] = $snylon;
			$data_d["Memo"] = $SaleType;
			$status = $StockOutDetail->add($data_d);
		}
		if ($status["stat"] == 0) {
			return err(9000, "64错误");
		}
		if ($status) {
			$data_d["StockOutID"] = $NewStockOutId;
			$data_d["ProductID"] = 36;
			$data_d["Amount"] = $snylon;
			$data_d["Memo"] = $SaleType;
			$status = $StockOutDetail->add($data_d);
		}
		if ($status["stat"] == 0) {
			return err(9000, "36错误");
		}
		if ($kitslanguage == "French") {
			$data_d["StockOutID"] = $NewStockOutId;
			$data_d["ProductID"] = 60;
			$data_d["Amount"] = $snylon;
			$data_d["Memo"] = $SaleType;
			$status = $StockOutDetail->add($data_d);
		} else {
			$data_d["StockOutID"] = $NewStockOutId;
			$data_d["ProductID"] = 59;
			$data_d["Amount"] = $snylon;
			$data_d["Memo"] = $SaleType;
			$status = $StockOutDetail->add($data_d);
		}
		if ($status["stat"] == 0) {
			return err(9000, "60错误");
		}
		return suc($status);
	}

	public function CheckIfDataCorrect($data) {

		return true;
	}

	public function CheckHalf($data) {
		foreach ($data as $v) {
			if (trim($v["Code"]) == "M041") {
				if ($v["QTY"] % 2 == 0) {
					$result = true;
				} else {
					return err(9000, "Half products error,M041's amount is " . $v["QTY"]);
					$result = false;
				}
			}
			if (trim($v["Code"]) == "M092") {
				if ($v["QTY"] % 12 == 0) {
					$result = true;
				} else {
					return err(9000, "Half products error,M092's amount is " . $v["QTY"]);
					$result = false;
				}
			}
		}

		return (int) $result;
	}

	public function Pre_Print() {
		$data = $this->get_data($this->input["groupid"]);
		$data_down = $this->Set_Qty_0($data);
		return $data_down;
	}

	public function Pre_Print_2() {
		$data = $this->get_data($this->input["groupid"]);
		$data_down = $this->Set_Qty_0($data);
		$err = $this->SaleEnteredByFront->Activate_FD_Sale($this->input["groupid"], $this->oper_name, $this->input["LastEditTime"], $this->input["ReportType"]);
		if (!is_numeric($err)) {
			return err(3000, $err);
		}
		return $data_down;
	}

	public function Set_Qty_0($data) {
		$ScBv = 0;
		$ScPv = 0;
		$ScNaira = 0;
		foreach ($data as $k => $v) {
			if (trim($v["Code"]) == "M041") {
				$data[$k]["Code"] = 'M04';
				$data[$k]["Products"] = 'Blood Circulattory';
				$data[$k]["BV(USD)"] = '450';
				$data[$k]["PV(USD)"] = '500';
				$data[$k]["Naira PRICE"] = '178500';
				$data[$k]["QTY"] = $data[$k]["QTY"] / 2;
			}
			if (trim($v["Code"]) == "M051") {
				$data[$k]["Code"] = 'M051';
				$data[$k]["Products"] = 'C&H USE MSGE CUSHION';
				$data[$k]["BV(USD)"] = '375';
				$data[$k]["PV(USD)"] = '375';
				$data[$k]["Naira PRICE"] = '102380';
				$data[$k]["QTY"] = $data[$k]["QTY"] / 3;
			}
			if (trim($v["Code"]) == "M092") {
				$data[$k]["Code"] = 'M092';
				$data[$k]["Products"] = 'Massage Chair';
				$data[$k]["BV(USD)"] = '3000';
				$data[$k]["PV(USD)"] = '3000';
				$data[$k]["Naira PRICE"] = '1071000';
				$data[$k]["QTY"] = $data[$k]["QTY"] / 12;
			}
			if ($v["Total BV"] == 0) {
				$data[$k]["QTY"] = '--';
				$data[$k]["Total BV"] = '--';
				$data[$k]["Total PV"] = "--";
				$data[$k]["Total NR"] = "--";
			}
			$ScBv += $v["BV(USD)"];
			$ScPv += $v["PV(USD)"];
			$ScNaira += $v["Naira PRICE"];
		}

		$rep["list"] = $data;
		$kits = $this->input["kits"];
		$rep["Kits"] = ["Suit", "15", self::$KitsValue, $this->input["kits"], $this->Get_KitsPV($kits), $this->Get_KitsNaira($kits)];
		$rep["Nylon"] = $ScNaira;
		return $rep;
	}

	private function Get_KitsPV($kits) {

		return $kits * 15;
	}

	private function Get_KitsNaira($kits) {

		return self::$KitsValue * $kits;
	}

	public function Get_Mem_Info() {
		$rule = [
			"GroupID" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {

			return err(9001, $check);
		}
		$FrontDesk_Report = model("FrontDesk_Report");
		$Sale = model("Sale");
		$data["search"] = $this->SaleEnteredByFront->select_reload(1, 0, 0, 1, $this->input["GroupID"]);
		$data["err"] = $this->SaleEnteredByFront->Get_ErrorBV($this->input["GroupID"]);
		$data["report"] = $FrontDesk_Report->getBywhereOne(["GroupID" => $this->input["GroupID"]]);
		$data["Sale"] = $Sale->Get_Order_Info($this->input["GroupID"]);
		return suc($data);
	}

	//back
	public function BringBack_Fromtb_sale() {
		$rule = [
			"SaleNO" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {

			return err(9001, $check);
		}
		$desk = $this->SaleEnteredByFront->getByWhereOne(["SaleNO" => $this->input["SaleNO"]]);
		if (empty($desk)) {
			return err(9001, "没有此订单");
		}
		$data_sale["Current_Status"] = 1;
		$data_sale["SaleDate"] = date("Y-m-d H:i:s");
		$data_sale["Calc_Oper_Name"] = self::$realname;
		Db::startTrans();
		try {
			$status1 = $this->SaleEnteredByFront->editById($data_sale, $desk["SaleID"]);

			if ($status1["stat"] == 0) {
				throw new \Exception($status1["errmsg"] . "1");
			}
			$status2 = $this->Sale->delBysaleid($desk["SaleID"]);
			if ($status2["stat"] == 0) {
				throw new \Exception($status2["errmsg"] . "2");
			}
			Db::commit();
			return suc($status2["data"]);
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9001, $exc->getMessage());
		}
	}

}
