<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class MarketShop extends Common {

        private$PrintIndex;     
    //初始化
    public function _initialize() {
        parent::_initialize();
        $this->input = input("post.");
        $this->MarketShop=model("MarketShop");
        $this->MarketShoplog=model("MarketShoplog");
    }
    public function load(){
        \think\Cache::clear();
    }
    public function add(){
        $data=$this->input;
        unset($data["usertoken"]);
        $check=$this->validate($this->input, "MarketShop.add");
        if($check!==true){
            return err(9000,$check);
        }
        if($data){
          $data["Oper"]=self::$realname;
           $data["Add_Date"]=date("Y-m-d H:i:s");
           $data["Status"]=1;
           $data["apply_status"]=0;
           $data["apply_action"]="add";
           $data["applay_date"]=date("Y-m-d H:i:s");
          $status=$this->MarketShoplog->addItem($data);  
        }
        if($status["stat"]){
            return suc($data["Shopno"]);
        }
        
        return err(9000,"新增失败".$status["errmsg"]);
    }
    public function edit(){
        $data=$this->input;
        $update=[];
        foreach($data as $k=>$v){
            if(!empty($v)||is_numeric($v)){
                $update[$k]=$v;
            }
        }
        $data=$update;
        unset($data["usertoken"]);
        $check=$this->validate($this->input, "MarketShop.edit");
        if($check!==true){
            return err(9000,$check);
        }
        if($data){
           $log=$this->MarketShoplog->getByWhereOne(["Shopno"=>$data["Shopno"],"apply_status"=>0]);
           if($log){
               return err(9000,"有状态未审核");
           }
          $data["Oper"]=self::$realname;
          $data["apply_status"]=0;
          $data["apply_action"]="edit";
           $data["UpdateDate"]=date("Y-m-d H:i:s");
           if($data["Status"]==0){
               $data["CloseDate"]=date("Y-m-d H:i:s"); 
           }else{
                $data["OpenDate"]=date("Y-m-d H:i:s");
           }
          $data["applay_date"]=date("Y-m-d H:i:s");
          $status=$this->MarketShoplog->addItem($data);  
        }
        if($status["stat"]){
            return suc($data["Shopno"]);
        }
        
        return err(9000,"编辑失败".$status["errmsg"]);
    }
    public function update(){
        $data=$this->input;
        unset($data["usertoken"]);
        $check=$this->validate($this->input, "MarketShop.update");
        if($check!==true){
            return err(9000,$check);
        }
       $status1=$this->MarketShoplog->editById(["apply_status"=>$data['apply_status'],"apply_info"=>$data['apply_info'],"apply_person"=> self::$realname],$data["Shopno"]);  
       if($status1&&$data['apply_status']==2){
           return suc("审核已拒绝");
       }elseif($status1['stat']==0){
           return err(9000,"审核操作失败".$status1['errmsg']);
       }
        if($data){
          $data["apply_person"]=self::$realname;
          $log=$this->MarketShoplog->getByWhereOne(["Shopno"=>$data["Shopno"],"apply_status"=>0]);
          if(empty($log)){
              return err(9000,"未提交审核");
          }
          unset($log["ROW_NUMBER"]);
          //更新到正式表
          foreach($log as $k=>$v){
          if((!empty($v)||is_numeric($v))&&$k!='apply_person'&&$k!='apply_info'&&$k!='apply_status'&&$k!='applay_date'){
                $data[$k]=$v;
            }
          }
          if($this->MarketShop->getCount(["Shopno"=>$data["Shopno"]])){
              $status=$this->MarketShop->editById($data,$data["Shopno"]);  
          }else{
              $status=$this->MarketShop->addItem($data);  
          }
          
        }
        if($status["stat"]){

            return suc($data["Shopno"]);
        }
        
        return err(9000,"编辑失败".$status["errmsg"]);
    }
    public function detail($Shopno){
        
        $data=$this->MarketShop->getById($Shopno);
        
        return suc($data);
    }
    public function get_list(){
        $map=[];
        $rule=[
            "limit"=>"require",
            "order"=>"require",
            
        ];
        $check=$this->validate($this->input, $rule);
        if($check!==true){
            return err(9000,$check);
        }
        if($this->input["Oper"]){
            
            $map["Oper"]=$this->input["Oper"];
        }
        if($this->input["apply_person"]){
            
            $map["apply_person"]=$this->input["apply_person"];
        }
        if(isset($this->input["apply_status"])&& is_numeric($this->input["apply_status"])){
            
            $map["apply_status"]=$this->input["apply_status"];
        }
        if($this->input['start']){
            $map["Add_Date"]=["between","{$this->input['start']},{$this->input['end']}"];
        }
        $limit=$this->input["limit"];
         $order=$this->input["order"];
        $data=$this->MarketShop->getByWhere($map,"*",$limit,$order);
        $count=$this->MarketShop->getCount($map);
        $rep["count"]=$count;
        $rep["list"]=$data;
        return suc($rep);
        
    }
    public function get_list_applay(){
        $map=[];
        $rule=[
            "limit"=>"require",
            "order"=>"require",
            
        ];
        $check=$this->validate($this->input, $rule);
        if($check!==true){
            return err(9000,$check);
        }
        if($this->input["Oper"]){
            
            $map["Oper"]=$this->input["Oper"];
        }
        if($this->input["apply_person"]){
            
            $map["apply_person"]=$this->input["apply_person"];
        }
        if(isset($this->input["apply_status"])&& is_numeric($this->input["apply_status"])){
            
            $map["apply_status"]=$this->input["apply_status"];
        }
        if($this->input['start']){
            $map["Add_Date"]=["between","{$this->input['start']},{$this->input['end']}"];
        }
        $limit=$this->input["limit"];
         $order=$this->input["order"];
        $data=$this->MarketShoplog->getByWhere($map,"*",$limit,$order);
        $note=[
            "OwnerName"=>"店主",
            "State"=>"区域",
            "Phone"=>"电话",
            "Address"=>"地址"
        ];
        foreach($data as $k=>$v){
            $mapb["Shopno"]=$v["Shopno"];
            $mapb["applay_date"]=["neq",$v['applay_date']];
            $before=$this->MarketShoplog->getByWhereOne($mapb,"*","*","applay_date desc");
            $str="";
            $not=["Oper","applay_date","applay_date","apply_action","apply_status","apply_info","apply_person"];
            if($before){
                $str="修改:";
                foreach($v as $kk=>$vv){
                    if(trim($vv)!==trim($before[$kk])&&!empty($vv)&&!in_array($kk, $not)){
                        if(array_key_exists($kk, $note)){
                            $str.="".$note[$kk]."由".$before[$kk].""."变更为".$vv.";"; 
                        }elseif($kk=="Status"){
                              $before_status=$before[$kk]?"可用":"禁用";
                              $after=$vv?"可用":"禁用";
                               $str.="状态"."".$before_status."变更为".$after.";"; 
                        }else{
                            $str.="".$kk."由".$before[$kk].""."变更为".$vv.";"; 
                        }

                    }
                }
            }else{
                $str="新增:";
              foreach($v as $kk=>$vv){
                    if(!empty($vv)&&!in_array($kk, $not)){
                        if(array_key_exists($kk, $note)){
                            $str.="".$note[$kk]."为".$vv; 
                        }elseif($kk=="Status"){
                              $after=$vv?"可用":"禁用";
                               $str.="状态"."".$after; 
                        }

                    }
                } 
                
                
            }
            $data[$k]["content"]=$str;
            
        }
        $count=$this->MarketShoplog->getCount($map);
        $rep["count"]=$count;
        $rep["list"]=$data;
        return suc($rep);
    }
}
