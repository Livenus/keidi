<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;

class StockOutDetail extends Base {

	protected $table = 'StockOutDetail';
	protected $pk = 'StockOutDetailID';
	public function add($data) {
		$data["StockOutDetailID"] = $this->GetNewStockOutDetailID();
		$stat = Db::table($this->table)->insert($data);
		if ($stat) {
			return suc($data);
		}
		return err(3000, "插入数据错误");

	}
	public function GetNewStockOutDetailID() {

		$sql = "select isnull(max(stockoutdetailid),0)+1 as newid from stockoutdetail";
		$data = $this->query($sql);
		return $data[0]["newid"];
	}
}
