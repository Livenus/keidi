<?php

namespace app\common\model;

use app\common\model\Base;
use think\Db;

class EnterappUser extends Base {

    protected $table = 'EnterApp_User';
    protected $pk = 'UserID';
    //数据添加
    public function addItem($data, $mod="") {
        $uid=$this->field("max(UserID) as uid")->find();
        $uid=$uid->toArray();
        $data["UserID"]=$uid["uid"]+1;
  return parent::addItem($data);
    }
    public function user_infolist($where = array(), $limit = 100) {
        $u = 'EnterApp_User';
        $i = 'EnterApp_UserInfo';
        $field = $u . '.*,' . $i . '.Gender,' . $i . '.Phone,' . $i . '.State,' . $i . '.Email,' . $i . '.DriveCardID,' . $i . '.PresentAddress,' . $i . '.HometownAddress,' . $i . '.Birthday,' . $i . '.CreateTime,' . $i . '.FullName,' . $i . '.Region,' . $i . '.People';
        $list = Db::table($u)->join('' . $i . '', $u . '.UserID=' . $i . '.UserID', 'left')
                        ->where($where)->field($field)->limit($limit)->select();
        return $list;
    }
}
