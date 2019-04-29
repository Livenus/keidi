<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class BankTellerSearch extends Common {

	private $PrintIndex; //当前页码
	private $dataIndex; //打印开始的数据行
	private $Count = 10;
	private $PrintMark = 0;
	private $status = ["全部", "已确认", "未确认"];
	private $use_status = ["未使用", "已使用"];
	private $lock_status = ["锁定", "未锁定"];
	private $effective_date = ["生效日期", "确认日期", "上传日期"];
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");

	}
	public function load() {
		$data['status'] = $this->status;
		$data['use_status'] = $this->use_status;
		$data['lock_status'] = $this->lock_status;
		$data['effective_date'] = $this->effective_date;
		return suc($data);
	}
	/**
	 * @title  账户管理中心--银行到账Teller查询
	 * @param effective_date 日期标识
	 * @param From 查询开始日期
	 * @param To 查询结束日期
	 * @param use_status 使用状态标识
	 * @param lock_status 锁定状态标识
	 * @param status 确认状态标识
	 */
	public function button1_Click() {
		$data = $this->Search();
		$data = utf_8($data);
		return suc($data);
	}
	private function Search() {
		$with = $this->SearchSql();
		$sql = "select TellerId as [ID],bank_name as 银行,deposit as 数额,专卖店 as SC,使用 as 使用状态,确认 as 确认状态,"
			. "CONVERT(varchar(12) ,effective_date,111) as 生效日,CONVERT(varchar(12) ,确认日期,111)as 确认日,CONVERT(varchar(12) ,uploaddate,111) as 上传日,lockbeforeconfirm as 锁定,lockreason as 锁定原因 from tb_systemteller" .
			" where " . $with;
		$data = $this->query_Sql($sql);
		return $data;
	}
	private function SearchSql() {
		if ($this->input['status'] == 0) //  选择全部则只有日期限制
		{
			return $this->GetDateType($this->input['effective_date']) . ">='" . $this->input['From'] .
			"' and " . $this->GetDateType($this->input['effective_date']) . "<='" . $this->input['To'] . "'";
		} else if ($this->input['status'] == 1) //选择已确认的则锁定不起作用，使用状态生效
		{
			return "确认='1' and 使用='" . $this->input['use_status'] . "' and " . $this->GetDateType($this->input['effective_date']) . ">='" . $this->input['From'] .
			"' and " . $this->GetDateType($this->input['effective_date']) . "<='" . $this->input['To'] . "'";

		} else {
			return "确认='0' and isnull(lockbeforeconfirm,0)" . $this->GetLockStatus($this->input['lock_status']) . " and " . $this->GetDateType($this->input['effective_date']) . ">='" . $this->input['From']
			. "' and " . $this->GetDateType($this->input['effective_date']) . "<='" . $this->input['To'] . "'";
		}

	}
	private function GetDateType($select) {
		if ($select == 0) {
			return "effective_date";
		} else if ($select == 1) {
			return "确认日期";
		} else {
			return "uploaddate";
		}

	}
	private function GetLockStatus($select) {
		if ($select == 0) {
			return "='LOCKED'";
		} else {
			return "<>'LOCKED'";
		}

	}
}
