<?php

namespace app\index\controller;

use think\Controller;
use think\captcha\Captcha;
use think\Request;
use app\common\model\EnterappUserlogin;

class Basic extends Controller {

    private $outlog = ['getcode', 'check_verify', 'information', 'login', 'login_ajax'];
    private $errmsg = array(
        '1001' => '您还没有登录，请您先登录系统',
        '2001' => '您没有当前操作的权限'
    );

    //初始化
    public function _initialize() {
        $request = Request::instance();
        $action = $request->action();
        if (!in_array($action, $this->outlog)) {
            $res = self::_check_login();
            if (isset($res['stat'])&&$res['stat'] === 1) {
                static::$userID = $res['data']['UserID'];
                static::$user = \think\Loader::model('EnterappUser')->getById($res['data']['UserID']);
            } else {
                $this->redirect('/index.php/index/basic/information?code=1001');
            }
        }
        //权限验证
        //$r = $this->checkauth();
        //if($r['code'] === 1){
        //	$this->redirect('/index.php/index/basic/information?code=2001');
        //}
    }

    protected function checkauth() {
        $request = Request::instance();
        $auth_val = strtolower($request->module() . '_' . $request->controller() . '_' . $request->action());
        $auths = \think\Loader::model('EnterappAuth')->all_auths_val_id();
        if (!empty($auths[$auth_val])) {
            $auth_id = $auths[$auth_val];
        } else {
            $auth_id = -1;
        }
        var_dump($auth_val);
    }

    public function information($code) {
        $msg = !empty($this->errmsg[$code]) ? $this->errmsg[$code] : '未知错误';
        return err(900,$msg);
    }

    static private function _check_login() {
        $usertoken = input('usertoken');
        if (empty($usertoken)) {
            return ['code' => 1001, 'msg' => '您还没有登录，请您先登录系统'];
        }
        $login_info = EnterappUserlogin::where('usertoken', $usertoken)->find();
        if (empty($login_info) || $login_info->logintime + 3600 * 4 < time()) {
            return ['code' => 1001, 'msg' => '您还没有登录，请您先登录系统'];
        } else {
            static::$usertoken = $usertoken;
            return suc(['UserID' => $login_info['UserID']]);
        }
    }

}
