<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class Product extends Common {
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
	public function Search() {
		$order = ["ProductID desc", "ProductID asc"];
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
		if (isset($this->input['ProductNO']) && !empty($this->input['ProductNO'])) {
			$map["ProductNO"] = ["like", "%{$this->input['ProductNO']}%"];
		}
		if (isset($this->input['CName']) && !empty($this->input['CName'])) {
			$map["CName"] = ["like", "%{$this->input['CName']}%"];
		}
		if (isset($this->input['EName']) && !empty($this->input['EName'])) {
			$map["EName"] = ["like", "%{$this->input['EName']}%"];
		}
		if ($this->input['RetailPrice_low'] >= 0 && ($this->input['RetailPrice_high'] > $this->input['RetailPrice_low'])) {
			$map["MemberPrice"] = ["between", "{$this->input['RetailPrice_low']},{$this->input['RetailPrice_high']}"];
		}
		if (isset($this->input['Status'])) {
			$map["Status"] = $this->input['Status'];
		}
		//var_export($map);die();
		$data["count"] = $this->Product->getCount($map);
		$data["list"] = $this->Product->getByWhere($map, "*", $this->input['limit'], $this->input['order']);
		return suc($data);
	}

	public function add() {
		//return $this->uploadimg();
		$rule = [
			"ProductNO" => "require",
			"CategoryID" => "require",
			"BrandID" => "require",
			"CName" => "require|length:5,65",
			"EName" => "require|length:5,65",
			"RetailPrice" => "require|length:1,65",
			"MemberPrice" => "require|length:1,65",
			"RebatePrice" => "require|length:1,65",
			"PresentPrice" => "require|length:1,65",
			"PV" => "require|length:1,650",
			"Pic" => "require|length:50,650000",
			"SmallPic" => "require|length:50,6500000",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9006, $check);
		}

		if ($this->Brand->getCount(["BrandID" => $this->input["BrandID"]]) == 0) {
			return err(9006, "brand不存在");
		}
		if ($this->Category->getCount(["CategoryID" => $this->input["CategoryID"]]) == 0) {
			return err(9006, "Category不存在");
		}
		$data["ProductID"] = $this->Product->newid();
		$data["ProductNO"] = $this->input["ProductNO"];
		$data["CategoryID"] = $this->input["CategoryID"];
		$data["BrandID"] = $this->input["BrandID"];
		$data["SerialID"] = $this->input["SerialID"];
		$data["CName"] = $this->input["CName"];
		$data["EName"] = $this->input["EName"];
		$data["ProductType"] = $this->input["ProductType"];
		$data["RetailPrice"] = $this->input["RetailPrice"];
		$data["MemberPrice"] = $this->input["MemberPrice"];
		$data["RebatePrice"] = $this->input["RebatePrice"];
		$data["PresentPrice"] = $this->input["PresentPrice"];
		$data["PV"] = $this->input["PV"];
		$data["InPrice"] = $this->input["InPrice"];
		$data["Unit"] = $this->input["Unit"];
		$data["ProduceDate"] = $this->input["ProduceDate"];
		$data["DeadLine"] = $this->input["DeadLine"];
		$data["Entry"] = $this->input["Entry"];
		$data["Introduce"] = $this->input["Introduce"];
		$data["Pic"] = ($this->input["Pic"]);
		$data["Memo"] = $this->input["Memo"];
		$data["Status"] = $this->input["Status"];
		$data["SmallPic"] = $this->input["SmallPic"];
		$data["MarketPrice"] = $this->input["MarketPrice"];
		$data["ShopPrice"] = $this->input["ShopPrice"];
		$data["Point"] = $this->input["Point"];
		$data["IsDS"] = $this->input["IsDS"];
		$data["IsPresent"] = $this->input["IsPresent"];
		$data["IsRecommend"] = $this->input["IsRecommend"];
		$data["IsNew"] = $this->input["IsNew"];
		$data["OnSaleDate"] = $this->input["OnSaleDate"];
		$data["Parameter"] = $this->input["Parameter"];
		$data["IsRebate"] = $this->input["IsRebate"];
		$data["IsElite"] = $this->input["IsElite"];
		$data["Spec"] = $this->input["Spec"];
		$data["BranchID"] = $this->input["BranchID"];
		$data["CreateTime"] = date("Y-m-d H:i:s");
		$data = setarray($data);
		$keys = array_keys($data);
		$values = array_values($data);
		$sql = "insert into tb_product(" . implode(",", $keys) . ") values('" . implode("','", $values) . "')";
		$real = explode(",", $sql);
		foreach ($real as $k => $v) {
			if (strlen($v) > 100) {
				$real[$k] = str_ireplace("'", "", $v);
			}
		}
		$sql = implode(",", $real);
		$status = $this->Exc_Sql($sql);
		if ($status) {
			return suc($data["ProductID"]);
		}
		return err(9002, "添加失败");
	}
	public function update() {
		$rule = [
			"ProductID" => "require",
			"ProductNO" => "require",
			"CategoryID" => "require",
			"BrandID" => "require",
			"CName" => "require|length:5,65",
			"EName" => "require|length:5,65",
			"RetailPrice" => "require|length:1,65",
			"MemberPrice" => "require|length:1,65",
			"RebatePrice" => "require|length:1,65",
			"PresentPrice" => "require|length:1,65",
			"PV" => "require|length:1,650",
			//"Pic" => "require|length:50,65000",
			//"SmallPic" => "require|length:50,65000",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9006, $check);
		}
		if ($this->Product->getCount(["ProductID" => $this->input["ProductID"]]) == 0) {
			return err(9006, "产品不存在");
		}
		//var_dump($this->input["Pic"]);
		$data["ProductNO"] = $this->input["ProductNO"];
		$data["CategoryID"] = $this->input["CategoryID"];
		$data["BrandID"] = $this->input["BrandID"];
		$data["SerialID"] = $this->input["SerialID"];
		$data["CName"] = $this->input["CName"];
		$data["EName"] = $this->input["EName"];
		$data["ProductType"] = $this->input["ProductType"];
		$data["RetailPrice"] = $this->input["RetailPrice"];
		$data["MemberPrice"] = $this->input["MemberPrice"];
		$data["RebatePrice"] = $this->input["RebatePrice"];
		$data["PresentPrice"] = $this->input["PresentPrice"];
		$data["PV"] = $this->input["PV"];
		$data["InPrice"] = $this->input["InPrice"];
		$data["Unit"] = $this->input["Unit"];
		$data["ProduceDate"] = $this->input["ProduceDate"];
		$data["DeadLine"] = $this->input["DeadLine"];
		$data["Entry"] = $this->input["Entry"];
		$data["Introduce"] = $this->input["Introduce"];
		//$data["Pic"] = $this->input["Pic"];
		$data["Memo"] = $this->input["Memo"];
		$data["Status"] = $this->input["Status"];
		//$data["SmallPic"] = $this->input["SmallPic"];
		$data["MarketPrice"] = $this->input["MarketPrice"];
		$data["ShopPrice"] = $this->input["ShopPrice"];
		$data["Point"] = $this->input["Point"];
		$data["IsDS"] = $this->input["IsDS"];
		$data["IsPresent"] = $this->input["IsPresent"];
		$data["IsRecommend"] = $this->input["IsRecommend"];
		$data["IsNew"] = $this->input["IsNew"];
		$data["OnSaleDate"] = $this->input["OnSaleDate"];
		$data["Parameter"] = $this->input["Parameter"];
		$data["IsRebate"] = $this->input["IsRebate"];
		$data["IsElite"] = $this->input["IsElite"];
		$data["Spec"] = $this->input["Spec"];
		$data["BranchID"] = $this->input["BranchID"];
		$data["LastEditTime"] = date("Y-m-d H:i:s");
		$data = setarray($data);
		unset($data["ProductID"]);
		$sql = "update tb_product ";
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
		$sql = $sql . $str . "  where ProductID=" . $this->input["ProductID"];
		$status = $this->Exc_Sql($sql);
		if ($status) {
			return suc("修改成功");
		}
		return err(9002, "修改失败");
	}
}
