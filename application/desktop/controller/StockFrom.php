<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class StockFrom extends Common {
	//初始化
	public function _initialize() {
	parent::_initialize();
	$this->input = input("post.");
	$this->StockFrom = model("StockFrom");
	}
	public function load(){
	$data['Table'] = $this->Table;
	$data['field'] = $this->field;
	$data['bank'] = $this->bank;
	return suc($data);
	}
	public  function Search()
	{
		$map=[];
		if(isset($this->input['FromCountry'])){
			  $map["FromCountry"]=["like","%{$this->input['FromCountry']}%"];
		}
		if(isset($this->input['Status'])&&is_numeric($this->input['Status'])){
			  $map["Status"]=$this->input['Status'];
		}
			  $list=$this->StockFrom->getByWhere($map);
			  $data["list"]=$list;
			  $data["count"]=count($list);
			  return suc($data);
	}

	public function add(){
		$rule=[
			"Status"=>"require|number|between:0,1",
		];
		$check=$this->validate($this->input, $rule);
		if($check!==true){
			return err(9000,$check);
		}
		$data["FromCountry"]=$this->input["FromCountry"];
		$data["Status"]=$this->input["Status"];
		$data["FromID"]=$this->StockFrom->newid();
		$data["CreateDate"]=date("Y-m-d H:i:s");
		$data["Oper"]= self::$realname;
		$status=$this->StockFrom->addItem($data);
		return $status;
	}
	public function update(){
		$rule=[
			"FromID"=>"require|number",
			"Status"=>"require|number|between:0,1",
		];
		$check=$this->validate($this->input, $rule);
		if($check!==true){
			return err(9000,$check);
		}
		$data["Status"]=$this->input["Status"];
		$data["LastEditDate"]=date("Y-m-d H:i:s");
		$data["FromCountry"]=$this->input["FromCountry"];
		$data=setarray($data);
		$status=$this->StockFrom->editById($data,$this->input["FromID"]);
		return $status;
	}
}
