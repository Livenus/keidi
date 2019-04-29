<?php

namespace app\common\model;

use app\common\model\Base;
use think\Model;
use think\Validate;

class EnterappAuth extends Base {

	protected $table = 'EnterApp_Auth';
	protected $pk = 'auth_id';

	public function add($data) {
		$res = $this->_validate($data);
		if ($res['stat'] == '0') {
			return $res;
		}
		$auth_id = $this->max('auth_id', 'add');
		$data['auth_id'] = $auth_id + 1;
		$res = parent::addItem($data, $this);
		return $res;
	}

	public function edit($data) {
		$res = $this->_validate($data, 'edit');
		if ($res['stat'] == '0') {
			return $res;
		}
		$res = parent::editById($data, $data[$this->pk]);
		return $res;
	}
	private function _validate(&$data, $act = 'add') {
		$rule = [
			'auth_name' => 'require',
			'auth_value' => 'require',
		];

		$validate = new Validate($rule);
		$result = $validate->check($data);

		if (!$result) {
			return err(2000, $validate->getError());
		}
		if ($act == 'edit') {
			$where = array('auth_value' => $data['auth_value'], 'auth_id' => array('neq', $data['auth_id']));
		} else {
			$where = array('auth_value' => $data['auth_value']);
		}
		$count = $this->where($where)->count();
		if ($count > 0) {
			return err(2000, '权限重复');
		}
		return suc();
	}

	public function get_menus_id($where = array()) {
		$data = $this->where($where)->field('menu_id')->distinct(true)->select();
		$data = $this->obj2data($data);
		return array_column($data, 'menu_id');
	}

	public function all_auths_val_id($groupid = '') {
		$groupid = trim($groupid);
		$data = $this->where(['auth_id' => ['in', $groupid]])->select();
		$auths = $this->obj2data($data);
		$auths = array_column($auths, 'auth_id', 'auth_value');
		return $auths;
	}

}
