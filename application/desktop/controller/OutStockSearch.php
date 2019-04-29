<?php
namespace app\desktop\controller;

use app\desktop\controller\Common;

/**
 * @title  出库
 * @type  menu
 * @login 1
 */
class OutStockSearch extends Common {
	public $ClickMark = 0;
	public $dataGridView2;
	//初始化
	public function _initialize() {
		parent::_initialize();
		$this->input = input("post.");
	}
	/**
	 *@title 查询出库单out
	 *@type interface
	 *@login 1
	 *@param From 查询开始日期 1
	 *@param To 查询截止日期 1
	 *@param OutStocke 查询仓库名称 1
	 *@return data 查询结果集列表 array
	 */
	public function Out() {
		$ClickMark = 1;
		$data['SearchOutStockReport'] = $this->SearchOutStockReport();
		$data['SearchAllDuringInfo'] = $this->SearchAllDuringInfo();
		return suc($data);
	}
	//获取已经发货的出库单GroupID等信息
	private function SearchOutStockReport() {
		$from = $this->input['From'];
		$to = $this->input['To'];
		$sql = "select GroupID,OutDate as 出库日期,OutPerson as 发货人,ToPlace as 收货人,OutStockName as 所出库,SaleType as 出库类型 from stockout where status='2' and outdate>='" . $from . "' and outdate<='" . $to . "' and outstockname='" . $this->input['OutStocke'] . "' order by stockoutid desc";
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
	//获取出库单中产品的信息
	private function SearchAllDuringInfo() {
		$from = $this->input['From'];
		$to = $this->input['To'];
		$sql = "select ProductNO,[Name],Amount,StockOut,BackOrder from stockproduct_material,"
		. "(select ProductID,sum(amount) as Amount,sum(realamount) as StockOut,sum(backorderamount)as BackOrder from stockoutdetailreal where groupid"
		. " in(select GroupID from stockout where status='2' and outdate>='" . $from . "' and outdate<='" . $to . "' and outstockname='" . $this->input['OutStocke'] . "') or groupid  in(select regiontogether from stockout where status='2' and outdate>='" . $from . "' and outdate<='" . $to . "' and outstockname='" . $this->input['OutStocke'] . "') group by productid) stockoutdetailreal"
			. " where stockoutdetailreal.productid=stockproduct_material.productid order by productno";
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
	/**
	 * @title 获取对应groupid的详细信息
	 * @type  interface
	 * @param Groupid  1
	 * @return  data   对应groupid的详细信息集合 array
	 */
	public function GetProductInfo() {
		$Groupid = $this->input['Groupid'];
		$sql = "select ProductNO,[Name],Amount,StockOut,BackOrder from stockproduct_material," . "(select ProductID,sum(amount) as Amount,sum(realamount) as StockOut,sum(backorderamount)as BackOrder from stockoutdetailreal where groupid='" . $Groupid . "' group by productid) stockoutdetailreal  where stockoutdetailreal.productid=stockproduct_material.productid order by productno";
		$data = $this->query_Sql($sql);
		$data = utf_8($data);
		return suc($data);
	}
	public function SearchReport() {
		$this->SearchReport_data($this->input['From']);
		$data['SearchReport'] = $this->dataGridView2;
		return suc($data);
	}
	private function SearchReport_data($outdate) {
		$sql = "select ProductNo,Name,'' as Borrowed,'' as ActualDelivery,'' as Retail,'' as GIFT,'' as DPBV,'' as BackOrder,'' as Complain,'" . $outdate . "' as Date from stockproduct_material order by type desc,productno  ";
		$this->dataGridView2 = $this->query_Sql($sql);
		$this->GetAllStockOutItem($outdate, "borrow", "借货系统", 2, 14);
		$this->GetAllStockOutItem($outdate, "业绩销售", "SaleAfterReturn", 3, 20);
		$this->GetAllStockOutItem($outdate, "Retail", "Retail", 4, 12);
		$this->GetAllStockOutItem($outdate, "GIFT", "Promo", 5, 13);
		$this->GetAllStockOutItem($outdate, "DPBV", "DPBV", 6, 8);
		$this->GetAllStockOutItem($outdate, "BackOrder", "BackOrder", 7, 9);
		$this->GetAllStockOutItem($outdate, "Complain", "Complain", 8, 15);
	}
	private function GetAllStockOutItem($Outdate, $type1, $type2, $col, $saletypeid) {

		$sql = "select productno,amount from stockproduct_material a,( select productid,sum(realamount) as amount from stockoutdetailreal where groupid in(select groupid from stockout where status='2' and"
			. " outdate='" . $Outdate . "'and (saletype='" . $type1 . "' or saletype='" . $type2 . "' or saletypeid=" . $saletypeid . ")) or  groupid in(select regiontogether from stockout where status='2' and"
			. " outdate='" . $Outdate . "'and (saletype='" . $type1 . "' or saletype='" . $type2 . "'))group by productid) b where a.productid=b.productid";
		$data = $this->query_Sql($sql);
		$this->AddAmountToDataGrid($data, $col);
	}
	private function AddAmountToDataGrid($data, $col) {
		foreach ($this->dataGridView2 as $k => $v) {
			$vv = array_values($v);
			$pno = $vv[0];
			foreach ($data as $vvv) {
				$vvvv = array_values($vvv);
				$sqlpno = $vvvv[0];
				$sqlamount = $vvvv[1];
				if ($sqlpno == $pno) {
					$this->dataGridView2[$k][$col] = $sqlamount;
					break;

				}

			}

		}

	}

}
