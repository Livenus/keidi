<?php

namespace app\desktop\controller;

use think\Controller;

class EnterAppLogin extends Controller {

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->user_model=model("EnterappUser");
        $this->EnterappUserInfo=model("EnterappUserInfo");
    }
    public  function findPassword($username){
                        $input = input("post.");
                $rule=[
                    "username"=>"require"
                ];
           $check=$this->validate($input, $rule);
          if ($check !== true) {
              return err(9000,$check);
          }
                $user=$this->user_model->getByWhereOne(["username"=>$username]);
                if($user["Question"]){
                    return suc($user["Question"]);
                }else{
                    return err(9000,"没有设置问题");
                }
    }
    public  function findPasswordAnswer($username){
                $input = input("post.");
                 $user=$this->user_model->getByWhereOne(["username"=>$username]);
                $rule=[
                    "Answer"=>"require",
                    "new_password"=>"require",
                    "username"=>"require",
                ];
           $check=$this->validate($input, $rule);
          if ($check !== true) {
              return err(9000,$check);
          }
        $count=$this->user_model->getCount(["username"=>trim($user['username']),"Answer"=>trim($input["Answer"])]);
         if($count){
            $this->user_model->editById(["userpassword"=>$input["new_password"]], $user["UserID"]);
            return suc("修改成功");
            }
                return err(9000,"答案错误");
    }

}
