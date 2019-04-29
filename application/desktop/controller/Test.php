<?php

namespace app\desktop\controller;
use app\desktop\controller\Common;

class Test extends Common {
	public $date = "";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->date = date('Y-m-d');
	}

	public function index() {

		$this->add();
		$step2 = $this->report();
		$groupid = json_decode($step2, true);
		$step3 = $this->report_list($groupid['data']['GroupID']);
		$data = json_decode($step3, true);
		$data = $data["data"];
		$data_input["usertoken"] = $data['input']['usertoken'];
		$data_input["GroupID"] = $data['input']['GroupID'];
		$data_input["SN"] = $data['S_Nylon'];
		$data_input["BN"] = $data['B_Nylon'];
		$data_input["LastEditTime"] = $data['input']['RealDate'];
		$data_input["kits"] = $data['input']['Kits'];
		$data_input["CashMoney"] = $data['CashMoney'];
		$data_input["TellarMoney"] = $data['TellarMoney'];
		$data_input["DebitMoney"] = $data['DebitMoney'];
		$data_input["Disc_Trsfer"] = $data['Disc_Trsfer'];
		$data_input["Cash_USD"] = $data['TellarMoney'];
		$data_input["Realdate"] = $data['input']['RealDate'];
		$data_input["CheckHalf"] = $data['CheckHalf'];
		$data_input["ScNaira"] = $data['ScNaira'];
		$data_input["ScBV"] = $data['ScBV'];
		$data_input["ScPV"] = $data['ScPV'];
		$step4 = $this->GoWareHouse($data_input);
		print_r($step4);
	}
	public function clear() {
		$sql = "delete from tb_Sale_Entered_ByFrontDesk where Oper_Name='吴万光'";
		$this->Exc_Sql($sql);
		$sql = "delete from FrontDesK_Report where Oper_Name='吴万光'";
		$this->Exc_Sql($sql);
		$sql = "delete from tb_Sale_Entered_ByFrontDesk where Oper_Name='王菁'";
		$this->Exc_Sql($sql);
		$sql = "delete from FrontDesK_Report where Oper_Name='王菁'";
		$this->Exc_Sql($sql);
		$sql = "delete from StockOut where InsertPerson='吴万光'";
		$this->Exc_Sql($sql);
		$sql = "delete from StockOut where InsertPerson='王菁'";
		$this->Exc_Sql($sql);
	}
	public function add() {
		$url = "http://192.168.0.254/index.php?s=desktop/Sale_Entered_By_Front/add";
		$data = [
			"usertoken" => input("usertoken"),
			"ProductNO" => '[{"ProductNO":"A01","Amount":3}]',
			"Shopno" => 105,
			"RealDate" => $this->date,
		];
		return curl_post($url, $data);
	}
	public function report() {
		$url = "http://192.168.0.254/index.php?s=desktop/Sale_Entered_By_Front/report";
		$data = [
			"usertoken" => input("usertoken"),
			"ShopNO" => 105,
			"RealDate" => $this->date,
			"Naira" => 12870,
		];
		return curl_post($url, $data);
	}
	public function report_list($GroupID) {
		$url = "http://192.168.0.254/index.php?s=desktop/Sale_Entered_By_Front/report_list";
		$data = [
			"usertoken" => input("usertoken"),
			"GroupID" => $GroupID,
			"ShopNO" => '105',
			"RealDate" => $this->date,
			"Area" => 'NONE',
			"Kits" => 0,
		];
		return curl_post($url, $data);
	}
	public function GoWareHouse($data) {
		$url = "http://192.168.0.254/index.php?s=desktop/Sale_Entered_By_Front/GoWareHouse";
		$data1 = [
			"usertoken" => $data['usertoken'],
			"groupid" => $data['GroupID'],
			"Shopno" => '105',
			"SN" => $data['SN'],
			"BN" => $data['BN'],
			"LastEditTime" => $data['LastEditTime'],
			"kits" => $data['kits'],
			"Area" => 'NONE',
			"status" => 'NO',
			"CashMoney" => $data['CashMoney'],
			"TellarMoney" => $data['TellarMoney'],
			"DebitMoney" => $data['DebitMoney'],
			"Disc_Trsfer" => $data['Disc_Trsfer'],
			"Cash_USD" => $data['Cash_USD'],
			"Realdate" => $data['Realdate'],
			"CheckHalf" => $data['CheckHalf'],
			"CheckIfDataCorrect" => 1,
			"ReportType" => 'SC',
			"Result" => 0,
			"ScNaira" => $data['ScNaira'],
			"ScBV" => $data['ScBV'],
			"ScPV" => $data['ScPV'],
		];
		return curl_post($url, $data1);
	}
	public function rest() {
		$sql = "select top 10 * from EnterApp_User ";
	}

}
