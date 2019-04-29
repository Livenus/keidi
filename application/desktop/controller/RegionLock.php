<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class RegionLock extends Common {

	public $newborrowok = 0;
	public $deductMark = 0;
	public $error = false;
	public $SaleType = "业绩销售";

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
	}

	public function RegionLock_Load() {

	}
	public function button1_Click() {
		Db::startTrans();
		try {
			$data = $this->Search($this->input['date']);
			Db::commit();
			return suc($data);
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}

	private function Search($sdate) {
		$sql = "drop table RegionLockTemp";
		$this->Exc_Sql($sql);
		$sql = "select GroupID,ShopNo,Area as Region,Kits,TotalMoney,CashMoney as Cash,TellarMoney as Teller,debitMoney as Debt,'未锁定' as 状态 into RegionLockTemp from frontdesk_report where realdate='" . $sdate . "' and area='" . $this->input['Region'] . "'";
		$data = $this->Exc_Sql($sql);
		$sql = "update RegionLockTemp set 状态='锁定' where groupid in(select groupid from tb_regionlockrecord where lock='-1')";
		$this->Exc_Sql($sql);
		$sql = "select * from RegionLockTemp";
		$data = $this->query_Sql($sql);
		return $data;
	}
	public function button3_Click() {
		$data = $this->Search($this->input['date']);
		$check = json_decode($this->input['check_all']);
		if (!is_array($check) || count($check) == 0) {
			return err(9000, "请选择要锁定的编号！");
		}
		$count = 0;
		Db::startTrans();
		try {
			foreach ($data as $v) {
				$vv = array_values($v);
				if (in_array($v['GroupID'], $check) && $vv[8] == "未锁定") {
					$this->LockRegion($v['GroupID'], $this->input['date']);
				}
			}

			$repos["msg"] = "成功锁定！";
			$repos["action"] = "button1_Click";
			Db::commit();
			return suc($repos);
		} catch (Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	private function LockRegion($GroupID, $LockDate) {

		$sql = "insert into tb_regionlockrecord(GroupID,lockdate,lock,Operator) " .
		" values('" . $GroupID . "','" . $LockDate . "','-1','" . self::$realname . "')";
		$this->Exc_Sql($sql);
	}
	public function button4_Click() {
		$data = $this->Search($this->input['date']);
		$check = json_decode($this->input['check_all']);
		if (!is_array($check) || count($check) == 0) {
			return err(9000, "请选择要解锁的编号！");
		}
		$count = 0;
		Db::startTrans();
		try {
			foreach ($data as $v) {
				$vv = array_values($v);
				if (in_array($v['GroupID'], $check) && $vv[8] == "锁定") {
					$this->UnLockRegion($v['GroupID']);
				}
			}

			$repos["msg"] = "成功解锁！";
			$repos["action"] = "button1_Click";
			Db::commit();
			return suc($repos);
		} catch (Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	private function UnLockRegion($GroupID) {

		$sql = "delete from tb_regionlockrecord where groupid='" . $GroupID . "'";
		$this->Exc_Sql($sql);
	}
}
