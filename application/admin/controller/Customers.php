<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use app\common\model\Customer;
use think\Validate;
use think\Db;

class Customers extends Common {

    public function _initialize() {
        parent::_initialize();
    }

    public function customer_list() {
        $current_page = input('post.current_page') ? input('post.current_page') : 1;
        $per_page = 2;
        $where = array();
        $Customer = new Customer();
        $list = $Customer->getlist($where, '*', 'CustomerID desc', $current_page . ',' . $per_page);
        return suc($list);
    }

}
