<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

/**
 * @title 账户管理
 */
class AccountManage extends Common {

	private $PrintIndex; //当前页码
	private $dataIndex; //打印开始的数据行
	private $Count = 10;
	private $PrintMark = 0;

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");

	}
	public function AccountSearch() {
		if ($this->input['accounts'] == "") {
			$accounts = "select RegserviceNo as '账户',customername as '注册卡姓名',customerno as '注册卡号',a.phone as '联系电话',a.regdate as '注册日期',a.oper as '确认人' from TelleraccountReg  " .
				"a,tb_customer b,tb_customerinfo c where a.kedicard=b.customerid and b.customerid=c.customerid ";
		} else {
			$accounts = "select RegserviceNo as '账户',customername as '注册卡姓名',customerno as '注册卡号',a.phone as '联系电话',a.regdate as '注册日期',a.oper as '确认人' from TelleraccountReg  " .
			"a,tb_customer b,tb_customerinfo c where a.kedicard=b.customerid and b.customerid=c.customerid and regserviceno='" . $this->input['accounts'] . "'";
		}
		$data = $this->query_Sql($accounts);
		$data = utf_8($data);
		return suc($data);
	}
	/**
	 * @title 获取用户姓名
	 * @type interface
	 * @login 1
	 * @param card 会员编号
	 * @return 会员姓名
	 */
	public function GetName() {

		$sql = "select customername from tb_customerinfo where customerid=(select customerid from tb_customer where customerno='" . $this->input['card'] . "')";
		$data = $this->query_Sql($sql);
		return suc($data);

	}
	/**
	 * @title 账户管理--修改
	 * @type interface
	 * @login 1
	 * @param accounts 账户
	 * @param password 密码
	 * @param password_re 确认密码
	 */
	public function button2_Click() {
		$rule = [
			"accounts" => "require",
			"password" => "require",
			"password_re" => "require",

		];
		$msg = [
			"accounts" => "请选中要修改的账户",
			"password" => "请输入正确密码并确认",
			"password_re" => "请输入正确密码并确认",

		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);

		}
		try {
			$msg = $this->UpdateAccount();
			return suc($msg);
		} catch (Exception $exc) {
			return err(9000, $exc->getMessage());
		}

	}
	//修改表telleraccountreg中数据
	private function UpdateAccount() {
		$sql = "update telleraccountreg set kedicard=(select customerid from tb_customer where customerno='" . $this->input['card'] . "'),registername='" . $this->input['name'] . "',phone='" . $this->input['phone'] . "',oper='" . self::$realname . "',password='" . $this->input['password'] . "' where regserviceno='" . $this->input['Account_name'] . "'";
		$this->Exc_Sql($sql);
		return ("修改成功！");
	}
}
