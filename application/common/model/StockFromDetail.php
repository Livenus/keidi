<?php

namespace app\common\model;
use app\common\model\Base;
class StockFromDetail extends Base {

    protected $table = 'StockFromDetail';
    protected $pk = 'FromDetailID';

    public function addItem($data, $mod=""){
        return parent::addItem($data);
    }
}
