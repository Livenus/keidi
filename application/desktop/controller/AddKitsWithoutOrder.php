<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class AddKitsWithoutOrder extends Common {

	public $GroupID = "";

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
		$this->TellerOper = self::$realname;
		$this->MarketShop = model("MarketShop");

	}
	public function load() {
		$data['comboBLcondition'] = $this->comboBLcondition;
		return suc($data);
	}
	/**
	 * @title
	 * @type interface
	 * @login 1
	 * @param Kits 套件数量
	 * @param Date 日期
	 * @param SC 专卖店编号
	 */
	public function button1_Click() {

		$rule = [
			"Kits" => "require",
			"Date" => "require",
			"SC" => "\d{3}",
		];
		$msg = [
			"Kits" => "The amount of kits should not be 0!",
			"Date" => "",
			"SC" => "Pls enter correct shopno!",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);

		}
		$shop = $this->MarketShop->getBywhereOne(["Shopno" => $this->input["SC"]]);
		if (empty($shop)) {
			return err(9001, "专卖店不存在");
		}
		Db::startTrans();
		try {
			$this->GroupID = $this->Get_FD_ID();
			$this->Insert_Kits_NoOrder();
			$data["msg"] = "添加成功，请关闭此窗口，搜索纯kits专卖店打印！";
			$data["action"] = "this.Close()";
			Db::commit();
			return suc($data);
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	//生成新的订单编号(GroupID)
	private function Get_FD_ID() {
		$time = strtotime($this->input['Date']);
		$ID = date("YmdHis") . "-" . $this->input["SC"];
		//$ID = date("Y", $time) . date("m", $time) . date("d", $time) . date("H") . date("i") . date("s") . "-" . $this->input["SC"];
		return $ID;
	}
	private function Insert_Kits_NoOrder() {
		$InsertSql = "insert FrontDesk_Report(GroupID) values('" . $this->GroupID . "') ";
		$this->Exc_Sql($InsertSql);

		$InsertSql = "update FrontDesk_Report set Oper_name='" . self::$realname . "',shopno='" . $this->input['SC'] . "',realdate='" . $this->input['Date'] . "',kits='" . $this->input['Kits']
		. "',area='" . $this->input['Region'] . "',Totalmoney=0,cashmoney=0,tellarmoney=0,debitmoney=0,disc_trsfer=0,cash_USD=0,scbv=0,scpv=0,scnaira=0, ReportType='SC',ReportStatus='NO',BelongedSC='None' where groupid='" . $this->GroupID . "'";

		$this->Exc_Sql($InsertSql);
		$price = self::$KitsValue;
		$InsertSql = "update FrontDesk_Report set Totalmoney=kits*{$price} where groupid='" . $this->GroupID . "'";

		$this->Exc_Sql($InsertSql);
	}
}
