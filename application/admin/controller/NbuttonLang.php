<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use think\Validate;

class NbuttonLang extends Common {

    public function _initialize() {
        parent::_initialize();
        $this->NbuttonLang = \Think\Loader::model('NbuttonLang');
    }

    public function create() {
        if (request()->ispost()) {

            $input = input("post.");
            unset($input["id"]);
            $check = $this->validate($input, "NbuttonLang.add");

            if ($check !== true) {
                return err(3000, $check);
            }
            $data = [];
            foreach ($input as $k => $v) {
                if (!empty($v) || is_numeric($v)) {
                    $data[$k] = $v;
                }
            }

            $stat = $this->NbuttonLang->addItem($data);
            if ($stat['stat'] == 1) {
                return suc($stat);
            }
            return err(3000, "添加失败" . $stat['errmsg']);
        }
        return view('./nbutton_lang/nbuttonlang');
    }

    public function edit($id) {
        if (request()->ispost()) {
            $input = input("post.");
            $check = $this->validate($input, "NbuttonLang.edit");
            if ($check !== true) {
                return err(3000, $check);
            }
            $data = [];
            foreach ($input as $k => $v) {
                if (!empty($v) || is_numeric($v)) {
                    $data[$k] = $v;
                }
            }
            unset($data['id']);
            $stat = $this->NbuttonLang->editById($data, $input["id"]);
            if ($stat['stat'] == 1) {
                return suc($stat);
            }
            return err(3000, "修改失败" . $stat['errmsg']);
        }
        $data = $this->NbuttonLang->getById($id);
        $this->assign("item", $data);
        return view('./nbutton_lang/nbuttonlang');
    }

    public function nbuttonlang_list() {
        return view('./nbutton_lang/nbuttonlang_list');
    }

    public function nbuttonlang_list_ajax() {
        $start = input('post.start') ? input('post.start') : 0;
        $length = input('post.length') ? input('post.length') : 10;
        
        $where = array();
        $input=input('post.');
        if($input['page']){
            $where['page']=$input['page'];
        }
        if($input['menu_key']){
            $where['menu_key']=$input['menu_key'];
        }
        if($input['zh']){
            $where['zh']=$input['zh'];
        }
        if($input['eng']){
            $where['eng']=$input['eng'];
        }
        $list = $this->NbuttonLang->getlist($where, '*', 'id asc', null, $start . ',' . $length);
        $data = array(
            "recordsTotal" => (int) $this->NbuttonLang->count(),
            "recordsFiltered" => (int) $this->NbuttonLang->where($where)->count(),
            "data" => $list
        );
        return $data;
    }

}
