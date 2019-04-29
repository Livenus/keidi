<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Validate;

class Group extends Common {

    public function _initialize() {
        parent::_initialize();
    }

    public function group_create_ajax() {
        $grp_data = [
            'Group_name' => input('Group_name'),
            'Group_ID' => input('Group_ID'),
        ];
        $Group = \think\Loader::model('EnterappGroup');
        $act = input('act');
        if ($act == 'add') {
            $res = $Group->add($grp_data);
        } else {
            $res = $Group->edit($grp_data);
        }

        return $res;
    }

    public function group_list() {
        return view('./group_list');
    }

    public function group_list_ajax() {
        $Menu = \think\Loader::model('EnterappGroup');
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

    public function auth_assign($Group_ID) {
        $Auth = \think\Loader::model('EnterappAuth');
        $auths = $Auth::all();
        $Group = \think\Loader::model('EnterappGroup');
        $tree=model("EnterappMenu")->getTreeAuth();
        $Group_item = $Group->getById($Group_ID);
        $Group_item['Group_auths'] = explode(',', $Group_item['Group_auths']);
        return view('./assign_auth', ['auths' => $auths, 'Group_ID' => $Group_ID, 'Group_item' => $Group_item,"tree"=>$tree]);
    }

    public function auth_assign_ajax() {
        $Group = \think\Loader::model('EnterappGroup');
        $Group_ID = input('post.Group_ID');
        $data = [
            'Group_auths' => implode(',', input('post.Group_auths/a'))
        ];
        $res = $Group->editById($data, $Group_ID);
        \think\Cache::clear();
        return $res;
    }

}
