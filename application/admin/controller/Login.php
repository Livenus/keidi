<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use app\common\model\EnterappUser;
use app\common\model\EnterappUserlogin;
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
			return err(1000, $validate->getError());
		}

		// $rec = parent::check_verify($data['code']);
		// if (!$rec) {
		// 	return err(1000, '验证码错误');
		// }

		$user = EnterappUser::where('username', $data['username'])->find();
		if (empty($user)) {
			return err(1000, '账号错误,请核对后再输');
		}
		$err_flag = $data['username'] . 'err_times';
		$err_times = cache($err_flag);
		if ($err_times > 5) {
			return err(1000, '密码输入错误次数过多，请稍后再试。。。');
		}
		if ($user->userpassword != $data['password']) {
			cache($err_flag, $err_times + 1, 3600);
			return err(1000, '用户名或密码输入错误');
		} else {
			cache($err_flag, NULL);
			$data = $this->_createlogin($user);
			return suc($data);
		}
	}

	public function login() {
		return view('./login');
	}

	private function _createlogin($user) {
		$user_token = md5($user->username . $user->userpassword . time());
		$login_info = array('UserID' => $user->UserID, 'usertoken' => $user_token);
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
			return view('./login');
		} catch (Exception $e) {
			Db::rollback();
			return err(9001, '退出失败请重试!' . $e->getMessage());
		}
	}

	public function logout() {
		$input = input('param.');
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
