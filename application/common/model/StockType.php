<?php

namespace app\common\model;
use app\common\model\Base;
class StockType extends Base {

    protected $table = 'StockType';
    protected $pk = 'StockTypeID';

    public function addItem($data, $mod=""){
        return parent::addItem($data);
    }
}
