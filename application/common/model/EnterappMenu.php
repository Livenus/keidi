<?php

namespace app\common\model;

use app\common\model\Base;
use think\Model;
use think\Validate;
use think\Cache;

class EnterappMenu extends Base {

    protected $table = 'EnterApp_Menu';
    protected $pk = 'Menu_id';

    public function add($data) {
        $rule = [
            'Menu_name' => 'require',
            'Menu_value' => 'require',
        ];

        $validate = new Validate($rule);
        $result = $validate->check($data);

        if (!$result) {
            return err(2000,$validate->getError());
        }
        $res = $this->_validate($data);
        if ($res['stat'] == '0') {
            return $res;
        }
        $Menu_id = $this->max('Menu_id');
        $data['Menu_id'] = $Menu_id + 1;
        $res = parent::addItem($data, $this);
        return $res;
    }

    private function _validate(&$data) {
        $count = $this->where(array('Menu_value' => $data['Menu_value']))->count();
        if ($count > 0) {
            return err(2000,'菜单值重复');
        } else {
            return suc();
        }
    }

    public function getTree() {
        $data = $this->obj2data($this->order('Menu_order asc,Menu_id asc')->select());
        $data = $this->_data2tree($data);
        return $data;
    }
    public function getTreeAuth() {
        $data = $this->obj2data($this->order('Menu_order asc,Menu_id asc')->select());
        $data = $this->_data2treeauth($data);
        return $data;
    }
    private function _data2treeauth($data) {
        $parents = array();
        $children = array();
        foreach ($data as $k => $v) {
            if ($v['Menu_pid'] == '0') {
                $parents[] = $v;
            } else {
                $children[] = $v;
            }
        }
        $auth_model=model("EnterappAuth");
        foreach ($parents as $pk => $pv) {
            $parents[$pk]['Menu_name_c'] = $pv['Menu_name'];
            $parents[$pk]['auth'] = $auth_model->getByWhere(['menu_id'=>$pv['Menu_id']]);

            $parents[$pk]['Menu_sons'] = array();
            foreach ($children as $ck => $cv) {
                if ($cv['Menu_pid'] == $pv['Menu_id']) {
                    $cv['Menu_name_c'] = '&nbsp;&nbsp;&nbsp;&nbsp;|——' . $cv['Menu_name'];
                    $cv['auth'] = $auth_model->getByWhere(['menu_id'=>$cv['Menu_id']]);
                    $parents[$pk]['Menu_sons'][] = $cv;
                }
            }
        }
        return $parents;
    }
    private function _data2tree($data) {
        $parents = array();
        $children = array();
        foreach ($data as $k => $v) {
            if ($v['Menu_pid'] == '0') {
                $parents[] = $v;
            } else {
                $children[] = $v;
            }
        }
        foreach ($parents as $pk => $pv) {
            $parents[$pk]['Menu_name_c'] = $pv['Menu_name'];
            $parents[$pk]['Menu_sons'] = array();
            foreach ($children as $ck => $cv) {
                if ($cv['Menu_pid'] == $pv['Menu_id']) {
                    $cv['Menu_name_c'] = '&nbsp;&nbsp;&nbsp;&nbsp;|——' . $cv['Menu_name'];
                    $parents[$pk]['Menu_sons'][] = $cv;
                }
            }
        }
        return $parents;
    }

    public function getAuthMenu($group_id) {
        $menus = Cache::get('Group_' . $group_id . '_menus');
        if (empty($menus)) {
            $group = \think\Loader::model('EnterappGroup')->getById($group_id);
            $auths = $group['Group_auths'];
            if (empty($auths)) {
                return array();
            }
            $menu_bottom_id = \think\Loader::model('EnterappAuth')->get_menus_id(array('auth_id' => array('in', $auths)));

            $son_menu = $this->getList(array('Menu_status' => '1', 'Menu_id' => array('in', $menu_bottom_id)),"*","Menu_order asc,Menu_id asc");
            $menu_top_id=[];
           foreach($son_menu as $v){
               if($v['Menu_pid']==0){
                   $menu_top_id[]=$v['Menu_id'];
               }
           }
            $parent_menu = $this->getList(array('Menu_status' => '1', 'Menu_id' => array('in', $menu_top_id)),"*","Menu_order asc,Menu_id asc");
           
            foreach ($parent_menu as $pk => $pv) {
                $parent_menu[$pk]['Menu_sons'] = array();
                foreach ($son_menu as $ck => $cv) {
                    if ($cv['Menu_pid'] == $pv['Menu_id']) {
                        $parent_menu[$pk]['Menu_sons'][] = $cv;
                    }
                }
            }
            $menus = $parent_menu;
            Cache::set('Group_' . $group_id . '_menus', $menus, 3600);
        }

        return $menus;
    }

}
