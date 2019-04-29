<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

class ManageStockList extends Common {
//初始化
	public function _initialize() {
	parent::_initialize();
	$this->input = input("post.");
	$this->ManageStockList = model("ManageStockList");
	}
	public function load(){
	$data['Table'] = $this->Table;
	$data['field'] = $this->field;
	$data['bank'] = $this->bank;
	return suc($data);
	}
	/* 
	*仓库列表	
	*/
	public  function Search(){
		$map=[];
		if(isset($this->input['StockName'])&&!empty($this->input['StockName'])){
		  $map["StockName"]=["like","%{$this->input['StockName']}%"];  
		}
		$data=$this->ManageStockList ->getByWhere($map);
		return suc($data);
	}
	public  function StartUse(){
		$rule=[
			"Status"=>"require|between:0,1",
			"StockID"=>"require|length:1,100"
		];
		$check=$this->validate($this->input, $rule);
		if($check!==true){
			
			return err(9000,$check);
		}
		$data["StockName"]=$this->input["StockName"];
		$data["Status"]=$this->input["Status"];
		$data["CreateTime"]=date("Y-m-d H:i:s");
		$data["Memo"]=$this->input["Memo"];
		$data["Oper"]=self::$realname;
		$data["StockShow"]=$this->input["StockShow"];
		$data=setarray($data);
		$old=$this->ManageStockList->getByWhereOne(["StockID"=>$this->input["StockID"]]);
		$status=$this->ManageStockList->editById($data,$old["StockID"]);
		 return suc("修改".$status["data"]);

	}
	public function AddNewStockIntoList(){
		$rule=[
			"StockName"=>"require|length:1,100",
			"Memo"=>"require|length:1,100",
			   "StockShow"=>"require|length:1,100"
		];
		$check=$this->validate($this->input, $rule);
		if($check!==true){
			
			return err(9000,$check);
		}

		try {
	   
		$data["StockName"]=$this->input["StockName"];
		$data["Status"]=0;
		$data["CreateTime"]=date("Y-m-d H:i:s");
		$data["Memo"]=$this->input["Memo"];
		$data["Oper"]=self::$realname;
		$data["StockShow"]=$this->input["StockShow"];
		$old=$this->ManageStockList->getByWhereOne(["StockName"=>$this->input["StockName"]]);
		if($old){
			 $status=$this->ManageStockList->editById($data,$old["StockID"]);
			  return suc("修改".$status["data"]);
		}else{
			 $data["StockID"]=$this-> GetNewStockListID();
			$status=$this->ManageStockList->addItem($data);
		}
		if ($status["stat"]==0)
			 exception("新库：" .$this->input["StockName"]. "添加失败！".$status["errmsg"]);
		else
		{
			$sql = "select * into ".$this->input["StockName"]." from stockmodel";
			if ($this->Exc_Sql($sql) < 0)
				exception("新库：" .$this->input["StockName"]. "插入失败！");
			else
			{
				return suc("新库："  .$this->input["StockName"].  "添加成功！");
			}
		}
		} catch (\Exception $exc) {
			return err(9000,$exc->getMessage());
		}


	}
	private function GetNewStockListID()
	{
	$sql = "select isnull(max(stockid),0)+1 from stocklist";
	$listid = $this->GetStringData($sql);
	return $listid;
}
}
