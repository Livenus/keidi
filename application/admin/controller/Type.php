<?php

namespace app\admin\controller;
use app\admin\controller\Common;
use think\Db;

/**
 *@title 欠款还款类型
 *@type menu
 *@login 1
 */
class Type extends Common {
	public function _initialize() {
		parent::_initialize();
	}
	//列表
	public function type_list() {
		$sql = "select * from RefundBorrowType";
		$data = Db::query($sql);
		return view('type/type_list', ['data' => $data]);
	}
	//编辑添加页面
	public function type_create() {
		$input = input("param.");

		if (array_key_exists('id', $input)) {
			$item['id'] = $input['id'];
			$item['type_name'] = $input['type_name'];
			$item['key'] = $input['key'];
			$item['priority'] = $input['priority'];
		}

		return view('type/type_create', ['item' => $item]);
	}
	//添加
	public function add() {

		if (request()->ispost()) {
			$input = input("post.");
			$rule = [
				"type_name" => "require",
				"key" => "require",
				"priority" => "require",
			];
			$msg = [
				'type_name' => '欠款类型名称必须填写!',
				'key' => '所属分组必须填写!',
				'priority' => '请选择类型的优先级!',
			];
			$check = $this->validate($input, $rule, $msg);
			if ($check !== true) {
				return err(3000, $check);
			}
			$data['type_name'] = $input['type_name'];
			$data['priority'] = $input['priority'];
			if ($input['key'] == '欠款类型') {
				$data['key'] = 1;
			} else if ($input['key'] == '还款类型') {
				$data['key'] = 2;
			} else {
				$data['key'] = $input['key'];
			}
			$re = model('RefundBorrowType')->addItem($data);
			if ($re['stat'] != 1) {
				return err(3000, "添加失败" . $re['errmsg']);
			}
			return suc('添加成功!');
		}

	}
	public function edit() {
		if (request()->ispost()) {
			$input = input("post.");
			//var_export($input);die();
			$rule = [
				"id" => "require",
				"type_name" => "require",
				"key" => "require",
				"priority" => "require",
			];
			$msg = [
				'id' => '主键id必须填写!',
				'type_name' => '欠款类型必须填写!',
				'key' => '分组类型名称必须填写!',
				'priority' => '请选择类型的优先级!',
			];
			$check = $this->validate($input, $rule, $msg);
			if ($check !== true) {
				return err(3000, $check);
			}
			if ($input['key'] == '欠款类型') {
				$data['key'] = 1;
			} else if ($input['key'] == '还款类型') {
				$data['key'] = 2;
			} else {
				$data['key'] = $input['key'];
			}
			$data['type_name'] = $input['type_name'];
			$data['priority'] = $input['priority'];
			$id = $input['id'];

			$re = model('RefundBorrowType')->editById($data, $id);

			if ($re['stat'] != 1) {
				return err(3000, "编辑失败" . $re['errmsg']);
			}
			return suc('编辑成功!');
		}

	}
	public function type_delete() {
		$input = input("param.");
		$rule = [
			"id" => "require",
		];
		$msg = [
			'id' => '请选择要删除的数据!',
		];
		$check = $this->validate($input, $rule, $msg);
		if ($check !== true) {
			return err(3000, $check);
		}
		Db::startTrans();
		try {
			$re = model('RefundBorrowType')->del(['id' => $input['id']]);
			Db::commit();
			return $this->type_list();
		} catch (Exception $e) {
			Db::rollback();
			return err(9000, '删除失败!失败原因:' . $e->getMessage());
		}

	}
}
