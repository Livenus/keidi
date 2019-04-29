<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

/**
 * @title 核销以及调整台账
 * @type menu
 * @login 1
 */
class FdUncheckPos extends Common {
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

	}
	public function load() {
		$data['comboBox1'] = $this->comboBox1;
		$data['comboBox2'] = $this->comboBox2;
		return suc($data);
	}
	/**
	 * @title 核销以及调整台账 搜索
	 * @type interface
	 * @login 1
	 * @param TellerID
	 * @param comboBox2 日期查询条件下拉选择框(0,1,2,3)
	 * @param system 是否勾选系统
	 * @param hand  是否勾选手动
	 * @param mark 是否勾选标记
	 * @param from  查询开始日期
	 * @param to  查询结束日期
	 * @param comboBox1  借方下拉选择框(0,1,2,3,4)
	 * @param textBox1   借方下拉选择框旁边的输入框
	 * @return data  数据集合
	 * @return debt  借方总额
	 * @return credit  贷方总额
	 */
	public function button1_Click() {
		$data['Search'] = $this->SearchResult('fd_Uncheckposinfo');
		$this->GetTotal($data['Search']);
		$data['input'] = $this->input;
		if ($data['Search']) {
			$data['Search'] = utf_8($data['Search']);
			return suc($data);
		}
		return err(9000, "没有数据");
	}

	/**
	 * @title 核销以及调整台账 标记或者去标记
	 * @type interface
	 * @login 1
	 * @param mark_status 执行的是去标记还是标记(0,1)
	 * @param data 勾选的数据集合
	 */
	public function button2_Click() {
		$rule = [
			"mark_status" => "require|number",

		];
		//var_export($this->input);die();
		$check = $this->validate($this->input['mark_status'], $rule);
		if ($check != true) {
			return err(9000, $check);
		}
		$data = json_decode($this->input['data'], true);
		if (empty($data)) {
			return err(9000, "未选择数据");
		}
		foreach ($data as $v) {
			$tellerid = $v['TellerID'];
			$this->Mark($this->input['mark_status'], $tellerid, $v['方向'], 'fd_Uncheckposinfo');
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
	 * @title 核销以及调整台账 反确认
	 * @type interface
	 * @login 1
	 * @param direction 方向
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
	//更改表tb_TempTeller数据
	private function ProccessTempTellerStatus($tellerid) {
		$confirmsql = "update tb_TempTeller set relevanceBit='0', 用途='POS台账',确认日期='' where TellerID='" . $tellerid . "'";
		$status = $this->Exc_Sql($confirmsql);
		if ($status < 1) {
			exception("Error," . $confirmsql);
		} else {
			$confirmsql = "delete from fd_Uncheckposinfo where tellerid=" . $tellerid . " and 方向='贷方'";
			$status = $this->Exc_Sql($confirmsql);
			if ($status < 1) {
				exception("Error," . $confirmsql);
			}

		}
	}
	//更改表tb_SystemTeller数据
	private function ProccessSystemTellerStatus($tellerid) {
		$confirmsql = "update tb_SystemTeller set 确认='0', 用途='POS台账',确认日期='' where TellerID='" . $tellerid . "'";
		$status = $this->Exc_Sql($confirmsql);
		if ($status < 1) {
			exception("Error," . $confirmsql);
		} else {
			$confirmsql = "delete from fd_Uncheckposinfo where tellerid=" . $tellerid . " and 方向='借方'";
			$status = $this->Exc_Sql($confirmsql);
			if ($status < 1) {
				exception("Error," . $confirmsql);
			}

		}
	}
}
