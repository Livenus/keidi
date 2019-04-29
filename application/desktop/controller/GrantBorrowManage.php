<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class GrantBorrowManage extends Common {

	public $SearchMark = "";public $GrantCode = "";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");

	}
	public function Search() {
		try {
			if (strlen($this->input["GroupID"]) > 4) {
				$sql = "select ProductNo,cname as [Name],memberprice as Price,Amount as Borrow,memberprice*amount as TotalNaira from(select productid,amount from tb_saledetailforbr where"
				. " saleid =(select saleid from tb_saleforborrowreturn where groupid='" . $this->input['GroupID'] . "')) a,tb_product b where a.productid=b.productid";
			} else {
				$sql = "select sc_dist as Sc,Totalnaira as 产品值,Kits,TotalMoney as 总货值,cash_tellersum as 总付款,paid_value_percent as 押金比,GroupID, rtrim(realname) as 授权人,Date,borrowtype as 类型,permitcode as Code"
				. " from borrow_return_report a,enterapp_user b where a.grantid=b.userid and sc_dist='" . $this->input["SC"] . "'";
			}
			$data['list'] = $this->query_Sql($sql);
			if (strlen($this->input["GroupID"]) <= 4) {
				$this->SearchMark = "SC";
			} else {
				$this->SearchMark = "GID";
				$this->SetTextBoxValue($this->input['GroupID']);
				$data['input'] = $this->input;
				return suc($data);
			}
		} catch (\Exception $exc) {
			return err(9000, $exc->getMessage());
		}

	}
	private function SetTextBoxValue($GID) {

		$sql = "select sc_dist as Sc,Totalnaira as Totalnaira,Kits,TotalMoney as TotalMoney,cash_tellersum as cash_tellersum,paid_value_percent ,borrowtype ,permitcode as Code"
			. " from borrow_return_report where groupid='" . $GID . "'";
		$ds = $this->query_Sql($sql);
		$ds = $ds[0];
		$this->input['SC'] = $ds["Sc"];
		$this->input['Totalnaira'] = $ds["Totalnaira"];
		$this->input['Kits'] = $ds["Kits"];
		$this->input['TotalMoney'] = $ds["TotalMoney"];
		$this->input['cash_tellersum'] = $ds["cash_tellersum"];
		$this->input['paid_value_percent'] = $ds["paid_value_percent"];
		$this->input['GroupID2'] = $ds["borrowtype"];
		$this->input['GroupID'] = $GID;
		if (strlen($ds["Code"]) >= 1) {
			return err(9000, "该次借货已经授权，授权人：");
		} else {}

	}
	public function button2_Click() {
		$this->GrantCode = $this->input['Totalnaira'];
		if (strlen($this->GrantCode) >= 1) {
			return err(9000, "该次借货已经获得授权，授权人：" . $this->GetGrantPerson($this->GrantCode));
		} else {
			if ($this->input['code'] == "1234") {
				return err(9000, "初始授权码无法授权，请更新您的授权码！");
			} else {
				if ($this->input['code'] == "") {
					return err(9000, "请输入授权码");
				} else {
					try {
						if ($this->CheckIfUrOwnCode()) {
							$data = $this->Grant();return suc($data);} else {return err(9000, "授权码错误！");}
					} catch (\Exception $exc) {
						return err(9000, $exc->getMessage());
					}

				}
			}
		}
	}
	private function GetGrantPerson($grant) {
		$sql = "select realname from enterapp_user where borrowallowcode='" . $grant . "'";
		$person = $this->GetStringData($sql);
		return $person;
	}
	private function CheckIfUrOwnCode() {
		$gcode = $this->input['code'];
		$sql = "select count(*) from enterapp_user where realname='" . self::$realname . "' and borrowallowcode='" . $gcode . "'";
		if ($this->GetStringData($sql) == "0") {
			return false;
		} else {
			return true;
		}

	}
	private function Grant() {
		$sql = "update borrow_return_report set permitcode='" . $this->input['code'] . "',grantid=" . $this->GetUserId($this->input['code']) . " where groupid='" . $this->input['GroupID2'] . "'";
		$status = $this->Exc_Sql($sql);
		if (is_numeric($status)) {
			return suc("授权成功！");
		} else {
			exception("授权失败！Sql:" . $sql);
		}

	}
	private function GetUserId($permitcode) {
		$sql = "select userid from enterapp_user where borrowallowcode='" . $permitcode . "'";
		$userid = $this->GetStringData($sql);
		if (strlen(trim($userid)) < 1) {
			exception("Error，Sql:" . $sql);
		}

		return $userid;
	}

	public function button3_Click() {
		if ($this->GrantCode < 1) {
			return err(9000, "该次借货未获得授权，无法取消");

		} else {
			if ($this->input['code'] == "1234") {
				return err(9000, "初始授权码无法取消授权，请更新您的授权码！");
			} else {
				if ($this->input['code'] == "") {
					return err(9000, "请输入授权码");
				} else {
					if ($this->CheckIfUrOwnCode()) {
						$data = $this->UnGrant();
						return suc($data);
					} else {return err(9000, "授权码错误！");}
				}
			}
		}
	}
	private function UnGrant() {
		$sql = "update borrow_return_report set permitcode='' where groupid='" . $this->input['GroupID'] . "'";
		$status = $this->Exc_Sql($sql);
		if (is_numeric($status)) {
			return suc("取消授权成功！");
		} else {
			return err(9000, "授权失败！Sql:" . $sql);
		}

	}
}
