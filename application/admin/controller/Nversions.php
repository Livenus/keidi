<?php

namespace app\admin\controller;

use app\admin\controller\Common;

class Nversions extends Common {

    public function _initialize() {
        parent::_initialize();
        $this->Nversions=model("Nversions");
    }

    public function create() {
                   if(request()->ispost()){
                       $input=input("post.");
                       $data=[];
                       foreach($input as $k=>$v){
                           if(!empty($v)||is_numeric($v)){
                               $data[$k]=$v;
                           }
                       }
                       $data["Versions_push_time"]= strtotime($data["Versions_push_time"]);
                       $data["Versions_add_time"]= time();
                       $stat=$this->Nversions->addItem($data);
                       if($stat['stat']==1){
                           return suc($stat);
                       }
                       return err(3000,"添加失败".$stat['errmsg']);
                   }
                 return view('./nversions/versions_create');
    }
    public function edit($Versions_id) {
                   if(request()->ispost()){
                       $input=input("post.");
                       $data=[];
                       foreach($input as $k=>$v){
                           if(!empty($v)||is_numeric($v)){
                               $data[$k]=$v;
                           }
                       }
                       unset($data["Versions_id"]);
                       $data["Versions_push_time"]= strtotime($data["Versions_push_time"]);
                       if($data["Versions_stat"]==0){
                           $data["Versions_disabled_time"]=time();
                       }
                       $stat=$this->Nversions->editById($data,$input["Versions_id"]);
                       if($stat['stat']==1){
                           return suc($stat);
                       }
                       return err(3000,"修改失败".$stat['errmsg']);
                   }
        $data=$this->Nversions->getById($Versions_id);
        $data["Versions_push_time"]=date("Y-m-d H:i:s",$data["Versions_push_time"]);
        $this->assign("item",$data);
         return  view('./nversions/versions_create');
    }
    public function update() {
    }
    public function del() {
    }
    public function Nversions_list_ajax() {
        $start = input('post.start') ? input('post.start') : 0;
        $length = input('post.length') ? input('post.length') : 10;
        $where = array();
        $list = $this->Nversions->getlist(array(), '*', 'versions_id asc', null, $start . ',' . $length);
        foreach($list as $k=>$v){
            $list[$k]["Versions_push_time"]=date("Y-m-d H:i:s",$v["Versions_push_time"]);
        }
        $data = array(
            "recordsTotal" => (int) $this->Nversions->count(),
            "recordsFiltered" => (int) $this->Nversions->where($where)->count(),
            "data" => $list
        );
        return $data;
    }
    public function Nversions_list() {
        return view('./nversions/nversions_list');
    }


}
