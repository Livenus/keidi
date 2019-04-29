<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Validate;

class Menu extends Common {

    public function _initialize() {
        parent::_initialize();
        $this->EnterappMenu = model("EnterappMenu");
    }

    public function menu_create() {
        $Menu = \think\Loader::model('EnterappMenu');

        $list = $Menu->getTree();
        $item["Menu_pid"]="";
      $item["Menu_status"]=1;
        $this->assign("item",$item);
        //顶级菜单
        $parents = $Menu->where(array('Menu_pid' => 0))->order('Menu_order asc,Menu_id asc')->select();
        return view('./menu_create', ['act' => 'add', 'parents' => $parents]);
    }

    public function menu_list() {
        $Menu = \think\Loader::model('EnterappMenu');
        $data = $Menu->getTree();
        //echo '<pre>';
        //var_dump($data);die;
        return view('./menu_list', ['data' => $data]);
    }

    public function menu_list_ajax() {
        $Menu = \think\Loader::model('EnterappMenu');
        $start = input('post.start') ? input('post.start') : 0;
        $length = input('post.length') ? input('post.length') : 10;
        $where = array();
        $list = $Menu->getlist(array(), '*', '', null, $start . ',' . $length);
        $data = array(
            "draw" => input('draw'),
            "recordsTotal" => (int) $Menu->count(),
            "recordsFiltered" => (int) $Menu->where($where)->count(),
            "data" => $list
        );
        return $data;
    }

    public function menu_create_ajax() {
        $mu_data = [
            'Menu_name' => input('Menu_name'),
            'Menu_value' => input('Menu_value'),
            'Menu_order' => input('Menu_order'),
            'Menu_pid' => input('Menu_pid')
        ];
        $res = $this->EnterappMenu->add($mu_data);
        return $res;
    }

    public function edit($id="") {
        if (request()->isPost()) {
            $mu_data = [
                'Menu_name' => input('Menu_name'),
                'Menu_value' => input('Menu_value'),
                'Menu_status' => input('Menu_status'),
                'Menu_order' => input('Menu_order'),
                'Menu_pid' => input('Menu_pid')
            ];
            $res = $this->EnterappMenu->editById($mu_data, input("Menu_id"));
            return $res;
        }
        $item = $this->EnterappMenu->getById($id);
        $parents = $this->EnterappMenu->where(array('Menu_pid' => 0))->select();
        $this->assign("item", $item);
        return view('./menu_create', ['act' => 'add', 'parents' => $parents]);
    }

}
