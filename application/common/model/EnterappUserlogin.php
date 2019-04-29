<?php

namespace app\common\model;

use app\common\model\Base;

class EnterappUserlogin extends Base {

	protected $table = 'Enterapp_UserLogin';

	public function update_login($data) {
		$info = $this->where('UserID', $data['UserID'])->find();
		if (empty($info)) {
			//新增
			$this->UserID = $data['UserID'];
			$this->usertoken = $data['usertoken'];
			$this->logintime = time();
			$this->save();
		} else {
			//更新
			$data['logintime'] = time();
			$this->where('UserID', $data['UserID'])->update($data);
		}
	}

	public function addtest() {
		$data = array('UserID' => '6', 'usertoken' => '2fwef1s1f31esfwf', 'logintime' => '111111');
		$r = parent::addItem($data, $this);
		var_dump($r);
		die;
	}

}
