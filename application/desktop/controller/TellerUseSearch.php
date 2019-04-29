<?php
namespace app\desktop\controller;
use app\desktop\controller\Common;

/**
 * @title Teller使用查询
 * @type menu
 * @login 1
 */
class TellerUseSearch extends Common {
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->TellerOper = self::$realname;
	}

	/**
	 *@title Teller使用查询  搜索已确认Teller
	 *@type interface
	 *@login 1
	 *@param from 搜索开始日期
	 *@param to 搜索结束日期
	 *@param textBox2 deposit(数额)
	 *@param textBox1 shopno(专卖店或经销商编号)
	 *@return data 结果集合
	 */
	public function button1_Click() {
		$data['select'] = $this->Searchs();
		//var_export($data['select']);die();
		$data['余额'] = $this->getBanlance();
		$data['余额'] = utf_8($data['余额']);
		return suc($data);
	}
	private function Searchs() {
		$sqlorder = "";
		$input = input('post.');
		//var_export($input);die();
		if ($input['textBox1'] == "") {
			if ($input['textBox2'] == "") {
				$sqlorder = "select TellerDetailID as Teller编号,shopno as 专卖店,deposit as 数额,bank as 银行,[date] as [日期(Date)],Oper as 操作人,memo1 as 备注,memo2 as 收款方式  from fd_tellerdetail where deposit<>0 and [date]>='" . $input['from'] . "' and [date]<='" . $input['to'] . "'";
			} else {
				$sqlorder = "select TellerDetailID as Teller编号,shopno as 专卖店,deposit as 数额,bank as 银行,[date] as [日期(Date),Oper as 操作人,memo1 as 备注,memo2 as 收款方式  from fd_tellerdetail where deposit<>0 and deposit=" . $input['textBox2'] . " and [date]>='" . $input['from'] . "' and [date]<='" . $input['to'] . "'";
			}

		} else {
			if ($input['textBox2'] == "") {
				$sqlorder = "select TellerDetailID as Teller编号,shopno as 专卖店,deposit as 数额,bank as 银行,[date] as [日期(Date)],Oper as 操作人,memo1 as 备注,memo2 as 收款方式  from fd_tellerdetail where deposit<>0 and shopno='" . $input['textBox1'] . "' and [date]>='" . $input['from'] . "' and [date]<='" . $input['to'] . "'";
			} else {
				$sqlorder = "select TellerDetailID as Teller编号,shopno as 专卖店,deposit as 数额,bank as 银行,[date] as [日期(Date)],Oper as 操作人,memo1 as 备注,memo2 as 收款方式  from fd_tellerdetail where deposit<>0 and shopno='" . $input['textBox1'] . "' and deposit=" . $input['textBox2'] . " and [date]>='" . $input['from'] . "' and [date]<='" . $input['to'] . "'";
			}

		}
		$data = $this->query_Sql($sqlorder);
		$data = utf_8($data);
		return $data;
	}
	private function getBanlance() {
		$data = '';
		$input = input('post.');
		if ($input['textBox1'] != "") {
			$sql = "select banlance from fd_teller where shopno='" . $input['textBox1'] . "'";
			$data = $this->query_Sql($sql);
		}
		return $data;
	}
}