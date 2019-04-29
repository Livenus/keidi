<?php

namespace app\common\model;

use app\common\model\Base;
use think\Db;

/**
 *借还款汇总表
 */
class Summarize extends Base {
	protected $table = 'Summarize';
	//删除表数据
	public function del_data() {
		$stat = false;
		Db::startTrans();
		try {
			$sql = "delete from Summarize";
			$data = Db::execute($sql);
			Db::commit();
			$stat = true;
		} catch (Exception $e) {
			Db::rollback();
		}
		return $stat;
	}

}