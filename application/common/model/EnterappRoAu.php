<?php
namespace app\common\model;

use app\common\model\Base;
use think\Model;

class EnterappRoAu extends Base
{
	protected $table = 'EnterApp_RoleConnectAuthority';

	public function roau_list($RoleID){
		$list = $this->where(array('Status'=>'1','RoleID'=>$RoleID))->field('RoleID,AuthorityID')->select();
		return parent::obj2data($list);
	}
}