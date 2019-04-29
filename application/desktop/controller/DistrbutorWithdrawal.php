<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title 提款界面
 * @type menu
 * @login 1
 */
class DistrbutorWithdrawal extends Common {

	private $PrintIndex;
	private $dataIndex;
	private $Count = 10;
	private $PrintMark = 0;
	private $Name = "提款界面";

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");

	}
	/**
	 * @title 提款界面 总账查询
	 * @type interface
	 * @login 1
	 * @param shopno 专卖店或经销商编号
	 * @param p 是否勾选P
	 * @return data
	 */
	public function button2_Click() {
		if ($this->input["p"]) {
			return err(9000, "之前进行的提款支票未打印，请打印后再进行其他操作！");
		} else {
			$data = $this->Search();
		}

		if ($data) {
			$data = utf_8($data);
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	//总账查询操作
	private function Search() {
		$sqlorder = "select TellerID,Shopno as [专卖店(经销商)],total as 总存款,used 已用金额,banlance as 余额  from FD_Teller where shopno like'" . $this->input['shopno'] . "%'";
		$data = $this->query_Sql($sqlorder);
		return $data;
	}
	/**
	 * @title 提款界面 提款查询
	 * @type interface
	 * @login 1
	 * @param id 提款ID
	 * @return data
	 */
	public function button3_Click() {
		$data = $this->WithdrawalSearch($this->input['id']);
		if ($data) {
			$data = utf_8($data);
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	//查询提款
	private function WithdrawalSearch($WithdrawalID) {
		if (trim($WithdrawalID) == "") {
			$sqlorder = "select TellerDetailID as 提款ID,fd_tellerdetail.shopno as 账户,fd_tellerdetail.used as [本次提款额(Amount)],[date] as [日期(Date)],banlance as [账户余额(Balance)],memo1 as 备注  from FD_Teller,fd_tellerdetail where fd_teller.tellerid=fd_tellerdetail.tellerid and 用途='个人提款'";
		} else {
			$sqlorder = "select TellerDetailID as 项目ID,fd_tellerdetail.shopno as 账户,fd_tellerdetail.used as [使用(Amount)],用途,[date] as [日期(Date)],banlance as [账户余额(Balance)],memo1 as 备注  from FD_Teller,fd_tellerdetail where fd_teller.tellerid=fd_tellerdetail.tellerid and tellerdetailid ='" . $WithdrawalID . "' union " .
				"select TellerDetailID as 项目ID,fd_tellerdetail.shopno as 账户,fd_tellerdetail.used as [使用(Amount)],用途,[date] as [日期(Date)],banlance as [账户余额(Balance)],memo1 as 备注  from FD_Teller,fd_tellerdetail where fd_teller.tellerid=fd_tellerdetail.tellerid and 用途='个人提款手续' and systemtellerid ='" . $WithdrawalID . "'";
		}
		$data = $this->query_Sql($sqlorder);
		return $data;
	}
	/**
	 * @title 提款界面 提款
	 * @type interface
	 * @login 1
	 * @param down_shop 提款的专卖店或经销商卡号
	 * @param Amount 提款金额
	 * @param date 提款日期
	 * @param use 用途
	 * @param remark 备注
	 * @param Region Region地区
	 * @param Year Year
	 * @param Month Month
	 * @param person_check 是否勾选提款手续
	 * @return data
	 */
	public function button1_Click() {
		$rule = [
			"down_shop" => "require",
			"Amount" => "require",
			"Region" => "require",

		];
		$msg = [
			"down_shop" => "请输入要提款的专卖店或经销商卡号",
			"Amount" => "请输入提款金额",
			"Region" => "请选择Region区域",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}

		if ($this->input['Amount'] > $this->Get_TellerBalance($this->input['down_shop'])) {
			return err(9000, "余额不足，请重新录入提款额度！");
		} else {
			Db::startTrans();
			try {
				$this->input['down_id'] = $this->Get_New_TellerDetailID();
				$data['InsertFD_tellerdetail'] = $this->InsertFD_tellerdetail();
				$data['WithdrawalSearch'] = $this->WithdrawalSearch($this->input['down_id']);
				if ($data['WithdrawalSearch']) {
					$data['WithdrawalSearch'] = utf_8($data['WithdrawalSearch']);
				}
				$this->input['p'] = 1;
				$data["input"] = $this->input;
				Db::commit();
				return suc($data);
			} catch (Exception $exc) {
				Db::rollback();
				return err(9000, $exc->getMessage());
			}

		}
	}
	//查询可提款余额
	private function Get_TellerBalance($sc) {
		$Get_TellarBalance = 0;
		$sql = "select isnull(sum(deposit)-sum(used),0) as amount  from fd_tellerdetail where shopno='" . $sc . "' and region='" . $this->input['Region'] . "'";
		$Get_TellarBalance = $this->GetStringData($sql);
		return $Get_TellarBalance;
	}
	//生成提款ID
	private function Get_New_TellerDetailID() {
		$sql = "select max(tellerdetailid)+1 as tellerdetailid from FD_TellerDetail";
		$Get_TellarID = $this->GetStringData($sql);
		return $Get_TellarID;
	}
	//提款并生成提款记录
	private function InsertFD_tellerdetail() {
		$WithdrawalSql = "insert FD_tellerDetail(TellerDetailID,shopno,Deposit,used,bank,[date],TellerID,用途,所属期,Region,Oper,memo1) " .
		"values('" . $this->input['down_id'] . "','" . $this->input['down_shop'] . "','0','" . $this->GetMain($this->input['Amount']) . "','" . $this->input['bank'] . "','" . $this->input['date'] . "','" . $this->Get_TellerID($this->input['down_shop']) . "','" . $this->input['use'] . "','" . $this->GetDate_AcheavieFrom() . "','" . $this->input['Region'] . "','" . self::$realname . "','" . $this->input['remark'] . "')";
		$status = $this->Exc_Sql($WithdrawalSql);
		if ($status == 1) {
			$this->ProcessChangeTeller($this->input['down_shop']);
			$data['msg'] = "提款成功！";
			if ($this->input['person_check']) {
				$this->DeductCashCommission($this->input['down_shop'], $this->input['Amount'], $this->input['Region'], $this->input['down_id'], "个人提款", date('Y-m-d'));
			}

			$data['action'] = "button2.PerformClick()";
			$this->RecordInLog("提款记录", $WithdrawalSql, $this->Name, trim(self::$realname));
			return suc($data);
		}

	}
	private function GetMain($amount) {
		if (trim($amount) == "") {
			return "0";
		} else {
			if ($this->input['person_check']) {
				$amount = $amount * 0.98;
			}
			return $amount;
		}
	}
	private function Get_TellerID($ShopNO) {

		$sql = "select tellerid from FD_Teller where shopno='" . $ShopNO . "'";
		$Get_TellarID = $this->GetStringData($sql);
		if (empty($Get_TellarID)) {
			$Get_TellarID = $this->Get_New_TellerID();
			$Upadte_Teller_Sql = "insert FD_Teller(TellerID,Shopno,total,used,banlance) values('" . $this->Get_New_TellerID() . "','" . $ShopNO . "','0','0','0')";
			$this->Exc_Sql($Upadte_Teller_Sql);
		}
		return $Get_TellarID;
	}
	//拼接所属期的年月
	private function GetDate_AcheavieFrom() {
		$Get_Date = "";
		if ($this->input['Month'] < 9) {
			$Get_Date = $this->input['Year'] . "0" . $this->input['Month'] + 1;
		} else {

			$Get_Date = $this->input['Year'] . $this->input['Month'] + 1;

		}
		return $Get_Date;

	}
	private function DeductCashCommission($ShopNo, $amount, $Region, $tellerid, $TellerType, $CDate) {
		$Add_Tellar_Sql = "";
		$Gid = "";
		$Pay = 0;
		$Pay = $amount * 0.02;
		$Gid = date('Y-m-d H:i:s');
		$Add_Tellar_Sql = "insert FD_TellerDetail(GroupID,TellerDetailID,ShopNo,deposit,used,[date],tellerid,用途,region,Oper,bank,所属期,RegPswStatus,systemtellerID) values('"
		. $Gid . "'," . $this->Get_New_TellerDetailID() . ",'" . $ShopNo . "','0','" . $Pay . "','" . $CDate . "'," . $this->Get_TellerID($ShopNo) . ",'个人提款手续','" . $Region . "','" . self::$realname . "','KEDI','" . trim($this->GetDate_AcheavieFrom()) . "','0'," . $tellerid . ")";
		$status = $this->Exc_Sql($Add_Tellar_Sql);
		if ($status == 1) {
			$this->Update_FD_Teller($ShopNo);

			$this->ProcessChangeTeller($ShopNo);
		} else {
			exception("Teller was not added successfully!");
		}

	}
	private function Update_FD_Teller($Shopno) {
		$Upadte_Teller_Sql = "";
		if ($this->Get_TellerID($Shopno) < 1) {
			$Upadte_Teller_Sql = "insert FD_Teller(TellerID,Shopno,total,used,banlance) values('" . $this->Get_New_TellerID() . "','" . $Shopno . "','0','0','0')";
			$this->Exc_Sql($Upadte_Teller_Sql);
		}

	}
	private function Get_New_TellerID() {

		$sql = "select max(tellerid)+1 as tellerid from FD_Teller ";
		$Get_TellarID = $this->GetStringData($sql);
		if (empty($Get_TellarID)) {
			$Get_TellarID = 1;
		}

		return $Get_TellarID;
	}
}
