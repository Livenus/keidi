<?php
namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title 借款表
 * @type menu
 * @login 1
 */
class RefundBorrow extends Common {
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
		$this->Oper = self::$realname;
		$this->RefundBorrow = model('RefundBorrow');
	}

	/**
	 * @title 返回未还清借款记录
	 * @type interface
	 * @login 1
	 * @param sc 装卖店经销商标号
	 * @return data
	 */
	public function refundlog() {
		$check = $this->validate($this->input, ["sc" => "require"], ["sc" => "请填写专卖店或者经销商号!"]);
		if ($check !== true) {
			return err(9000, $check);
		}
		$re = $this->verifySC($this->input['sc']);
		if (!$re) {
			return err(9000, '专卖店或者经销商号格式不对!');
		}
		$where = ['sc' => strtoupper($this->input['sc']), 'is_over' => 1];
		return $this->RefundBorrow->getlistAC($where);
	}

	/**
	 * @title 借款列表
	 * @type interface
	 * @login 1
	 * @param sc 装卖店经销商标号
	 * @param is_over 是否还清(0全部,1未还清,2已还清)
	 * @param typeid 借款类型id(0全部)
	 * @param id 借款记录id
	 * @param from 查询开始日期
	 * @param to 查询结束日期
	 * @param offset
	 * @param limit 分页每页显示数据条数
	 * @return data
	 */
	public function listAc() {
		$where = $this->get_condition();
		$offset = $this->input['offset'];
		$limit = $this->input['limit'];
		$data = $this->RefundBorrow->get_limitlist($where, '*', 'date DESC,id DESC', $offset, $limit);
		return suc($data);
	}
	//修改借款数据
	/**
	 * @title 修改借款数据
	 * @type interface
	 * @login 1
	 * @param log_time 日期时间
	 * @param sc 装卖店经销商标号
	 * @param typeid 借款还款的类型id
	 * @param memo 备注
	 * @param amount 借还款金额
	 * @param id 借款记录的id
	 * @return
	 */
	public function edit_borrow() {
		$this->verify_param();
		$amount = $this->input['amount']; //前端传参金额
		$re = model('RefundBorrow')->getByWhereOne(['id' => $this->input['id']]);
		if ($re['total_money'] > $amount) {
			//数据库金额大于前端传参金额
			$money = $re['total_money'] - $amount;
			$result['surplus_money'] = $re['surplus_money'] - $money;
		} else {
			$money = $amount - $re['total_money'];
			$result['surplus_money'] = $re['surplus_money'] + $money;
		}
		//var_export($result['surplus_money']);die();
		if ($result['surplus_money'] == 0) {
			$result['is_over'] = 2;
		} else if ($result['surplus_money'] > 0) {
			$result['is_over'] = 1;
			//var_export($result['surplus_money']);die();
		} else {
			return err(9000, '更改后未还款金额小于0,不符合操作,请联系Eirc!');
		}
		//var_export($result['surplus_money']);die();
		$result['total_money'] = $amount;
		$result['type_id'] = $this->input['typeid'];
		$result['oper'] = $this->Oper;
		$result['memo'] = $this->input['memo'] ?: $re['memo'];
		$result['date'] = $this->input['log_time'];
		$result['sc'] = strtoupper($this->input['sc']);
		return model('RefundBorrow')->editById($result, $this->input['id']);
	}
	//验证前端传参
	private function verify_param() {
		$rule = [
			"log_time" => "require",
			"sc" => "require",
			"typeid" => "require|\d",
			"amount" => "require",
			"id" => "require|\d",
		];
		$msg = [
			"log_time" => "时间日期必须填写!",
			"sc" => "请填写专卖店或者经销商号!",
			"typeid" => "请选择类型",
			"amount" => "请填写金额!",
			"id" => "请输入借款编号!",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
	}
	//拼接查询条件
	private function get_condition() {
		$map = [];
		if ($this->input['is_over']) {
			$map['is_over'] = $this->input['is_over'];
		}
		if ($this->input['sc']) {
			$re = $this->verifySC($this->input['sc']);
			if (!$re) {
				return err(9000, '专卖店或者经销商号格式不对!');
			}
			$map['sc'] = strtoupper($this->input['sc']);
		}
		if ($this->input['typeid']) {
			$map['type_id'] = $this->input['typeid'];
		}
		if ($this->input['from'] && $this->input['to']) {
			$map['date'] = ["between", "{$this->input['from']},{$this->input['to']}"];
		}
		if ($this->input['id']) {
			$map['id'] = $this->input['id'];
		}
		return $map;
	}
	//汇总列表
	/**
	 * @title 汇总列表
	 * @type interface
	 * @login 1
	 * @param from 查询开始时间
	 * @param to  查询结束时间
	 * @return data
	 */
	public function summarizing() {
		$rule = [
			"from" => "require",
			"to" => "require",
		];
		$msg = [
			"from" => "查询开始时间",
			"to" => "查询结束时间",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		Db::startTrans();
		try {
			$this->Drop_Table1('summarize'); //删除旧表
			$this->createtable(); //创建新的临时表
			$this->insert_sc(); //先查询出借款表中所有的专卖店插入summarize表中
			$this->update_history(); //修改表中历史欠款数据
			$this->update_date($this->input['from'], $this->input['to']); //修改表中当月借款数据
			$this->update_refund($this->input['from'], $this->input['to']); //修改表中当月teller和cash还款
			$this->update_payment($this->input['from'], $this->input['to']); //修改表中当月扣款数据
			$data = $this->get_alldata();
			Db::commit();
			return suc($data);
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, $e->getMessage());
		}

	}
	//创建临时表summarize
	private function createtable() {
		$sql = "CREATE TABLE summarize(
			sc VARCHAR(50) NOT NULL,
			[历史业绩欠款] VARCHAR(255) NOT NULL DEFAULT 0,
			[历史个人欠款] VARCHAR(255) NOT NULL DEFAULT 0,
			[历史房贷欠款] VARCHAR(255) NOT NULL DEFAULT 0,
			[历史车贷欠款] VARCHAR(255) NOT NULL DEFAULT 0,
			[历史保险欠款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月业绩借款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月个人借款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月房贷借款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月车贷借款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月teller还款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月cash还款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月业绩扣款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月个人扣款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月房贷扣款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月车贷扣款] VARCHAR(255) NOT NULL DEFAULT 0,
			[当月保险扣款] VARCHAR(255) NOT NULL DEFAULT 0
		)";
		$data = $this->Exc_Sql($sql);
		return $data;
	}
	//从表RefundBorrow中获取数据
	private function history_borrow($where) {
		$field = "sc ,sum(surplus_money) as amount";
		$data = model('RefundBorrow')->get_summarize($field, $where);
		return $data;
	}
	//插入所有专卖店的数据
	private function insert_sc() {
		$data = model('RefundBorrow')->get_summarize('sc');
		$data = array_column($data, 'sc');
		foreach ($data as $v) {
			$sql = "INSERT INTO summarize(sc) VALUES('" . $v . "')";
			$this->Exc_Sql($sql);
		}
	}
	//修改表中历史欠款数据
	private function update_history() {
		$type_array = model('RefundBorrowType')->idname(['key' => 1]);
		foreach ($type_array as $key => $value) {
			$data = $this->history_borrow(['type_id' => $key]);
			foreach ($data as $k => $v) {
				$sql = "UPDATE summarize SET [历史" . $value . "欠款]='" . $v['amount'] . "' WHERE sc='" . $v['sc'] . "'";
				$this->Exc_Sql($sql);
			}
		}

	}
	//修改表中当月借款数据
	private function update_date($from, $to) {
		$type_array = model('RefundBorrowType')->idname(['key' => 1]);
		foreach ($type_array as $key => $value) {
			$where['type_id'] = $key;
			$where['date'] = ["between", "{$from},{$to}"];
			$data = $this->history_borrow($where);
			foreach ($data as $k => $v) {
				$sql = "UPDATE summarize SET [当月" . $value . "借款]='" . $v['amount'] . "' WHERE sc='" . $v['sc'] . "'";
				$this->Exc_Sql($sql);
			}
		}
	}
	//从表RefundBorrowLog获取数据
	private function history_refund($where) {
		$field = "sc ,sum(amount) as amount";
		$data = model('RefundBorrowLog')->getgrouplist($field, $where, 'sc');
		return $data;
	}
	//修改表中当月teller和cash还款
	private function update_refund($from, $to) {
		$type_array = model('RefundBorrowType')->idname(['key' => 2]);
		foreach ($type_array as $key => $value) {
			$where['type_id'] = $key;
			$where['log_time'] = ["between", "{$from},{$to}"];
			$where['direction'] = 2; //表示还款
			$data = $this->history_refund($where);
			foreach ($data as $k => $v) {
				$sql = "UPDATE summarize SET [当月" . $value . "还款]='" . $v['amount'] . "' WHERE sc='" . $v['sc'] . "'";
				$this->Exc_Sql($sql);
			}
		}

	}
	//修改表中当月扣款数据
	private function update_payment($from, $to) {
		$type_array = model('RefundBorrowType')->idname(['key' => 1]);
		foreach ($type_array as $key => $value) {
			$where['type_id'] = $key;
			$where['log_time'] = ["between", "{$from},{$to}"];
			$where['direction'] = 3; //表示被动扣款
			$data = $this->history_refund($where);
			foreach ($data as $k => $v) {
				$sql = "UPDATE summarize SET [当月" . $value . "借款]='" . $v['amount'] . "' WHERE sc='" . $v['sc'] . "'";
				$this->Exc_Sql($sql);
			}
		}

	}
	//查询表中所有数据
	private function get_alldata() {
		$sql = "SELECT * FROM summarize";
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return $data;
	}
}