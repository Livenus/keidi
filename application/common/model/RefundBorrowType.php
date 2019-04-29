<?php

namespace app\common\model;

use app\common\model\Base;

/**
 *借还款类型表
 */
class RefundBorrowType extends Base {

	protected $table = 'RefundBorrowType';
	protected $pk = 'id';
	//获取还款类型
	public function refund_type() {
		$where = ['key' => 2];
		$data = $this->getlistAC($where);
		return $data;
	}
	//获取借款类型
	public function borrow_type() {
		$where = ['key' => 1];
		$data = $this->getlistAC($where);
		return $data;
	}

	//将类型表中id与类型名以键值对的形式返回
	public function idname($where = []) {
		$data = $this->getlistAC($where, 'id,type_name,priority', ['priority' => 'asc']);
		$data = trimarray($data);
		foreach ($data as $k => $v) {
			$newdata[$v['id']] = $v['type_name'];
		}
		return $newdata;
	}
}