<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

/**
 * @title 产品分类管理
 */
class Category extends Common {
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
	 * @title 分类查询
	 * @type interface
	 * @login 1
	 * @param CName 中文名
	 * @param EName 英文名
	 */
	public function Search() {
		$map = [];
		$map["ParentID"] = 0;
		$data = $this->Category->getByWhere($map);
		$list = [];
		foreach ($data as $v) {
			$list[] = $v;
			$child = $this->Category->getByWhere(["ParentID" => $v["CategoryID"]]);
			foreach ($child As $vv) {
				$list[] = $vv;
				$child0 = $this->Category->getByWhere(["ParentID" => $vv["CategoryID"]]);
				foreach ($child0 as $vvv) {
					$list[] = $vvv;
				}
			}
		}
		if (isset($this->input['CName']) && !empty($this->input['CName'])) {
			//var_export($this->input['CName']);die();
			foreach ($list as $k => $v) {
				if ($this->input['CName'] != $v["CName"]) {
					unset($list[$k]);
				} else {
					$lists[] = $v;
				}

			}
			$list = $lists;
		}
		if (isset($this->input['EName']) && !empty($this->input['EName'])) {
			foreach ($list as $k => $v) {
				if (isset($this->input['CName']) && !empty($this->input['CName'])) {
					if ($this->input['EName'] != $v["EName"]) {
						unset($list[$k]);
					}
				} else {
					if ($this->input['EName'] != $v["EName"]) {
						unset($list[$k]);
					} else {
						$listss[] = $v;
					}
				}

			}
			if ($listss) {
				$list = $listss;
			}

		}
		$datas["list"] = $list;
		$datas["count"] = count($list);
		return suc($datas);
	}
	/**
	 * @title 分类添加
	 * @type interface
	 * @login 1
	 * @param CategoryNo 分类编号
	 * @param ParentID 父级ID
	 * @param CName 中文名
	 * @param EName 英文名
	 * @param Status 状态(0代表禁用,1代表启用)
	 */
	public function add() {
		$rule = [
			"CategoryNo" => "require",
			"ParentID" => "number",
			"CName" => "require",
			"EName" => "require",
			"Status" => "require|between:0,1",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9006, $check);
		}
		if ($this->input["ParentID"]) {
			$pid = $this->Category->getById($this->input["ParentID"]);
			if ($pid["Level"]) {
				$data["Level"] = $pid["Level"] + 1;
			} else {
				$data["Level"] = 0;
			}

		}

		$data["CategoryID"] = $this->Category->newid();
		$data["CategoryNo"] = $this->input["CategoryNo"];
		$data["ParentID"] = $this->input["ParentID"];

		$data["CName"] = $this->input["CName"];
		$data["EName"] = $this->input["EName"];
		$data["Introduce"] = $this->input["Introduce"];
		$data["Memo"] = $this->input["Memo"];
		$data["Status"] = $this->input["Status"];
		$data["CategoryType"] = $this->input["CategoryType"];
		$data["BranchID"] = $this->input["BranchID"];
		$data["CreateTime"] = date("Y-m-d H:i:s");
		$data = setarray($data);
		$keys = array_keys($data);
		$values = array_values($data);

		$status = $this->Category->addItem($data);
		if ($status) {
			return suc($data["CategoryID"]);
		}
		return err(9002, "添加失败");
	}
	/**
	 * @title 分类修改
	 * @type interface
	 * @login 1
	 * @param CategoryID 分类id
	 * @param CategoryNo 分类编号
	 * @param CName 中文名
	 * @param EName 英文名
	 */
	public function update() {
		$rule = [
			"CategoryID" => "require",
			"CategoryNo" => "require",
			"CName" => "require",
			"EName" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9006, $check);
		}
		$old = $this->Category->getById($this->input["CategoryID"]);
		if (empty($old)) {
			return err(9006, "分类不存在");
		}
		$data["CategoryNo"] = $this->input["CategoryNo"];
		$data["ParentID"] = $this->input["ParentID"];
		$data["Level"] = $this->input["Level"];
		$data["CName"] = $this->input["CName"];
		$data["EName"] = $this->input["EName"];
		$data["Introduce"] = $this->input["Introduce"];
		$data["Memo"] = $this->input["Memo"];
		$data["Status"] = $this->input["Status"];
		$data["CategoryType"] = $this->input["CategoryType"];
		$data["BranchID"] = $this->input["BranchID"];
		$data["LastEditTime"] = date("Y-m-d H:i:s");
		$data = setarray($data);
		$status = $this->Category->editById($data, $this->input["CategoryID"]);
		if ($status) {
			return suc("修改成功");
		}
		return err(9002, "修改失败");
	}
}
