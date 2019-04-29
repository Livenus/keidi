<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;

class Nversions extends Common {

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->Nversions=model("Nversions");
    }

    public function versions_detail() {
            $data=$this->Nversions->getByWhere([]);
            return suc($data);
    }

}
