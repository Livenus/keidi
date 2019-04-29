<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class Register extends Common {

        private $PrintIndex;     //当前页码
        private $dataIndex;      //打印开始的数据行
        private $Count = 10;
        private $PrintMark = 0;

    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        
    }
 
    public function button3_Click()
        {
            $rule=[
                'CARD_NO'=>'require|^\w{2}\d{6}$'
            ];
            $check=$this->validate($this->input, $rule);
            if($check!==true){
                return err(9000,$check);
            }
            
            $this->input['Name']= $this->GetName( $this->input['CARD_NO']);
            if($this->input['Name']){
              return suc($this->input);    
            }
            return err(9000,"没有数据");
        } 
        public function GetName($code)
        {



                   $sql = "select customername from tb_customerinfo where customerid=(select customerid from tb_customer where customerno='" .$code . "')";
                   $name=$this->GetStringData($sql);
      
            return $name;

        }
        public function button1_Click()
        {
            $rule=[
                'CARD_NO'=>'^\w{2}\d{6}$',
                'Phone'=>'require',
                'Password'=>'require',
                'Name'=>'require',
                'Rewite'=>'require|confirm:Password'
            ];
            $msg=[
                'CARD_NO'=>'Please Enter Your Kedi Code!',
                'Phone'=>'Please Enter Your Phone Number!',
                'Password'=>'Please Enter Your Name!',
                'Name'=>'Please Enter Your Name!',
                'Rewite'=>'The password you entered second time\n is not same to the first one!' 
                
            ];
            $check=$this->validate($this->input, $rule,$msg);
            if($check!==true){
                return err(9000,$check);
            }
            try {
            $data['RegisterNew']=$this->RegisterNew();
             $data['input']=$this->input;
           return suc($data);  
            } catch (\Exception $exc) {
                return err(9000,$exc->getMessage());
            }


        }
        private function RegisterNew()
        {
            $Regsql = "insert TelleraccountReg(regId,regserviceNo,registername,Kedicard,phone,[password],regdate,oper) ".
              "values('" .$this->Get_New_RegID() . "','" .$this->input['personal_card']. "','"  .$this->input['Name'].  "','" .$this->GetCustomerid( $this->input['CARD_NO']) . "','" .$this->input['Phone']. "','" .$this->input['Password']. "','" .$this->input['Date']. "','" .self::$realname. "')";
                    
            $status=$this->Exc_Sql($Regsql);
            if (is_numeric($status))
                return suc("注册成功！");
            
        }
        private function  Get_New_RegID()
        {
            $RegID = "";

                $sql = "select max(regid)+1 as regid from TelleraccountReg ";
                $RegID=$this->GetStringData($sql);
                if(empty($RegID)){
                   $RegID = "1"; 
                }
                


            return $RegID;
        }
        public function GetCustomerid($code)
        {

                    $sql= "select customerid from tb_customer where customerno='" .$code. "'";
                  $name=$this->GetStringData($sql);
                    if(empty($name)){
                        exception("获取ID错误");
                    }
            return $name;

        }
}
