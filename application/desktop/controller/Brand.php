<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class Brand extends Common {
//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
		$this->Product = model("Product");
		$this->Brand = model("Brand");
		$this->Category = model("Category");
	}
	public function load() {
		$data['Table'] = $this->Table;
		$data['field'] = $this->field;
		$data['bank'] = $this->bank;
		return suc($data);
	}
	/**
	 * @title 产品管理--商标查询
	 * @type interface
	 * @login 1
	 * @param order 排序规则
	 * @param limit
	 * @param CName 中文名
	 * @param EName 英文名
	 */
	public function Search() {
		$order = ["BrandID desc", "BrandID asc"];
		if (!in_array($this->input["order"], $order)) {
			return err(9005, "排序规则错误");
		}
		$rule = [
			"order" => "require",
			"limit" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9003, $check);
		}
		$map = [];
		if (isset($this->input['CName']) && !empty($this->input['CName'])) {
			$map["CName"] = ["like", "%{$this->input['CName']}%"];
		}
		if (isset($this->input['EName']) && !empty($this->input['EName'])) {
			$map["EName"] = ["like", "%{$this->input['EName']}%"];
		}
		$list = $this->Brand->getByWhere($map, "*", $this->input['limit'], $this->input['order']);
		$data["count"] = count($list);
		$data["list"] = $list;
		return suc($data);
	}
	/**
	 * @title 产品管理--商标添加
	 */
	public function add() {
		$rule = [
			"BrandNO" => "require",
			"CName" => "require",
			"EName" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9006, $check);
		}

		$data["BrandID"] = $this->Brand->newid();
		$data["BrandNO"] = $this->input["BrandNO"];
		$data["CName"] = $this->input["CName"];
		$data["EName"] = $this->input["EName"];
		$data["BrandType"] = $this->input["BrandType"];
		$data["Introduce"] = $this->input["Introduce"];
		$data["Pic"] = $this->input["Pic"];
		$data["Memo"] = $this->input["Memo"];
		$data["CreateTime"] = date("Y-m-d H:i:s");
		$data = setarray($data);
		$keys = array_keys($data);
		$values = array_values($data);
		$sql = "insert into tb_Brand(" . implode(",", $keys) . ") values('" . implode("','", $values) . "')";
		$status = $this->Exc_Sql($sql);
		if ($status) {
			return suc($data["BrandID"]);
		}
		return err(9002, "添加失败");
	}
	/**
	 * @title 产品管理--商标修改
	 *
	 */
	public function update() {
		$rule = [
			"BrandID" => "require",
			"BrandNO" => "require",
			"CName" => "require",
			"EName" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9006, $check);
		}
		if ($this->Brand->getCount(["BrandID" => $this->input["BrandID"]]) == 0) {
			return err(9006, "商标不存在");
		}
		$data["BrandNO"] = $this->input["BrandNO"];
		$data["CName"] = $this->input["CName"];
		$data["EName"] = $this->input["EName"];
		$data["BrandType"] = $this->input["BrandType"];
		$data["Introduce"] = $this->input["Introduce"];
		$data["Pic"] = $this->input["Pic"];
		$data["Memo"] = $this->input["Memo"];
		$data["LastEditTime"] = date("Y-m-d H:i:s");
		$data = setarray($data);
		$sql = "update tb_Brand ";
		$str = "";
		$i = 0;
		foreach ($data as $k => $v) {
			if ($i == 0) {
				$str .= "set " . $k . "='" . $v . "',";
			} else {
				$str .= " " . $k . "='" . $v . "',";
			}

			$i++;
		}
		$str = substr($str, 0, strlen($str) - 1);
		$sql = $sql . $str . "where BrandID=" . $this->input["BrandID"];
		$status = $this->Exc_Sql($sql);
		if ($status) {
			return suc("修改成功");
		}
		return err(9002, "修改失败");
	}
}
