<?php

namespace app\common\model;

use app\common\model\Base;

/**
 *借还款记录表
 */
class RefundBorrowLog extends Base {
	protected $table = 'RefundBorrowLog';
	protected $pk = 'id';

	public function add($data) {
		return parent::addItem($data);
	}
	public function getgrouplist($field = '*', $where = [], $group = '', $order = '') {
		$data = $this->where($where)->field($field)->group($group)->order($order)->select();
		$data = $this->obj2data($data);
		$data = trimarray($data);
		return $data;
	}

}