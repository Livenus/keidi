<?php

namespace app\common\model;
use app\common\model\Base;
class ManageStockList extends Base {

    protected $table = 'StockList';
    protected $pk = 'StockID';

    public function addItem($data, $mod=""){
        return parent::addItem($data);
    }
}
