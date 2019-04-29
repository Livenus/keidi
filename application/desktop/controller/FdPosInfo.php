<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title POS台账
 * @type menu
 * @login 1
 */
class FdPosInfo extends Common {
	private $PrintIndex;
	private $dataIndex;
	private $Count = 10;
	private $PrintMark = 0;
	private $Name = "提款界面";
	protected $comboBox1 = [
		"借方",
		"贷方",
		"Bank",
		"确认人",
		"摘要"];
	protected $comboBox2 = [
		"无日期",
		"EffectDate",
		"确认日期",
		"POS日期"];
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
		$this->TellerOper = self::$realname;

	}
	public function load() {
		$data['comboBox1'] = $this->comboBox1;
		$data['comboBox2'] = $this->comboBox2;
		return suc($data);
	}
	/**
	 * @title POS台账 搜索
	 * @type interface
	 * @login 1
	 * @param mark 是否勾选标记
	 * @param system 是否勾选系统
	 * @param hand  是否勾选手动
	 * @param from  查询开始日期
	 * @param to  查询结束日期
	 * @param TellerID
	 * @param comboBox2  日期查询条件下拉选择框(0,1,2,3)
	 * @param comboBox1  借方下拉选择框(0,1,2,3,4)
	 * @param textBox1   借方下拉选择框旁边的输入框
	 * @return data  数据集合
	 * @return debt  借方总额
	 * @return credit  贷方总额
	 */
	public function button1_Click() {
		$data['Search'] = $this->SearchResult('fd_posinfo'); //查询
		$this->GetTotal($data['Search']); //计算借方和贷方总额
		$data['input'] = $this->input;
		if ($data['Search']) {
			$data['Search'] = utf_8($data['Search']);
			return suc($data);
		}
		return err(9000, "没有数据");
	}
	/**
	 * @title POS台账 标记或者去标记
	 * @type interface
	 * @login 1
	 * @param mark_status 执行的是去标记还是标记(0,1)
	 * @param data 勾选的数据集合
	 */
	public function button2_Click() {
		$rule = [
			"mark_status" => "require|number",

		];
		$check = $this->validate($this->input['mark_status'], $rule);
		if ($check != true) {
			return err(9000, $check);
		}
		$data = $this->input['data'];
		if (empty($data)) {
			return err(9000, "未选择数据");
		}
		foreach ($data as $v) {
			$tellerid = "";
			$tellerid = $vv['TellerID'];
			$this->Mark($this->input['mark_status'], $tellerid, $v['方向'], 'fd_posinfo');
		}

		if ($this->input['mark_status']) {
			$rep['msg'] = "标记成功！";
		} else {
			$rep['msg'] = "去标记成功！";
		}
		$rep['action'] = 'search';
		return suc($rep);
	}

	/**
	 * @title POS台账 核对选中
	 * @type interface
	 * @login 1
	 * @param data 选择的数据集合(PosID,摘要)
	 * @return [type] [description]
	 */
	public function button6_Click() {
		Db::startTrans();
		try {
			$this->ZiDongPiPei();
			Db::commit();
			return suc("成功");
		} catch (\Exception $exc) {
			Db::rollback();
			return err(9000, $exc->getMessage());
		}

	}
	private function ZiDongPiPei() {
		$data = json_decode($this->input['data'], true);
		if (empty($data)) {
			return err(9000, "未选择数据");
		}
		foreach ($data as $key => $v) {
			//$vv = array_values($v);
			$ID = "0";
			$TargetPosID = "0"; //ID是选中行的贷方的POSID;PosID是匹配后的POS上传的数据的字段POSID值
			$ID = $v['PosID'];
			$TargetPosID = $this->ConfirmPos($ID);
			if ($TargetPosID == "0" || $TargetPosID == "") {
			} else {
				$v['摘要'] = trim($TargetPosID);
				foreach ($data as $key => $value) {
					if ($value['POSID'] == $TargetPosID) {
						$value['摘要'] = trim($ID);
						$this->PiPei($ID, $TargetPosID);
						$this->PiPei($TargetPosID, $ID);
						break;
					}
				}
			}
		}
	}
	private function ConfirmPos($PosID) {
		$sql = "select min(PosID)from  fd_posinfo  where pos日期=(select pos日期 from fd_posinfo where posid=" . $PosID . ") and "
			. "GrossAmount=(select 贷方 from fd_posinfo where posid=" . $PosID . ") and Bank=(select Bank from fd_posinfo where posid=" . $PosID . ") and 方向='POS' and isnull(mark,0)=0";
		$PosID = $this->GetStringData($sql);
		return $PosID;
	}
	private function PiPei($PosID, $RelPosID) {
		$sql = "update fd_posinfo set Mark='2',摘要='" . $RelPosID . "' where posid='" . $PosID . "'";
		$status = $this->Exc_Sql($sql);
		if ($status < 0) {
			exception("Error:sql" . $sql);
		}

	}
	/**
	 * @title POS台账 反确认
	 * @type interface
	 * @login 1
	 * @param direction 方向
	 * @param SC 专卖店或者经销商编号
	 * @param TellerID
	 * @return data
	 */
	public function button4_Click() {
		$rule = [
			"direction" => "require",
			"TellerID" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		if ($this->input['direction'] == "借方") {
			$this->ProccessSystemTellerStatus($this->input['TellerID']);
		} else {
			$this->ProccessTempTellerStatus($this->input['TellerID']);
		}

		return suc("反确认成功！");
	}
	//修改tb_TempTeller表中数据
	private function ProccessTempTellerStatus($tellerid) {
		$confirmsql = "update tb_TempTeller set relevanceBit='0',专卖店='" . $this->input['SC'] . "', 用途='POS台账',确认日期='' where TellerID='" . $tellerid . "'";
		$status = $this->Exc_Sql($confirmsql);
		if ($status < 1) {
			exception("Error," . $confirmsql);
		} else {
			$confirmsql = "delete from fd_posinfo where tellerid=" . $tellerid . " and 方向='贷方'";
			$status = $this->Exc_Sql($confirmsql);
			if ($status < 1) {
				exception("Error," . $confirmsql);
			}

		}
	}
	//修改tb_SystemTeller表中数据
	private function ProccessSystemTellerStatus($tellerid) {
		$confirmsql = "update tb_SystemTeller set 确认='0', 用途='POS台账',专卖店='" . $this->input['SC'] . "',确认日期='' where TellerID='" . $tellerid . "'";
		$status = $this->Exc_Sql($confirmsql);
		if ($status < 1) {
			exception("Error," . $confirmsql);
		} else {
			$confirmsql = "delete from fd_posinfo where tellerid=" . $tellerid . " and 方向='借方'";
			$status = $this->Exc_Sql($confirmsql);
			if ($status < 1) {
				exception("Error," . $confirmsql);
			}

		}
	}
	/**
	 *@title POS台账 上传POS
	 *@type interface
	 *@login 1
	 *@return data
	 */
	public function button_Upload_Click() {
		$this->Drop_Table("Pos");
		$sql = "CREATE TABLE Pos (
			[Tellerid] IDENTITY(1,1) NOT NULL,
			[Bank] VARCHAR(255) NULL DEFAULT NULL,
			[POS日期] VARCHAR(255) NULL DEFAULT NULL,
			[TerminalID] VARCHAR(255) NULL DEFAULT NULL,
			[GrossAmount(总共金额)] VARCHAR(255) NULL DEFAULT NULL,
			[Surcharge(额外费用)] VARCHAR(255) NULL DEFAULT NULL,
			[NetAmount(纯金额)] VARCHAR(255) NULL DEFAULT NULL,
		)";
		$status = $this->Exc_Sql($sql); //创建新表
		$fileName = $this->uploads();
		if (is_array($fileName)) {
			return $fileName;
		}
		$data = $this->get_data($fileName);
		if (count($data) <= 0) {
			return err(9000, '上传的数据为空');
		}
		unset($data['0']);
		foreach ($data as $v) {
			$Bank = $v[0];
			$POS = $v[1];
			$TerminalID = $v[2];
			$GrossAmount = $v[3];
			$Surcharge = $v[4];
			$NetAmount = $v[5];
			$sql = "INSERT INTO Pos([Bank],[POS日期],TerminalID,[GrossAmount(总共金额)],[Surcharge(额外费用)],[NetAmount(纯金额)]) VALUES ('" . $Bank . "','" . $POS . "','" . $TerminalID . "','" . $GrossAmount . "','" . $Surcharge . "','" . $NetAmount . "')";
			$result = $this->Exc_Sql($sql);
			if (!$result) {
				exception('数据写入临时表Pos错误,请重新上传!');
			}
		}
		exception("上传成功,共导入" . count($data) . "数据!");
		if ($this->InsertPosinfo()) {
			return suc("更新成功!");
		}
	}
	//删除旧表
	private function Drop_Table($TB_Name) {
		$sql = "";
		$sql = "drop table " . $TB_Name;
		$this->Exc_Sql($sql);
	}
	//将数据插入到正式表fd_posinfo
	private function InsertPosinfo() {
		$sql = "INSERT INTO fd_posinfo(CreateDate,TellerID,Bank,Pos日期,GrossAmount,Surcharge,NetAmount,TerminalID,方向,[From]) select '" . date(Y - m - d) . "' as CreateDate,Tellerid,Bank,Pos日期,GrossAmount,Surcharge,NetAmount,TerminalID,'POS' as 方向,'" . $this->TellerOper . "'as [From] from pos";
		$result = $this->Exc_Sql($sql);
		if ($result > 0) {
			return true;
		} else {
			exception("插入错误，Sql:" . $sql);
			return false;
		}
	}
}
