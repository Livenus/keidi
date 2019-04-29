<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Validate;

class Auth extends Common {

    public function _initialize() {
        parent::_initialize();
    }

    public function auth_create() {
        $Menu = \Think\Loader::model('EnterappMenu');
        $menus = $Menu->getTree();
        return view('./auth_create', ['item' => [], 'act' => 'add', 'menus' => $menus]);
    }

    public function auth_update() {
        $auth_id = input('auth_id');
        $Auth = \think\Loader::model('EnterappAuth');
        $item = $Auth->getById($auth_id);
        $Menu = \Think\Loader::model('EnterappMenu');
        $menus = $Menu->getTree();
        return view('./auth_create', ['item' => $item, 'act' => 'edit', 'menus' => $menus]);
    }

    public function auth_create_ajax() {
        $au_data = [
            'auth_id' => input('auth_id'),
            'auth_name' => input('auth_name'),
            'auth_value' => input('auth_value'),
            'show' => input('show'),
            'auth_order' => input('auth_order') ? input('auth_order') : 0,
            'menu_id' => input('menu_id')
        ];
        $Auth = \think\Loader::model('EnterappAuth');
        $act = input('act');
        if ($act == 'edit') {
            $res = $Auth->edit($au_data);
        } else if ($act == 'add') {
            $res = $Auth->add($au_data);
        } else {
            return err(3000, '非法操作');
        }

        return $res;
    }

    public function auth_list() {
        return view('./auth_list');
    }

    public function auth_list_ajax() {
        $Auth = \think\Loader::model('EnterappAuth');
        $Menu = \think\Loader::model('EnterappMenu');
        $start = input('post.start') ? input('post.start') : 0;
        $length = input('post.length') ? input('post.length') : 10;
        $where = array();
        if(input('post.auth_value')){
            $value=input('post.auth_value');
            $where["auth_value"]=["like","%{$value}%"];
        }
        $list = $Auth->getlist($where, '*', 'auth_order asc,auth_id desc', false, $start . ',' . $length);
        foreach ($list as $k => $v) {
            $menu = $Menu->getById($v['menu_id'], 'Menu_name');
            if (!empty($menu)) {
                $menu_name = $menu['Menu_name'];
            } else {
                $menu_name = '无';
            }
            $list[$k]['menu_name'] = $menu_name;
        }
        $data = array(
            "draw" => input('draw'),
            "recordsTotal" => (int) $Auth->count(),
            "recordsFiltered" => (int) $Auth->where($where)->count(),
            "data" => $list
        );
        return $data;
    }

}
