<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title 财务Teller查询系统
 * @type menu
 * @login 1
 */
class TellerRecharge extends Common {
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
	 * @title 获取地区列表
	 * @type interface
	 * @login 1
	 * @return data 结果集合 array
	 */
	public function getRegion() {
		$sql = "select region from regioninfo group by region";
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
	/**
	 * @title 获取右侧银行列表
	 * @type interface
	 * @login 1
	 * @return data 结果集合 array
	 */
	public function getBank() {
		$sql = 'select BANK_NAME from tb_TempTeller group by BANK_NAME';
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
	//Teller部分上端的查询按钮
	/**
	 * @title 财务Teller查询 左侧查询
	 * @type  inteface
	 * @login 1
	 * @param confirm 确认   0
	 * @param Unconfirm 未确认   0
	 * @param textBox1 选择筛选条件后输入的值 0
	 * @param comboBLcondition 筛选条件 0
	 * @param From 查询开始日期 1
	 * @param To 查询截止日期 1
	 * @return data 查询结果集合 array
	 */
	public function button2_Click() {
		if (($this->input['Unconfirm'] && $this->input['confirm']) || ($this->input['checkBox3'] && $this->input['checkBox4']) || ($this->input['checkBox10'] && $this->input['checkBox9'] || ($this->input['checkBox7'] && $this->input['checkBox8']))) {
			return err(9000, "不能同时选择两项！");
		} else {

			$data['Get_Teller_Info'] = $this->Get_Teller_Info("system");
			$this->CalcSum($data['Get_Teller_Info']); //计算收入支出总和
			$data['input'] = $this->input;
			$data['Get_Teller_Info'] = utf_8($data['Get_Teller_Info']);
			return suc($data);
		}
	}
	//查询对应的信息并返回
	private function Get_Teller_Info($table) {
		try
		{

			$sqlorder = "";
			//左侧查询
			if ($table == "system") {
				if (trim($this->input['textBox1']) == "") {
					$sqlorder = "select " . $this->SearchResult . "  from tb_SystemTeller where effective_date>='" . $this->input['From'] . "' and effective_date<='" . $this->input['To'] . "' " . $this->SearchSql();

				} else {
					$sqlorder = "select " . $this->SearchResult . "  from tb_SystemTeller where memo like'%" . $this->input['Memo'] . "%' and effective_date>='" . $this->input['From'] . "' and effective_date<='" . $this->input['To'] . "' and " . $this->Get_from_combobox($this->input['comboBLcondition']) . " like'%" . $this->input['textBox1'] . "%' " . $this->SearchSql();
				}
				//右侧查询
			} else if ($table == "temp") {
				if ($this->input['right_comboBox8'] == 4) {
					if ($this->input['right_pos']) {
						$sqlorder = $this->TempTellerSqlResults() . "  from tb_TempTeller where memo='POS' and isnull(SystemTellerID,0)<1 and isnull(relevanceBit,0)<1 and posdate>='" . $this->input['right_from1'] . "' and posdate<='" . $this->input['right_to1'] . "' " . $this->SearchSql_T();
					} else {
						$sqlorder = $this->TempTellerSqlResults() . "  from tb_TempTeller where memo='POS' and isnull(SystemTellerID,0)<1 and isnull(relevanceBit,0)<1 and 确认日期>='" . $this->input['right_from'] . "' and 确认日期<='" . $this->input['right_to'] . "' " . $this->SearchSql_T();
					}

				} else if ($this->input['right_comboBox8'] == 5) {
					$sqlorder = $this->TempTellerSqlResults() . "  from tb_TempTeller where memo<>'POS' and isnull(SystemTellerID,0)<1 and isnull(relevanceBit,0)<1 and 确认日期>='" . $this->input['right_from'] . "' and 确认日期<='" . $this->input['right_to'] . "' " . $this->SearchSql_T();
				} else if ($this->input['right_textBox5'] == "") {
					$sqlorder = $this->TempTellerSqlResults() . " from tb_TempTeller where isnull(SystemTellerID,0)<1 and isnull(relevanceBit,0)<1 and 确认日期>='" . $this->input['right_from'] . "' and 确认日期<='" . $this->input['right_to'] . "' " . $this->SearchSql_T();
				} else {
					$sqlorder = $this->TempTellerSqlResults() . " from tb_TempTeller where isnull(SystemTellerID,0)<1 and isnull(relevanceBit,0)<1 and 确认日期>='" . $this->input['right_from'] . "' and 确认日期<='" . $this->input['right_to'] . "' and " . $this->Get_from_combobox($this->input['right_comboBox8']) . " like'%" . $this->input['right_textBox5'] . "%' " . $this->SearchSql_T();
				}

			}

			$data = $this->query_Sql($sqlorder);
			return $data;
		} catch (\Exception $ee) {
			return err(9000, $ee->getMessage());
		}
	}
	//拼接Teller查询时的查询条件
	private function SearchSql() {
		$searchsql = "";

		if ($this->input['Unconfirm']) {
			$searchsql = " and 确认='0'";

			if ($this->input['checkBox3']) {
				$searchsql = "and withdrawal>0 and 确认='0'";
			}

			if ($this->input['checkBox4']) {
				$searchsql = "and deposit>0 and 确认='0'";
			}

		}
		if ($this->input['confirm']) {
			$searchsql = " and 确认='1'";
			if ($this->input['checkBox3']) {
				$searchsql = "and withdrawal>0 and 确认='1'";
			}

			if ($this->input['checkBox4']) {
				$searchsql = "and deposit>0 and 确认='1'";
			}

		}
		if (!$this->input['Unconfirm'] && !$this->input['confirm']) {

			if ($this->input['checkBox3']) {
				$searchsql = "and withdrawal>0 ";
			}

			if ($this->input['checkBox4']) {
				$searchsql = "and deposit>0 ";
			}

		}
		if ($this->input['checkBox5']) {
			$searchsql = $searchsql . " and 使用='1'";
		}

		if ($this->input['checkBox6']) {
			$searchsql = $searchsql . " and 使用='0'";
		}

		return $searchsql;
	}
	//将筛选条件转化为对应字段
	private function Get_from_combobox($index) {
		if ($index == 0) {
			return "deposit";
		} else if ($index == 1) {
			return "bank_name";
		} else if ($index == 2) {
			return "专卖店";
		} else {
			return "memo";
		}

	}
	//拼接查询sql
	private function TempTellerSqlResults() {
		return "select relevanceBit as 关联,TellerID as ID,Bank_name as 银行,withdrawal as 取款,Deposit as 存款,确认日期,posdate as POS日期,业绩所属期,用途,ConfirmPerson as ConfPerson,确认,使用,专卖店,Region,memo as 来源,Note as 备注";
	}
	private function SearchSql_T() {
		$searchsql = "";

		if ($this->input['checkBox10']) {
			$searchsql = " and 确认='0'";

			if ($this->input['checkBox8']) {
				$searchsql = "and withdrawal>0 and 确认='0'";
			}

			if ($this->input['checkBox7']) {
				$searchsql = "and deposit>0 and 确认='0'";
			}

		}
		if ($this->input['checkBox9']) {
			$searchsql = " and 确认='1'";
			if ($this->input['checkBox8']) {
				$searchsql = "and withdrawal>0 and 确认='1'";
			}

			if ($this->input['checkBox7']) {
				$searchsql = "and deposit>0 and 确认='1'";
			}

		}
		if (!$this->input['checkBox10'] && !$this->input['checkBox9']) {

			if ($this->input['checkBox8']) {
				$searchsql = "and withdrawal>0 ";
			}

			if ($this->input['checkBox7']) {
				$searchsql = "and deposit>0 ";
			}

		}
		return $searchsql;
	}
	//计算左则查询收入支出总和
	private function CalcSum($data) {

		$withdrawal = 0.0;
		$deposit = 0.0;
		foreach ($data as $v) {
			$vv = array_values($v);

			$withdrawal = $withdrawal + $vv[6]; //取款金额

			$deposit = $deposit + $vv[7]; //存款金额
		}
		$this->input['current_income'] = $deposit; //本期收入
		$this->input['current_expenditures'] = $withdrawal; //本期支出

	}
	//计算右则查询收入支出总和
	private function CalcSum_T($data) {

		$withdrawal = 0.0;
		$deposit = 0.0;
		foreach ($data as $v) {
			$vv = array_values($v);

			$withdrawal = $withdrawal + $vv[3];

			$deposit = $deposit + $vv[4];
		}
		$this->input['right_current_income'] = $deposit;
		$this->input['right_current_expenditures'] = $withdrawal;

	}
	/**
	 *@title 财务Teller查询 左侧确认
	 *@type  inteface
	 *@login 1
	 *@param confirmation_date 确认日期
	 *@param TellerID
	 *@param bank 银行
	 *@param Region 地区
	 *@param amount 金额
	 *@param shop_type 专卖店或者经销商
	 *@param shopno 对应专卖店(经销商)号
	 *@return data 查询结果集合 array
	 */
	public function Sconfirm_button9_Click() {
		$this->TeLLID = $this->input["TellerID"];
		$rule = [
			"TellerID" => "require",
			"confirmation_date" => "require",
			"bank" => "require",
			"shop_type" => "require",
			"shopno" => "require",
			"Region" => "require",
			"amount" => "require",
		];
		$msg = [
			"TellerID" => "请选择要确认的teller",
			"confirmation_date" => "请填写确认日期",
			"bank" => "请填写银行名称",
			"shop_type" => "请选择专卖店或者经销商",
			"shopno" => "请填写专卖店或者经销商编号",
			"Region" => "请选择地区",
			"amount" => "请填写数额",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$bool = $this->CheckIfJustConfirm("系统", trim($this->input['TellerID']));
		if (isset($bool['stat'])) {
			return $bool;
		}
		if (!$bool) {

			if ($this->TeLLID == "-1") {
				return err(9000, "请选择要确认的teller");
			} else {
				if ($this->CheckIfConfirm($this->input['TellerID'])) {
					return err(9000, "此Teller已经确认！");
				} else if (strtoupper(trim($this->CheckIfLock($this->TeLLID))) != "LOCKED") {

					if ($this->input['Region'] < 0 && $this->input['freeze']) {
						return err(9000, "现在是冻结状态，请选择区域店");
					} else if (!$this->CheckIfcorrectShopno()) {
						return err(9000, "请填写正确的专卖店或卡号！");
					} else {

						if (strlen($this->input['shopno']) == 3 && $this->input['shop_type'] == 1) {
							return err(9000, "请选择专卖店选项！");
						} else {

							Db::startTrans();
							try {
								$data = $this->ConfirmTeller();
								Db::commit();
								return suc($data);
							} catch (Exception $exc) {
								Db::rollback();
								return err(9000, $exc->getMessage());
							}

						}

					}
				} else {
					return err(9000, "该Teller已经被锁定不能确认，原因如下：\n" . $this->GetLOCKEDReason($this->input['TellerID']));
				}

			}
		}
		return err(9000, "系统Teller确认错误");
		//系统Teller确认

	}
	public function CheckIfJustConfirm($Temp_System, $SystemtellerID) {
		$status = true;
		$sql = "";
		try {

			$number = 0;
			$sql = "select count(*) as c  from fd_tellerdetail where 用途='" . $Temp_System . "' and systemtellerid=" . $SystemtellerID;
			$number = $this->GetStringData($sql);
			if ($number == 1) {
				return err(3000, "报警！！！已经确认进系统，请联系Eric");
				$status = true;
			} else if ($number > 1) {
				return err(3000, "报警！！！已经确认进系统，并且确认了" . $number . "次，请联系Eric");
				$status = true;
			} else {
				$status = false;
			}

		} catch (\Exception $ee) {
			//MessageBox.Show(sql);
		}
		return $status;
	}
	//从表tb_systemteller中获取锁定说明
	private function GetLOCKEDReason($tellerid) {
		$reason = "";

		$sql = "select isnull(lockreason,0) from tb_systemteller where tellerid='" . $tellerid . "'";
		$reason = $this->GetStringData($sql);
		return $reason;
	}
	//从表tb_systemteller中判断是否确认(0 未确认,1 已确认)
	private function CheckIfConfirm($tellerid) {
		$sql = "";
		$result = "";
		$sql = "select 确认 from tb_systemteller where TellerID=" . $tellerid;
		$result = $this->GetStringData($sql);
		if (trim($result) == "True") {
			return true;
		} else {
			return false;
		}

	}
	//从表tb_systemteller中获取对应tellerid的lockbeforeconfirm字段的值
	private function CheckIfLock($tellerid) {
		$Lockstatus = "";
		//isnull() 函数 作用: lockbeforeconfirm字段的值是null的话则返回 0;
		$sql = "select isnull(lockbeforeconfirm,0) as Lockstatus from tb_systemteller where tellerid='" . $tellerid . "'";
		$Lockstatus = $this->GetStringData($sql);
		return $Lockstatus;
	}
	//验证专卖店或者经销商号是否符合要求
	private function CheckIfcorrectShopno() {
		if (preg_match("/^\d{3}$/", $this->input['shopno']) || preg_match("/^(k|K)(n|N)\d{6}$|^(k|K)(g|G)\d{6}$/", $this->input['shopno'])) {
			return true;
		}
		return false;
	}
	//更改tb_SystemTeller表中确认字段的值为1
	private function ConfirmTeller() {

		$confirmsql = "";

		$confirmsql = "update tb_SystemTeller set 确认='1', region='" . $this->input['Region'] . "',用途='" . $this->textBox2 . "',专卖店='" . $this->input['shopno'] . "',确认日期='" . $this->input['confirmation_date'] . "',confirmperson='" . $this->TellerOper . "' where TellerID='" . $this->input['TellerID'] . "'";
		$status = $this->Exc_Sql($confirmsql);
		if (is_numeric($status)) {
			$this->InsertFD_tellerdetail();
			$this->TeLLID = "-1";
			if ($this->GetStringData("select count(*) as c from fd_tellerdetail where 用途='系统' and systemtellerid=" . $this->input['TellerID']) == "1") {
				$data["msg"] = "确认成功";
				$data["action"] = "查询";
				return suc($data);
			} else {
				$this->Exc_Sql("delete from fd_tellerdetail where 用途='系统' and systemtellerid=" . $this->input['TellerID']);

				$this->Exc_Sql("update tb_SystemTeller set 确认='0', 用途='" . $this->textBox2 . "',专卖店='" . $this->input['shopno'] . "',确认日期='" . $this->input['confirmation_date'] . "',confirmperson='" . $this->TellerOper . "' where TellerID='" . $this->input['TellerID'] . "'");
				return err(9000, "确认失败，数据回滚！");

			}
			$this->ProcessChangeTeller($this->input['shopno']);
		} else {
			return err(9000, "更新systemteller失败！");
		}

	}
	//FD_tellerDetail表中生成记录
	private function InsertFD_tellerdetail() {

		$confirmsql = "";
		if ($this->input['freeze']) //勾选冻结
		{
			$confirmsql = "insert FD_tellerDetail(TellerDetailID,shopno,Deposit,used,bank,[date],TellerID,用途,所属期,SystemTellerID,Region,Oper,memo1,freezemark) " .
			"values('" . $this->Get_New_TellerDetailID() . "','" . $this->input['shopno'] . "','" . $this->ProccessNull($this->input['amount']) . "','0','" . $this->input['bank'] . "','" . $this->input['confirmation_date'] . "','" . $this->Get_TellerID($this->input['shopno']) . "','" . $this->textBox2 . "','','" . $this->input['TellerID'] . "','" . $this->input['Region'] . "','" . $this->TellerOper . "','" . $this->input['Memo'] . "','1')";
			//冻结选项后将其标志位置1
		} else {
			//未勾选冻结生成的记录
			$confirmsql = "insert FD_tellerDetail(TellerDetailID,shopno,Deposit,used,bank,[date],TellerID,用途,所属期,SystemTellerID,Region,Oper,memo1,freezemark) " .
			"values('" . $this->Get_New_TellerDetailID() . "','" . $this->input['shopno'] . "','" . $this->ProccessNull($this->input['amount']) . "','0','" . $this->ProccessNull($this->input['bank']) . "','" . $this->input['confirmation_date'] . "','" . $this->Get_TellerID($this->input['shopno']) . "','" . $this->textBox2 . "','','" . $this->input['TellerID'] . "','" . $this->input['Region'] . "','" . $this->TellerOper . "','" . $this->input['Memo'] . "','0')";
		}

		$status = $this->Exc_Sql($confirmsql);
		if ($status <= 0) {
			exception("插入数据错误" . __LINE__);
		}
		$this->ProcessChangeTeller($this->input['shopno']);

	}
	//生成FD_TellerDetail表的新id
	public function Get_New_TellerDetailID() {
		$Get_TellarID = 0;
		$sql = "select max(tellerdetailid)+1 as tellerdetailid from FD_TellerDetail";
		$Get_TellarID = $this->GetStringData($sql);
		return $Get_TellarID;
	}
	public function Get_TellerID($ShopNO) {
		$Get_TellarID = 0;
		$sql = "";
		try
		{
			$sql = "select tellerid from FD_Teller where shopno='" . $ShopNO . "'";
			$data = $this->GetStringData($sql);
			if ($data) {
				$Get_TellarID = $data;
			} else {
				$Upadte_Teller_Sql = "";
				$Get_TellarID = $this->Get_New_TellerID();
				$Upadte_Teller_Sql = "insert FD_Teller(TellerID,Shopno,total,used,banlance) values('" . $this->Get_New_TellerID() . "','" . $ShopNO . "','0','0','0')";
				$this->Exc_Sql($Upadte_Teller_Sql);
			}
		} catch (\Exception $ee) {
			$Upadte_Teller_Sql = "";
			$Get_TellarID = $this->Get_New_TellerID();
			$Upadte_Teller_Sql = "insert FD_Teller(TellerID,Shopno,total,used,banlance) values('" . $this->Get_New_TellerID() . "','" . $ShopNO . "','0','0','0')";
			$this->Exc_Sql($Upadte_Teller_Sql);
		}

		return $Get_TellarID;
	}
	private function Get_New_TellerID() {
		$sql = "select max(tellerid)+1 as tellerid  from FD_Teller ";
		$Get_TellarID = $this->GetStringData($sql);
		return (int) $Get_TellarID;
	}
	/**
	 *@title 财务部teller查询 左侧反确认
	 *@type  inteface
	 *@login 1
	 *@param confirmation_date 确认日期
	 *@param TellerID
	 *@param bank 银行
	 *@param Region 地区
	 *@param amount 金额
	 *@param shop_type 专卖店或者经销商
	 *@param shopno 对应专卖店(经销商)号
	 *@param textBox29_S 用途
	 *@param dt1 EFFECTIVE_DATE(时间)
	 *@param textBox4 确认
	 *@return data 查询结果集合 array
	 */
	public function button13_Click() {
		$this->TeLLID = $this->input["TellerID"];
		$rule = [

			"TellerID" => "require",
			"confirmation_date" => "require",
			"bank" => "require",
			"shop_type" => "require",
			"shopno" => "require",
			"Region" => "require",
			"amount" => "require",
			"dt1" => "require",
			"textBox29_S" => "require",
		];
		$msg = [
			"TellerID" => "请选择要确认的teller",
			"confirmation_date" => "require",
			"bank" => "require",
			"shop_type" => "require",
			"shopno" => "未选择专卖店",
			"Region" => "require",
			"amount" => "require",
			"dt1" => "teller时间必填",
			"textBox29_S" => "用途必填",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$datedif = time() - strtotime($this->input['dt1']);
		$days = $datedif / 24 / 3600;
		if ($days > 30) {
			return err(9000, floor($days) . " 天距今，无法反确认！");
		} else {
			if ($this->input['textBox29_S'] == "关联" && $this->CheckRelativeType()) {
				return err(9000, "关联项过多，请联系Eric!");
			} else {
				$data = $this->Anti_Confirm();
				return $data;
			}
		}
	}
	private function CheckRelativeType() {
		$RelativeQTY = 0;
		$sql = "SELECT COUNT(*) as c FROM TB_TEMPTELLER WHERE SYSTEMTELLERID='" . $this->input['TellerID'] . "'";
		$RelativeQTY = $this->GetStringData($sql);
		if ($RelativeQTY > 1) {
			return true;} else {
			return false;
		}

	}
	private function Anti_Confirm() {
		$warn = "";
		if ($this->input['textBox29_S'] == "关联") {
			$warn = "此Teller为与临时Teller相关联的数据，请慎重反确认！";
		}
		if ($this->input['textBox4'] == "0") {
			return err(9000, "当前Teller未确认，不用反确认！");
		} else {
			if ($this->CheckIf_Unused($this->ProccessNull($this->input['amount']), $this->input['shopno']) == 1) {
				Db::startTrans();
				try {
					$anti_confirmsql = "";
					$anti_confirmsql = "update tb_systemteller set 确认='0',专卖店='',用途='', confirmperson='" . $this->TellerOper . "' where TellerID='" . $this->TeLLID . "'";
					$status = $this->Exc_Sql($anti_confirmsql);
					if ($status == 1) {
						$this->Delete_From_TellerDetail($this->textBox2, $this->input['TellerID']);
						$this->TeLLID = "-1";
						$this->ProcessChangeTeller($this->input['shopno']);
						$this->TellerLog("系统确认", $this->input['shopno'], $this->input['Region'], $this->input['bank'], $this->input['amount'], "tb_SystemTeller", $this->input['TellerID'], $this->input['confirmation_date'], date('Y-m-d'), $this->TellerOper, $anti_confirmsql);
						Db::commit();
						return suc("反确认成功!");

					}
				} catch (\Exception $exc) {
					Db::rollback();
					return err(9000, "反确认systemteller失败！" . $exc->getMessage());
				}

			} else {
				return err(9000, "Teller已经使用！");
			}

		}
	}
	private function Delete_From_TellerDetail($table, $systemtellerID) {
		//textBox29_S 是用途字段
		$deletesql = "";
		$UpdateTempTeller = "";
		if ($this->input['textBox29_S'] == "关联") {

			$deletesql = "delete from FD_tellerDetail where systemtellerID='" . $this->GetRelativeTempTellerID($systemtellerID) . "' and 用途='" . $this->input['right_remark'] . "'";
			$status = $this->Exc_Sql($deletesql);
			if ($status) {
				$UpdateTempTeller = "delete from TB_TEMPTELLER WHERE SYSTEMTELLERID='" . $systemtellerID . "' ";
				$status = $this->Exc_Sql($UpdateTempTeller);
			} else {
				return err(9000, "关联项删除失败，Sql:" . $deletesql);
			}

			$this->ProcessChangeTeller(PP . GetStringData("select shopno from fd_tellerDetail where systemtellerID='" . $this->GetRelativeTempTellerID($systemtellerID) . "' and 用途='" . $this->input['right_remark'] . "'"));

		} else {
			$deletesql = "delete from FD_tellerDetail where systemtellerID='" . $systemtellerID . "' and 用途='" . $table . "'";
			$status = $this->Exc_Sql($deletesql);
			if (!is_numeric($status)) {
				return err("SQL命令" . "执行失败，速速联系ERIC! ");
			} else {
				$this->ProcessChangeTeller($this->GetStringData("select shopno from fd_tellerDetail where systemtellerID='" . $systemtellerID . "' and 用途='" . $table . "'"));
			}

		}

	}
	//获取tb_TempTeller表中对应systemtellerID的id
	private function GetRelativeTempTellerID($systemtellerID) {
		$TempTellerID = "";

		$sql = "SELECT tellerID FROM TB_TEMPTELLER WHERE SYSTEMTELLERID='" . $systemtellerID . "' ";
		$TempTellerID = $this->GetStringData($sql);
		return $TempTellerID;

	}
	private function CheckIf_Unused($telleramount, $sc) {
		$checkless = 0;
		if ($this->Get_TellerBalance($sc) >= $telleramount || $sc == "998") {
			$checkless = 1;
		}

		return $checkless;

	}
	//根据shopno 从FD_Teller表中查询计算可用余额
	private function Get_TellerBalance($sc) {

		$sql = "select banlance from FD_Teller where shopno='" . $sc . "'";

		$Get_TellarBalance = $this->GetStringData($sql);
		return $Get_TellarBalance;
	}
	/**
	 * @title 财务部teller查询 锁定未确认
	 * @type interface
	 * @login 1
	 * @param  Memo 锁定备注(lockreason)
	 * @param TellerID
	 * @return data 返回执行结果状态和提示消息 array
	 */
	public function button26_Click() {
		if (!$this->input["TellerID"]) {
			return err(9000, "请选择要确认的teller");
		} else if ($this->input['Memo'] == "") {
			return err(9000, "请在备注处填写明锁定原因！");
		} else {
			$data = $this->LockTellerWithoutConfirm();
			return $data;
		}
	}
	//执行锁定未确认
	private function LockTellerWithoutConfirm() {
		$locksql = "update tb_systemTeller set lockbeforeconfirm='LOCKED',lockreason='" . $this->input['Memo'] . "' where TellerID='" . $this->input["TellerID"] . "' and isnull(使用,0)!='1' and isnull(确认,0)!='1'";
		$status = $this->Exc_Sql($locksql);
		if ($status) {
			return suc("锁定成功！");
		} else {

			return err(9000, "锁定失败！该Teller已经使用或者确认,无法进行锁定!");
		}

	}
	/**
	 * @title 财务部teller查询 关联
	 * @type  interface
	 * @login 1
	 * @param textBox4 左侧选择数据的背景色(LIGHTGRAY,LIGHTBLUE)
	 * @param bank 左侧选择数据的银行名称
	 * @param manualbank 右侧所选数据的银行
	 * @param amount 左侧金额
	 * @param right_amount 右侧关联数额(勾选关联的金额之和)
	 * @param shopno  右侧专卖店编号
	 * @param TellerID 左侧tellerid
	 * @param confirmation_date 左侧的确认日期
	 * @return data 数据集合 array
	 */
	public function button1_Click() {

		if ($this->input['textBox4'] == "LIGHTGRAY") {
			return err(9000, "系统Teller已经确认，请先反确认再和临时Teller关联！");
		} else if ($this->input['textBox4'] == "LIGHTBLUE") {
			return err(9000, "系统Teller已经使用，请联系ERIC！");
		} else if ($this->input['bank'] != $this->input['manualbank']) {
			return err(9000, "关联双方的银行不匹配！");

		} else {
			$data = $this->Get_Teller_Info("system");
			$this->Get_GroupTempTeller($data);
			if ((int) ($this->ProccessNull($this->input['amount'])) > 0 && (int) ($this->ProccessNull($this->input['amount'])) == (int) ($this->ProccessNull($this->input['right_amount ']))) {
				Db::startTans();
				try {
					$data = $this->Reletive_UpdateSystemTeller();
					Db::commit();
					return suc($data);
				} catch (\Exception $exc) {
					Db::rollback();
					return err(9000, $exc->getMessage());
				}

			} else {
				return err(9000, "Teller数额核对不上！");
			}

		}
	}
	private function Get_GroupTempTeller($data) {
		$TempTellerMoney = 0;
		foreach ($data as $v) {
			$vv = array_values($v);
			if ($vv[0] == "True") {

				$TempTellerMoney = $TempTellerMoney + $vv[4];
			}

		}
		$this->input['right_amount '] = $TempTellerMoney;
	}
	//执行关联动作,修改tb_SystemTeller表中的数据 确认='1', 用途='关联'
	private function Reletive_UpdateSystemTeller() //关联动作
	{
		$confirmsql = "update tb_SystemTeller set 确认='1', 用途='关联',region='" . $this->input['Region'] . "',专卖店='" . $this->input['shopno'] . "',确认日期='" . $this->input['confirmation_date'] . "',confirmperson='" . $this->TellerOper . "' where TellerID=" . $this->input['TellerID'] . "";
		$status = $this->Exc_Sql($confirmsql);
		if ($status) //更新失败终止执行后续的程序
		{
			return $this->Reletive_UpdateTempTeller();

		} else {
			exception("关联失败！");
		}

	}
	private function Reletive_UpdateTempTeller() {
		$data = $this->Get_Teller_Info("system");
		foreach ($data as $v) {
			$vv = array_values($v);
			if ($vv[0] == "True") {
				$confirmsql = "update tb_TempTeller set relevanceBit='1',systemtellerID='" . $this->input['TellerID'] . "' where TellerID='" . $vv[1] . "'";
				$status = $this->Exc_Sql($confirmsql);
				if (!is_numeric($status)) {
					exception("更新TempTeller数据失败，Teller编号是" . $vv[1]);
					// $this->RecordInLog("更新TempTeller数据失败记录：", confirmsql, this.Name, TellerOper.Text.Trim());
				} else {
					$confirmsql = "update tb_SystemTeller set 确认='1',确认日期='" . $vv[5] . "' where TellerID='" . $this->input['TellerID'] . "'";
					$status = $this->Exc_Sql($confirmsql);
					if ($status) {
						$data['msg'] = "关联成功！";
						$data['action'] = "button2_Search.PerformClick button3.PerformClick";
						return suc($data);
					} else {
						exception("Error:" . $confirmsql);
					}

				}

			}

		}

	}
	//POS部分上端的查询按钮
	/**
	 * @title 财务Teller查询 右侧查询
	 * @type  inteface
	 * @login 1
	 * @param right_comboBox8 筛选条件
	 * @param right_textBox5 筛选条件输入值
	 * @param right_pos 右侧POS勾选框
	 * @param right_from1 勾选POS查询开始日期
	 * @param right_to1 勾选POS查询截止日期
	 * @param right_from 查询开始日期
	 * @param right_to 查询截止日期
	 * @return data 查询结果集合 array
	 */
	public function button3_Click() {
		$data["Get_Teller_Info"] = $this->Get_Teller_Info("temp");
		$data['CalcSum_T'] = $this->CalcSum_T($data["Get_Teller_Info"]);
		$data['input'] = $this->input;
		$data['Get_Teller_Info'] = utf_8($data['Get_Teller_Info']);
		return suc($data);
	}
	/**
	 * @title 财务Teller查询 右侧删除
	 * @type interface
	 * @login 1
	 * @param TempTellID
	 * @param right_amount	deposit数额(存款)
	 * @param right_shopno 专卖店或者经销商号
	 * @param right_remark  用途
	 * @param right_Region  右侧地区
	 * @param right_bank  右侧银行
	 * @param right_confirmation_date  确认日期
	 * @return data 结果集合 array
	 */
	public function button11_Click() {
		$this->TempTellID = $this->input['TempTellID'];
		if ($this->TempTellID == "-1") {
			return err(9000, "请选择要删除的teller");
		} else {

			if ($this->CheckIf_Unused($this->ProccessNull($this->input['right_amount']), $this->input['right_shopno']) == 1) {

				$deletesql = "";

				Db::startTrans();
				try {
					$deletesql = "update tb_TempTeller set relevanceBit='2',memo='删除',systemtellerid=-1 where TellerID='" . $this->TempTellID . "' and isnull(使用,0)!='1'";
					$status = $this->Exc_Sql($deletesql);
					$this->Delete_From_TellerDetail($this->input['right_remark'], $this->TempTellID);
					$this->ProcessChangeTeller($this->input['right_shopno']);
					$data['msg'] = "删除成功！";
					$data['action'] = "button3.PerformClick()";

					$this->TempTellID = "-1";
					$this->TellerLog("删除临时Teller", $this->input['right_shopno'], $this->input['right_Region'], $this->input['right_bank'], $this->input['right_amount'], "tb_TempTeller", $this->input['right_TellerID'], $this->input['right_confirmation_date'], date('Y-m-d'), $this->TellerOper, $deletesql);
					Db::commit();
					return suc($data);
				} catch (\Exception $exc) {
					Db::rollback();
					return err(9000, $exc->getMessage());
				}

				return err(9000, "删除失败！");

			} else {
				return err(900, "Teller已经使用不能删除，请联系ERIC！");
			}

		}
	}
	/**
	 * @title 财务Teller查询 右侧批量删除
	 * @type interface
	 * @login 1
	 * @param data array形式
	 * @return [type] [description]
	 */
	public function button30_Click() {
		//$data = json_decode($this->input['data'], true);
		$data = $this->input['data'];
		if (empty($data)) {
			return err(9000, "数据为空");
		}
		Db::startTrans();
		try {
			foreach ($data as $v) {
				$vv = array_values($v);
				$TempTellerID = "";
				$ShopNo = "";
				$TellerAmount = "";
				$TempTellerID = $vv[0];
				$ShopNo = $vv[2];
				$TellerAmount = $vv[1];
				$this->DeleteAllSelect($TempTellerID, $TellerAmount, $ShopNo);

			}
			Db::commit();
			return suc("所选记录删除成功");
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	//右侧临时teller删除方法
	private function DeleteAllSelect($TempTellerID, $TellerAmount, $ShopNo) {

		if ($this->CheckIf_Unused($this->ProccessNull($TellerAmount), $ShopNo) == 1) {

			$deletesql = "";
			$deletesql = "update tb_TempTeller set relevanceBit='2',memo='删除',systemtellerid=-1 where TellerID='" . $TempTellerID . "' and isnull(使用,0)!='1'";
			$status = $this->Exc_Sql($deletesql);
			if (is_numeric($status)) {
				$this->Delete_From_TellerDetail("临时", $TempTellerID);
				$this->ProcessChangeTeller(trim($ShopNo));
				$this->TellerLog("确认临时Teller", $this->input['right_shopno'], $this->input['right_Region'], $this->input['right_bank'], $this->input['right_amount'], "tb_TempTeller", $this->input['right_TellerID'], $this->input['right_confirmation_date'], date('Y-m-d'), $this->TellerOper, $deletesql);

			} else {
				$this->RecordInLog("删除表fd_tellerdetail失败记录：", $deletesql, $this->Name, $this->TellerOper);

				exception("删除失败！");
			}

		} else {
			exception("编号：" . $TempTellerID . "专卖店：" . $ShopNo . ",数额：" . $TellerAmount . "的Teller已经使用不能删除，请联系ERIC！");
		}

	}
	/**
	 * @title 财务Teller查询 右侧确认
	 * @type  interface
	 * @login 1
	 * @param right_shopno 专卖店编号
	 * @param checkBox10  专卖店或者经销商
	 * @param right_bank   银行
	 * @param right_Region 地区
	 * @param right_TellerID
	 * @param right_freeze 是否勾选冻结
	 * @param right_Cash_Last 是否勾选扣除现金手续
	 * @param right_confirmation_date 确认日期
	 * @param right_amount deposit数额
	 * @param right_pos 是否勾选右测中间的POS
	 * @param right_from1 右侧POS旁边的开始日期
	 * @param textBox27 pos旁边的备注输入框
	 * @param textBox3 用途
	 * @param right_remark 右侧输入框中写的备注
	 * @return data 数据集合 array
	 */
	public function button12_Click() {
		$rule = [
			"right_shopno" => "require",
			"right_bank" => "require",
		];
		$msg = [
			"right_shopno" => "未选择专卖店",
			"right_bank" => "未选择银行",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);
		}
		$this->input['right_TellerID'] = $this->CreateTempTeller(); //为tb_tempteller 生成新的TellerID
		if ($this->input['right_Region'] < 0 && $this->input['right_freeze']) {
			return err(9000, "现在是冻结状态，请选择区域店");
		} else {

			if (strlen($this->input['right_shopno']) == 3 && $this->input['checkBox10'] == 1) {
				return err(9000, "请选择专卖店选项！");
			} else {
				Db::startTrans();
				try {
					$msg = $this->CheckIfReconfirm();
					if (isset($msg['stat']) && $msg['stat']) {

						$data = $this->ConfirmTeller_T();

					} else {
						$data = $this->ConfirmTeller_T();
					}
					Db::commit();
					return suc($data);
				} catch (\Exception $exc) {
					Db::rollback();
					return err(9000, $exc->getMessage());
				}

			}

		}

	}
	//为tb_tempteller 生成新的TellerID
	private function CreateTempTeller() {
		(int) $Get_TempTellarID = 0;

		$sql = "select max(tellerid)+1 as tellerid from tb_tempteller";

		$Get_TempTellarID = $this->GetStringData($sql);

		return $Get_TempTellarID;
	}
	//判断是否已经确认过
	private function CheckIfReconfirm() {
		$confirmsql = "";
		$confirmsql = "update tb_TempTeller set 专卖店=专卖店 where 确认日期='" . $this->input['right_confirmation_date'] . "' and bank_name='" . $this->input['right_bank'] . "' and deposit='" . $this->ProccessNull($this->input['right_amount']) . "'";
		$status = $this->Exc_Sql($confirmsql);
		if (is_numeric($status)) {
			$msg = $this->GetStringData("select top 1 'TellerID:'+CAST(TellerID as varchar(50))+',Bank:'+Bank_Name+',Memo:'+Memo +',Effective日期：'+CAST(Effective_Date as varchar(50))+',金额：'+CAST(deposit as varchar(50))+',确认日期：'+CAST(确认日期 as varchar(50))+',专卖店：'+专卖店+',确认人：'+confirmPerson from tb_TempTeller where 确认日期='" . $this->input['right_confirmation_date'] . "' and bank_name='" . $this->input['right_bank'] . "' and deposit='" . $this->ProccessNull($this->input['right_amount']) . "'");
			return suc($msg . "重复Teller信息：");

		} else {
			return false;
		}

	}
	private function ConfirmTeller_T() {
		$POSDate = "";
		if ($this->input['right_pos']) {
			$POSDate = $this->input['right_from1'];
		}

		$confirmsql = "";
		$confirmsql = "set IDENTITY_INSERT tb_TempTeller on insert tb_TempTeller(确认,用途,专卖店,业绩所属期,确认日期,confirmperson,tellerid,bank_name,Deposit,Memo,effective_date,withdrawal,POSDate,Note,region) " .
		" values('1','" . trim($this->input['textBox3']) . "','" . trim($this->input['right_shopno']) . "','','" . $this->input['right_confirmation_date'] . "','" . $this->TellerOper . "','" . $this->input['right_TellerID'] . "','" .
		$this->input['right_bank'] . "','" . $this->ProccessNull($this->input['right_amount']) . "','" . $this->input['textBox27'] . "','" . $this->input['right_confirmation_date'] . "','0','" . $POSDate . "','" . $this->input['right_remark'] . "','" . $this->input['right_Region'] . "')";
		$status = $this->Exc_Sql($confirmsql);
		if ($status == 1) {
			//向tellerlog表中写入记录
			$this->TellerLog("确认临时Teller", $this->input['right_shopno'], $this->input['right_Region'], $this->input['right_bank'], $this->input['right_amount'], "tb_TempTeller", $this->input['right_TellerID'], $this->input['right_confirmation_date'], date("Y-m-d"), $this->TellerOper, $confirmsql);

			if (!$this->CheckIfJustConfirm("临时", trim($this->input['right_TellerID']))) {
				$this->Exc_Sql("set IDENTITY_INSERT tb_TempTeller off ");

				$this->InsertFD_tellerdetail_T();
				$data['action'] = 'button3.PerformClick';
				$this->TeLLID = "-1";
				(int) $number = 0;
				$number = (int) $this->GetStringData("select count(*) as c from fd_tellerdetail where 用途='临时' and systemtellerid=" . $this->input['right_TellerID']);
				if ($number == 1) {
					$data["msg"] = "确认成功!";
					if ($this->input['right_Cash_Last']) {
						$this->DeductCashCommission($this->input['right_shopno'], $this->input['right_amount'], $this->input['right_Region'], $this->input['right_TellerID'], "T", $this->input['right_confirmation_date']);
					}

				} else {
					if ($number > 1) {

						$this->Exc_Sql("delete from fd_tellerdetail where 用途='临时' and systemtellerid=" . $this->input['right_TellerID']);
						$data['msg'] = "确认了" . $number . "次,已经全部清除，请重新确认";
					}
					$this->ProcessChangeTeller($this->input['right_shopno']);
					$this->Exc_Sql("delete from tb_TempTeller where tellerid=" . $this->input['right_TellerID']);
					$data['msg'] = "确认失败，数据回滚！" . $number;
				}
				return $data;
			}
		} else {
			exception("确认TempTeller失败！" . $status);
		}

	}
	private function InsertFD_tellerdetail_T() {

		$confirmsql = "";
		if ($this->input['right_freeze']) //冻结
		{
			$confirmsql = "insert FD_tellerDetail(TellerDetailID,shopno,Deposit,used,bank,[date],TellerID,用途,所属期,SystemTellerID,Region,Oper,memo1,memo2,freezemark) values('" . $this->Get_New_TellerDetailID() . "','" . $this->input['right_shopno'] . "','" . $this->ProccessNull($this->input['right_amount']) . "','0','" . $this->input['right_bank'] . "','" . $this->input['right_confirmation_date'] . "','" . $this->Get_TellerID($this->input['right_shopno']) . "','" . $this->input['textBox3'] . "','','" . $this->input['right_TellerID'] . "','" . $this->input['right_Region'] . "','" . $this->TellerOper . "','" . $this->input['right_remark'] . "','" . $this->input['textBox27'] . "','1')";

		} else {
			$confirmsql = "insert FD_tellerDetail(TellerDetailID,shopno,Deposit,used,bank,[date],TellerID,用途,所属期,SystemTellerID,Region,Oper,memo1,memo2,freezemark) values('" . $this->Get_New_TellerDetailID() . "','" . $this->input['right_shopno'] . "','" . $this->ProccessNull($this->input['right_amount']) . "','0','" . $this->input['right_bank'] . "','" . $this->input['right_confirmation_date'] . "','" . $this->Get_TellerID($this->input['right_shopno']) . "','" . $this->input['textBox3'] . "','','" . $this->input['right_TellerID'] . "','" . $this->input['right_Region'] . "','" . $this->TellerOper . "','" . $this->input['right_remark'] . "','" . $this->input['textBox27'] . "','0')";

		}

		$this->Exc_Sql($confirmsql);

		$this->ProcessChangeTeller(trim($this->input['right_shopno']));

	}
	private function DeductCashCommission($ShopNo, $amount, $Region, $tellerid, $TellerType, $CDate) {
		$Add_Tellar_Sql = "";
		$Gid = "";
		$Pay = 0;
		$Pay = (int) ($amount * 0.02);
		$Gid = date('Y-m-d H:i:s');
		$Add_Tellar_Sql = "insert FD_TellerDetail(GroupID,TellerDetailID,ShopNo,deposit,used,[date],tellerid,用途,region,Oper,bank,所属期,RegPswStatus) values('" . $Gid . "'," . $this->Get_New_TellerDetailID() . ",'" . $ShopNo . "','0','" . $Pay . "','" . $CDate . "'," . $this->Get_TellerID($ShopNo) . ",'现金存款手续','" . $Region . "','" . $this->TellerOper . "','KEDI','" . $TellerType . $tellerid . "','0')";
		$status = $this->Exc_Sql($Add_Tellar_Sql);
		if ($status) {
			$this->Update_FD_Teller($ShopNo); //完成在无账号情况下添加账号的功能

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
	/**
	 * @title 财务Teller查询 中间POS
	 * @type interface
	 * @login 1
	 * @param data 数据结果集合(包括左右两边查询出来的所有数据)
	 * @return data
	 */
	public function button4_Click() {

		Db::startTrans();
		try {
			$data = $this->ProccessAllPos();
			Db::commit();
			return $data;
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	private function ProccessAllPos() {
		$TellerID = "0"; //TellerID
		$Bank = ""; //BANK_NAME
		$Edate = ""; //EFFECTIVE_DATE
		$CheckDate = ""; //确认日期
		$PosDate = ""; //
		$memo = ""; //MEMO
		$SC = ""; //专卖店
		$amount = ""; //Deposit
		$direct = "";
		$confirmer = ""; //ConfirmPerson
		$oper = "";
		$data = json_decode($this->input['data'], true);
		if (empty($data['left'])) {
			exception("左侧列表数据为空");
		}
		$status = true;
		foreach ($data['left'] as $v) {
			$vv = array_values($v);
			if ($this->CheckIfConfirm($vv[1])) {
				$TellerID = $vv[0];
				$Bank = $vv[2];
				$Edate = $vv[4];
				$CheckDate = $vv[8];
				$PosDate = "";
				$memo = $vv[5];
				$amount = $vv[7];
				$direct = "借方";
				$confirmer = $vv[13];
				$oper = $this->TellerOper;
				$SC = $vv[9];
				$status = $this->InsertFD_PosInfo($TellerID, $Bank, $Edate, $CheckDate, $PosDate, $memo, $amount, $direct, $confirmer, $oper, $SC, "系统上传");
				$this->ProccessSystemTellerStatus($TellerID);
			}
		}
		if (empty($data['right'])) {
			exception("右侧列表数据为空");
		}
		foreach ($data['right'] as $v) {
			$vv = array_values($v);
			if ($this->CheckIfConfirm_T($vv[1])) {
				$TellerID = $v[1];
				$Bank = $vv[2];
				$Edate = "";
				$CheckDate = $vv[5];
				$PosDate = $vv[6];
				$memo = "";
				$amount = $vv[4];
				$direct = "贷方";
				$confirmer = $vv[9];
				$oper = $this->TellerOper;
				$SC = trim($vv[12]);
				$status = $this->InsertFD_PosInfo($TellerID, $Bank, $Edate, $CheckDate, $PosDate, $memo, $amount, $direct, $confirmer, $oper, $SC, "手动确认");
				$this->ProccessTempTellerStatus($TellerID);
			}
		}
		if ($status) {
			return suc("已经进入POS台账表！");
		} else {
			exception("进入失败！");
		}
	}
	//pos时将数据写入FD_PosInfo表中
	private function InsertFD_PosInfo($TellerID, $Bank, $Edate, $CheckDate, $PosDate, $memo, $amount, $direct, $confirmer, $oper, $sc, $tellerfrom) {
		$status = true;
		$sql = "";
		if ($tellerfrom == "系统上传") {
			$sql = "insert FD_PosInfo(TellerID,Bank,EffectDate,确认日期,POS日期,摘要,借方,方向,确认人,Oper,CreateDate,LastEditDate,shopno,[from])"
			. " values(" . $TellerID . ",'" . $Bank . "','" . $Edate . "','" . $CheckDate . "','" . $PosDate
			. "','" . $memo . "'," . $this->ProccessAmount($amount) . ",'" . $direct . "','" . $confirmer . "','" . $oper . "','" . date('Y-m-d') . "','" . date('Y-m-d') . "','" . $sc . "','" . $tellerfrom . "')";
		} else {
			$sql = "insert FD_PosInfo(TellerID,Bank,EffectDate,确认日期,POS日期,摘要,贷方,方向,确认人,Oper,CreateDate,LastEditDate,shopno,[from])"
			. " values(" . $TellerID . ",'" . $Bank . "','" . $Edate . "','" . $CheckDate . "','" . $PosDate
			. "','" . $memo . "'," . $this->ProccessAmount($amount) . ",'" . $direct . "','" . $confirmer . "','" . $oper . "','" . date('Y-m-d') . "','" . date('Y-m-d') . "','" . $sc . "','" . $tellerfrom . "')";
		}

		$status1 = $this->Exc_Sql($sql);
		if ($status1 < 1) {
			$status = false;
			exception("Error,Sql:" . $sql);
		}
		return $status;
	}
	//更改tb_SystemTeller表中的用途,确认等字段
	private function ProccessSystemTellerStatus($tellerid) {
		$confirmsql = "update tb_SystemTeller set 确认='1', 用途='POS台账',确认日期='" . date('Y-m-d') . "',confirmperson='" . $this->TellerOper . "' where TellerID='" . $tellerid . "'";
		return $this->Exc_Sql($confirmsql);
	}
	//更改tb_TempTeller表中的用途,relevanceBit等字段
	private function ProccessTempTellerStatus($tellerid) {
		$confirmsql = "update tb_TempTeller set relevanceBit='1', 用途='POS台账',确认日期='" . date('Y-m-d') . "',confirmperson='" . $this->TellerOper . "' where TellerID='" . $tellerid . "'";
		return $this->Exc_Sql($confirmsql);
	}
	//判断tb_tempteller表中的数据是否已经确认
	private function CheckIfConfirm_T($TellerID) {
		$sql = "select 确认 from tb_tempteller where tellerid=" . $TellerID;
		$status = $this->GetStringData($sql);
		if ($status == "True") {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * @title 财务Teller查询 中间核销及调整
	 * @type  interface
	 * @login 1
	 * @param data 勾选的数据集合(右侧)
	 * @return [type] [description]
	 */
	public function button27_Click() {
		Db::startTrans();
		try {
			$data = $this->ProccessAllUnCheckPos();
			Db::commit();
			return $data;
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	private function ProccessAllUnCheckPos() {
		$data1 = json_decode($this->input['data'], true);
		if (empty($data1)) {
			exception("所选数据不能为空");
		}
		$status = true;
		$TellerID = "0";
		$Bank = "";
		$Edate = "";
		$CheckDate = "";
		$PosDate = "";
		$memo = "";
		$amount = "";
		$direct = "";
		$confirmer = "";
		$oper = "";
		$SC = "";
		foreach ($data1 as $v) {
			$vv = array_values($v);
			if ($this->CheckIfConfirm_T($vv[1])) {
				$TellerID = $vv[1];
				$Bank = $vv[2];
				$Edate = "";
				$CheckDate = $vv[5]; //确认日期
				$PosDate = $vv[6]; //POS日期
				$memo = $vv[15]; //备注
				$amount = $vv[4]; //存款
				$direct = "贷方";
				$confirmer = trim($vv[9]); //ConfPerson
				$oper = $this->TellerOper;
				$SC = trim($vv[12]); //专卖店
				$status = $this->InsertFD_UncheckPosInfo($TellerID, $Bank, $Edate, $CheckDate, $PosDate, $memo, $amount, $direct, $confirmer, $oper, $SC, "手动确认");
				$this->ProccessTempTellerStatus($TellerID);
			}
		}
		if ($status) {
			return suc("已经进入核销调整表！");
		} else {exception("进入失败！");}
	}
	private function InsertFD_UncheckPosInfo($TellerID, $Bank, $Edate, $CheckDate, $PosDate, $memo, $amount, $direct, $confirmer, $oper, $sc, $tellerfrom) {
		$status = true;
		$sql = "";
		if ($tellerfrom == "系统上传") {
			$sql = "insert FD_UncheckPosInfo(TellerID,Bank,EffectDate,确认日期,POS日期,摘要,借方,方向,确认人,Oper,CreateDate,LastEditDate,shopno,[from])"
			. " values(" . $TellerID . ",'" . $Bank . "','" . $Edate . "','" . $CheckDate . "','" . $PosDate
			. "','" . $memo . "'," . $this->ProccessAmount($amount) . ",'" . $direct . "','" . $confirmer . "','" . $oper . "','" . date('Y-m-d') . "','" . date('Y-m-d') . "','" . $sc . "','" . $tellerfrom . "')";
		} else {
			$sql = "insert FD_UncheckPosInfo(TellerID,Bank,EffectDate,确认日期,POS日期,摘要,贷方,方向,确认人,Oper,CreateDate,LastEditDate,shopno,[from])"
			. " values(" . $TellerID . ",'" . $Bank . "','" . $Edate . "','" . $CheckDate . "','" . $PosDate
			. "','" . $memo . "'," . $this->ProccessAmount($amount) . ",'" . $direct . "','" . $confirmer . "','" . $oper . "','" . date('Y-m-d') . "','" . date('Y-m-d') . "','" . $sc . "','" . $tellerfrom . "')";
		}

		$stat = $this->Exc_Sql($sql);
		if ($stat < 1) {
			$status = false;
			exception("Error,Sql:" . $sql);
		}
		return $status;
	}
	/**
	 * @title 财务Teller查询 上传pos
	 * @type  interface
	 * @login 1
	 * @param upfile 上传文件信息(from表单单文件上传)
	 * @return data 提示消息集合
	 */
	public function ButtonUploadPos_Click() {
		$data = $this->LoadPos();
		return $data;
	}
	/**
	 * @title 获取权限码
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function jurisdiction() {
		$data = $this->GetTempPsw($this->TellerOper);
		return suc($data);
	}
	/**
	 * @title 财务Teller查询  导入teller
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function UploadTeller() {
		$fileName = $this->uploads('teller_file');
		if (is_array($fileName)) {
			return $fileName;
		}
		$data = $this->get_data($fileName);
		//var_dump($data);die();
		if (count($data) <= 0) {
			return err(9000, '上传的数据为空');
		}
		$this->Drop_Table1("tellerinfo"); //删除旧表
		$sql = "CREATE TABLE tellerinfo (
				[BANK_NAME] VARCHAR(255) NULL DEFAULT NULL,
				[EFFECTIVE_DATE] VARCHAR(255) NULL DEFAULT NULL,
				[DESCRIPTION] VARCHAR(255) NULL DEFAULT 0,
				[DEPOSIT] VARCHAR(255) NULL DEFAULT 0,
				majorkey varchar(250) NULL DEFAULT NULL
			)";
		$this->Exc_Sql($sql); //创建新表

		unset($data['0']);
		foreach ($data as $v) {
			$BANK_NAME = trim($v[0]);
			$EFFECTIVE_DATE = date('Y-m-d', strtotime(trim($v[1])));
			$DESCRIPTION = trim($v[2]);
			$DEPOSIT = trim($this->getstrmoney($v[3])) ?: 0;
			$majorkey = $EFFECTIVE_DATE . $DESCRIPTION . $DEPOSIT;
			$sql = "insert into tellerinfo VALUES('" . $BANK_NAME . "','" . $EFFECTIVE_DATE . "','" . $DESCRIPTION . "','" . $DEPOSIT . "','" . $majorkey . "')";
			$result = $this->Exc_Sql($sql);
			if (!$result) {
				exception('数据写入错误请重新上传!');
			}

		}
		$select_sql = "select * from tellerinfo";
		$re['select'] = $this->query_Sql($select_sql);
		$re['msg'] = "上传成功,共导入" . count($data) . "数据!";
		return suc($re);
	}
	/**
	 * @title 核对数据 更新
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function checkdata() {
		$sql = "select count(*) from tellerinfo where convert(datetime,effective_date)>'" . date('Y-m-d') . "'";
		$result = $this->GetStringData($sql);
		if (!$result) {
			if ($this->Insert_SunnyTeller()) {
				return suc('数据更新成功!');
			} else {
				return err(9000, '该日期数据已更新或者更新失败,请检查!');
			}
		} else {
			exception('日期不正确，联系Eric！');
		}
	}
	//把数据写入tb_systemteller表中
	private function Insert_SunnyTeller() {
		$status = false;
		try
		{
			$sql = "insert into tb_systemteller(Bank_name,effective_date,memo,deposit,确认,ImportPerson,唯一,uploaddate) select bank_name,convert(datetime,effective_date),description,deposit,'0','" . $this->TellerOper . "',majorkey,'" . date('Y-m-d') . "' from tellerinfo where deposit<>0";
			$row = $this->Exc_Sql($sql);
			if ($row > 0) {
				$status = true;
			}
		} catch (Exception $e) {
			Db::rollback();
		} finally {
			return $status;
		}
	}
	//上传POS建立临时表PosTeller并插入数据
	private function LoadPos() {
		$file = request()->file("upfile");
		//var_export($file);die();
		$fileName = $this->uploads("upfile");
		$data = $this->get_data($fileName);
		if (count($data) <= 0) {
			return err(9000, '上传的数据为空');
		}
		$this->Drop_Table1("PosTeller"); //删除旧表
		$sql = "CREATE TABLE PosTeller (
			[ID号] VARCHAR(255) NULL DEFAULT NULL,
			[银行] VARCHAR(255) NULL DEFAULT NULL,
			[存款] VARCHAR(255) NULL DEFAULT NULL,
			[确认日期(导入日期）] VARCHAR(255) NULL DEFAULT NULL,
			[POS日期] VARCHAR(255) NULL DEFAULT NULL,
			[业绩所属期] VARCHAR(255) NULL DEFAULT NULL,
			[用途] VARCHAR(255) NULL DEFAULT NULL,
			[（导入人）] VARCHAR(255) NULL DEFAULT NULL,
			[SC] VARCHAR(255) NULL DEFAULT NULL,
			[Region] VARCHAR(255) NULL DEFAULT NULL,
			[来源POS] VARCHAR(255) NULL DEFAULT NULL,
			[备注] VARCHAR(255) NULL DEFAULT NULL
		)";
		$status = $this->Exc_Sql($sql); //创建新表

		unset($data['0']);
		foreach ($data as $v) {
			$id = $v[0];
			$bank = $v[1];
			$deposit = $v[2];
			$date = $v[3];
			$PosDate = str_replace('/', '-', $v[4]);
			$Date = $v[5];
			$purpose = $v[6];
			$oper = $v[7];
			$sc = $v[8];
			$Region = $v[9];
			$source = $v[10];
			$memo = $v[11];
			$sql = "insert into PosTeller VALUES('" . $id . "','" . $bank . "','" . $deposit . "','" . $date . "','" . $PosDate . "','" . $Date . "','" . $purpose . "','" . $oper . "','" . $sc . "','" . $Region . "','" . $source . "','" . $memo . "')";
			$result = $this->Exc_Sql($sql);
			if (!$result) {
				exception('数据写入错误请重新上传!');
			}
		}
		return suc("上传成功,共导入" . count($data) . "数据!");
	}

	//更改tellerinfo表中deposit字段值为字符串形式
	protected function getstrmoney($str) {
		$i = strrpos($str, '.');
		if ($i !== false) {
			$str = substr($str, 0, $i);
		}
		$str = str_replace(',', '', $str);
		return $str;
	}

}
