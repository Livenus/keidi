<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title 管理佣金
 * @type menu
 */
class CommissionGet extends Common {

	public $Bank = ["ACCESS", "NEWFIRST",
		"OLDFIRST",
		"SKYE",
		"UNION",
		"ZENITH",
		"ALL"];
	public $CommissionID = "";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");

	}
	/**
	 * @title 查询全部佣金
	 * @type interface
	 * @login 1
	 * @param Year 年份
	 * @param Month 月份
	 */
	public function button1_Click() {
		if (strlen($this->input['Year']) != 4 || strlen($this->input['Month']) != 2) {
			return err(9000, "请输入正确的所属年月！");
		} else {
			if ($this->input['SC1'] == "" && $this->input['SC2'] == "" && $this->input['SC3'] == "" && $this->input['SC4'] == "" && $this->input['SC5'] == "" && $this->input['SC6'] == "") {
				return err(9000, "请输入店主卡号");
			} else {
				$data['search'] = $this->Search();
				$data['input'] = $this->input;
				return suc($data);
			}
		}
	}
	/**
	 * @title 管理佣金--查询
	 */
	private function Search() {
		$sql = "select shopno as SC,sum(TotalMoney) as Achievement,sum(debitMoney)as Debit,' ' as Memo from frontdesk_report where realdate between '" . $this->input['OldDate'] . "' and '" . $this->input['NewDate'] . "' and area='" . $this->input['Region'] . "'  and reporttype='SC' group by shopno order by shopno";
		$data = $this->query_Sql($sql);
		$data = $this->GetCommission($data);
		return $data;
	}
	private function GetCommission($data) {
		try
		{
			$TotalMoney = 0;
			$DeductMoney = 0;
			$DebitMoney = 0;
			foreach ($data as $k => $v) {
				$TotalMoney += $v['Achievement'];
				if ($v['SC'] == $this->input['SC1'] || $v['SC'] == $this->input['SC2'] || $v['SC'] == $this->input['SC3'] || $v['SC'] == $this->input['SC4'] || $v['SC'] == $this->input['SC5'] || $v['SC'] == $this->input['SC6']) {
					$DeductMoney += $v['Debit'];
					$data[$k]['Memo'] = "Deduct";
				} else {
					$DebitMoney += $v['Debit'];
				}

			}
			$this->input['RegionMoney'] = $TotalMoney;
			$this->input['DeductSc'] = $DeductMoney;
			$this->input['Debt'] = $DebitMoney;
			$this->input['Commission'] = (Floor(($TotalMoney - $DeductMoney - $DebitMoney) * 0.01));
			return $data;
		} catch (\Exception $ee) {}
	}
	public function button2_Click() {
		$rule = [
			"RegionMoney" => "require|number",
			"RegionMoney" => "require|number",
			"RegionMoney" => "require|number",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9001, $check);
		}
		$sql = "select count(*)  as count from kedi_commission where date_year=" . $this->input['Year'] . " and date_month=" . $this->input['Month'] . " and region='" . $this->input['Region'] . "'";
		$count = $this->GetStringData($sql);
		Db::startTrans();
		try {
			if ($count > 0) {
				$data = $this->UpdateCommission();} else { $data = $this->SaveCom();}

			Db::commit();
			return $data;
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	public function button3_Click() {
		$rule = [
			"Year" => "require|number",
			"Month" => "require|number",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9001, $check);
		}
		$last = $this->input['Year'] - 1;
		$sql = "select region as region,''as Currency,commission as'" . $this->input['Year'] . "-" . $this->input['Month'] . "',LastCommission as '" . $last . "-" . $this->input['Month'] . "',Rate as year_growth from kedi_commission where date_year=" . $this->input['Year'] . " and date_month=" . $this->input['Month'];
		$data = $this->query_Sql($sql);
		return suc($data);
	}
	private function UpdateCommission() {
		$sql = "select comid from kedi_commission where date_year=" . $this->input['Year'] . " and date_month=" . $this->input['Month'] . " and region='" . $this->input['Region'] . "'";
		$CommissionID = $this->GetStringData($sql);
		$sql = "update kedi_commission set TotalMoney=" . $this->input['RegionMoney'] . ",DeductMoney=" . $this->input['DeductSc'] . ",Debit=" . $this->input['Debt'] . ",Commission=" . $this->input['Commission'] . " where comid=" . $CommissionID;
		$status = $this->Exc_Sql($sql);
		if (is_numeric($status)) {
			$this->SaveDetail($CommissionID);
			return suc("更新成功！");
		} else {
			throw new \Exception("Error:Sql:" . $sql);
		}

	}
	private function SaveDetail($ComID) {
		$data = $this->Search();
		foreach ($data as $v) {
			$Memo = $v['Memo'];
			$Mark = 0;
			if (strtoupper($Memo) == "DEDUCT") {
				$Mark = "1";
			}

			$sql = "insert kedi_comdetail(ComdetailID,SC,TotalMoney,Debit,DeductMark,ComID,Memo) "
			. "values(" . $this->GetNewCDID() . ",'" . $v['SC'] . "'," . $v['Achievement'] . "," . $v['Debit'] . ",'" . $Mark . "'," . $ComID . ",'" . $Memo . "')";
			$status = $this->Exc_Sql($sql);
			if (!is_numeric($status)) {
				throw new \Exception("插入专卖店详情失败！sql:" . $sql);

				break;
			}
		}
	}
	private function GetNewCDID() {
		$sql = "select isnull(max(ComdetailID),0)+1 from kedi_comdetail";
		return $this->GetStringData($sql);
	}
	private function SaveCom() {
		$CommissionID = $this->GetNewCID();
		$sql = "insert kedi_commission(ComID,Region,TotalMoney,DeductMoney,Debit,Commission,Date_Year,Date_Month,Memo) values(" . $CommissionID . ",'" . $this->input['Region'] . "'," . $this->input['RegionMoney'] . "," . $this->input['DeductSc'] . "," . $this->input['Debt'] . "," . $this->input['Commission'] . "," . $this->input['Year'] . "," . $this->input['Month'] . ",'" . $this->input['Memo'] . "')";
		$status = $this->Exc_Sql($sql);
		if (!is_numeric($status)) {
			$this->SaveDetail($CommissionID);
			return suc("保存成功！");
		} else {
			throw new \Exception("Error:Sql:" . $sql);
		}

	}

}
