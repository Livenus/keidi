<?php
namespace app\admin\controller;
use app\admin\controller\Common;
use think\Validate;
class Index extends Common
{	
	public function _initialize(){
		parent::_initialize();
	}
	public function index(){
		return view('./index');
	}
	public function welcome(){
		return view('./welcome');
	}
}
