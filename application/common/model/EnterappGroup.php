<?php

namespace app\common\model;

use app\common\model\Base;
use think\Model;
use think\Validate;

class EnterappGroup extends Base {

    protected $table = 'EnterApp_Group';
    protected $pk = 'Group_ID';

    public function add($data) {
        $res = $this->_validate($data);
        if ($res['stat'] == '0') {
            return $res;
        }
        $Group_ID = $this->max('Group_ID');
        $data['Group_ID'] = $Group_ID + 1;
        $res = parent::addItem($data, $this);
        return $res;
    }

    public function edit($data) {
        $res = $this->_validate($data);
        if ($res['stat'] == '0') {
            return $res;
        }
        $res = parent::editById($data, $data[$this->pk]);
        return $res;
    }

    private function _validate($data) {
        $rule = [
            'Group_name' => 'require',
        ];

        $validate = new Validate($rule);
        $result = $validate->check($data);

        if (!$result) {
            return err(2000,$validate->getError());
        } else {
            return suc();
        }
    }

}
