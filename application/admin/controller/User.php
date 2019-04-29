<?php

namespace app\admin\controller;

use app\admin\controller\Common;
use app\common\model\EnterappUser;
use think\Validate;

class User extends Common {

    public function _initialize() {
        parent::_initialize();
        $this->EnterappUser=model("EnterappUser");
        $this->EnterappGroup=model("EnterappGroup");
    }

    public function user_list() {
        return view('./user_list');
    }

    public function user_list_ajax() {
        $input=input('post.');
        $start = input('post.start') ? input('post.start') : 0;
        $length = input('post.length') ? input('post.length') : 10;
        $where = array();
        $User = new EnterappUser();
        if($input['username']){
            $where['username']=$input['username'];
        }
        if($input['realname']){
            $where['realname']=$input['realname'];
        }
        if($input['Country']){
            $where['Country']=$input['Country'];
        }
        if($input['Dept']){
            $where['Dept']=$input['Dept'];
        }
        if($input['GroupID']){
             $group=$this->EnterappGroup->getByWhereOne(["Group_name"=>$input['GroupID']]);
            $where['GroupID']=$group['Group_ID'];
        }
        $list = $User->user_infolist($where, $start . ',' . $length);
        foreach($list as $k=>$v){
            $group=$this->EnterappGroup->getByWhereOne(["Group_ID"=>$v['GroupID']]);
            if($group){
                            $list[$k]["Group_name"]=$group['Group_name'];
            }else{
                            $list[$k]["Group_name"]="";
            }

        }
        $data = array(
            "draw" => input('draw'),
            "recordsTotal" => (int) $User->count(),
            "recordsFiltered" => (int) $User->where($where)->count(),
            "data" => $list
        );
        return $data;
    }
    public function add(){
        if(request()->isPost()){
            $input=input("post.");
            $check=$this->validate($input, "User");
            if($check!==true){
                return err(9000,$check);
            }
            if($this->EnterappUser->getCount(["username"=>$input["username"]])){
                 return err(9000,"用户已存在");
            }

            $data["username"]=$input["username"];
            $data["GroupID"]=$input["GroupID"];
            $data["realname"]=$input["realname"];
            $data["Dept"]=$input["Dept"];
            $data["userpassword"]=$input["userpassword"];
            $status=$this->EnterappUser->addItem($data);
            if($status["stat"]==1){
                return suc("添加成功");
            }
            return err(9000,"添加失败".$status["errmsg"]);
            exit();
        }
        $group_model=model("EnterappGroup");
        $group_data=$group_model->getByWhere([]);

        $this->assign("group_data",$group_data);
        return view('./user_edit');
    }
    public function edit($userID){
        if(request()->isPost()){
            $input=input("post.");
            $data["username"]=$input["username"];
            $data["GroupID"]=$input["GroupID"];
            $data["realname"]=$input["realname"];
            $data["status"]=$input["status"];
            $data["Dept"]=$input["Dept"];
            $data["userpassword"]=$input["userpassword"];
            $status=$this->EnterappUser->editByid($data,$input["userID"]);
            if($status["stat"]==1){
                return suc("编辑成功");
            }
            return err(9000,"编辑失败".$status["errmsg"]);
            exit();
        }
        $data=$this->EnterappUser->getById($userID);
        $group_model=model("EnterappGroup");
        $group_data=$group_model->getByWhere([]);
        $rand= rand(1000, 999);
        $this->assign("item",$data);
         $this->assign("rand",$rand);
        $this->assign("group_data",$group_data);
        return view('./user_edit');
    }

}
