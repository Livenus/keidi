<?php

namespace app\common\model;

use app\common\model\Base;
use think\Validate;

class CustomerInfo extends Base {

    protected $table = 'tb_CustomerInfo';
    protected $pk = 'CustomerID';

    public function add($data) {
        $res = $this->_validate($data);
        if ($res['stat'] ===0) {
            return $res;
        }
        $res = parent::addItem($data, $this);
        return $res;
    }

    private function _validate(&$data) {
        $Customer_info = $this->where(array('CustomerID' => $data['CustomerID']))->count();
        if ($Customer_info > 0) {
            return err(0,'Customer_info 系统已经存在，不能重复添加');
        }
        return suc();
    }

}
