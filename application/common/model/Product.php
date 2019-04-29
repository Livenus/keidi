<?php

namespace app\common\model;

use app\common\model\Base;

class Product extends Base {

	protected $table = 'tb_Product';
	protected $pk = 'ProductID';

	public function add($data) {
		$res = $this->_validate($data);
		if ($res['stat'] === 0) {
			return $res;
		}
		$res = parent::addItem($data, $this);
		return $res;
	}
	private function _validate(&$data) {
		$Customer_info = $this->where(array('CustomerID' => $data['CustomerID']))->count();
		if ($Customer_info > 0) {
			return err(0, 'Customer_info 系统已经存在，不能重复添加');
		}
		return suc();
	}
	public function getByWhere($where, $field = '*', $limit = "0,100", $order = "", $fitter = "") {
		//$fitter = 'Pic,SmallPic';
		$fitter = '';
		return parent::getByWhere($where, $field, $limit, $order, $fitter);
	}
	public function getByWhereAc($where, $field = '*', $limit = "0,100", $order = "", $fitter = "") {
		return parent::getByWhere($where, $field, $limit, $order, $fitter);
	}
	public function getByWhereOne($where, $field = '*', $fitter = "", $order = "") {

		return parent::getBywhereOne($where, "ProductID,ProductNO,CName,MemberPrice,PV as BV,RetailPrice as PV,RebatePrice,'' as Qty");
	}
	public function report() {
		$sql = "select top " . self::$ProductNumber . " Productno as Code,cname as Products,'个'as unit,convert(decimal(10,1),PV) AS 'BV(USD)',MEMO as 'BV/PV(%)',convert(decimal(10,1),RETAILPRICE) AS 'PV(USD)',str(memberprice) as 'Naira PRICE','0' as QTY,''as [Total BV],''as [Total PV],''as [Total NR]" .
			" from tb_product a,frontdesk_reportPrintSort b where a.productno=b.code and memberprice>0 AND status='1' and productno<>'p10' and productno<>'m04' and productno<>'m10' and productno<>'m091' and productno<>'m09'and productno<>'m05' and productno<>'AJ01' and productno<>'Ajust3' order by codeid";

		return $this->query($sql);
	}
}
