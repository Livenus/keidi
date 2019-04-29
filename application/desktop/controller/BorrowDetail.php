<?php
namespace app\desktop\controller;
use app\desktop\controller\Common;

/**
 * @title 历史借货
 * @type menu
 * @login 1
 */

class BorrowDetail extends Common {
	public function _initialize() {
		parent::_initialize();
		$this->input = input('post.');
	}
	/**
	 * @title 历史借货 左侧search
	 * @param From 开始日期
	 * @param To 结束日期
	 * @param shopno 专卖店编号
	 * @return data
	 */
	public function button1_Click() {
		$data = $this->GetBorrowDetail();
		$data = utf_8($data);
		return suc($data);
	}
	//从tb_saleforborrowreturn表中查询详细信息
	private function GetBorrowDetail() {
		$sql = "select SaleID as BorrowID,rtrim(shopno),realdate as BorrowDate,total_bv as BV,total_pv as PV,total_naira as Naira,Kits,TotalMoney,GroupID,current_status as Staus,Directstatus as Direct,DeductGroupID,deductname as ItemName,memo as Note  from tb_saleforborrowreturn where Directstatus='借货' and current_status<>'0'and  realdate>='" . $this->input['From'] . "' and realdate<='" . $this->input['To'] . "'and shopno='" . $this->input['shopno'] . "'";
		$data = $this->query_Sql($sql);
		return $data;
	}
}