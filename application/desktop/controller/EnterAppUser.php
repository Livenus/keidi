<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class EnterAppUser extends Common {

	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->user_model = model("EnterappUser");
		$this->EnterappUserInfo = model("EnterappUserInfo");
	}
	/**
	 * @title 修改密码
	 * @type interface
	 * @login 1
	 * @param old_password 旧密码
	 * @param new_password 新密码
	 */
	public function setPassword() {
		$input = input("post.");
		$user = self::$user;
		$check = $this->validate($input, "EnterAppUser");
		if ($check !== true) {
			return err(9000, $check);
		}
		$count = $this->user_model->getCount(["username" => trim($user['username']), "userpassword" => trim($input["old_password"])]);
		if ($count) {
			$this->user_model->editById(["userpassword" => $input["new_password"]], self::$userID);
			return suc("修改成功");
		}
		return err(9000, "密码错误");
	}
	/**
	 * @title 修改个人信息
	 * @type interface
	 * @login 1
	 * @param Phone 手机号(电话)
	 * @param Birthday 出生日期
	 * @param Email 邮箱
	 * @param Region 国家地区
	 */
	public function setUserinfo() {
		$allow = ["username", "realname"];
		$input = input("post.");
		//var_export($input);die();
		$rule = [
		];
		$check = $this->validate($input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$user = json_decode($input["user"], true);
		$user_info = json_decode($input["userInfo"], true);
		if ($user) {
			$status = $this->user_model->editById($user, self::$userID);

		}
		if ($user_info) {
			$status = $this->EnterappUserInfo->editById($user_info, self::$userID);
		}
		if ($status["stat"] == 1) {

			return suc("修改成功");
		}
		return err(9000, "错误" . $status["errmsg"]);
	}
	public function findPassword() {
		$user = self::$user;
		if ($user["Question"]) {
			return suc($user["Question"]);
		} else {
			return err(9000, "没有设置问题");
		}
	}
	public function findPasswordAnswer() {
		$input = input("post.");
		$user = self::$user;
		$rule = [
			"Answer" => "require",
			"new_password" => "require",
		];
		$check = $this->validate($input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$count = $this->user_model->getCount(["username" => trim($user['username']), "Answer" => trim($input["Answer"])]);
		if ($count) {
			$this->user_model->editById(["userpassword" => $input["new_password"]], self::$userID);
			return suc("修改成功");
		}
		return err(9000, "答案错误");
	}
	public function admin_info() {

		return suc(self::$user);

	}
	/**
	 * @title 获取当前登录管理员详细信息
	 * @type interface
	 * @login 1
	 * @return data
	 */
	public function admin_info_detail() {
		$data = $this->EnterappUserInfo->getById(self::$userID);

		return suc($data);

	}
}
