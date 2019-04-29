<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class NbuttonLang extends Common {

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->NbuttonLang=model("NbuttonLang");
    }

    public function  get_lang_list() {
        $where = array();
        $input=input('post.');
        if(!empty($input['page'])){
            $where['page']=$input['page'];
        }
        if(!empty($input['menu_key'])){
            $where['menu_key']=$input['menu_key'];
        }
        if(!empty($input['zh'])){
            $where['zh']=$input['zh'];
        }
        if(!empty($input['eng'])){
            $where['eng']=$input['eng'];
        }
        $data['count'] =$this->NbuttonLang->getCount($where);
        $data['list'] = $this->NbuttonLang->getByWhere($where, '*', $input['limit'],'id asc');

        return suc($data);
    }

}
