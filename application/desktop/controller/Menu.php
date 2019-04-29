<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class Menu extends Common {

    //初始化
    public function _initialize() {
        parent::_initialize();
    }

    public function getMenus() {
        $group_id = parent::$user['GroupID'];
        if (empty($group_id)) {
            return err(2000,"menu_nodata");
        } else {
            $menus = \think\Loader::model('EnterappMenu')->getAuthMenu($group_id);
            return suc($menus);
        }
    }
    public function load(){
        \think\Cache::clear();
    }
}
