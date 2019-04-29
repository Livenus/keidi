<?php

namespace app\common\model;

use app\common\model\Base;

/**
 *借款表
 */
class RefundBorrow extends Base {

	protected $table = 'RefundBorrow';
	protected $pk = 'id';
	public function add($data) {
		return parent::addItem($data);
	}
	public function getsurplus_money($where = [], $field = '*') {
		return $this->where($where)->field($field)->find();
	}
	public function get_summarize($field, $where = []) {
		$data = $this->where($where)->field($field)->group('sc')->order('sc asc')->select();
		$data = $this->obj2data($data);
		$data = trimarray($data);
		return $data;
	}
}