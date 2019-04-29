<?php
namespace app\desktop\controller;
use app\desktop\controller\Common;

class TbProduct extends Common {

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->Product = model("Product");
	}
	/**
	 * @title 获取产品信息
	 * @type  interface
	 * @login  1
	 */

	public function get_list($order = "ProductID asc", $limit = "0,100") {
		$input = input("post.");
		$map = [];
		if (isset($input["ProductNO"]) && !empty($input["ProductNO"])) {
			$map["ProductNO"] = $input["ProductNO"];
		}
		if (isset($input["CName"]) && !empty($input["CName"])) {
			$map["CName"] = $input["CName"];
		}
		if (isset($input["CreateTime"]) && !empty($input["CreateTime"])) {
			$from = $input['CreateTime'];
			$start = date('Y-m-d', strtotime("$from -1 day"));
			$date = date('Y-m-d', strtotime("$from +1 day"));
			$map["CreateTime"] = ["between", "$start,$date"];
		}
		$map["Status"] = 1;
		$data = $this->Product->getByWhereAc($map, "ProductID,ProductNO,CName,EName,ProductType,RetailPrice as PV,MemberPrice,RebatePrice,PresentPrice,PV as BV,InPrice,Unit", $limit, $order);
		$count = $this->Product->getCount($map);
		if (!empty($data)) {
			$res["count"] = $count;
			$res["list"] = $data;
			return suc($res);
		}
		return err(9000, "没有数据");
	}
}
