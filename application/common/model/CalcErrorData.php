<?php

namespace app\common\model;
use app\common\model\Base;
use think\Db;
class CalcErrorData extends Base {

    protected $table = 'Calc_Error_Data';
    protected $pk = 'SaleNO';

    public function add($data) {
            return parent::addItem($data);
    }
   
}
