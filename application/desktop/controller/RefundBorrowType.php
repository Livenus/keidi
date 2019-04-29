<?php
namespace app\desktop\controller;

use app\desktop\controller\Common;

/**
 * @title 借还款类型表
 * @type menu
 * @login 1
 */
class RefundBorrowType extends Common {
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
		$this->Oper = self::$realname;
		$this->RefundBorrowType = model('RefundBorrowType');
	}

	/**
	 * @title欠款类型列表接口
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function listAc() {
		$data = model('RefundBorrowType')->borrow_type();
		return suc($data);

	}

	/**
	 * @title还款方式列表接口
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function refund_list() {
		$data = model('RefundBorrowType')->refund_type();
		return suc($data);
	}
}