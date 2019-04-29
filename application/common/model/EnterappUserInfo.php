<?php

namespace app\common\model;

use app\common\model\Base;
use think\Db;

class EnterappUserInfo extends Base {

    protected $table = 'EnterApp_UserInfo';
    protected $pk = 'UserID';

    public function editById($data,$id) {
      if($this->getById($id)){
          return parent::editById($data, $id);
      }else{
          $data["UserID"]=$id;
          return $this->addItem($data);
      }
    }
}
