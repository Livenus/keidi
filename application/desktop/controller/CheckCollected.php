<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class CheckCollected extends Common {

	public $From = "";
	public $To = "";
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
	public function button1_Click_1() {
		$sql = "select * from DPBV_CHECK WHERE COLLECTMARK='2' AND comment>='" . $this->input['From'] . "' and comment<='" . $this->input['To'] . "'";
		$num = $this->get_num($sql);
		$sql = $sql . $this->limit('DpbvID');
		$data = $this->query_Sql($sql);
		$rep['num'] = $num;
		$rep['list'] = $data;
		return suc($rep);

	}
}
