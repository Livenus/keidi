<?php

namespace app\desktop\controller;

use think\Controller;
use think\captcha\Captcha;
use think\Request;
use app\common\model\EnterappUserlogin;

class Basic extends Controller {

    private $outlog = ['getcode', 'check_verify', 'information', 'login', 'login_ajax', 'logout', 'versions_detail'];

    //初始化
    public function _initialize() {
        $request = Request::instance();
        $action = $request->action();
        if (!in_array($action, $this->outlog)) {
            $res = self::_check_login();
            if (isset($res['stat'])&&$res['stat'] === 1) {
                static::$userID = $res['data']['UserID'];

                static::$user = \think\Loader::model('EnterappUser')->getById($res['data']['UserID']);
               static::$realname = static::$user ['realname'];
               //权限验证
                $r = $this->checkauth();
                if($r['stat'] === 0){
                    mexit("没有权限",1002);
                      }
            } else {
                mexit("没有登录");
            }
        }

    }

    protected function checkauth() {
        $request = Request::instance();
        $nocheck=["admin_info","getmenus","test","setpassword","setuserinfo","admin_info_detail","findpassword","findpassword","findpasswordanswer","getregion","load","get_lang_list","readimg","uploadimg","get_time"];
        $action=$request->action();
        $controller=$request->controller();
        if(in_array($action, $nocheck)||$controller=='Test'){
            return suc();
        }
        $auth_val = strtolower($request->module() . '_' . $request->controller() . '_' . $request->action());
        $auth_val= strtolower($auth_val);
        if(empty(static::$user['GroupID'])){
            return err(2000,"未分配权限");
        }
        $group=model("EnterappGroup")->getBywhereOne(['Group_ID'=>static::$user['GroupID']]);
        $auths =model("EnterappAuth")->all_auths_val_id($group["Group_auths"]);
        $auth=[];
        foreach($auths as $k=>$v){
            $kk= strtolower($k);
            $vv= strtolower($v);
            $auth[$kk]=$vv;
        }
        if (isset($auth[$auth_val])&&!empty($auth[$auth_val])) {
            $auth_id = $auth[$auth_val];
            return suc();
        } else {
            $auth_id = -1;
            return err(5000,"没有权限");
        }
    }

    public function information($code) {
            return err(1001, "basic_nologin");
    }

    static private function _check_login() {
        $usertoken = input('usertoken');
        if (empty($usertoken)) {
            return err(1001, "basic_nologin");
        }
        $login_info = EnterappUserlogin::where('usertoken', $usertoken)->find();
        if (empty($login_info) || $login_info->logintime + 3600 * 4 < time()) {
            return err(1001, "basic_nologin");
        } else {
            static::$usertoken = $usertoken;
            return suc(['UserID' => $login_info['UserID']]);
        }
    }

}
