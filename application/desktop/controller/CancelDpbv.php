<?php

namespace app\desktop\controller;

use app\desktop\controller\Common;
use think\Db;

/**
 * @title DPBV系统
 */
class CancelDpbv extends Common {

	private $SaleType = "DPBV";
	private $TotalBV = 0.0;
	private $Name = "CancelDpbv";
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = $_POST;

	}
	public function load() {
		$data['comboBox1'] = $this->comboBox1;
		$data['comboBox2'] = $this->comboBox2;
		return suc($data);
	}

	public function CancelDpbv_Load() {
		$data['LoadTodayCollectedDpbv'] = $this->LoadTodayCollectedDpbv();
		$data['Get_Product_Info'] = $this->Get_Product_Info();
		return suc($data);
	}
	private function LoadTodayCollectedDpbv() {
		$sql = "select distinct groupid,comment from dpbv_check where collectmark>'0'and groupid is not null  order by comment desc";
		$data = $this->query_Sql($sql);
		return $data;
	}
	//获取产品信息
	public function Get_Product_Info() {
		$sqlorder = "select Productno as ID,cname as Name,str(memberprice) as MemberPrice,convert(decimal(10,1),PV) AS BV,convert(decimal(10,1),RETAILPRICE) AS PV,'  ' as Qty  from tb_product where (memberprice>0 AND status='1'and productno<>'M051') or productno='M05' ";
		$data = $this->query_Sql($sqlorder);
		return $data;
	}
	/**
	 * @title DPBV系统--查询
	 * @param GID 订单编号
	 */
	public function Search($GID = "") {
		$rule = [
			"GID" => "require",
		];
		$check = $this->validate($this->input, $rule);
		if ($check !== true) {
			return err(9000, $check);
		}
		$GID = $this->input['GID'];
		$SearchSql = "select DpbvID as 编号,Code as 卡号,[name] as 姓名,[date] as 日期,sc as 专卖店,dpbv as 个人BV,Remark as 备注,collectmark as 状态,comment as 领取日期,oper_name as 操作人,GroupID from dpbv_check where groupid like'%" . $GID . "%'";

		$data = $this->query_Sql($SearchSql);
		if (empty($data)) {
			return err(9000, "没有数据");
		}
		$data = utf_8($data);
		$rep["input"] = $this->GetTotal($data);
		$rep["data"] = $data;
		return suc($rep);
	}
	private function GetTotal($data) {
		$TotalBV = 0.0;
		foreach ($data as $v) {
			$vv = array_values($v);
			$TotalBV = $TotalBV + $vv[5];
		}
		$this->input["BV"] = $TotalBV;
	}

	public function button1_Click() {
		$rule = [
			"NO" => "require",
			"code" => "require",
		];
		$msg = [
			"NO" => "NO必填",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);

		}
		$data = $this->LoadTodayCollectedDpbv();
		if (!$this->CheckIfWenttoStock()) {
			if (count($data) == 1) {
				return err(9222, "没有可撤销的个人消费！");
			} else {
				Db::startTrans();
				try {
					$rep["Cancel"] = $this->Cancel();
					if ($this->CheckIfStockedOut()) {
						exception("仓库已经发货，无法撤销！");
					} else {
						$rep["RemoveToStock"] = $this->RemoveToStock();

					}
					Db::commit();
					return suc($rep);
				} catch (\Exception $exc) {
					Db::rollback();
					return err(9000, $exc->getMessage());
				}

			}
		}
		return err(9000, "sock errr");
	}
	//region 检查已进入库存系统数据是否正确
	private function CheckIfWenttoStock() {
		$result = true;
		$sql = "select count(*) as c from stockoutdetail where stockoutid in(select stockoutid from stockout where groupid='" . $this->input['NO'] . "') ";
		$StockInfo = $this->GetStringData($sql);
		if ($StockInfo == "0") {
			$result = false;
		}

		return $result;

	}
	private function Cancel() {
		if ($this->CheckIfStockOut()) {
			exception("数据进入仓库已经发货，无法撤销！");
		} else if ($this->CheckIfReturnBorrow()) {
			exception("个人消费进行完扣货，请去除扣货后撤销！");
		} else {
			$CancelSql = "update dpbv_check set collectmark='0',remark='撤销' where groupid ='" . $this->input['code'] . " '";
			$status = $this->Exc_Sql($CancelSql);
			if ($status > -1) {
				$CancelSql = "delete from stockout where saletype='DPBV' and groupid ='" . $this->input['code'] . "'";
				$status = $this->Exc_Sql($CancelSql);
				if ($status > -1) {
					$CancelSql = "delete from dpbv_products where groupid='" . $this->input['code'] . "'";
					$status = $this->Exc_Sql($CancelSql);
					if ($status > -1) {
						$data['action'] = 'PerformClick()';
						$data['action'] = "撤销成功！";
						return suc($data);
					}
				}
			} else {
				exception("撤销失败！");
			}

		}
	}
	private function CheckIfStockOut() {
		$status = true;
		$sql = "select status from stockout where groupid='" . $this->input['code'] . "'";
		$result = $this->GetStringData($sql);
		if (trim($result) == "2") {
			$status = true;
		} else {
			$status = false;
		}

		return $status;

	}
	private function CheckIfReturnBorrow() {
		$sql = "select returnborrowedstatus from dpbv_products where groupid='" . $this->input['code'] . "'";
		$status = $this->GetStringData($sql);
		if (trim($status) == "OK") {
			return true;
		} else {
			return false;
		}

	}
	private function CheckIfStockedOut() {
		$stockoutstatus = false;
		$sql = "select count(*) from stockout where status='2' and saletype='" . $this->SaleType . "'and groupid='" . $this->input['NO'] . "'";
		$data = $this->GetStringData($sql);
		if ($data > 0) {
			$stockoutstatus = true;
		}

		return $stockoutstatus;
	}
	//region 退出库存
	private function RemoveToStock() {
		$sql = "delete from stockoutdetail where stockoutid in(select stockoutid from stockout where saletype='" . $this->SaleType . "'and groupid='" . $this->input['NO'] . "')";
		$status = $this->Exc_Sql($sql);
		if ($status < 0) {
			exception("表stockoutdetail记录撤销失败！Sql:" . $sql);
		} else {
			$sql = "delete from stockoutinfo where stockoutid in(select stockoutid from stockout where saletype='" . $this->SaleType . "'and groupid='" . $this->input['NO'] . "')";
			$status = $this->Exc_Sql($sql);
			if ($status < 0) {
				exception("表stockoutinfo记录撤销失败！Sql:" . sql);
			} else {
				$sql = "delete from stockout where saletype='" . $this->SaleType . "'and groupid='" . $this->input['NO'] . "'";
				$status = $this->Exc_Sql($sql);
				if ($status < 0) {
					exception("表stockoutinfo记录撤销失败！Sql:" . $sql);
				} else {
					return suc("撤销扣货成功！");
				}
			}
		}

	}
	public function button3_Click() {
		$rule = [
			"SC" => "length:1,25",
			"Date" => "length:1,25",
			"NO" => "length:1,25",
		];
		$msg = [
			"SC" => "请填写专卖店号！\n Pls enter shopno or card no!",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);

		}
		if (!$this->CheckIfWenttoStock()) {
			Db::startTrans();
			try {
				$name = self::$realname;
				$this->GotoWarehouse($this->input["NO"], $name, $this->SaleType, $this->input["Date"], $this->input["SC"], "个人消费");
				if ($this->CheckIfWentStockIsCorrect()) {
					$this->SetMarkStandforInto();
				} else {
					exception("已经进入库存提货系统,核对不正确，联系Eric！\n Already entered warehouse,but it is incorrect!");

				}
				Db::commit();
				return suc("成功进入提货系统！\n Entered warehouse successfully!");
			} catch (\Exception $exc) {
				Db::rollback();
				return err(9000, $exc->getMessage());
			}

		} else {
			return err(9000, "已经进入库存提货系统,核对不正确，联系Eric！\n Already entered warehouse,but it is incorrect!");
		}

	} //录入成功后显示在表单上
	private function GotoWarehouse($GID, $Oper, $SaleType, $SaleDate, $ShopNO, $memo) {
		$status = true;
		$sql = "";
		$NewStockOutId = "";
		$NewStockOutId = $this->GetNewStockOutID();
		$sql = "  insert stockout(stockoutid,lastedittime,groupid,status,toplace,insertperson,insertdate,saletype,saledate,shopno,memo,snylon,bnylon)"
		. " values(" . $NewStockOutId . ",'" . date('Y-m-d') . "','" . $GID . "','0','Distributor','" . self::$realname
		. "','" . date('Y-m-d') . "','" . $this->SaleType . "','" . $SaleDate . "','" . $ShopNO . "','" . $memo . "',0,0)";
		$status = $this->Exc_Sql($sql);
		if ($status > 0) {

			$sql = "insert stockoutinfo(stockoutid,sendperson,sendmac,sendip,sendsoftware,sendfunction)"
			. " values(" . $NewStockOutId . ",'" . self::$realname . "','" . $this->Local_Mac() . "','" . $this->Local_IP() . "','DPBV系统','" . $this->Name . "')";
			$status = $this->Exc_Sql($sql);
			if ($status > 0) {
				$pid = "";
				$amount = "";
				$sql = "select productid,sum(amount) as amount from "
					. "( select productid,sum(amount) as amount from tb_saledetailforbr where saleid in(select saleid from tb_saleforborrowreturn where deductgroupid='" . $GID . "' and current_status='1') group by productid"
					. " union all "
					. "select productid,sum(amount)as amount from dpbv_productsdetail where saleid in(select saleid from dpbv_products where groupid ='" . $GID . "') group by productid) a"
					. " group by productid"; //载入扣货后的销售单产品
				$data = $this->query_Sql("select * from (" . $sql . ") b where amount>0");
				foreach ($data as $v) {
					$vv = array_values($v);
					$pid = $vv[0];
					$amount = $vv[1];
					$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)"
					. " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . "," . $pid . "," . $amount . ",'" . $SaleType . "') ";
					$status = $this->Exc_Sql($sql);
					if ($status < 1) {
						exception("向表stockoutdetail中插入数据失败！Sql:" . $sql);
						$status = false;
						break;
					}

				}
				if ($status) {
					$this->ProcessNylon_kitsIntoDetail($NewStockOutId, "0", $this->input["Kits"], $this->GetKitsLanguage());
				} else {
					exception("插入库存详情失败，终止插入kits,即将回滚！");
				}
			} else {
				exception("向表stockoutinfo中插入数据失败！Sql:" . $sql);
				$sql = "delete from stockout where stockoutid=" . $NewStockOutId;
				$status = $this->Exc_Sql($sql);
				if ($status < 1) {
					exception("回滚清除表stockout数据失败！Sql:" . $sql);
				}

			}
		} else {
			exception("向表stockout中插入数据失败！Sql:" . $sql);
		}

	}
	private function GetNewStockOutID() {
		$sql = "select isnull(max(stockoutid),0)+1 as stockoutid from stockout";
		$outid = $this->GetStringData($sql);
		return $outid;

	}
	private function GetNewStockOutDetailID() {
		$sql = "select isnull(max(stockoutdetailid),0)+1 as stockoutdetailid from stockoutdetail";
		$outid = $this->GetStringData($sql);
		return $outid;

	}
	private function ProcessNylon_kitsIntoDetail($NewStockOutId, $snylon, $kits, $kitslanguage) {
		$sql = "";
		$qty1 = "";
		$qty1 = $snylon;
		if ((int) $qty1 > 0) {
			$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //小尼龙袋
			 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId+",65," . $qty1 . ",'" . $this->SaleType . "') "; //加小尼龙袋
			$status = $this->Exc_Sql($sql);
			if ($status > 0) {
				if ((int) $qty1 >= 4) {
					$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //大尼龙袋
					 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",66," . floor(($qty1 / 4)) . ",'" . $this->SaleType . "') ";
					$status = $this->Exc_Sql($sql);
					if ($status < 0) {
						exception("向表stockoutdetail中插入大尼龙袋数据失败！Sql:" . $sql);
					}

				}
			} else {
				exception("向表stockoutdetail中插入小尼龙袋数据失败！Sql:" . $sql);
			}

		}
		if ($kits > 0) {
			$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //pin
			 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",61," . $kits . ",'" . $this->SaleType . "') ";
			$status = $this->Exc_Sql($sql);
			if ($status > 0) {
				$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //NonwovenBag
				 . " values(" . $this->GetNewStockOutDetailID() . "," . $this->NewStockOutId . ",63," . $kits . ",'" . $this->SaleType . "') ";
				$status = $this->Exc_Sql($sql);
				if ($status > 0) {
					$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //Siliconebracelet手环
					 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",64," . $kits . ",'" . $this->SaleType . "') ";
					$status = $this->Exc_Sql($sql);
					if ($status > 0) {
						$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //KIT-DVD-第一版
						 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",36," . $kits . ",'" . $this->SaleType . "') ";
						$status = $this->Exc_Sql($sql);
						if ($status > 0) {
							if ($kitslanguage == "French") {
								$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //KIT-DVD-第一版
								 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",60," . $kits . ",'" . $this->SaleType . "') ";
								$status = $this->Exc_Sql($sql);
								if ($status > 0) {} else {
									exception("插入数据错误");
								}

							} else {
								$sql = " insert stockoutdetail(stockoutdetailid,stockoutid,productid,amount,memo)" //KIT-DVD-第一版
								 . " values(" . $this->GetNewStockOutDetailID() . "," . $NewStockOutId . ",59," . $kits . ",'" . $this->SaleType . "') ";
								$status = $this->Exc_Sql($sql);
								if ($status > 0) {} else {
									exception("插入数据错误1");
								}

							}
						} else {
							exception("插入数据错误2");
						}

					} else {
						exception("插入数据错误3");
					}

				} else {
					exception("插入数据错误4");
				}

			} else {
				exception("插入数据错误5");
			}

		}
	}
	private function GetKitsLanguage() {
		$sql = "select kitslanguage from dpbv_products where groupid='" . $this->input['NO'] . "'";
		$language = $this->GetStringData($sql);
		return $language;
	}
	private function CheckIfWentStockIsCorrect() //倒序排列，以前台数据为循环体，因为仓库数据比前台数据多些尼龙袋和kits5项
	{

		$result = true;
		$sql = "select productid,sum(amount) as amount from "
		. "( select productid,sum(amount) as amount from tb_saledetailforbr where saleid in(select saleid from tb_saleforborrowreturn where deductgroupid='" . $this->input['NO'] . "' and current_status='1') group by productid"
		. " union all "
		. "select productid,sum(amount)as amount from dpbv_productsdetail where saleid in(select saleid from dpbv_products where groupid ='" . $this->input['NO'] . "') group by productid) a"
			. " group by productid "; //载入扣货后的销售单产品,尼龙袋和kits编号是比较小的数字，故库存里多出了的数字是小数字，倒序可以提前循环比较完
		$Sale_DeductDS = $this->query_Sql("select * from (" . $sql . ") b where amount>0 order by productid desc"); //来自DPBV销售产品和扣货产品之和
		$sql = "select Productid,Amount from stockout,stockoutdetail where stockoutdetail.stockoutid=stockout.stockoutid and groupid='" . $this->input['NO'] . "' order by productid desc"; //来自提货系统的数据
		$StockDS = $this->query_Sql($sql);
		foreach ($Sale_DeductDS as $k => $v) {
			if ($v['amount'] != $StockDS[$k]['Amount']) {
				exception("数据不一致信息如下：\n" . $v['productid'] . ":" . $v['amount'] . "\n" . $StockDS[$k]['Productid'] . ":" . $StockDS[$k]['amount']);
				$result = false;
			}

		}

		return $result;

	} //检查进入的是否完全正确
	private function SetMarkStandforInto() {
		$sql = "update stockout set status='1' where status<2 and groupid='" . $this->input['NO'] . "' and saletype='DPBV'";
		$status = $this->Exc_Sql($sql);
		if ($status < 1) {
			exception("设置进入库存提货系统成功的标志位失败！");
		}

	}
	public function toolStripMenuItem4_Click() {
		$rule = [
			"code" => "require",
		];
		$msg = [
			"code" => "NO必填",
		];
		$check = $this->validate($this->input, $rule, $msg);
		if ($check !== true) {
			return err(9000, $check);

		}
		$this->input['NO'] = $this->input['code'];
		$data["LoadCollectProducts"] = $this->LoadCollectProducts();
		$this->SetDpbvIfGotoWarehouseStatus();
		$data["input"] = $this->input;

		return suc($data);
	}
	private function LoadCollectProducts() {

		$sql = "select productno,amount from dpbv_productsdetail,tb_product where dpbv_productsdetail.productid=tb_product.productid and saleid=(select saleid from dpbv_products where groupid='" . $this->input['code'] . "')";
		$ds = $this->query_Sql($sql);
		$data = $this->Get_Product_Info();
		foreach ($data as $k => $v) {
			$vv = array_values($v);
			$pno = $vv[0];
			foreach ($ds as $vvv) {
				$vvvv = array_values($vvv);
				$SqlPno = "";
				$amount = "0";
				$SqlPno = $vvv['productno'];
				$amount = $vvv['amount'];
				if ($pno == $SqlPno) {
					$data[$k]["Qty"] = $amount;
				}

			}
		}
		$sql = "select kits,sc_code,lastedittime from dpbv_products where groupid='" . $this->input['NO'] . "'";
		$ds1 = $this->query_Sql($sql);
		$ds1 = $ds1[0];
		$this->input['Kits'] = $ds1['kits'];
		$this->input['SC'] = $ds1['sc_code'];
		$this->input['Date'] = $ds1['lastedittime'];
		return $data;
	}

	public function button5_Click() {
		if ($this->CheckIfStockedOut()) {
			return err(9000, "仓库已经发货，无法撤销！");
		} else {
			$data = $this->RemoveToStock();
			return suc($data);
		}
	}
	private function SetDpbvIfGotoWarehouseStatus() {
		if ($this->CheckIfWenttoStock()) {
			$this->input['button3.BackColor'] = 'Color.Red';
			$this->input['button3.Enabled'] = false;
			$this->input['button3.Text'] = 'WentToWarehouse';
			$this->input['button5.BackColor '] = 'Color.Green';
			$this->input['button5.Enabled'] = true;
			$this->input['button5.Text'] = 'RevertFromStcok';
		} else {
			$this->input['button3.BackColor'] = 'Color.Green';
			$this->input['button3.Enabled'] = true;
			$this->input['button3.Text'] = 'WentToWarehouse';
			$this->input['button5.BackColor '] = 'Color.Red';
			$this->input['button5.Enabled'] = false;
			$this->input['button5.Text'] = 'RevertFromStcok';
		}
	}
}
