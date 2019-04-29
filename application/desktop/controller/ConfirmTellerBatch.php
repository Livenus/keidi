<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title 批量确认
 * @type menu
 * @login 1
 */
class ConfirmTellerBatch extends Common {

	public $manualbank = "";
	public $dt1;
	#region 定时退出
	public $OperCount = 0;
	public $SearchResult = "TellerID,确认,BANK_NAME,CheckNo,EFFECTIVE_DATE,MEMO,Withdrawal,Deposit,确认日期,专卖店,Region,业绩所属期,用途,ConfirmPerson,ImportPerson,唯一,使用,UploadDate,LockBeforeConfirm,LockReason";
	public $comboBLcondition = [
		"Deposit",
		"银行",
		"专卖店",
		"备注",
	];
	public $shop_type = [
		"专卖店",
		"经销商",
	];
	public $TeLLID = "-1";
	public $TempTellID = "-1";
	public $TellerOper = "";
	public $textBox2 = "系统";
	public $Name = "查询系统";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
		$this->TellerOper = self::$realname;

	}
	public function load() {
		$data['comboBLcondition'] = $this->comboBLcondition;
		return suc($data);
	}
	/**
	 * @title 批量确认 搜索
	 * @type interface
	 * @login 1
	 * @param check 是否勾选已确认
	 * @param check_not 是否勾选未确认
	 * @param date 上传日期
	 * @return data
	 */
	public function button4_Click() {
		$rule = [
			"date" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$data = $this->set_data();
		if ($data) {
			$data = utf_8($data);
			return suc($data);
		}
		return err(9000, "没有数据");

	}
	//搜索查询
	private function set_data() {
		if ($this->input["check"] && $this->input["check_not"]) {
			$sql = "select * from tb_tempPos where 上传日期='" . $this->input['date'] . "'";
		} else if ($this->input["check_not"]) {
			$sql = "select * from tb_tempPos where 上传日期='" . $this->input['date'] . "' and isnull(mark,0)=0";
		} else {
			$sql = "select * from tb_tempPos where 上传日期='" . $this->input['date'] . "' and isnull(mark,0)<>0";
		}

		$data = $this->query_Sql($sql);
		// foreach ($data as $k => $v) {
		// 	$data[$k]["sort"] = $k;
		// }
		return $data;
	}
	/**
	 * @title 批量确认 确认
	 * @type interface
	 * @login 1
	 * @param data 选择的数据集合(银行,ShopNo,POS日期,存款,来源,备注,Region)
	 * @return data
	 */
	public function button3_Click() {
		Db::startTrans();
		try {
			$data = $this->Confirm();
			Db::commit();
			return suc($data);
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}
	}
	//确认选择的数据
	private function Confirm() {
		$data = json_decode($this->input['data'], true); //接收数据
		if (empty($data)) {
			exception("所选数据为空");
		}
		//var_export($data);die();
		foreach ($data as $k => $v) {
			$vv = array_values($v);
			$PtellerID = $this->CreateTempTeller(); //产生tb_tempteller表中的新tellerid
			$Pbank = $vv[1]; //银行
			$Psc = $vv[2]; //ShopNo
			$Pdate = $vv[3]; //POS日期
			$Pdeposit = $vv[4]; //存款
			$Pfrom = $vv[5]; //来源
			$Pmemo = $vv[6]; //备注
			$Region = $vv[7]; //Region
			if ($this->ConfirmPosTeller($PtellerID, $Pbank, $Psc, $Pdate, $Pdeposit, $Pfrom, $Pmemo, $Region)) {
				$this->Exc_Sql("update Tb_TempPos set Mark='" . $PtellerID . "' where 备注='" . $Pmemo . "'");
			} else {
				$PtellerID = $this->CreateTempTeller();
				if (!$this->ConfirmPosTeller($PtellerID, $Pbank, $Psc, $Pdate, $Pdeposit, $Pfrom, $Pmemo, $Region)) {
					exception("确认teller" . $Pmemo . "失败，请重新确认！");
					break;
				}
			}
		}
		$data["msg"] = "确认成功！";
		$data["action"] = "button4.PerformClick()";
		return $data;
	}
	//产生tb_tempteller表中的新tellerid
	private function CreateTempTeller() {
		$sql = "select isnull(max(tellerid),0)+1 as tellerid from tb_tempteller";
		$Get_TempTellarID = $this->GetStringData($sql);
		return $Get_TempTellarID;
	}
	private function ConfirmPosTeller($PtellerID, $Pbank, $Psc, $Pdate, $Pdeposit, $Pfrom, $Pmemo, $Pregion) {
		$status = false;
		$POSDate = "";
		$POSDate = $Pdate;
		$confirmsql = "set IDENTITY_INSERT tb_TempTeller on;insert tb_TempTeller(确认,用途,专卖店,业绩所属期,确认日期,confirmperson,tellerid,bank_name,Deposit,Memo,withdrawal,POSDate,Note,region) values('1','临时','" . $Psc . "','','" . date('Y-m-d') . "','" . $this->TellerOper . "','" . $PtellerID . "','" . $Pbank . "','" . $this->ProccessNull($Pdeposit) . "','" . $Pfrom . "','0','" . $POSDate . "','" . $Pmemo . "','" . $Pregion . "')";
		$status = $this->Exc_Sql($confirmsql);
		if ($status == 1) {
			$TellerRecharge = action("TellerRecharge/CheckIfJustConfirm", ['临时', $PtellerID]);

			if (!$TellerRecharge) {
				$this->Exc_Sql("set IDENTITY_INSERT tb_TempTeller off ");

				$this->InsertPosFD_tellerdetail_T($Psc, $Pdeposit, $Pbank, $PtellerID, $Pregion, $Pfrom, $Pmemo);

				$number = 0;
				$number = $this->GetStringData("select count(*) as c from fd_tellerdetail where 用途='临时' and systemtellerid=" . $PtellerID);
				if ($number == 1) {
					$status = true;
				} else {
					if ($number > 1) {

						$this->Exc_Sql("delete from fd_tellerdetail where 用途='临时' and systemtellerid=" . $PtellerID);
						exception("确认了" . $number . "次,已经全部清除，请重新确认");
					}
					$this->ProcessChangeTeller($Psc);
					$this->Exc_Sql("delete from tb_TempTeller where tellerid=" . $PtellerID); // set 确认='0', 用途='" + textBox2.Text + "',专卖店='" + textBox13.Text + "',确认日期='" + dateTimePicker6.Value.Date.ToShortDateString() + "',confirmperson='" + toolStripStatusLabel3.Text.Substring(0, GetLenth()) + "' where TellerID='" + textBox9.Text + "'");
					exception("确认失败，数据回滚！");
				}
			}
		} else {
			exception("确认TempTeller失败！");
		}

		return status;

	}
	private function InsertPosFD_tellerdetail_T($Psc, $Pdeposit, $Pbank, $systemtellerid, $Pregion, $Pfrom, $Pmemo) {
		$confirmsql = "insert FD_tellerDetail(TellerDetailID,shopno,Deposit,used,bank,[date],TellerID,用途,SystemTellerID,Region,Oper,memo1,memo2,freezemark) values('" . $this->Get_New_TellerDetailID() . "','" . $Psc . "','" . $this->ProccessNull($Pdeposit) . "','0','" . $Pbank . "','" . date('Y-m-d') . "','" . $this->Get_TellerID($Psc) . "','临时','" . $systemtellerid . "','" . $Pregion . "','" . $this->TellerOper . "','" . $Pmemo . "','" . $Pfrom . "','0')";
		$status = $this->Exc_Sql($confirmsql);
		if ($status > 0) {
			$this->ProcessChangeTeller($Psc);
		} else {
			exception("插入确认表是错误，sql:" . $confirmsql);
		}

	}
	private function Get_New_TellerDetailID() {
		return action("TellerRecharge/Get_New_TellerDetailID");
	}
	private function Get_TellerID($Psc) {
		return action("TellerRecharge/Get_TellerID", ["ShopNO" => $Psc]);
	}
	/**
	 * @title 批量确认 更新数据
	 * @type interface
	 * @login 1
	 * @return data 提示信息
	 */
	public function button1_Click() {
		try {
			$data = $this->UpdateTempPos();
			return $data;
		} catch (\Exception $exc) {
			return err(9000, $exc->getMessage());
		}

	}
	//更新数据,将数据写入表tb_TempPos中
	private function UpdateTempPos() {
		$amount = 0;
		$date = date('Y-m-d');
		$sql = "insert into tb_TempPos(银行,shopno,pos日期,存款,来源,备注,Region,上传日期) select 银行,SC,POS日期,cast(存款 as money),来源POS,备注,Region,'" . $date . "' as 上传日期  from posteller";
		$amount = $this->Exc_Sql($sql);
		if ($amount > 0) {
			return suc($amount . "条数据更新成功!");
		} else {
			exception("更新失败,Sql:" . $sql);
		}

	}
}
