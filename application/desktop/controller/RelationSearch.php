<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

/**
 * @title 关联查询RelationSearch
 * @type menu
 * @login 1
 */
class RelationSearch extends Common {
	private $comboBox1 = [
		"有效日期",
		"确认日期"];
	private $comboBox2 = [
		"系统TellerID",
		"手动TellerID"];
	private $comboBox4 = [
		"系统",
		"手动"];
	//拼接查询语句
	private $SelectResult = "select tb_systemteller.Tellerid as 系统ID," .
		"tb_tempteller.tellerid as 手动ID," .
		"tb_systemteller.bank_name as 系统银行," .
		"tb_tempteller.BANK_NAME AS 手动银行," .
		"tb_systemteller.EFFECTIVE_DATE AS 有效日," .
		"tb_systemteller.DEPOSIT AS 系统数额," .
		"tb_tempteller.deposit as 手动数额," .
		"tb_systemteller.确认日期," .
		"tb_tempteller.专卖店 as 手动SC," .
		"tb_systemteller.IMPORTPERSON AS 导入人," .
		"tb_systemteller.MEMO AS 系统备注," .
		"tb_tempteller.MEMO AS 转账类型," .
		"tb_tempteller.使用 " .
		"from tb_systemteller,tb_tempteller";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");

	}
	public function load() {
		$data['comboBox1'] = $this->comboBox1;
		$data['comboBox2'] = $this->comboBox2;
		$data['comboBox4'] = $this->comboBox4;
		return suc($data);
	}
	/**
	 * @title 关联查询RelationSearch获取银行列表
	 * @type interface
	 * @login 1
	 * @return data 结果集合 array
	 */
	public function getBank() {
		$sql = 'select BANK_NAME from tb_systemteller group by BANK_NAME';
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
	/**
	 * @title 关联查询RelationSearch 关联项搜索
	 * @type interface
	 * @login 1
	 * @param comboBox1 有效日期或确认日期(0,1)
	 * @param comboBox2 系统TellerID或手动TellerID(0,1)
	 * @param comboBox3 选择的银行名称
	 * @param comboBox4 系统或手动(0,1)
	 * @param amount  数额
	 * @param shopno  专卖店或经销商编号
	 * @param from   查询开始日期
	 * @param to     查询结束日期
	 * @param textBox1 编号(tellerid)
	 * @param pos  是否勾选POS
	 * @return data
	 */
	public function button1_Click() {
		$data = $this->Searchs();
		if ($data) {
			$data = utf_8($data);
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	//查询
	private function Searchs() {
		if ($this->input['textBox1'] == "") //不按编号查询
		{
			if ($this->input['amount'] != "") {
				if ($this->input['comboBox4'] == 0) {
					$sql = $this->SelectResult .
					" where tb_tempteller.systemtellerid=tb_systemteller.tellerid" .
					" and tb_systemteller.DEPOSIT=" . $this->input['amount'] . $this->PosStatus() . "  and " . $this->DateType() . ">='" . $this->input['from'] . "' and " . $this->DateType() . "<='" . $this->input['to'] . "' and tb_tempteller.专卖店 like'" . $this->input['shopno'] . "%'" . $this->GetBankCondition();
				} else {
					$sql = $this->SelectResult .
					" where tb_tempteller.systemtellerid=tb_systemteller.tellerid" .
					" and tb_tempteller.DEPOSIT=" . $this->input['amount'] . $this->PosStatus() . "  and " . $this->DateType() . ">='" . $this->input['from'] . "' and " . $this->DateType() . "<='" . $this->input['to'] . "' and tb_tempteller.专卖店 like'" . $this->input['shopno'] . "%'" . $this->GetBankCondition();
				}

			} else {
				$sql = $this->SelectResult .
				" where tb_tempteller.systemtellerid=tb_systemteller.tellerid" . $this->PosStatus() .
				" and " . $this->DateType() . ">='" . $this->input['from'] . "' and " . $this->DateType() . "<='" . $this->input['to'] . "' and tb_tempteller.专卖店 like'" . $this->input['shopno'] . "%'" . $this->GetBankCondition();
			}
		} else {
			if ($this->input['comboBox2'] == 0) {
				$sql = $this->SelectResult .
				" where tb_tempteller.systemtellerid=tb_systemteller.tellerid" .
				" and tb_systemteller.tellerid=" . $this->input['textBox1'];
			} else {
				$sql = $this->SelectResult .
				" where tb_tempteller.systemtellerid=tb_systemteller.tellerid" .
				" and tb_tempteller.tellerid=" . $this->input['textBox1'];
			}

		}

		$data = $this->query_Sql($sql);
		return $data;
	}
	//是否选择POS
	private function PosStatus() {
		if ($this->input['pos']) {
			return " and tb_tempteller.MEMO='POS' ";
		} else {
			return "";
		}

	}
	//选择的是有效日期 0还是确认日期 1
	private function DateType() {
		if ($this->comboBox1[$this->input['comboBox1']] == "有效日期") {
			return "tb_systemteller.EFFECTIVE_DATE";
		} else {
			return "tb_systemteller.确认日期";
		}

	}
	//拼接选择银行的选择条件
	private function GetBankCondition() {
		if ($this->input['comboBox3'] == 'ALL') {
			return "";
		} else {
			return " and tb_systemteller.bank_name='" . $this->input['comboBox3'] . "'";
		}

	}
}
