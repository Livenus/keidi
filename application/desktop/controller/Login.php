<?php

namespace app\desktop\controller;

use app\common\model\EnterappUserlogin;
use app\desktop\controller\Common;
use think\Db;
use think\Validate;

class Login extends Common {

	public function _initialize() {
		parent::_initialize();
	}

	public function login_ajax() {
		$data = input('post.');
		$rule = [
			'username' => 'require|max:25',
			'password' => 'require|min:3',
		];

		$validate = new Validate($rule);
		$result = $validate->check($data);

		if (!$result) {
			return err("900", "login_password", $validate->getError());
		}
		$user_model = model("EnterappUser");
		$sql = "([status] is null or [status]='Y' )and [username]='" . $data["username"] . "'";
		$user = $user_model->getByWhereOne($sql);
		if (empty($user)) {
			return err('9000', "login_password", '账号错误,请核对后再输');
		}
		$err_flag = $data['username'] . 'err_times';
		$err_times = cache($err_flag);
		if ($err_times > 5) {
			return err("9001", "login_password", '密码输入错误次数过多，请稍后再试。。。');
		}
		if ($user["userpassword"] != $data['password']) {
			cache($err_flag, $err_times + 1, 3600);
			return err('9001', "login_password", '用户名或密码输入错误');
		} else {
			cache($err_flag, NULL);
			$data = $this->_createlogin($user);
			return suc($data);
		}
	}
	private function _createlogin($user) {
		$user_token = md5($user['username'] . $user['userpassword'] . time());
		$login_info = array('UserID' => $user['UserID'], 'usertoken' => $user_token);
		$login_mod = new EnterappUserlogin();
		$login_mod->update_login($login_info);
		return ['usertoken' => $user_token];
	}

	private function _cleanlogin($where) {
		Db::startTrans();
		try {
			$data['logintime'] = '111111';
			model('EnterappUserlogin')->editByMap($data, $where);
			Db::commit();
			return suc('退出成功!');
		} catch (Exception $e) {
			Db::rollback();
			return err(9001, '退出失败请重试!' . $e->getMessage());
		}

	}

	public function logout() {
		$input = input('post.');
		$usertoken = $input['usertoken'];
		$validate = new Validate(['usertoken' => 'require']);
		$result = $validate->check($input);
		if ($result !== true) {
			return err(9001, '退出失败请重试!');
		}
		$where['usertoken'] = $usertoken;
		$data = model('EnterappUserlogin')->getByWhereOne($where, 'UserID');
		if (!$data['UserID']) {
			return err('9000', '账号信息不存在,请核对!');
		}
		return $this->_cleanlogin($where);
	}

}
