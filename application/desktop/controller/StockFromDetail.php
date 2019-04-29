<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class StockFromDetail extends Common {
//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
		$this->StockFromDetail = model("StockFromDetail");
		$this->StockFrom = model("StockFrom");
	}
	public function load() {
		$data['Table'] = $this->Table;
		$data['field'] = $this->field;
		$data['bank'] = $this->bank;
		return suc($data);
	}
	public function Search() {
		$map = [];
		if (isset($this->input['FromPlace'])) {
			$map["FromPlace"] = ["like", "%{$this->input['FromPlace']}%"];
		}
		if (isset($this->input['Status']) && is_numeric($this->input['Status'])) {
			$map["Status"] = $this->input['Status'];
		}
		$list = $this->StockFromDetail->getByWhere($map);
		foreach ($list as $k => $v) {
			$from = $this->StockFrom->getById($v["FromID"]);
			$list[$k]["FromCountry"] = $from["FromCountry"];
		}
		$data["list"] = $list;
		$data["count"] = count($list);
		return suc($data);
	}

	public function add() {
		$rule = [
			"FromID" => "require",
			"FromPlace" => "require",
			"Status" => "require|number|between:0,1",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$data["FromID"] = $this->input["FromID"];
		$data["FromPlace"] = $this->input["FromPlace"];
		$data["Status"] = $this->input["Status"];
		$data["Memo"] = $this->input["Memo"];
		$data["FromDetailID"] = $this->StockFromDetail->newid();
		$data["CreateDate"] = date("Y-m-d H:i:s");
		$data["Oper"] = self::$realname;
		$status = $this->StockFromDetail->addItem($data);
		return $status;
	}
	public function update() {
		$rule = [
			"FromDetailID" => "require|number",
			"Status" => "require|number|between:0,1",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$data["Status"] = $this->input["Status"];
		$data["FromPlace"] = $this->input["FromPlace"];
		$data["Memo"] = $this->input["Memo"];
		$data["LastEditDate"] = date("Y-m-d H:i:s");
		$data = setarray($data);
		$status = $this->StockFromDetail->editById($data, $this->input["FromDetailID"]);
		return $status;
	}

}
