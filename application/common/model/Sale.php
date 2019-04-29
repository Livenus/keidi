<?php

namespace app\common\model;

use app\common\model\Base;

class Sale extends Base {

	protected $table = 'tb_Sale';
	protected $pk = 'SaleID';

	public function add($data) {
		return parent::addItem($data);
	}

	public function Get_Order_Info($groupid) {
		$sqlorder = "select saleno as 报单编号,returnman as 会员编号,a.shopno as 专卖店,a.totalpv as 总BV,a.totalretail as 总PV,a.Totalmember as 总金额,buydate as 购买日期,SaleID from tb_sale a where  a.status='0' and saleid in(select saleid from tb_sale_entered_Byfrontdesk where groupid='" . $groupid . "') order by saleid desc";
		$data = $this->query($sqlorder);
		if (empty($data)) {
			return false;
		}
		// $data1 = $data[0];
		// foreach ($data1 as $k => $v) {
		//     $kk = iconv("GBK", "UTF-8", $k);
		//     $data[$kk] = $v;
		// }
		$data1 = utf_8($data);
		return $data1;
	}

	public function del($groupid) {
		$count = model("SaleEnteredByFront")->getCount(["GroupID" => $groupid]);
		if ($count == 0) {
			return err(6000, "没有这条记录");
		}
		$sql = "delete from tb_sale where saleid in(select saleid from tb_sale_entered_byfrontdesk where groupid='" . $groupid . "' and current_status='3')";
		$status = $this->execute($sql);
		if ($status) {
			return suc();
		} else {
			return err(3000, "删除失败");
		}
	}

	public function delBysaleid($saleid) {
		$stats = $this->where(["SaleID" => $saleid])->delete();
		if ($stats) {
			return suc("删除成功");
		} else {
			return err(3000, "删除失败");
		}
	}

	public function ProcessCustomerIDlost($groupid) {
		$sql = "update tb_sale set customerid=(select customerid from tb_customer where customerno=tb_sale.returnMan)" .
			" where isnull(tb_sale.customerid,0)=0 and saleid in(select saleid from tb_sale_entered_byfrontdesk where groupid='" . $groupid . "')";
		$status = $this->execute($sql);
		if ($status) {
			return suc("已经修正" . $status . "条丢失卡号订单");
		} else {
			return err(8000, "没有丢失卡号的订单");
		}
	}

}
