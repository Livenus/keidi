<?php

namespace app\common\model;

use app\common\model\Base;

class FrontDeskReportPrint extends Base {

    protected $table = 'FrontDesk_ReportPrint';
    protected $pk = 'Code';

    public function add($data) {
        $res = parent::addItem($data, $this);
        return $res;
    }

}
