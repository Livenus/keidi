<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;

class FrontDeskCash extends Base {

	protected $table = 'FrontDesk_Cash';
	protected $pk = 'GroupID';

	public function add($data) {
		$data["SaleDetailID"] = $this->_setSaleDetailID(); //
		$res = Db::table($this->table)->insert($data);
		if ($res) {
			return suc($data);
		}
		return err("出错了" . $res);
	}
	//查询对应groupid在FrontDesk_Cash表中的amount总金额
	public function sum_amount($groupid) {
		$sqlcmd = "select sum(amount) as amount from FrontDesk_Cash where groupid='{$groupid}'";
		$data = $this->query($sqlcmd);
		return $data[0]["amount"] + 0;
	}
	//查询FrontDesk_Cash(前台现金)表对应groupid的信息
	public function Get_Cash_Info($groupid) {
		$sqlorder = "select * from FrontDesk_Cash where GroupID='" . $groupid . "'";
		return $this->query($sqlorder);
	}
	public function Add_Cash($GroupID, $ShopNo, $Amount, $Realdate, $Oper, $memo) {
		$data["GroupID"] = $GroupID;
		$data["CashID"] = $this->Get_CashID();
		$data["ShopNo"] = $ShopNo;
		$data["Amount"] = $Amount;
		$data["RealDate"] = $Realdate;
		$data["Oper"] = $Oper;
		$data["Memo"] = $memo;
		return parent::addItem($data);
	}
	public function Get_CashID() {
		$sql = "select max(cashid)+1 as newid from FrontDesk_Cash";
		$data = $this->query($sql);
		return $data[0]["newid"];
	}
	public function Delete_Cash($CashID) {
		$where["CashID"] = $CashID;
		return parent::del($where);
	}
}
