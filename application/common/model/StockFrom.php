<?php

namespace app\common\model;
use app\common\model\Base;
class StockFrom extends Base {

    protected $table = 'StockFrom';
    protected $pk = 'FromID';

    public function addItem($data, $mod=""){
        return parent::addItem($data);
    }
}
