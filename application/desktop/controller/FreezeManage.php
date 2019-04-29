<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title 冻结管理
 * @type menu
 * @login 1
 */
class FreezeManage extends Common {
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");

	}
	/**
	 * @title 冻结管理 查询冻结资金
	 * @type interface
	 * @login 1
	 * @param shopno 专卖店或者经销商编号
	 * @param amount 数额
	 * @param region 地区
	 * @param from   查询开始日期
	 * @param to     查询结束日期
	 * @return data
	 */
	public function button1_Click() {
		$data = $this->SearchFreeze();
		if ($data) {
			$data = utf_8($data);
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	//查询冻结资金
	private function SearchFreeze() {
		if (trim($this->input['shopno']) == "") {
			if (trim($this->input['amount']) == "") {
				$sqlorder = "select freezeselect as 筛选,TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 使用,[date] as [日期(Date)],'资金冻结'as 用途,memo1 as 备注,region as 所属区域,用途 as 来源,systemtellerid as 源ID,Oper as 操作人  from fd_tellerdetail where isnull(freezemark,0)='1' and [date]>='" . $this->input['from'] . "' and [date]<='" . $this->input['to'] . "'" . $this->GetRegionSelect();
			} else {
				$sqlorder = "select freezeselect as 筛选,TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 使用,[date] as [日期(Date)],'资金冻结'as 用途,memo1 as 备注,region as 所属区域,用途 as 来源,systemtellerid as 源ID,Oper as 操作人  from fd_tellerdetail where deposit=" . $this->input['amount'] . " and isnull(freezemark,0)='1' and [date]>='" . $this->input['from'] . "' and [date]<='" . $this->input['to'] . "'" . $this->GetRegionSelect();
			}

		} else {
			if (trim($this->input['amount']) == "") {
				$sqlorder = "select freezeselect as 筛选,TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 使用,[date] as [日期(Date)],'资金冻结'as 用途,memo1 as 备注,region as 所属区域,用途 as 来源,systemtellerid as 源ID,Oper as 操作人  from fd_tellerdetail where shopno='" . $this->input['shopno'] . "' and isnull(freezemark,0)='1' and [date]>='" . $this->input['from'] . "' and [date]<='" . $this->input['to'] . "'" . $this->GetRegionSelect();
			} else {
				$sqlorder = "select freezeselect as 筛选,TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 使用,[date] as [日期(Date)],'资金冻结'as 用途,memo1 as 备注,region as 所属区域,用途 as 来源,systemtellerid as 源ID,Oper as 操作人  from fd_tellerdetail where shopno='" . $this->input['shopno'] . "' and deposit=" . $this->input['amount'] . " and isnull(freezemark,0)='1' and [date]>='" . $this->input['from'] . "' and [date]<='" . $this->input['to'] . "'" . $this->GetRegionSelect();
			}

		}
		$data = $this->query_Sql($sqlorder);
		return $data;
	}
	//拼接地区查询条件
	private function GetRegionSelect() {
		if ($this->input['region'] == "ALL") {
			return "";
		} else {
			return " and region='" . $this->input['region'] . "'";
		}
	}
	/**
	 * @title 冻结管理 查询未冻结资金
	 * @type interface
	 * @login 1
	 * @param shopno 专卖店或者经销商编号
	 * @param amount 数额
	 * @param region 地区
	 * @param from   查询开始日期
	 * @param to     查询结束日期
	 * @return data
	 */
	public function button4_Click() {
		$data = $this->SearchUnFreeze();
		if ($data) {
			$data = utf_8($data);
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	//查询未冻结资金
	private function SearchUnFreeze() {

		if (trim($this->input['shopno']) == "") {
			if (trim($this->input['amount']) == "") {
				$sqlorder = "select freezeselect as 筛选,TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 存款,[date] as [日期(Date)],'未资金冻结'as 用途,memo1 as 备注,region as 所属区域,用途 as 来源,systemtellerid as 源ID,Oper as 操作人  from fd_tellerdetail where (用途='临时'or 用途='系统') and isnull(freezemark,0)<>'1' and [date]>='" . $this->input['from'] . "' and [date]<='" . $this->input['to'] . "'" . $this->GetRegionSelect();
			} else {
				$sqlorder = "select freezeselect as 筛选,TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 存款,[date] as [日期(Date)],'未资金冻结'as 用途,memo1 as 备注,region as 所属区域,用途 as 来源,systemtellerid as 源ID,Oper as 操作人  from fd_tellerdetail where (用途='临时'or 用途='系统') and deposit=" . $this->input['amount'] . " and isnull(freezemark,0)<>'1' and [date]>='" . $this->input['from'] . "' and [date]<='" . $this->input['to'] . "'" . $this->GetRegionSelect();
			}

		} else {
			if (trim($this->input['amount']) == "") {
				$sqlorder = "select freezeselect as 筛选,TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 存款,[date] as [日期(Date)],'未资金冻结'as 用途,memo1 as 备注,region as 所属区域,用途 as 来源,systemtellerid as 源ID,Oper as 操作人  from fd_tellerdetail where (用途='临时'or 用途='系统') and shopno='" . trim($this->input['shopno']) . "' and isnull(freezemark,0)<>'1' and [date]>='" . $this->input['from'] . "' and [date]<='" . $this->input['to'] . "'" . $this->GetRegionSelect();
			} else {
				$sqlorder = "select freezeselect as 筛选,TellerDetailID as Teller编号,fd_tellerdetail.shopno as 账户,fd_tellerdetail.deposit as 存款,[date] as [日期(Date)],'未资金冻结'as 用途,memo1 as 备注,region as 所属区域,用途 as 来源,systemtellerid as 源ID,Oper as 操作人  from fd_tellerdetail where (用途='临时'or 用途='系统') and shopno='" . $this->input['shopno'] . "' and deposit=" . $this->input['amount'] . " and isnull(freezemark,0)<>'1' and [date]>='" . $this->input['from'] . "' and [date]<='" . $this->input['to'] . "'" . $this->GetRegionSelect();
			}

		}
		$data = $this->query_Sql($sqlorder);
		return $data;
	}
	/**
	 * @title 冻结管理 解冻被选资金
	 * @type interface
	 * @login 1
	 * @param data 所选择的数据集合包括(TellerdetailID.SC)
	 * @param remark 备注
	 * @return data
	 */
	public function button2_Click() {
		Db::startTrans();
		try {
			$data = $this->UnFreezeSelect();
			Db::commit();
			return $data;
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	private function UnFreezeSelect() {
		$data = $this->input['data'];
		//$data = json_decode($data, true);
		if (empty($data)) {
			return err(9000, "未选中任何数据");
		}
		foreach ($data as $v) {
			$vv = array_values($v);
			$this->UnFreeze($vv[0]);
			$this->ProcessChangeTeller(trim($vv[1]));
		}

		return suc("成功解冻" . count($data) . "笔资金");
	}
	private function UnFreeze($TellerdetailID) {
		$UnFreezeSql = "update fd_tellerdetail set freezemark='2' where tellerdetailid='" . $TellerdetailID . "'";
		$status = $this->Exc_Sql($UnFreezeSql);
		if ($status < 1) {
			exception("编号为" . $TellerdetailID . "的资金解冻失败！");
		}

	}
	/**
	 * @title 冻结管理 冻结被选资金
	 * @type interface
	 * @login 1
	 * @param data 所选择的数据集合包括(TellerdetailID,sc,money)
	 * @param remark 备注
	 * @return data
	 */
	public function button3_Click() {
		Db::startTrans();
		try {
			$data = $this->FreezeSelect();
			Db::commit();
			return $data;
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	private function FreezeSelect() {
		$data = $this->input['data'];
		//$data = json_decode($data, true);
		if (empty($data)) {
			return err(9000, "未选中任何数据");
		}
		foreach ($data as $v) {
			$vv = array_values($v);
			$this->Freeze($vv[0], $vv[1], $vv[2]);
			//$this->ProcessChangeTeller(trim($vv[1]));

		}

		return suc("成功冻结" . count($data) . "笔资金");
	}
	//执行冻结操作
	private function Freeze($TellerdetailID, $sc, $money) {
		$status = false;
		if ($this->CheckIfBalanceEnough($sc, $money)) {
			$FreezeSql = "";
			$FreezeSql = "update fd_tellerdetail set freezemark='1',memo1='" . $this->input['remark'] . "' where tellerdetailid='" . $TellerdetailID . "'";
			$status = $this->Exc_Sql($FreezeSql);
			if ($status < 1) {
				$this->ProcessChangeTeller($sc);
				exception("编号为" . $TellerdetailID . "的资金冻结失败！");
			} else {
				$this->ProcessChangeTeller($sc);
				$status = true;
			}
		} else {
			exception("专卖店：" . $sc . "里编号为：" . $TellerdetailID . "的存款已经使用，无法冻结！");
		}

		return $status;
	}
	//检查判断账户余额是否大于等于冻结的资金
	private function CheckIfBalanceEnough($sc, $FreezeMoney) {
		$status = false;
		$sql = "";
		$balance = "";
		$sql = "select banlance from fd_teller where shopno='" . $sc . "'";
		$balance = $this->GetStringData($sql);
		if ($balance >= $FreezeMoney) {
			$status = true;
		}
		return $status;
	}
}
