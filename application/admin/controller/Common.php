<?php

namespace app\admin\controller;

use think\Controller;
use think\captcha\Captcha;
use think\Request;
use app\common\model\EnterappUserlogin;
use app\index\controller\Basic;

class Common extends Basic {

    static protected $usertoken;
    static protected $user;
    static protected $userID;

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->assign('usertoken', self::$usertoken);
    }

    public function getcode() {
        $captcha = new Captcha();
        return $captcha->entry();
    }

    protected function check_verify($code, $id = '') {
        $captcha = new Captcha();
        return $captcha->check($code, $id);
    }
    public function clear_cache(){
        \think\Cache::clear();
        return view('./clear_cache');
    }
}
