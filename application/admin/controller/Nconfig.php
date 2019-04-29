<?php

namespace app\admin\controller;

use app\admin\controller\Common;

class Nconfig extends Common {

    public function _initialize() {
        parent::_initialize();
        $this->Nconfig=model("Nconfig");
    }

    public function create() {
                   if(request()->ispost()){
                       $input=input("post.");
                       $data["Unit_Price"]=$input['Unit_Price'];
                       if($this->Nconfig->getCount([])){

                          $stat=$this->Nconfig->edItById($data,$input['id']);
                       }else{
                           $stat=$this->Nconfig->addItem($data);
                       }
                       
                       if($stat['stat']==1){
                           return suc($stat);
                       }
                       return err(9000,"添加失败".$stat['errmsg']);
                   }
                 $item=$this->Nconfig->getByWhereOne([]);
                 $this->assign("item",$item);
                 return view('./nconfig/create');
    }
  
    


}
